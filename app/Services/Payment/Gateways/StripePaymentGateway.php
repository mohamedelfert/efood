<?php

namespace App\Services\Payment\Gateways;

use Exception;
use Stripe\Stripe;
use App\Models\Setting;
use Stripe\Checkout\Session;
use Illuminate\Support\Facades\Log;
use App\Services\Payment\PaymentGatewayInterface;

class StripePaymentGateway implements PaymentGatewayInterface
{
    protected $secretKey;
    protected $publicKey;
    protected $webhookSecret;
    protected $mode;

    public function __construct()
    {
        $this->initializeConfig();
    }

    /**
     * Initialize Stripe configuration from database settings
     */
    private function initializeConfig(): void
    {
        try {
            $setting = Setting::where('settings_type', 'payment_config')
                ->where('key_name', 'stripe')
                ->first();

            if (!$setting) {
                throw new Exception('Stripe configuration not found');
            }

            $this->mode = $setting->mode ?? 'test';
            $values = $this->mode === 'live' ? $setting->live_values : $setting->test_values;

            if (!is_array($values)) {
                $values = json_decode($values, true);
            }

            $this->secretKey = $values['api_key'] ?? null;
            $this->publicKey = $values['published_key'] ?? null;
            $this->webhookSecret = $values['webhook_secret'] ?? null;

            if (!$this->secretKey || !$this->publicKey) {
                throw new Exception('Stripe API keys not configured');
            }

            Stripe::setApiKey($this->secretKey);

            Log::info('Stripe gateway initialized', [
                'mode' => $this->mode,
                'has_secret_key' => !empty($this->secretKey),
                'has_public_key' => !empty($this->publicKey),
            ]);
        } catch (Exception $e) {
            Log::error('Stripe initialization failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Request payment via Stripe Checkout Session
     *
     * @param array $data Payment request data
     * @return array Response with checkout session details
     */
    public function requestPayment(array $data): array
    {
        try {
            $amount = $data['amount'] ?? 0;
            $currency = strtolower($data['currency'] ?? 'egp');
            $purpose = $data['purpose'] ?? 'payment';
            $customerData = $data['customer_data'] ?? [];
            $callbackUrl = $data['callback_url'] ?? null;
            $transactionId = $data['transaction_id'] ?? 'TXN_' . time();
            $orderId = $data['order_id'] ?? null;

            // Validate amount
            if ($amount <= 0) {
                throw new Exception('Invalid amount');
            }

            // Convert amount to cents (Stripe uses smallest currency unit)
            $amountCents = round($amount * 100);

            // Build line items
            $lineItems = $this->buildLineItems($data, $amountCents, $currency);

            // Build metadata
            $metadata = [
                'transaction_id' => $transactionId,
                'purpose' => $purpose,
                'user_id' => $customerData['user_id'] ?? null,
            ];

            if ($orderId) {
                $metadata['order_id'] = $orderId;
            }

            // Prepare session parameters
            $sessionParams = [
                'payment_method_types' => ['card'],
                'line_items' => $lineItems,
                'mode' => 'payment',
                'success_url' => $callbackUrl . '?session_id={CHECKOUT_SESSION_ID}&transaction_id=' . $transactionId,
                'cancel_url' => $callbackUrl . '?session_id={CHECKOUT_SESSION_ID}&transaction_id=' . $transactionId . '&status=cancelled',
                'metadata' => $metadata,
                'client_reference_id' => $transactionId,
            ];

            // Add customer email if provided
            if (!empty($customerData['email'])) {
                $sessionParams['customer_email'] = $customerData['email'];
            }

            // Add customer details if available
            if (!empty($customerData['name']) || !empty($customerData['phone'])) {
                $sessionParams['customer_creation'] = 'always';
                
                if (!empty($customerData['phone'])) {
                    $sessionParams['phone_number_collection'] = ['enabled' => true];
                }
            }

            // Create Stripe Checkout Session
            $session = Session::create($sessionParams);

            Log::info('Stripe Checkout Session created', [
                'session_id' => $session->id,
                'transaction_id' => $transactionId,
                'amount' => $amount,
                'currency' => $currency,
                'purpose' => $purpose,
            ]);

            return [
                'status' => true,
                'message' => 'Stripe session created successfully',
                'id' => $session->id,
                'checkout_url' => $session->url,
                'session_id' => $session->id,
                'payment_intent' => $session->payment_intent,
                'transaction_id' => $transactionId,
                'amount' => $amount,
                'currency' => $currency,
                'expires_at' => $session->expires_at,
            ];
        } catch (Exception $e) {
            Log::error('Stripe payment request failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'data' => $data,
            ]);

            return [
                'status' => false,
                'error' => $e->getMessage(),
                'message' => 'Failed to create Stripe session',
            ];
        }
    }

    /**
     * Handle payment callback from Stripe
     *
     * @param array $data Callback data
     * @return array Callback response
     */
    public function handleCallback(array $data): array
    {
        try {
            $sessionId = $data['session_id'] ?? $data['id'] ?? null;
            $transactionId = $data['transaction_id'] ?? null;
            $status = $data['status'] ?? null;

            if (!$sessionId) {
                throw new Exception('Session ID not provided');
            }

            // Handle cancelled status
            if ($status === 'cancelled') {
                Log::info('Stripe payment cancelled', [
                    'session_id' => $sessionId,
                    'transaction_id' => $transactionId,
                ]);

                return [
                    'status' => 'failed',
                    'error' => 'Payment was cancelled by user',
                    'stripe_transaction_id' => $sessionId,
                ];
            }

            // Retrieve the session from Stripe
            $session = Session::retrieve($sessionId);

            Log::info('Stripe session retrieved', [
                'session_id' => $sessionId,
                'payment_status' => $session->payment_status,
                'payment_intent' => $session->payment_intent,
            ]);

            // Check payment status
            if ($session->payment_status === 'paid') {
                return [
                    'status' => 'success',
                    'message' => 'Payment completed successfully',
                    'stripe_transaction_id' => $sessionId,
                    'payment_intent' => $session->payment_intent,
                    'amount_total' => $session->amount_total / 100, // Convert from cents
                    'currency' => strtoupper($session->currency),
                    'customer_email' => $session->customer_details->email ?? null,
                    'metadata' => $session->metadata,
                ];
            } elseif ($session->payment_status === 'unpaid') {
                return [
                    'status' => 'failed',
                    'error' => 'Payment not completed',
                    'stripe_transaction_id' => $sessionId,
                    'payment_status' => $session->payment_status,
                ];
            } else {
                return [
                    'status' => 'pending',
                    'message' => 'Payment is being processed',
                    'stripe_transaction_id' => $sessionId,
                    'payment_status' => $session->payment_status,
                ];
            }
        } catch (Exception $e) {
            Log::error('Stripe callback handling failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'data' => $data,
            ]);

            return [
                'status' => 'failed',
                'error' => $e->getMessage(),
                'message' => 'Failed to verify payment',
            ];
        }
    }

    /**
     * Verify payment status
     *
     * @param string $transactionId Transaction identifier
     * @return array Verification result
     */
    public function verifyPayment(string $transactionId): array
    {
        try {
            $session = Session::retrieve($transactionId);

            Log::info('Stripe payment verification', [
                'session_id' => $transactionId,
                'payment_status' => $session->payment_status,
            ]);

            return [
                'status' => $session->payment_status === 'paid',
                'payment_status' => $session->payment_status,
                'amount' => $session->amount_total / 100,
                'currency' => strtoupper($session->currency),
                'session_id' => $session->id,
                'payment_intent' => $session->payment_intent,
            ];
        } catch (Exception $e) {
            Log::error('Stripe payment verification failed', [
                'error' => $e->getMessage(),
                'transaction_id' => $transactionId,
            ]);

            return [
                'status' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Refund a payment
     *
     * @param string $transactionId Transaction identifier
     * @param float|null $amount Optional partial refund amount
     * @return array Refund result
     */
    public function refundPayment(string $transactionId, ?float $amount = null): array
    {
        try {
            $session = Session::retrieve($transactionId);
            $paymentIntentId = $session->payment_intent;

            if (!$paymentIntentId) {
                throw new Exception('Payment intent not found for this session');
            }

            $refundParams = ['payment_intent' => $paymentIntentId];

            if ($amount !== null) {
                $refundParams['amount'] = round($amount * 100); // Convert to cents
            }

            $refund = \Stripe\Refund::create($refundParams);

            Log::info('Stripe refund created', [
                'refund_id' => $refund->id,
                'transaction_id' => $transactionId,
                'amount' => $amount,
                'status' => $refund->status,
            ]);

            return [
                'status' => true,
                'refund_id' => $refund->id,
                'amount' => $refund->amount / 100,
                'currency' => strtoupper($refund->currency),
                'refund_status' => $refund->status,
            ];
        } catch (Exception $e) {
            Log::error('Stripe refund failed', [
                'error' => $e->getMessage(),
                'transaction_id' => $transactionId,
                'amount' => $amount,
            ]);

            return [
                'status' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get gateway configuration
     *
     * @return array Configuration details
     */
    public function getConfig(): array
    {
        return [
            'name' => 'Stripe',
            'mode' => $this->mode,
            'public_key' => $this->publicKey,
            'supports_refunds' => true,
            'supports_partial_refunds' => true,
            'payment_methods' => ['card'],
        ];
    }

    /**
     * Build line items for Stripe Checkout
     *
     * @param array $data Payment data
     * @param int $amountCents Amount in cents
     * @param string $currency Currency code
     * @return array Line items
     */
    private function buildLineItems(array $data, int $amountCents, string $currency): array
    {
        $items = $data['items'] ?? [];
        
        if (!empty($items)) {
            $lineItems = [];
            foreach ($items as $item) {
                $lineItems[] = [
                    'price_data' => [
                        'currency' => $currency,
                        'product_data' => [
                            'name' => $item['name'] ?? 'Product',
                            'description' => $item['description'] ?? null,
                        ],
                        'unit_amount' => $item['amount_cents'] ?? $amountCents,
                    ],
                    'quantity' => $item['quantity'] ?? 1,
                ];
            }
            return $lineItems;
        }

        // Default single item
        $purpose = $data['purpose'] ?? 'payment';
        $itemName = $purpose === 'wallet_topup' ? 'Wallet Top-up' : 'Order Payment';

        return [
            [
                'price_data' => [
                    'currency' => $currency,
                    'product_data' => [
                        'name' => $itemName,
                        'description' => ucfirst($purpose),
                    ],
                    'unit_amount' => $amountCents,
                ],
                'quantity' => 1,
            ],
        ];
    }

    /**
     * Handle webhook notifications from Stripe
     *
     * @param string $payload Raw webhook payload
     * @param string $signature Stripe signature header
     * @return array Webhook handling result
     */
    public function handleWebhook(string $payload, string $signature): array
    {
        try {
            if (!$this->webhookSecret) {
                throw new Exception('Webhook secret not configured');
            }

            $event = \Stripe\Webhook::constructEvent(
                $payload,
                $signature,
                $this->webhookSecret
            );

            Log::info('Stripe webhook received', [
                'event_type' => $event->type,
                'event_id' => $event->id,
            ]);

            // Handle different event types
            switch ($event->type) {
                case 'checkout.session.completed':
                    return $this->handleCheckoutSessionCompleted($event->data->object);
                
                case 'payment_intent.succeeded':
                    return $this->handlePaymentIntentSucceeded($event->data->object);
                
                case 'payment_intent.payment_failed':
                    return $this->handlePaymentIntentFailed($event->data->object);
                
                default:
                    Log::info('Unhandled Stripe webhook event', ['event_type' => $event->type]);
                    return ['status' => true, 'message' => 'Event received'];
            }
        } catch (Exception $e) {
            Log::error('Stripe webhook handling failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'status' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Handle checkout.session.completed event
     */
    private function handleCheckoutSessionCompleted($session): array
    {
        Log::info('Checkout session completed', [
            'session_id' => $session->id,
            'payment_status' => $session->payment_status,
        ]);

        return ['status' => true, 'session_id' => $session->id];
    }

    /**
     * Handle payment_intent.succeeded event
     */
    private function handlePaymentIntentSucceeded($paymentIntent): array
    {
        Log::info('Payment intent succeeded', [
            'payment_intent_id' => $paymentIntent->id,
            'amount' => $paymentIntent->amount / 100,
        ]);

        return ['status' => true, 'payment_intent_id' => $paymentIntent->id];
    }

    /**
     * Handle payment_intent.payment_failed event
     */
    private function handlePaymentIntentFailed($paymentIntent): array
    {
        Log::warning('Payment intent failed', [
            'payment_intent_id' => $paymentIntent->id,
            'failure_message' => $paymentIntent->last_payment_error->message ?? null,
        ]);

        return ['status' => false, 'payment_intent_id' => $paymentIntent->id];
    }
}