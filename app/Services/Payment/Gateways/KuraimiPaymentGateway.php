<?php

namespace App\Services\Payment\Gateways;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Services\Payment\PaymentGatewayInterface;

class KuraimiPaymentGateway implements PaymentGatewayInterface
{
    protected $baseUrl;
    protected $username;
    protected $password;
    protected $timeout;

    public function __construct()
    {
        $isProduction = config('payment.kuraimi.is_production', false);
        $this->baseUrl = $isProduction
            ? config('payment.kuraimi.production_url')
            : config('payment.kuraimi.uat_url');

        // Get credentials from config (not env directly)
        $this->username = config('payment.kuraimi.username');
        $this->password = config('payment.kuraimi.password');
        $this->timeout = config('payment.kuraimi.timeout', 30);

        // Validate credentials are set
        if (empty($this->username) || empty($this->password)) {
            Log::error('Kuraimi credentials not configured', [
                'username_set' => !empty($this->username),
                'password_set' => !empty($this->password),
            ]);
        }

        Log::info('Kuraimi Gateway Initialized', [
            'base_url' => $this->baseUrl,
            'is_production' => $isProduction,
            'username' => substr($this->username, 0, 3) . '***', // Log partial username only
        ]);
    }

    /**
     * Generate Basic Auth header
     */
    private function getBasicAuthHeader(): string
    {
        // PHP's base64_encode handles this correctly
        $credentials = base64_encode("{$this->username}:{$this->password}");

        Log::debug('Basic Auth Header Generated', [
            'credentials_length' => strlen($credentials),
            'username_length' => strlen($this->username),
        ]);

        return "Basic {$credentials}";
    }

    /**
     * Verify customer details on supplier platform
     * This is called BY Kuraimi TO our system
     */
    public function verifyCustomerDetails(array $data): array
    {
        try {
            $phone = !empty($data['phone']) ? (string) $data['phone'] : null;
            if (!$phone) {
                return ['status' => false, 'error' => 'Mobile number is required', 'error_code' => 'invalid_input'];
            }

            // Normalization
            $phone = preg_replace('/[^0-9]/', '', $phone);
            $localPhone = $phone;
            if (str_starts_with($phone, '967') && strlen($phone) > 9) {
                $localPhone = substr($phone, 3);
            }
            $prefixedPhone = '967' . (str_starts_with($localPhone, '0') ? substr($localPhone, 1) : $localPhone);
            if (strlen($localPhone) > 9 && str_starts_with($localPhone, '0')) {
                $localPhone = substr($localPhone, 1);
            }

            $zone = (string) ($data['customer_zone'] ?? 'YE0012004');

            // Variations to probe: [Key, Value]
            $variations = [
                ['MobileNo', $localPhone],
                ['MOBILENO', $localPhone],
                ['MSISDN', $localPhone],
                ['MSISDN', $prefixedPhone],
                ['SCustID', $localPhone],
                ['SCUSTID', $localPhone],
                ['CustID', $localPhone],
            ];

            Log::info('Kuraimi Multi-Probe Started', ['variations_count' => count($variations)]);

            foreach ($variations as $variation) {
                [$key, $val] = $variation;
                $payload = [
                    $key => $val,
                    'CustomerZone' => $zone,
                ];

                try {
                    $response = Http::timeout(10)
                        ->withHeaders([
                            'Authorization' => $this->getBasicAuthHeader(),
                            'Content-Type' => 'application/json',
                            'Accept' => 'application/json',
                        ])
                        ->post("{$this->baseUrl}/v1/PHEPaymentAPI/EPayment/VerifyCustomer", $payload);

                    $result = $response->json();
                    $code = $result['Code'] ?? ($result['CODE'] ?? 'N/A');

                    Log::info("Kuraimi Probe Variation: $key=$val", [
                        'code' => $code,
                        'response' => $result,
                        'status' => $response->status()
                    ]);

                    // If we get Code 1 (Success) or Code 2 (Not Found), it means the structure is CORRECT
                    if ($code == 1 || $code == 2) {
                        Log::info("!!! Kuraimi Probe SUCCESS for key $key !!!", ['payload' => $payload]);
                        return [
                            'status' => $code == 1,
                            'data' => $result,
                            'message' => $result['Message'] ?? ($result['DescriptionEn'] ?? null),
                            'error' => $code == 2 ? 'Customer not found' : null,
                            'error_code' => $code
                        ];
                    }
                } catch (\Exception $e) {
                    Log::error("Kuraimi Probe Exception for $key: " . $e->getMessage());
                }
            }

            return [
                'status' => false,
                'error' => 'Verification failed with Exception-1 for all variations. Contact Kuraimi support.',
                'error_code' => 3
            ];
        } catch (\Exception $e) {
            Log::error('Kuraimi Verify Customer Exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'status' => false,
                'error' => 'Verification error: ' . $e->getMessage(),
                'error_code' => 'exception'
            ];
        }
    }

    /**
     * Request payment (initiate e-payment transaction)
     */
    public function requestPayment(array $data): array
    {
        try {
            // Verify customer first (optional but recommended)
            $verifyResult = $this->verifyCustomerDetails([
                'customer_id' => $data['customer_id'] ?? $data['payment_SCustID'] ?? null,
                'phone' => $data['phone'] ?? null,
                'email' => $data['email'] ?? null,
                'customer_zone' => $data['customer_zone'] ?? 'YE0012004',
            ]);

            if (!$verifyResult['status']) {
                Log::warning('Customer verification failed before payment', [
                    'verify_result' => $verifyResult,
                ]);
                return $verifyResult;
            }

            // Prepare payment request
            $payload = [
                'SCustID' => (string) ($data['payment_SCustID'] ?? ''),
                'REFNO' => (string) ($data['transaction_id'] ?? 'REF_' . time()),
                'AMOUNT' => (float) $data['amount'],
                'CRCY' => (string) ($data['currency'] ?? 'YER'),
                'MRCHNTNAME' => (string) ($data['merchant_name'] ?? config('app.name', 'Merchant')),
                'PINPASS' => base64_encode($data['pin_pass'] ?? ''),
            ];

            Log::info('Kuraimi E-Payment Request', [
                'url' => "{$this->baseUrl}/v1/PHEPaymentAPI/EPayment/SendPayment",
                'customer_id' => $payload['SCustID'],
                'amount' => $payload['AMOUNT'],
                'currency' => $payload['CRCY'],
                'reference_no' => $payload['REFNO'],
            ]);

            $response = Http::timeout($this->timeout)
                ->withHeaders([
                    'Authorization' => $this->getBasicAuthHeader(),
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ])
                ->post("{$this->baseUrl}/v1/PHEPaymentAPI/EPayment/SendPayment", $payload);

            $result = $response->json();

            Log::info('Kuraimi E-Payment Response', [
                'status_code' => $response->status(),
                'code' => $result['CODE'] ?? null,
                'message' => $result['MESSAGE'] ?? null,
            ]);

            // Check if payment was successful (CODE: 1 = Success)
            if (isset($result['CODE']) && $result['CODE'] == 1) {
                return [
                    'status' => true,
                    'message' => $result['MESSAGE'] ?? 'Payment initiated successfully',
                    'message_ar' => $result['MESSAGEDESC'] ?? 'تم بدء الدفع بنجاح',
                    'transaction_id' => $result['ResultSet']['PH_REF_NO'] ?? $payload['REFNO'],
                    'reference_number' => $payload['REFNO'],
                    'requires_otp' => false,
                    'error_code' => '0',
                ];
            }

            // Map error codes to user-friendly messages
            $errorMessages = $this->getErrorMessages();
            $errorCode = $result['CODE'] ?? 'unknown';
            $errorMessage = $errorMessages[$errorCode] ?? ($result['MESSAGE'] ?? 'Payment initiation failed');

            return [
                'status' => false,
                'error' => $errorMessage,
                'error_ar' => $result['MESSAGEDESC'] ?? 'فشل بدء الدفع',
                'error_code' => $errorCode,
                'errors' => [['code' => $errorCode, 'message' => $errorMessage]]
            ];

        } catch (\Exception $e) {
            Log::error('Kuraimi E-Payment Exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'status' => false,
                'error' => 'Payment initiation failed: ' . $e->getMessage(),
                'errors' => [['code' => 'kuraimi_exception', 'message' => $e->getMessage()]]
            ];
        }
    }

    /**
     * Confirm payment (already processed synchronously in requestPayment)
     */
    public function confirmPayment(array $data): array
    {
        // Kuraimi processes payments synchronously, no separate confirmation needed
        return [
            'status' => true,
            'message' => 'Payment already processed',
            'transaction_id' => $data['transaction_id'] ?? null,
        ];
    }

    /**
     * Resend OTP (not applicable for Kuraimi)
     */
    public function resendOTP(array $data): array
    {
        return [
            'status' => false,
            'error' => 'OTP not required for Kuraimi payments',
            'error_code' => 'not_applicable'
        ];
    }

    /**
     * Check payment status
     */
    public function checkPaymentStatus(array $data): array
    {
        try {
            $payload = [
                'REFNO' => $data['reference_number'] ?? $data['transaction_id'],
                'SCUSTID' => $data['customer_id'],
            ];

            Log::info('Kuraimi Payment Status Check', [
                'reference_number' => $payload['REFNO'],
                'customer_id' => $payload['SCUSTID'],
            ]);

            $response = Http::timeout($this->timeout)
                ->withHeaders([
                    'Authorization' => $this->getBasicAuthHeader(),
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ])
                ->post("{$this->baseUrl}/v1/PHEPaymentAPI/EPayment/PaymentStatus", $payload);

            $result = $response->json();

            Log::info('Kuraimi Payment Status Response', [
                'status_code' => $response->status(),
                'code' => $result['Code'] ?? null,
                'payment_status' => $result['ResultSet']['Status'] ?? null,
            ]);

            if (isset($result['Code']) && $result['Code'] == 200) {
                $status = $result['ResultSet']['Status'] ?? 'UNKNOWN';

                return [
                    'status' => true,
                    'payment_status' => $status,
                    'is_paid' => $status === 'PAID',
                    'is_initiated' => $status === 'INITIATED',
                    'is_refunded' => $status === 'REFUNDED',
                    'is_reversed' => $status === 'REVERSED',
                    'is_partial_refunded' => $status === 'PARTIALREFUNDED',
                    'message' => "Payment status: {$status}",
                ];
            }

            return [
                'status' => false,
                'error' => $result['Message'] ?? 'Failed to check payment status',
                'error_code' => $result['Code'] ?? 'unknown',
            ];

        } catch (\Exception $e) {
            Log::error('Kuraimi Payment Status Exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'status' => false,
                'error' => 'Payment status check failed: ' . $e->getMessage(),
                'error_code' => 'exception'
            ];
        }
    }

    /**
     * Reverse payment transaction
     */
    public function reversePayment(array $data): array
    {
        try {
            $payload = [
                'SCUSTID' => $data['customer_id'],
                'REFNO' => $data['reference_number'] ?? $data['transaction_id'],
            ];

            Log::info('Kuraimi Reverse Payment', [
                'customer_id' => $payload['SCUSTID'],
                'reference_number' => $payload['REFNO'],
            ]);

            $response = Http::timeout($this->timeout)
                ->withHeaders([
                    'Authorization' => $this->getBasicAuthHeader(),
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ])
                ->post("{$this->baseUrl}/v1/PHEPaymentAPI/EPayment/ReversePayment", $payload);

            $result = $response->json();

            Log::info('Kuraimi Reverse Payment Response', [
                'status_code' => $response->status(),
                'code' => $result['CODE'] ?? null,
                'message' => $result['MESSAGE'] ?? null,
            ]);

            if (isset($result['CODE']) && $result['CODE'] == 1) {
                return [
                    'status' => true,
                    'message' => $result['MESSAGE'] ?? 'Payment reversed successfully',
                    'message_ar' => $result['MESSAGEDESC'] ?? 'تم عكس الدفع بنجاح',
                    'reference_number' => $result['ResultSet']['PH_REF_NO'] ?? $payload['REFNO'],
                ];
            }

            return [
                'status' => false,
                'error' => $result['MESSAGE'] ?? 'Payment reversal failed',
                'error_ar' => $result['MESSAGEDESC'] ?? 'فشل عكس الدفع',
                'error_code' => $result['CODE'] ?? 'unknown',
            ];

        } catch (\Exception $e) {
            Log::error('Kuraimi Reverse Payment Exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'status' => false,
                'error' => 'Payment reversal failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Handle callback (Kuraimi uses synchronous processing)
     */
    public function handleCallback(array $data): array
    {
        return [
            'status' => 'success',
            'message' => 'Kuraimi uses synchronous payment processing',
        ];
    }

    /**
     * Get payment methods
     */
    public function getPaymentMethods(): array
    {
        return [
            [
                'id' => 'kuraimi',
                'name' => 'Kuraimi Pay',
                'description' => 'Pay via Kuraimi Bank - Direct bank transfer',
                'requires_otp' => false,
                'is_synchronous' => true,
            ]
        ];
    }

    /**
     * Get error messages mapping
     */
    private function getErrorMessages(): array
    {
        return [
            35 => 'Invalid data input',
            232 => 'User must be registered in Kuraimi app first',
            270 => 'Wrong PIN, please check and try again',
            190 => 'Invalid customer account',
            271 => 'Insufficient balance',
            111 => 'Transaction not allowed - frozen account',
            86 => 'User found in blacklist',
            233 => 'Supplier zone not available',
            260 => 'Transaction limit exceeded',
            238 => 'Reference number already exists',
            272 => 'Duplicate request detected',
            36 => 'Undefined error',
            3 => 'System exception occurred',
        ];
    }
}