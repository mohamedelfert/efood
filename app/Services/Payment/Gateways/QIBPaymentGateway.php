<?php

namespace App\Services\Payment\Gateways;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Services\Payment\PaymentGatewayInterface;

class QIBPaymentGateway implements PaymentGatewayInterface
{
    protected $baseUrl;
    protected $apiKey;
    protected $appKey;
    protected $timeout;

    public function __construct()
    {
        $isProduction = config('payment.alqutaibi.is_production', false);
        $this->baseUrl = $isProduction 
            ? config('payment.alqutaibi.production_url')
            : config('payment.alqutaibi.base_url');
        $this->apiKey = config('payment.alqutaibi.api_key');
        $this->appKey = config('payment.alqutaibi.app_key');
        $this->timeout = config('payment.alqutaibi.timeout', 30);
    }

    /**
     * Encrypt string using AES-128-CBC (matching C# implementation)
     */
    private function encryptString(string $key, string $plainInput): string
    {
        // IV must be exactly 16 null bytes (128 bit)
        $iv = str_repeat("\0", 16);
        
        // Use AES-128-CBC with PKCS7 padding
        $encrypted = openssl_encrypt(
            $plainInput,
            'aes-128-cbc',
            $key,
            OPENSSL_RAW_DATA,
            $iv
        );
        
        return base64_encode($encrypted);
    }

    /**
     * Normalize customer number (remove leading 4 if 9 digits)
     */
    private function normalizeCustomerNumber($number): string
    {
        $number = (string) $number;
        
        if (strlen($number) == 9 && substr($number, 0, 1) == '4') {
            return substr($number, 1);
        }
        
        return $number;
    }

    /**
     * Map currency code to QIB currency ID
     */
    private function getCurrencyId(string $currency): int
    {
        return match(strtoupper($currency)) {
            'YER' => 1,
            'SAR' => 2,
            'USD' => 3,
            default => 1, // Default to YER
        };
    }

    /**
     * Request payment - initiates payment and sends OTP
     */
    public function requestPayment(array $data): array
    {
        try {
            // Normalize and encrypt customer_no
            $normalizedDestination = $this->normalizeCustomerNumber($data['payment_DestNation']);
            $encryptedCustomerNo = $this->encryptString($this->apiKey, $normalizedDestination);

            // Prepare request payload
            $payload = [
                'customer_no' => $encryptedCustomerNo,
                'payment_CustomerNo' => (int) $data['payment_CustomerNo'],
                'payment_DestNation' => (int) $normalizedDestination,
                'payment_Code' => (int) $data['payment_Code'],
                'payment_Amount' => (float) $data['amount'],
                'payment_Curr' => $this->getCurrencyId($data['currency'] ?? 'YER'),
            ];

            Log::info('QIB RequestPayment', [
                'payload' => $payload,
                'encrypted_customer_no' => $encryptedCustomerNo,
                'normalized_destination' => $normalizedDestination,
            ]);

            // Make API request
            $response = Http::timeout($this->timeout)
                ->withHeaders([
                    'X-API-KEY' => $this->apiKey,
                    'X-APP-KEY' => $this->appKey,
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ])
                ->post("{$this->baseUrl}/E_Payment/RequestPayment", $payload);

            $result = $response->json();

            Log::info('QIB RequestPayment Response', [
                'status_code' => $response->status(),
                'response' => $result,
            ]);

            // Check if request was successful
            if (isset($result['status']) && $result['status'] === true) {
                return [
                    'status' => true,
                    'message' => $result['description'] ?? 'OTP sent successfully',
                    'message_en' => $result['descriptionEn'] ?? 'OTP sent successfully',
                    'transaction_id' => $result['transactionID'] ?? null,
                    'currency_id' => $result['currencyId'] ?? null,
                    'requires_otp' => true,
                    'error_code' => '0',
                ];
            }

            // Handle error
            return [
                'status' => false,
                'error' => $result['description'] ?? 'Payment request failed',
                'error_en' => $result['descriptionEn'] ?? 'Payment request failed',
                'error_code' => $result['errorCode'] ?? 'unknown',
                'errors' => [['code' => $result['errorCode'] ?? 'qib_error', 'message' => $result['description'] ?? 'Payment request failed']]
            ];

        } catch (\Exception $e) {
            Log::error('QIB RequestPayment Exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'status' => false,
                'error' => 'Payment request failed: ' . $e->getMessage(),
                'errors' => [['code' => 'qib_exception', 'message' => $e->getMessage()]]
            ];
        }
    }

    /**
     * Confirm payment with OTP
     */
    public function confirmPayment(array $data): array
    {
        try {
            // Normalize and encrypt customer_no
            $normalizedDestination = $this->normalizeCustomerNumber($data['payment_DestNation']);
            $encryptedCustomerNo = $this->encryptString($this->apiKey, $normalizedDestination);

            // Prepare request payload
            $payload = [
                'customer_no' => $encryptedCustomerNo,
                'payment_CustomerNo' => (int) $data['payment_CustomerNo'],
                'payment_DestNation' => (int) $normalizedDestination,
                'payment_Code' => (int) $data['payment_Code'],
                'payment_Amount' => (float) $data['payment_Amount'],
                'payment_Curr' => $this->getCurrencyId($data['payment_Curr'] ?? 'YER'),
                'Payment_OTP' => (int) $data['Payment_OTP'],
            ];

            Log::info('QIB ConfirmPayment', ['payload' => $payload]);

            // Make API request
            $response = Http::timeout($this->timeout)
                ->withHeaders([
                    'X-API-KEY' => $this->apiKey,
                    'X-APP-KEY' => $this->appKey,
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ])
                ->post("{$this->baseUrl}/E_Payment/ConfirmPayment", $payload);

            $result = $response->json();

            Log::info('QIB ConfirmPayment Response', [
                'status_code' => $response->status(),
                'response' => $result,
            ]);

            // Check if confirmation was successful
            if (isset($result['status']) && $result['status'] === true) {
                return [
                    'status' => true,
                    'message' => $result['description'] ?? 'Payment confirmed successfully',
                    'message_en' => $result['descriptionEn'] ?? 'Payment confirmed successfully',
                    'transaction_id' => $result['transactionID'] ?? null,
                    'qib_transaction_id' => $result['transactionID'] ?? null,
                    'error_code' => '0',
                ];
            }

            // Handle error
            return [
                'status' => false,
                'error' => $result['description'] ?? 'Payment confirmation failed',
                'error_en' => $result['descriptionEn'] ?? 'Payment confirmation failed',
                'error_code' => $result['errorCode'] ?? 'unknown',
            ];

        } catch (\Exception $e) {
            Log::error('QIB ConfirmPayment Exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'status' => false,
                'error' => 'Payment confirmation failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Resend OTP
     */
    public function resendOTP(array $data): array
    {
        try {
            // Normalize and encrypt customer_no
            $normalizedDestination = $this->normalizeCustomerNumber($data['payment_DestNation']);
            $encryptedCustomerNo = $this->encryptString($this->apiKey, $normalizedDestination);

            // Prepare request payload
            $payload = [
                'customer_no' => $encryptedCustomerNo,
                'payment_CustomerNo' => (int) $data['payment_CustomerNo'],
                'payment_DestNation' => (int) $normalizedDestination,
                'payment_Code' => (int) $data['payment_Code'],
                'payment_Amount' => (float) $data['payment_Amount'],
                'payment_Curr' => $this->getCurrencyId($data['payment_Curr'] ?? 'YER'),
                'Payment_OTP' => (int) $data['Payment_OTP'], // Expired OTP
            ];

            Log::info('QIB ResendOTP', ['payload' => $payload]);

            // Make API request
            $response = Http::timeout($this->timeout)
                ->withHeaders([
                    'X-API-KEY' => $this->apiKey,
                    'X-APP-KEY' => $this->appKey,
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ])
                ->post("{$this->baseUrl}/E_Payment/ResendOTP", $payload);

            $result = $response->json();

            Log::info('QIB ResendOTP Response', [
                'status_code' => $response->status(),
                'response' => $result,
            ]);

            // Check if resend was successful
            if (isset($result['status']) && $result['status'] === true) {
                return [
                    'status' => true,
                    'message' => $result['description'] ?? 'OTP resent successfully',
                    'error_number' => $result['errorNumber'] ?? 1011,
                ];
            }

            return [
                'status' => false,
                'error' => $result['description'] ?? 'OTP resend failed',
                'error_number' => $result['errorNumber'] ?? 'unknown',
            ];

        } catch (\Exception $e) {
            Log::error('QIB ResendOTP Exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'status' => false,
                'error' => 'OTP resend failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Handle callback (QIB doesn't use callbacks, confirmation is synchronous)
     */
    public function handleCallback(array $data): array
    {
        // QIB uses synchronous OTP confirmation, no async callbacks
        return [
            'status' => 'success',
            'message' => 'QIB uses synchronous confirmation',
        ];
    }

    /**
     * Get payment methods
     */
    public function getPaymentMethods(): array
    {
        return [
            [
                'id' => 'qib',
                'name' => 'QIB Bank',
                'description' => 'Pay via QIB Bank with OTP verification',
                'requires_otp' => true,
            ]
        ];
    }
}