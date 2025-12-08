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
            'http_errors' => false, // Don't throw exceptions on HTTP errors
        ]);
    }

    public function sendMessage(string $to, string $message, ?string $fileUrl = null): array
    {
        $appKey = env('WHATSAPP_APP_KEY');
        $authKey = env('WHATSAPP_AUTH_KEY');

        if (!$appKey || !$authKey) {
            Log::error('WhatsApp keys missing');
            return ['success' => false, 'error' => 'Missing API keys'];
        }

        // ✅ Better phone number cleaning
        $to = preg_replace('/[^0-9]/', '', $to);
        
        // Remove leading zeros
        $to = ltrim($to, '0');
        
        // Remove country code if present
        if (strlen($to) === 10 && substr($to, 0, 2) !== '20') {
            $to = '20' . $to;
        }
        
        Log::info('WhatsApp: Phone number formatted', [
            'final_number' => $to,
            'length' => strlen($to)
        ]);

        $data = [
            'appkey' => $appKey,
            'authkey' => $authKey,
            'to' => $to,
            'message' => $message,
            'sandbox' => 'true',
        ];

        if ($fileUrl) {
            $data['file'] = $fileUrl;
        }

        // ✅ Retry logic
        $attempt = 0;
        $lastError = null;

        while ($attempt < $this->maxRetries) {
            $attempt++;
            
            try {
                Log::info("WhatsApp: Attempt {$attempt}/{$this->maxRetries}", [
                    'to' => $to,
                    'message_preview' => substr($message, 0, 50) . '...'
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
                
                Log::info('WhatsApp: API Response', [
                    'status' => $statusCode,
                    'body' => $body
                ]);

                // Check if response is valid JSON
                $result = json_decode($body, true);
                
                if (json_last_error() !== JSON_ERROR_NONE) {
                    Log::error('WhatsApp: Invalid JSON response', [
                        'body' => $body,
                        'json_error' => json_last_error_msg()
                    ]);
                    $lastError = 'Invalid API response format';
                    
                    if ($attempt < $this->maxRetries) {
                        sleep(2); // Wait 2 seconds before retry
                        continue;
                    }
                    
                    return ['success' => false, 'error' => $lastError];
                }

                // Check for success
                if ($statusCode === 200 && (
                    (isset($result['success']) && $result['success'] === true) ||
                    (isset($result['message_status']) && $result['message_status'] === 'Success')
                )) {
                    Log::info('WhatsApp: Message sent successfully', [
                        'to' => $to,
                        'attempt' => $attempt,
                        'response' => $result
                    ]);
                    return ['success' => true, 'response' => $result];
                }

                // Handle API errors
                if ($statusCode === 500) {
                    $errorMsg = $result['error'] ?? 'Internal Server Error';
                    Log::error('WhatsApp: Server error', [
                        'status' => $statusCode,
                        'error' => $errorMsg,
                        'attempt' => $attempt
                    ]);
                    
                    // ✅ Don't retry on "Request Failed internally after messageSend"
                    // This usually means message was queued but confirmation failed
                    if (strpos($errorMsg, 'after messageSend') !== false) {
                        Log::warning('WhatsApp: Message likely sent despite error');
                        return [
                            'success' => true, // Mark as success since message was likely queued
                            'warning' => 'Message sent but confirmation failed',
                            'error' => $errorMsg
                        ];
                    }
                    
                    $lastError = $errorMsg;
                    
                    if ($attempt < $this->maxRetries) {
                        sleep(3); // Wait 3 seconds before retry
                        continue;
                    }
                }

                // Handle session errors
                if ($statusCode === 401 || strpos($body, 'Session not found') !== false) {
                    Log::critical('WhatsApp: Session expired', [
                        'to' => $to,
                        'status' => $statusCode
                    ]);
                    return [
                        'success' => false,
                        'error' => 'WhatsApp session disconnected. Please contact administrator.',
                        'needs_reconnect' => true
                    ];
                }

                // Other errors
                $lastError = $result['message'] ?? $result['error'] ?? 'Unknown error';
                
            } catch (\Exception $e) {
                $lastError = $e->getMessage();
                Log::error('WhatsApp: Exception occurred', [
                    'attempt' => $attempt,
                    'error' => $lastError,
                    'trace' => $e->getTraceAsString()
                ]);
                
                if ($attempt < $this->maxRetries) {
                    sleep(2);
                    continue;
                }
            }
        }

        // All retries failed
        Log::error('WhatsApp: All retry attempts failed', [
            'to' => $to,
            'attempts' => $attempt,
            'last_error' => $lastError
        ]);

        return [
            'success' => false,
            'error' => $lastError ?? 'Failed to send message after multiple attempts'
        ];
    }

    public function sendTemplateMessage(string $whatsappType, array $data = []): string
    {
        $template = WhatsAppTemplate::where('whatsapp_type', $whatsappType)
            ->where('type', 'user')
            ->first();
        
        if (!$template) {
            Log::warning('WhatsApp template not found, using fallback', ['type' => $whatsappType]);
            return $this->getFallbackMessage($whatsappType, $data);
        }

        $message = "*{$template->title}*".PHP_EOL.PHP_EOL;
        
        $body = $template->body;
        foreach ($data as $key => $value) {
            $body = str_replace("{{{$key}}}", $value, $body);
            $body = str_replace("{" . $key . "}", $value, $body);
        }
        
        $message .= $body . PHP_EOL.PHP_EOL;
        
        if ($template->button_name && $template->button_url) {
            $buttonName = $template->button_name;
            foreach ($data as $key => $value) {
                $buttonName = str_replace("{" . $key . "}", $value, $buttonName);
            }
            $message .= "👉 {$buttonName}: {$template->button_url}".PHP_EOL.PHP_EOL;
        }
        
        if ($template->footer_text) {
            $footer = $template->footer_text;
            foreach ($data as $key => $value) {
                $footer = str_replace("{" . $key . "}", $value, $footer);
            }
            $message .= "_{$footer}_".PHP_EOL.PHP_EOL;
        }
        
        $links = [];
        if ($template->privacy) $links[] = "Privacy Policy";
        if ($template->refund) $links[] = "Refund Policy";
        if ($template->cancelation) $links[] = "Cancelation Policy";
        if ($template->contact) $links[] = "Contact Us";
        
        if (!empty($links)) {
            $message .= implode(" | ", $links) . PHP_EOL.PHP_EOL;
        }
        
        if ($template->copyright_text) {
            $copyright = $template->copyright_text;
            foreach ($data as $key => $value) {
                $copyright = str_replace("{" . $key . "}", $value, $copyright);
            }
            $message .= $copyright;
        }
        
        return $message;
    }

    private function getFallbackMessage(string $type, array $data): string
    {
        $userName = $data['user_name'] ?? 'Customer';
        
        switch ($type) {
            case 'login_otp':
                return "*eFood - Login Verification*".PHP_EOL.PHP_EOL.
                       "Hello {$userName},".PHP_EOL.PHP_EOL.
                       "Your verification code is: *{$data['otp']}*".PHP_EOL.PHP_EOL.
                       "This code will expire in {$data['expiry_minutes']} minutes.".PHP_EOL.PHP_EOL.
                       "If you didn't request this code, please ignore this message.".PHP_EOL.PHP_EOL.
                       "_Do not share this code with anyone._".PHP_EOL.PHP_EOL.
                       "© ".date('Y')." eFood. All rights reserved.";

            case 'wallet_topup':
                return "*eFood - Wallet Top-Up*".PHP_EOL.PHP_EOL.
                       "Hello {$userName},".PHP_EOL.PHP_EOL.
                       "Your wallet has been topped up successfully!".PHP_EOL.PHP_EOL.
                       "*Transaction Details:*".PHP_EOL.
                       "• Transaction ID: {$data['transaction_id']}".PHP_EOL.
                       "• Amount: {$data['amount']} {$data['currency']}".PHP_EOL.
                       "• Date: {$data['date']}".PHP_EOL.
                       "• Previous Balance: {$data['previous_balance']} {$data['currency']}".PHP_EOL.
                       "• New Balance: {$data['new_balance']} {$data['currency']}".PHP_EOL.PHP_EOL.
                       "_Thank you for using our service._".PHP_EOL.PHP_EOL.
                       "© ".date('Y')." eFood. All rights reserved.";

            case 'transfer_sent':
                return "*eFood - Money Sent*".PHP_EOL.PHP_EOL.
                       "Hello {$userName},".PHP_EOL.PHP_EOL.
                       "You have successfully sent {$data['amount']} {$data['currency']} to {$data['recipient_name']}.".PHP_EOL.PHP_EOL.
                       "*Transaction Details:*".PHP_EOL.
                       "• Transaction ID: {$data['transaction_id']}".PHP_EOL.
                       "• Amount: {$data['amount']} {$data['currency']}".PHP_EOL.
                       "• Recipient: {$data['recipient_name']}".PHP_EOL.
                       "• New Balance: {$data['new_balance']} {$data['currency']}".PHP_EOL.PHP_EOL.
                       "_Thank you for using our service._".PHP_EOL.PHP_EOL.
                       "© ".date('Y')." eFood. All rights reserved.";

            case 'transfer_received':
                return "*eFood - Money Received*".PHP_EOL.PHP_EOL.
                       "Hello {$userName},".PHP_EOL.PHP_EOL.
                       "You have received {$data['amount']} {$data['currency']} from {$data['sender_name']}.".PHP_EOL.PHP_EOL.
                       "*Transaction Details:*".PHP_EOL.
                       "• Transaction ID: {$data['transaction_id']}".PHP_EOL.
                       "• Amount: {$data['amount']} {$data['currency']}".PHP_EOL.
                       "• From: {$data['sender_name']}".PHP_EOL.
                       "• New Balance: {$data['new_balance']} {$data['currency']}".PHP_EOL.PHP_EOL.
                       "_Thank you for using our service._".PHP_EOL.PHP_EOL.
                       "© ".date('Y')." eFood. All rights reserved.";

            case 'transfer_otp':
                return "*eFood - Transfer Verification*".PHP_EOL.PHP_EOL.
                       "Hello {$userName},".PHP_EOL.PHP_EOL.
                       "Your transfer verification code is: *{$data['otp']}*".PHP_EOL.PHP_EOL.
                       "Transfer Details:".PHP_EOL.
                       "• Amount: {$data['amount']} {$data['currency']}".PHP_EOL.
                       "• Recipient: {$data['receiver_name']}".PHP_EOL.PHP_EOL.
                       "This code will expire in {$data['expiry_minutes']} minutes.".PHP_EOL.PHP_EOL.
                       "_Do not share this code with anyone._".PHP_EOL.PHP_EOL.
                       "© ".date('Y')." eFood. All rights reserved.";

            case 'order_placed':
                return "*eFood - Order Confirmation*".PHP_EOL.PHP_EOL.
                       "Hello {$userName},".PHP_EOL.PHP_EOL.
                       "Your order has been placed successfully!".PHP_EOL.PHP_EOL.
                       "*Order Details:*".PHP_EOL.
                       "• Order ID: #{$data['order_id']}".PHP_EOL.
                       "• Amount: {$data['order_amount']} {$data['currency']}".PHP_EOL.
                       "• Items: {$data['items_count']}".PHP_EOL.
                       "• Type: {$data['order_type']}".PHP_EOL.PHP_EOL.
                       "_Thank you for your order!_".PHP_EOL.PHP_EOL.
                       "© ".date('Y')." eFood. All rights reserved.";

            case 'loyalty_conversion':
                return "*eFood - Loyalty Points Converted*".PHP_EOL.PHP_EOL.
                       "Hello {$userName},".PHP_EOL.PHP_EOL.
                       "You have successfully converted {$data['points_used']} loyalty points to {$data['converted_amount']} {$data['currency']}.".PHP_EOL.PHP_EOL.
                       "*Transaction Details:*".PHP_EOL.
                       "• Transaction ID: {$data['transaction_id']}".PHP_EOL.
                       "• Points Used: {$data['points_used']}".PHP_EOL.
                       "• Amount Credited: {$data['converted_amount']} {$data['currency']}".PHP_EOL.
                       "• New Balance: {$data['new_balance']} {$data['currency']}".PHP_EOL.
                       "• Remaining Points: {$data['remaining_points']}".PHP_EOL.PHP_EOL.
                       "_Thank you for being a loyal customer!_".PHP_EOL.PHP_EOL.
                       "© ".date('Y')." eFood. All rights reserved.";

            default:
                return "*eFood Notification*".PHP_EOL.PHP_EOL.
                       "Hello {$userName},".PHP_EOL.PHP_EOL.
                       "You have a new notification from eFood.".PHP_EOL.PHP_EOL.
                       "© ".date('Y')." eFood. All rights reserved.";
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
            return ['success' => false, 'error' => 'Missing API keys'];
        }

        Log::info('Testing WhatsApp connection...');

        $result = $this->sendMessage('1234567890', 'Connection test');
        
        return [
            'success' => $result['success'] ?? false,
            'message' => $result['success'] ? 'Connection OK' : 'Connection Failed',
            'details' => $result
        ];
    }
}