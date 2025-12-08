<?php

namespace App\Services;

use GuzzleHttp\Client;
use App\Models\WhatsAppTemplate;
use Illuminate\Support\Facades\Log;
use GuzzleHttp\Exception\RequestException;

class WhatsAppService
{
    private $client;
    private $baseUrl = 'https://wa.sendsnefru.xyz/api/create-message';
    private $maxRetries = 2;

    public function __construct()
    {
        $this->client = new Client([
            'timeout' => env('WHATSAPP_TIMEOUT', 30),
            'connect_timeout' => 10,
            'verify' => false,
            'http_errors' => false,
        ]);
    }

    /**
     * Format Egyptian phone number correctly
     */
    private function formatPhoneNumber(string $phone): string
    {
        // Remove all non-numeric characters
        $phone = preg_replace('/[^0-9]/', '', $phone);
        
        // Remove leading zeros
        $phone = ltrim($phone, '0');
        
        // Handle different formats
        if (strlen($phone) === 10) {
            // Local format: 1153225410
            $phone = '20' . $phone;
        } elseif (strlen($phone) === 11 && substr($phone, 0, 1) === '1') {
            // Format with leading 1: 11153225410
            $phone = '20' . $phone;
        } elseif (strlen($phone) === 12 && substr($phone, 0, 2) !== '20') {
            // Already has country code but not 20
            // Keep as is
        } elseif (strlen($phone) === 13 && substr($phone, 0, 3) === '020') {
            // Has 020 prefix
            $phone = '20' . substr($phone, 3);
        }
        
        return $phone;
    }

    public function sendMessage(string $to, string $message, ?string $fileUrl = null): array
    {
        $appKey = env('WHATSAPP_APP_KEY');
        $authKey = env('WHATSAPP_AUTH_KEY');

        if (!$appKey || !$authKey) {
            Log::error('WhatsApp: Missing API credentials');
            return ['success' => false, 'error' => 'Missing API keys'];
        }

        // Format phone number
        $originalNumber = $to;
        $to = $this->formatPhoneNumber($to);
        
        Log::info('WhatsApp: Phone number formatted', [
            'original' => $originalNumber,
            'formatted' => $to,
            'length' => strlen($to)
        ]);

        // Validate phone number length
        if (strlen($to) < 10 || strlen($to) > 15) {
            Log::error('WhatsApp: Invalid phone number length', [
                'number' => $to,
                'length' => strlen($to)
            ]);
            return ['success' => false, 'error' => 'Invalid phone number format'];
        }

        $data = [
            'appkey' => $appKey,
            'authkey' => $authKey,
            'to' => $to,
            'message' => $message,
            'sandbox' => 'false', // Changed to false for production
        ];

        if ($fileUrl) {
            // Validate file URL
            if (filter_var($fileUrl, FILTER_VALIDATE_URL)) {
                $data['file'] = $fileUrl;
                Log::info('WhatsApp: Including file attachment', ['url' => $fileUrl]);
            } else {
                Log::warning('WhatsApp: Invalid file URL provided', ['url' => $fileUrl]);
            }
        }

        // Retry logic
        $attempt = 0;
        $lastError = null;

        while ($attempt < $this->maxRetries) {
            $attempt++;
            
            try {
                Log::info("WhatsApp: Sending message (Attempt {$attempt}/{$this->maxRetries})", [
                    'to' => $to,
                    'has_file' => isset($data['file']),
                    'message_length' => strlen($message),
                    'message_preview' => substr($message, 0, 100) . '...'
                ]);

                $response = $this->client->post($this->baseUrl, [
                    'form_params' => $data,
                    'headers' => [
                        'User-Agent' => 'Laravel-WhatsApp/1.0',
                        'Accept' => 'application/json',
                    ],
                ]);

                $statusCode = $response->getStatusCode();
                $body = $response->getBody()->getContents();
                
                Log::info('WhatsApp: API Response received', [
                    'status' => $statusCode,
                    'body_length' => strlen($body),
                    'body' => $body
                ]);

                // Parse JSON response
                $result = json_decode($body, true);
                
                if (json_last_error() !== JSON_ERROR_NONE) {
                    Log::error('WhatsApp: Invalid JSON response', [
                        'body' => $body,
                        'json_error' => json_last_error_msg(),
                        'attempt' => $attempt
                    ]);
                    $lastError = 'Invalid API response format: ' . json_last_error_msg();
                    
                    if ($attempt < $this->maxRetries) {
                        sleep(2);
                        continue;
                    }
                    
                    return ['success' => false, 'error' => $lastError];
                }

                // ✅ SUCCESS CASES
                if ($statusCode === 200) {
                    // Check multiple success indicators
                    $isSuccess = (
                        (isset($result['success']) && $result['success'] === true) ||
                        (isset($result['message_status']) && $result['message_status'] === 'Success') ||
                        (isset($result['status']) && $result['status'] === 'success')
                    );

                    if ($isSuccess) {
                        Log::info('WhatsApp: ✅ Message sent successfully', [
                            'to' => $to,
                            'attempt' => $attempt,
                            'response' => $result
                        ]);
                        return ['success' => true, 'response' => $result, 'attempts' => $attempt];
                    }
                }

                // ✅ HANDLE 500 ERROR WITH "after messageSend"
                if ($statusCode === 500) {
                    $errorMsg = $result['error'] ?? $result['message'] ?? 'Internal Server Error';
                    
                    Log::error('WhatsApp: 500 Server Error', [
                        'status' => $statusCode,
                        'error' => $errorMsg,
                        'attempt' => $attempt,
                        'full_response' => $result
                    ]);
                    
                    // Message was likely sent but confirmation failed
                    if (stripos($errorMsg, 'after messagesend') !== false || 
                        stripos($errorMsg, 'message sent') !== false) {
                        
                        Log::warning('WhatsApp: ⚠️ Message likely sent despite error', [
                            'error' => $errorMsg,
                            'to' => $to
                        ]);
                        
                        return [
                            'success' => true,
                            'warning' => 'Message sent but confirmation failed',
                            'error' => $errorMsg,
                            'attempts' => $attempt
                        ];
                    }
                    
                    $lastError = $errorMsg;
                    
                    // Retry on other 500 errors
                    if ($attempt < $this->maxRetries) {
                        sleep(3);
                        continue;
                    }
                }

                // ✅ HANDLE SESSION ERRORS
                if ($statusCode === 401 || 
                    stripos($body, 'session not found') !== false ||
                    stripos($body, 'unauthorized') !== false) {
                    
                    Log::critical('WhatsApp: 🔴 Session/Auth Error', [
                        'status' => $statusCode,
                        'body' => $body,
                        'to' => $to
                    ]);
                    
                    return [
                        'success' => false,
                        'error' => 'WhatsApp session disconnected. Please contact administrator.',
                        'needs_reconnect' => true
                    ];
                }

                // Handle other HTTP errors
                if ($statusCode >= 400) {
                    $lastError = $result['error'] ?? $result['message'] ?? "HTTP {$statusCode} error";
                    
                    Log::error('WhatsApp: HTTP Error', [
                        'status' => $statusCode,
                        'error' => $lastError,
                        'attempt' => $attempt
                    ]);
                    
                    if ($attempt < $this->maxRetries && $statusCode >= 500) {
                        sleep(2);
                        continue;
                    }
                }

                // Unexpected response format
                $lastError = $result['message'] ?? $result['error'] ?? 'Unexpected API response';
                Log::warning('WhatsApp: Unexpected response', [
                    'status' => $statusCode,
                    'result' => $result,
                    'attempt' => $attempt
                ]);
                
            } catch (\Exception $e) {
                $lastError = $e->getMessage();
                Log::error('WhatsApp: Exception occurred', [
                    'attempt' => $attempt,
                    'error' => $lastError,
                    'class' => get_class($e),
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ]);
                
                if ($attempt < $this->maxRetries) {
                    sleep(2);
                    continue;
                }
            }
        }

        // All retries exhausted
        Log::error('WhatsApp: ❌ All retry attempts failed', [
            'to' => $to,
            'attempts' => $attempt,
            'last_error' => $lastError
        ]);

        return [
            'success' => false,
            'error' => $lastError ?? 'Failed to send message after multiple attempts',
            'attempts' => $attempt
        ];
    }

    public function sendTemplateMessage(string $whatsappType, array $data = []): string
    {
        $template = WhatsAppTemplate::where('whatsapp_type', $whatsappType)
            ->where('type', 'user')
            ->first();
        
        if (!$template) {
            Log::warning('WhatsApp: Template not found, using fallback', [
                'type' => $whatsappType,
                'data_keys' => array_keys($data)
            ]);
            return $this->getFallbackMessage($whatsappType, $data);
        }

        Log::info('WhatsApp: Using template', [
            'type' => $whatsappType,
            'template_id' => $template->id
        ]);

        $message = "*{$template->title}*" . PHP_EOL . PHP_EOL;
        
        // Replace placeholders in body
        $body = $template->body;
        foreach ($data as $key => $value) {
            $body = str_replace(["{{{$key}}}", "{{$key}}", "{".$key."}"], $value, $body);
        }
        
        $message .= $body . PHP_EOL . PHP_EOL;
        
        // Add button
        if ($template->button_name && $template->button_url) {
            $buttonName = $template->button_name;
            foreach ($data as $key => $value) {
                $buttonName = str_replace(["{{$key}}", "{".$key."}"], $value, $buttonName);
            }
            $message .= "👉 {$buttonName}: {$template->button_url}" . PHP_EOL . PHP_EOL;
        }
        
        // Add footer
        if ($template->footer_text) {
            $footer = $template->footer_text;
            foreach ($data as $key => $value) {
                $footer = str_replace(["{{$key}}", "{".$key."}"], $value, $footer);
            }
            $message .= "_{$footer}_" . PHP_EOL . PHP_EOL;
        }
        
        // Add policy links
        $links = [];
        if ($template->privacy) $links[] = "Privacy Policy";
        if ($template->refund) $links[] = "Refund Policy";
        if ($template->cancelation) $links[] = "Cancelation Policy";
        if ($template->contact) $links[] = "Contact Us";
        
        if (!empty($links)) {
            $message .= implode(" | ", $links) . PHP_EOL . PHP_EOL;
        }
        
        // Add copyright
        if ($template->copyright_text) {
            $copyright = $template->copyright_text;
            foreach ($data as $key => $value) {
                $copyright = str_replace(["{{$key}}", "{".$key."}"], $value, $copyright);
            }
            $message .= $copyright;
        }
        
        return $message;
    }

    private function getFallbackMessage(string $type, array $data): string
    {
        $userName = $data['user_name'] ?? 'Customer';
        $appName = env('APP_NAME', 'eFood');
        
        switch ($type) {
            case 'wallet_topup':
                return "*{$appName} - Wallet Top-Up Successful*" . PHP_EOL . PHP_EOL .
                       "Hello {$userName}," . PHP_EOL . PHP_EOL .
                       "Your wallet has been topped up successfully! 🎉" . PHP_EOL . PHP_EOL .
                       "*Transaction Details:*" . PHP_EOL .
                       "• Transaction ID: {$data['transaction_id']}" . PHP_EOL .
                       "• Amount: {$data['amount']} {$data['currency']}" . PHP_EOL .
                       "• Date: {$data['date']}" . PHP_EOL .
                       "• Previous Balance: {$data['previous_balance']} {$data['currency']}" . PHP_EOL .
                       "• New Balance: {$data['new_balance']} {$data['currency']}" . PHP_EOL . PHP_EOL .
                       "_Thank you for using our service._" . PHP_EOL . PHP_EOL .
                       "© " . date('Y') . " {$appName}. All rights reserved.";

            // Add other cases as needed...

            default:
                return "*{$appName} Notification*" . PHP_EOL . PHP_EOL .
                       "Hello {$userName}," . PHP_EOL . PHP_EOL .
                       "You have a new notification from {$appName}." . PHP_EOL . PHP_EOL .
                       "© " . date('Y') . " {$appName}. All rights reserved.";
        }
    }

    /**
     * Test connection to WhatsApp API
     */
    public function testConnection(): array
    {
        $appKey = env('WHATSAPP_APP_KEY');
        $authKey = env('WHATSAPP_AUTH_KEY');

        if (!$appKey || !$authKey) {
            return [
                'success' => false,
                'error' => 'Missing API credentials in .env file',
                'missing' => [
                    'WHATSAPP_APP_KEY' => empty($appKey),
                    'WHATSAPP_AUTH_KEY' => empty($authKey)
                ]
            ];
        }

        Log::info('WhatsApp: Testing connection...', [
            'base_url' => $this->baseUrl,
            'timeout' => env('WHATSAPP_TIMEOUT', 30)
        ]);

        // Use a test number
        $result = $this->sendMessage(
            env('WHATSAPP_TEST_NUMBER', '1234567890'),
            "Connection test from " . env('APP_NAME', 'Application') . " at " . now()->format('Y-m-d H:i:s')
        );
        
        return [
            'success' => $result['success'] ?? false,
            'message' => $result['success'] ? 'Connection OK ✅' : 'Connection Failed ❌',
            'details' => $result
        ];
    }
}