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
        $credentials = base64_encode("{$this->username}:{$this->password}");

        Log::debug('Basic Auth Header', [
            'credentials_b64_preview' => substr($credentials, 0, 12) . '...',
            'username' => $this->username,
            'password_length' => strlen($this->password),
        ]);

        return "Basic {$credentials}";
    }

    /**
     * Verify customer details on our supplier platform.
     * INBOUND API (Section 13.1): Kuraimi calls this endpoint on OUR system
     * to verify a customer exists before processing a payment.
     * This method is NOT called outbound to Kuraimi.
     */
    public function verifyCustomerDetails(array $data): array
    {
        try {
            $phone = !empty($data['phone']) ? (string) $data['phone'] : null;
            if (!$phone) {
                return ['status' => false, 'error' => 'Mobile number is required', 'error_code' => 'invalid_input'];
            }

            // Normalize: digits only, local 9-digit format
            $phone = preg_replace('/[^0-9]/', '', $phone);
            if (str_starts_with($phone, '967') && strlen($phone) > 9) {
                $phone = substr($phone, 3);
            }
            if (str_starts_with($phone, '0')) {
                $phone = substr($phone, 1);
            }

            $payload = [
                'MobileNo' => $phone,
                'CustomerZone' => (string) ($data['customer_zone'] ?? 'YE0012004'),
            ];

            Log::info('Kuraimi VerifyCustomer Request', [
                'url' => "{$this->baseUrl}/v1/PHEPaymentAPI/EPayment/VerifyCustomer",
                'payload' => $payload,
            ]);

            $response = Http::timeout($this->timeout)
                ->withHeaders([
                    'Authorization' => $this->getBasicAuthHeader(),
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ])
                ->post("{$this->baseUrl}/v1/PHEPaymentAPI/EPayment/VerifyCustomer", $payload);

            $result = $response->json();

            Log::info('Kuraimi VerifyCustomer Response', [
                'status_code' => $response->status(),
                'response' => $result,
            ]);

            if ($response->successful() && isset($result['Code'])) {
                $code = (int) $result['Code'];

                if ($code === 1) {
                    return [
                        'status' => true,
                        'customer_id' => $result['SCustID'] ?? null,
                        'message' => $result['DescriptionEn'] ?? 'Customer verified',
                        'error_code' => 0,
                    ];
                }

                $errorMessages = $this->getErrorMessages();
                return [
                    'status' => false,
                    'error' => $errorMessages[$code] ?? ($result['Message'] ?? 'Verification failed'),
                    'error_code' => $code,
                ];
            }

            return ['status' => false, 'error' => 'Invalid response from gateway', 'error_code' => 'invalid_response'];
        } catch (\Exception $e) {
            Log::error('Kuraimi VerifyCustomer Exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return ['status' => false, 'error' => $e->getMessage(), 'error_code' => 'exception'];
        }
    }

    /**
     * Request payment (initiate e-payment transaction)
     * Per Kuraimi docs Section 14.1: Supplier calls SendPayment directly.
     * VerifyCustomer (Section 13.1) is an INBOUND API that Kuraimi calls on OUR system.
     */
    public function requestPayment(array $data): array
    {
        try {

            // Prepare payment request per docs Section 14.1
            // SCustID = unique customer ID on OUR platform (returned by our VerifyCustomer to Kuraimi)
            $scustId = $data['payment_SCustID'] ?? $data['customer_id'] ?? '';
            $payload = [
                'SCustID' => (string) $scustId,
                'REFNO' => (string) ($data['transaction_id'] ?? 'REF_' . time()),
                'AMOUNT' => (float) $data['amount'],
                'CRCY' => (string) ($data['currency'] ?? 'YER'),
                'MRCHNTNAME' => (string) ($data['merchant_name'] ?? config('app.name', 'Merchant')),
                'PINPASS' => base64_encode($data['pin_pass'] ?? ''),
            ];

            Log::info('Kuraimi E-Payment Request', [
                'url' => "{$this->baseUrl}/v1/PHEPaymentAPI/EPayment/SendPayment",
                'full_payload' => $payload,
                'pin_pass_raw' => $data['pin_pass'] ?? 'NOT_SET',
                'pin_pass_encoded' => $payload['PINPASS'],
            ]);

            $response = Http::timeout($this->timeout)
                ->withHeaders([
                    'Authorization' => $this->getBasicAuthHeader(),
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ])
                ->post("{$this->baseUrl}/v1/PHEPaymentAPI/EPayment/SendPayment", $payload);

            $result = $response->json();

            // Normalize response keys: API uses PascalCase (Code) not UPPERCASE (CODE)
            $code = $result['CODE'] ?? $result['Code'] ?? null;
            $message = $result['MESSAGE'] ?? $result['Message'] ?? null;
            $messageDesc = $result['MESSAGEDESC'] ?? $result['MessageDesc'] ?? null;
            $resultSet = $result['ResultSet'] ?? $result['resultSet'] ?? null;

            Log::info('Kuraimi E-Payment Response', [
                'status_code' => $response->status(),
                'code' => $code,
                'message' => $message,
                'message_desc' => $messageDesc,
                'body' => $response->body(),
            ]);

            // Check if payment was successful (Code: 1 = Success)
            if ($code !== null && (int) $code === 1) {
                return [
                    'status' => true,
                    'message' => $message ?? 'Payment initiated successfully',
                    'message_ar' => $messageDesc ?? 'تم بدء الدفع بنجاح',
                    'transaction_id' => $resultSet['PH_REF_NO'] ?? $payload['REFNO'],
                    'reference_number' => $payload['REFNO'],
                    'requires_otp' => false,
                    'error_code' => '0',
                ];
            }

            // Map error codes to user-friendly messages
            $errorMessages = $this->getErrorMessages();
            $errorCode = $code ?? 'unknown';
            $errorMessage = $errorMessages[$errorCode] ?? ($message ?? 'Payment initiation failed');

            return [
                'status' => false,
                'error' => $errorMessage,
                'error_ar' => $messageDesc ?? 'فشل بدء الدفع',
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