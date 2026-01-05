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

    /**
     * Request payment using Payment Intent (Pure API - No Redirect)
     */
    public function requestPayment(array $data): array
    {
        try {
            // Use Payment Intent for pure API flow (no redirect needed)
            $params = [
                'amount' => (int) ($data['amount'] * 100), // Convert to cents
                'currency' => strtolower($data['currency'] ?? 'usd'),
                'description' => 'Payment for Invoice #' . ($data['invoice_id'] ?? 'N/A'),
                'automatic_payment_methods[enabled]' => 'true',
            ];

            // Add customer email if provided
            if (isset($data['customer_data']['email'])) {
                $params['receipt_email'] = $data['customer_data']['email'];
            }

            // Add metadata
            $metadataFields = [
                'invoice_id', 
                'customer_id', 
                'payment_CustomerNo', 
                'payment_DestNation', 
                'payment_Code',
                'user_id',
                'transaction_type',
                'order_id'
            ];

            foreach ($metadataFields as $field) {
                if (isset($data[$field])) {
                    $params['metadata[' . $field . ']'] = (string) $data[$field];
                }
            }

            // Create Payment Intent
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->secretKey,
            ])->asForm()->post($this->baseUrl . '/payment_intents', $params);

            $responseData = $response->json();

            if ($response->successful() && isset($responseData['id'])) {
                Log::info('Stripe Payment Intent Created', [
                    'payment_intent_id' => $responseData['id'],
                    'amount' => $data['amount'],
                    'customer_id' => $data['customer_id'] ?? null
                ]);

                return [
                    'status' => true,
                    'description' => 'Payment intent created successfully',
                    'payment_intent_id' => $responseData['id'],
                    'client_secret' => $responseData['client_secret'],
                    'publishable_key' => $this->publishableKey,
                    'amount' => $data['amount'],
                    'currency' => strtoupper($data['currency'] ?? 'usd'),
                    'requires_action' => $responseData['status'] === 'requires_action',
                ];
            }

            Log::error('Stripe Payment Intent Creation Failed', [
                'error' => $responseData['error'] ?? 'Unknown', 
                'data' => $data
            ]);
            
            return [
                'status' => false, 
                'error' => $responseData['error']['message'] ?? 'Payment intent creation failed'
            ];

        } catch (\Exception $e) {
            Log::error('Stripe Payment Request Exception', [
                'error' => $e->getMessage(), 
                'data' => $data
            ]);
            
            return [
                'status' => false, 
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Confirm payment after client-side card processing
     */
    public function confirmPayment(array $data): array
    {
        try {
            $paymentIntentId = $data['payment_intent_id'] ?? $data['transaction_id'];
            
            // Retrieve the Payment Intent to check its status
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->secretKey,
            ])->get($this->baseUrl . '/payment_intents/' . $paymentIntentId);

            $json = $response->json();

            if ($response->successful()) {
                Log::info('Stripe Payment Intent Status', [
                    'payment_intent_id' => $paymentIntentId,
                    'status' => $json['status'] ?? 'unknown'
                ]);

                // Check if payment is successful
                if (isset($json['status']) && $json['status'] === 'succeeded') {
                    return [
                        'status' => true, 
                        'description' => 'Payment confirmed successfully', 
                        'transaction' => $json,
                        'payment_status' => 'succeeded',
                        'payment_intent_id' => $paymentIntentId,
                        'amount' => $json['amount'] / 100,
                        'currency' => strtoupper($json['currency']),
                        'metadata' => $json['metadata'] ?? []
                    ];
                }

                // Handle other statuses
                return [
                    'status' => false,
                    'payment_status' => $json['status'] ?? 'unknown',
                    'error' => 'Payment not confirmed. Status: ' . ($json['status'] ?? 'unknown'),
                    'requires_action' => $json['status'] === 'requires_action'
                ];
            }

            return [
                'status' => false, 
                'error' => 'Failed to retrieve payment status'
            ];
            
        } catch (\Exception $e) {
            Log::error('Stripe Payment Confirmation Failed', [
                'error' => $e->getMessage()
            ]);
            
            return [
                'status' => false, 
                'error' => $e->getMessage()
            ];
        }
    }

    public function resendOTP(array $data): array
    {
        return [
            'status' => false, 
            'error' => 'Resend OTP not supported in Stripe'
        ];
    }

    /**
     * Handle webhook callbacks (for automatic payment confirmation)
     */
    public function handleCallback(array $data): array
    {
        try {
            // Handle webhook events
            $payload = file_get_contents('php://input');
            $sigHeader = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';
            $endpointSecret = env('STRIPE_WEBHOOK_SECRET');

            // Verify webhook signature
            if (!empty($endpointSecret) && !empty($sigHeader)) {
                if (!$this->verifyWebhookSignature($payload, $sigHeader, $endpointSecret)) {
                    Log::warning('Stripe webhook signature validation failed');
                    if (env('APP_ENV') !== 'local') {
                        throw new \Exception('Invalid webhook signature');
                    }
                }
            }

            $event = json_decode($payload ?: '{}', true);
            
            Log::info('Stripe Webhook Received', [
                'event_type' => $event['type'] ?? 'unknown',
                'event_id' => $event['id'] ?? null
            ]);

            // Handle Payment Intent succeeded
            if ($event['type'] === 'payment_intent.succeeded') {
                $paymentIntent = $event['data']['object'];
                
                return [
                    'status' => 'success',
                    'message' => 'Payment completed successfully',
                    'payment_intent_id' => $paymentIntent['id'],
                    'transaction_id' => $paymentIntent['id'],
                    'stripe_transaction_id' => $paymentIntent['id'],
                    'amount' => $paymentIntent['amount'] / 100,
                    'currency' => strtoupper($paymentIntent['currency']),
                    'metadata' => $paymentIntent['metadata'] ?? [],
                ];
            }

            // Handle Payment Intent failed
            if (in_array($event['type'], ['payment_intent.payment_failed', 'payment_intent.canceled'])) {
                $paymentIntent = $event['data']['object'];
                
                return [
                    'status' => 'failed',
                    'error' => $paymentIntent['last_payment_error']['message'] ?? 'Payment failed',
                    'payment_intent_id' => $paymentIntent['id'],
                    'transaction_id' => $paymentIntent['id'],
                ];
            }

            // Handle charge succeeded (alternative)
            if ($event['type'] === 'charge.succeeded') {
                $charge = $event['data']['object'];
                
                return [
                    'status' => 'success',
                    'message' => 'Payment completed successfully',
                    'transaction_id' => $charge['id'],
                    'stripe_transaction_id' => $charge['id'],
                    'payment_intent_id' => $charge['payment_intent'] ?? null,
                    'amount' => $charge['amount'] / 100,
                    'currency' => strtoupper($charge['currency']),
                    'metadata' => $charge['metadata'] ?? [],
                ];
            }

            return [
                'status' => 'ignored', 
                'message' => 'Event type not handled: ' . ($event['type'] ?? 'unknown')
            ];
            
        } catch (\Exception $e) {
            Log::error('Stripe Webhook Failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return [
                'status' => 'failed', 
                'error' => $e->getMessage()
            ];
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
            [
                'id' => 'card', 
                'name' => 'Credit/Debit Card', 
                'description' => 'Visa, Mastercard, etc.'
            ],
        ];
    }
}