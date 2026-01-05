<?php

namespace App\Services\Payment\Gateways;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Services\Payment\PaymentGatewayInterface;

class StripePaymentGateway implements PaymentGatewayInterface
{
    protected $baseUrl;
    protected $secretKey;
    protected $publishableKey;
    protected $mode;

    public function __construct()
    {
        $this->baseUrl = env('STRIPE_BASE_URL', 'https://api.stripe.com/v1');
        $this->secretKey = env('STRIPE_SECRET_KEY');
        $this->publishableKey = env('STRIPE_PUBLISHABLE_KEY');
        $this->mode = env('STRIPE_MODE', 'test');
    }

    public function requestPayment(array $data): array
    {
        try {
            $params = [
                'mode' => 'payment',
                'payment_method_types[]' => 'card',
                'locale' => 'auto',
                // 'billing_address_collection' => 'required',
                'submit_type' => 'pay',
                // 'line_items[0][price_data][currency]' => strtolower($data['currency'] ?? 'usd'),
                'line_items[0][price_data][currency]' => 'usd',
                'line_items[0][price_data][unit_amount]' => (int) ($data['amount'] * 100),
                'line_items[0][quantity]' => 1,
            ];

            if (isset($data['customer_data']['name'])) {
                $params['customer_email'] = $data['customer_data']['email'] ?? null;
            }

            if (isset($data['invoice_id'])) {
                $params['metadata[invoice_id]'] = (string) $data['invoice_id'];
            }
            if (isset($data['customer_id'])) {
                $params['metadata[customer_id]'] = (string) $data['customer_id'];
            }
            if (isset($data['payment_CustomerNo'])) {
                $params['metadata[payment_CustomerNo]'] = (string) $data['payment_CustomerNo'];
            }
            if (isset($data['payment_DestNation'])) {
                $params['metadata[payment_DestNation]'] = (string) $data['payment_DestNation'];
            }
            if (isset($data['payment_Code'])) {
                $params['metadata[payment_Code]'] = (string) $data['payment_Code'];
            }

            if (isset($data['customer_data']['email'])) {
                $params['customer_email'] = $data['customer_data']['email'];
            }

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->secretKey,
            ])->asForm()->post($this->baseUrl . '/checkout/sessions', $params);

            $responseData = $response->json();

            if ($response->successful() && isset($responseData['id'])) {
                return [
                    'status' => true,
                    'description' => 'Checkout session created successfully',
                    'transaction_id' => $responseData['id'],
                    'checkout_url' => $responseData['url'],
                    'amount' => $data['amount'],
                    'currency' => strtoupper($data['currency'] ?? 'usd'),
                ];
            }

            Log::error('Stripe Checkout Session Creation Failed', ['error' => $responseData['error'] ?? 'Unknown', 'data' => $data]);
            return ['status' => false, 'error' => $responseData['error']['message'] ?? 'Checkout session creation failed'];

        } catch (\Exception $e) {
            Log::error('Stripe Payment Request Exception', ['error' => $e->getMessage(), 'data' => $data]);
            return ['status' => false, 'error' => $e->getMessage()];
        }
    }

    public function confirmPayment(array $data): array
    {
        try {
            $sessionId = $data['transaction_id'];
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->secretKey,
            ])->get($this->baseUrl . '/checkout/sessions/' . $sessionId);

            $json = $response->json();

            if ($response->successful() && isset($json['payment_status']) && $json['payment_status'] === 'paid') {
                return [
                    'status' => true, 
                    'description' => 'Payment confirmed', 
                    'transaction' => $json,
                    'payment_status' => $json['payment_status']
                ];
            }

            return ['status' => false, 'error' => 'Payment not confirmed or still pending'];
        } catch (\Exception $e) {
            Log::error('Stripe Payment Confirmation Failed', ['error' => $e->getMessage()]);
            return ['status' => false, 'error' => $e->getMessage()];
        }
    }

    public function resendOTP(array $data): array
    {
        return ['status' => false, 'error' => 'Resend OTP not supported in Stripe'];
    }

    public function handleCallback(array $data): array
    {
        try {
            if (isset($data['session_id'])) {
                $sessionId = $data['session_id'];
                $status = $data['status'] ?? 'unknown';
                
                Log::info('Stripe Checkout Session Callback', ['session_id' => $sessionId, 'status' => $status]);
                
                if ($status === 'success') {
                    return [
                        'status' => 'success',
                        'message' => 'Payment completed successfully',
                        'transaction_id' => $sessionId,
                        'stripe_transaction_id' => $sessionId,
                    ];
                } elseif ($status === 'cancelled') {
                    return [
                        'status' => 'failed',
                        'error' => 'Payment was cancelled by user',
                        'transaction_id' => $sessionId,
                    ];
                }
            }

            $payload = file_get_contents('php://input');
            $sigHeader = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';
            $endpointSecret = env('STRIPE_WEBHOOK_SECRET');

            if (!empty($endpointSecret)) {
                if (!$this->verifyWebhookSignature($payload, $sigHeader, $endpointSecret)) {
                    Log::warning('Stripe webhook signature validation failed');
                    if (env('APP_ENV') !== 'local') {
                        throw new \Exception('Invalid webhook signature');
                    }
                }
            }

            $event = json_decode($payload ?: '{}', true);
            Log::info('Stripe Webhook Received', ['event_type' => $event['type'] ?? 'unknown']);

            if (in_array($event['type'], ['checkout.session.completed'])) {
                $session = $event['data']['object'];
                return [
                    'status' => 'success',
                    'message' => 'Payment processed via webhook',
                    'transaction_id' => $session['id'],
                    'stripe_transaction_id' => $session['id'],
                ];
            }

            if (in_array($event['type'], ['payment_intent.succeeded', 'charge.succeeded'])) {
                $object = $event['data']['object'];
                return [
                    'status' => 'success',
                    'message' => 'Payment processed via webhook',
                    'transaction_id' => $object['id'],
                    'stripe_transaction_id' => $object['id'],
                ];
            }

            if (in_array($event['type'], ['checkout.session.expired', 'payment_intent.payment_failed', 'charge.failed'])) {
                $object = $event['data']['object'];
                return [
                    'status' => 'failed',
                    'error' => $object['last_payment_error']['message'] ?? 'Payment failed',
                    'transaction_id' => $object['id'],
                ];
            }

            return ['status' => 'ignored', 'message' => 'Event type not handled: ' . ($event['type'] ?? 'unknown')];
        } catch (\Exception $e) {
            Log::error('Stripe Callback/Webhook Failed', ['error' => $e->getMessage()]);
            return ['status' => 'failed', 'error' => $e->getMessage()];
        }
    }

    private function verifyWebhookSignature($payload, $sigHeader, $endpointSecret)
    {
        if (empty($sigHeader) || empty($endpointSecret)) {
            return false;
        }

        $signatureParts = explode(',', $sigHeader);
        $timestamp = null;
        $signature = null;

        foreach ($signatureParts as $part) {
            if (strpos($part, 't=') === 0) {
                $timestamp = (int) str_replace('t=', '', $part);
            } elseif (strpos($part, 'v1=') === 0) {
                $signature = str_replace('v1=', '', $part);
            }
        }

        if (!$timestamp || !$signature) {
            return false;
        }

        $signedPayload = $timestamp . '.' . $payload;
        $expectedSignature = hash_hmac('sha256', $signedPayload, $endpointSecret);

        return hash_equals($expectedSignature, $signature) && abs(time() - $timestamp) <= 300;
    }

    public function getPaymentMethods(): array
    {
        return [
            ['id' => 'card', 'name' => 'Credit/Debit Card', 'description' => 'Visa, Mastercard, etc.'],
        ];
    }
}