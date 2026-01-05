<?php

namespace App\Services\Payment\Gateways;

use Exception;
use App\Models\Setting;
use Stripe\StripeClient;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use App\Services\Payment\PaymentGatewayInterface;

class StripePaymentGateway implements PaymentGatewayInterface
{
    protected $stripe;
    protected $config;

    public function __construct()
    {
        $setting = Setting::where('key_name', 'stripe')->first();
        if (!$setting) {
            throw new Exception('Stripe configuration not found');
        }

        $mode = $setting->mode ?? 'test';
        $this->config = $mode === 'live' ? json_decode($setting->live_values, true) : json_decode($setting->test_values, true);

        if (empty($this->config['secret_key'])) {
            throw new Exception('Stripe API key not configured');
        }

        $this->stripe = new StripeClient($this->config['secret_key']);
    }

    public function requestPayment(array $data): array
    {
        try {
            $amount = (int) ($data['amount'] * 100); // Convert to cents
            $currency = strtolower($data['currency'] ?? 'egp');
            $purpose = $data['purpose'] ?? 'payment';
            $customerData = $data['customer_data'] ?? [];
            $callbackUrl = $data['callback_url'] ?? null;
            $orderId = $data['order_id'] ?? null;
            $transactionId = $data['transaction_id'] ?? null;

            if (!$callbackUrl) {
                throw new Exception('Callback URL is required');
            }

            // Create Checkout Session
            $sessionParams = [
                'payment_method_types' => ['card'],
                'line_items' => [
                    [
                        'price_data' => [
                            'currency' => $currency,
                            'unit_amount' => $amount,
                            'product_data' => [
                                'name' => ucfirst($purpose),
                                'description' => $purpose === 'order_payment' ? "Order ID: {$orderId}" : "Transaction ID: {$transactionId}",
                            ],
                        ],
                        'quantity' => 1,
                    ],
                ],
                'mode' => 'payment',
                'success_url' => $callbackUrl . '?session_id={CHECKOUT_SESSION_ID}&transaction_id=' . urlencode($transactionId),
                'cancel_url' => $callbackUrl . '?canceled=true',
                'customer_email' => $customerData['email'] ?? null,
                'metadata' => [
                    'user_id' => $customerData['user_id'] ?? null,
                    'purpose' => $purpose,
                    'order_id' => $orderId,
                    'transaction_id' => $transactionId,
                ],
            ];

            $session = $this->stripe->checkout->sessions->create($sessionParams);

            return [
                'status' => true,
                'id' => $session->id,
                'order_id' => $session->id, // Using session ID as order_id for consistency
                'payment_key' => $session->payment_intent,
                'checkout_url' => $session->url,
            ];
        } catch (Exception $e) {
            Log::error('Stripe requestPayment failed', [
                'error' => $e->getMessage(),
                'data' => $data
            ]);
            return [
                'status' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    public function confirmPayment(array $data): array
    {
        // Stripe doesn't typically use OTP, but if needed for 3DS or something
        // For now, not implemented
        return [
            'status' => false,
            'message' => 'Confirmation not required for Stripe'
        ];
    }

    public function resendOTP(array $data): array
    {
        // Not applicable for Stripe
        return [
            'status' => false,
            'message' => 'OTP not supported for Stripe'
        ];
    }

    public function handleCallback(array $data): array
    {
        try {
            $sessionId = $data['session_id'] ?? null;
            if (!$sessionId) {
                throw new Exception('Missing session_id in callback');
            }

            $session = $this->stripe->checkout->sessions->retrieve($sessionId);

            if ($session->payment_status === 'paid' && $session->status === 'complete') {
                return [
                    'status' => 'success',
                    'stripe_transaction_id' => $session->payment_intent,
                    'amount' => $session->amount_total / 100,
                    'currency' => strtoupper($session->currency),
                    'metadata' => $session->metadata,
                ];
            } else {
                throw new Exception('Payment not successful: ' . $session->payment_status);
            }
        } catch (Exception $e) {
            Log::error('Stripe handleCallback failed', [
                'error' => $e->getMessage(),
                'data' => $data
            ]);
            return [
                'status' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    public function getPaymentMethods(): array
    {
        // Return supported methods
        return [
            [
                'gateway' => 'stripe',
                'title' => 'Stripe',
                'image' => null, // Can be set from additional_data
                'mode' => $this->config['mode'] ?? 'test',
                'is_enabled' => Arr::get($this->config, 'status') == '1' ? 1 : 0,
            ]
        ];
    }
}