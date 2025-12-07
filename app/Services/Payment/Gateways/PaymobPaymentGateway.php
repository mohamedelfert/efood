<?php

namespace App\Services\Payment\Gateways;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use App\Services\Payment\PaymentGatewayInterface;

class PaymobPaymentGateway implements PaymentGatewayInterface
{
    protected $baseUrl = 'https://accept.paymob.com/api';
    protected $apiKey;
    protected $iframeId;
    protected $integrationIds;
    protected $hmacSecret;

    public function __construct()
    {
        $this->apiKey = env('PAYMOB_API_KEY');
        $this->iframeId = env('PAYMOB_IFRAME_ID');
        $this->hmacSecret = env('PAYMOB_HMAC_SECRET');
        
        $this->integrationIds = [
            'card' => env('PAYMOB_INTEGRATION_ID_CARD'),
        ];
    }

    private function authenticate(): ?array
    {
        $response = Http::timeout(30)->post("{$this->baseUrl}/auth/tokens", [
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
        $amountCents = (int) ($data['amount'] * 100);
        $integrationId = $this->integrationIds['card'];

        $response = Http::timeout(30)
            ->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->post("{$this->baseUrl}/ecommerce/orders", [
                'delivery_needed' => false,
                'amount_cents' => $amountCents,
                'currency' => $data['currency'] ?? 'EGP',
                'items' => [[
                    'name' => 'Payment via Paymob',
                    'amount_cents' => $amountCents,
                    'description' => 'Payment via Paymob',
                    'quantity' => 1,
                ]],
                'integration_id' => $integrationId,
            ]);

        if ($response->successful()) {
            return $response->json();
        }
        
        Log::error('Paymob Order Failed', ['status' => $response->status(), 'body' => $response->body()]);
        return null;
    }

    private function getPaymentKey(string $token, array $orderData, array $data): ?array
    {
        $customerData = $data['customer_data'] ?? [];
        $billingData = [
            'first_name' => $customerData['f_name'] ?? $customerData['name'] ?? 'Customer',
            'last_name' => $customerData['l_name'] ?? 'User',
            'email' => $customerData['email'] ?? 'customer@example.com',
            'phone_number' => $customerData['phone'] ?? '+201234567890',
            'street' => $customerData['address_street'] ?? 'NA',
            'building' => $customerData['address_building'] ?? 'NA',
            'floor' => $customerData['address_floor'] ?? 'NA',
            'apartment' => $customerData['address_apartment'] ?? 'NA',
            'city' => $customerData['address_city'] ?? 'NA',
            'state' => $customerData['address_state'] ?? 'NA',
            'country' => $customerData['address_country'] ?? 'EG',
            'postal_code' => $customerData['address_postal_code'] ?? 'NA',
            'shipping_method' => 'NA',
        ];

        $integrationId = $this->integrationIds['card'];

        $response = Http::timeout(30)
            ->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->post("{$this->baseUrl}/acceptance/payment_keys", [
                'order_id' => $orderData['id'],
                'billing_data' => $billingData,
                'amount_cents' => $orderData['amount_cents'],
                'currency' => $orderData['currency'],
                'integration_id' => $integrationId,
                'expiration' => 3600,
            ]);

        if ($response->successful()) {
            return $response->json();
        }
        
        Log::error('Paymob Payment Key Failed', ['status' => $response->status(), 'body' => $response->body()]);
        return null;
    }

    public function requestPayment(array $data): array
    {
        try {
            $auth = $this->authenticate();
            if (!$auth || !isset($auth['token'])) {
                return ['status' => false, 'error' => 'Authentication failed'];
            }

            $order = $this->createOrder($auth['token'], $data);
            if (!$order || !isset($order['id'])) {
                return ['status' => false, 'error' => 'Order creation failed'];
            }

            $paymentKey = $this->getPaymentKey($auth['token'], $order, $data);
            if (!$paymentKey || !isset($paymentKey['token'])) {
                return ['status' => false, 'error' => 'Payment key generation failed'];
            }

            $iframeUrl = "https://accept.paymob.com/api/acceptance/iframes/{$this->iframeId}?payment_token={$paymentKey['token']}";

            return [
                'status' => true,
                'description' => 'Payment iframe ready',
                'payment_url' => $iframeUrl,
                'iframe_url' => $iframeUrl,
                'order_id' => $order['id'],
                'payment_key' => $paymentKey['token'],
                'id' => null, // Transaction ID not available until payment completion
            ];
        } catch (\Exception $e) {
            Log::error('Paymob Request Exception', ['error' => $e->getMessage()]);
            return ['status' => false, 'error' => 'Payment request failed: ' . $e->getMessage()];
        }
    }

    public function handleCallback(array $data): array
    {
        Log::info('Paymob Callback Received', $data);
        
        // Parse boolean values properly
        $success = filter_var($data['success'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $errorOccured = filter_var($data['error_occured'] ?? false, FILTER_VALIDATE_BOOLEAN);
        
        // Get message from multiple possible fields
        $message = $data['data_message'] ?? $data['data.message'] ?? $data['message'] ?? null;
        
        Log::info('Paymob Callback Parsed', [
            'success' => $success,
            'error_occured' => $errorOccured,
            'message' => $message,
            'message_upper' => strtoupper($message ?? '')
        ]);

        // Use case-insensitive comparison for the message
        $isApproved = $success && 
                      !$errorOccured && 
                      (strtoupper($message ?? '') === 'APPROVED');

        if ($isApproved) {
            return [
                'status' => 'success',
                'paymob_transaction_id' => $data['id'] ?? null,
                'paymob_order_id' => $data['order'] ?? null,
            ];
        }

        // If success is true but message is not approved, log the details
        if ($success && !$errorOccured) {
            Log::warning('Paymob: Success but unexpected message', [
                'message' => $message,
                'expected' => 'APPROVED',
                'data' => $data
            ]);
        }

        return [
            'status' => 'failed',
            'error' => $message ?? 'Payment failed',
        ];
    }

    public function getPaymentMethods(): array
    {
        return [
            ['id' => 'card', 'name' => 'Card', 'description' => 'Pay with Credit/Debit Card'],
        ];
    }

    public function confirmPayment(array $data): array 
    { 
        return ['status' => false, 'error' => 'Not implemented for Paymob']; 
    }
    
    public function resendOTP(array $data): array 
    { 
        return ['status' => false, 'error' => 'Not implemented for Paymob']; 
    }
}