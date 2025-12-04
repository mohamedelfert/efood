<?php

namespace App\Services\Payment\Gateways;

use App\Model\WalletTransaction;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use App\Services\Payment\PaymentGatewayInterface;

class PaymobPaymentGateway implements PaymentGatewayInterface
{
    protected $baseUrl = 'https://accept.paymob.com/api';
    protected $apiKey;
    protected $iframeId;
    protected $integrationId;
    protected $hmacSecret;
    protected $mode;

    public function __construct()
    {
        $this->apiKey = env('PAYMOB_API_KEY');
        $this->iframeId = env('PAYMOB_IFRAME_ID');
        $this->integrationId = env('PAYMOB_INTEGRATION_ID');
        $this->hmacSecret = env('PAYMOB_HMAC_SECRET');
        $this->mode = env('PAYMOB_MODE', 'test');
    }

    private function authenticate(): ?array
    {
        $response = Http::post("{$this->baseUrl}/auth/tokens", [
            'api_key' => $this->apiKey,
        ]);

        if ($response->successful()) {
            return $response->json();
        }
        Log::error('Paymob Auth Failed', ['status' => $response->status(), 'body' => $response->body()]);
        return null;
    }

    private function createOrder(string $token, array $data): ?array
    {
        $amountCents = $data['amount'] * 100;
        if ($amountCents > env('PAYMOB_MAX_AMOUNT', 1000000000) || $amountCents < env('PAYMOB_MIN_AMOUNT', 1)) {
            return ['status' => false, 'error' => 'Amount out of range'];
        }

        $response = Http::withHeaders(['Authorization' => 'Bearer ' . $token])
            ->post("{$this->baseUrl}/ecommerce/orders", [
                'delivery_needed' => 'no',
                'amount_cents' => $amountCents,
                'currency' => $data['currency'],
                'items' => [[
                    'name' => 'Wallet Top-Up',
                    'amount_cents' => $amountCents,
                    'description' => 'Top-up via Paymob',
                    'quantity' => 1,
                ]],
                'api_source' => $data['api_source'] ?? 'INVOICE',
                'integration_id' => $this->integrationId,
            ]);

        Log::debug('Paymob Order Response', ['status' => $response->status(), 'body' => $response->body(), 'data' => $data]);
        if ($response->successful()) {
            return $response->json();
        }
        Log::error('Paymob Order Creation Failed', ['status' => $response->status(), 'body' => $response->body()]);
        return null;
    }

    private function getPaymentKey(string $token, array $orderData, array $data): ?array
    {
        $customerData = $data['customer_data'] ?? [];
        $billingData = [
            'first_name' => $customerData['name'] ?? 'Customer',
            'last_name' => $customerData['name'] ?? 'User',
            'email' => $customerData['email'] ?? 'customer@example.com',
            'phone_number' => $customerData['phone'] ?? '01234567890',
            'street' => $customerData['address_street'] ?? 'Unknown Street',
            'building' => $customerData['address_building'] ?? '1',
            'floor' => $customerData['address_floor'] ?? '1',
            'apartment' => $customerData['address_apartment'] ?? '1',
            'city' => $customerData['address_city'] ?? 'Cairo',
            'country' => $customerData['address_country'] ?? 'EG',
        ];

        $response = Http::withHeaders(['Authorization' => 'Bearer ' . $token])
            ->post("{$this->baseUrl}/acceptance/payment_keys", [
                'order_id' => $orderData['id'],
                'billing_data' => $billingData,
                'amount_cents' => $orderData['amount_cents'],
                'currency' => $orderData['currency'],
                'integration_id' => $this->integrationId,
                'expiration' => 3600,
            ]);

        Log::debug('Paymob Payment Key Response', ['status' => $response->status(), 'body' => $response->body(), 'billing_data' => $billingData]);
        if ($response->successful()) {
            return $response->json();
        }
        Log::error('Paymob Payment Key Failed', ['status' => $response->status(), 'body' => $response->body(), 'billing_data' => $billingData]);
        return null;
    }

    public function requestPayment(array $data): array
    {
        $auth = $this->authenticate();

        if (!$auth) {
            return ['status' => false, 'error' => 'Authentication failed'];
        }

        $order = $this->createOrder($auth['token'], $data);
        if (!$order) {
            return ['status' => false, 'error' => 'Order creation failed'];
        }

        $paymentKey = $this->getPaymentKey($auth['token'], $order, $data);
        if (!$paymentKey) {
            return ['status' => false, 'error' => 'Payment key generation failed'];
        }

        $iframeUrl = "{$this->baseUrl}/acceptance/iframes/{$this->iframeId}?payment_token={$paymentKey['token']}";

        $response = [
            'status' => true,
            'description' => 'Payment iframe ready',
            'iframe_url' => $iframeUrl,
            'order_id' => $order['id'],
            'payment_key' => $paymentKey['token'],
            'id' => $paymentKey['id'] ?? null,
            'transaction_id' => 'PAY_' . time() . '_' . ($data['customer_data']['user_id'] ?? 'guest'),
        ];

        Log::info('Paymob Payment Request Response', ['response' => $response, 'order' => $order, 'payment_key' => $paymentKey]);

        return $response;
    }

    public function confirmPayment(array $data): array
    {
        $transactionId = $data['transaction_id'] ?? null;
        if (!$transactionId) {
            return ['status' => false, 'error' => 'Transaction ID required'];
        }

        $auth = $this->authenticate();
        $response = Http::withHeaders(['Authorization' => 'Bearer ' . $auth['token']])
            ->get("{$this->baseUrl}/ecommerce/transactions/{$transactionId}");

        if ($response->successful() && $response->json()['success'] === true) {
            $walletTransaction = WalletTransaction::where('transaction_id', $transactionId)->first();
            if ($walletTransaction) {
                $metadata = $walletTransaction->metadata;
                $metadata['paymob_transaction_id'] = $response->json()['id'] ?? null;
                $walletTransaction->update(['metadata' => json_encode($metadata)]);
            }

            return ['status' => true, 'description' => 'Payment confirmed', 'transaction' => $response->json()];
        }

        return ['status' => false, 'error' => 'Confirmation failed'];
    }

    public function resendOTP(array $data): array
    {
        return ['status' => false, 'error' => 'Resend OTP not supported in Paymob'];
    }

    public function handleCallback(array $data): array
    {
        Log::info('Paymob Callback', $data);

        // Check if the callback indicates a successful payment
        if (
            isset($data['success']) 
            && $data['success'] === "true" 
            && isset($data['data_message']) 
            && $data['data_message'] === "Approved"
        ) {
            return [
                'status' => 'success',
                'order_id' => $data['order_id'] ?? null,
                'message' => 'Payment processed',
                'paymob_transaction_id' => $data['id'] ?? null,
            ];
        }

        return [
            'status' => 'failed',
            'error' => $data['data_message'] ?? $data['error'] ?? 'Unknown error',
        ];
}

    public function getPaymentMethods(): array
    {
        return [
            ['id' => 1, 'name' => 'Card', 'description' => 'Credit/Debit Card'],
            ['id' => 2, 'name' => 'Mobile Wallet', 'description' => 'Fawry/Etisalat'],
            ['id' => 3, 'name' => 'valU', 'description' => 'Installments'],
            ['id' => 4, 'name' => 'Meeza', 'description' => 'QR Code'],
        ];
    }
}