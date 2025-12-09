<?php

namespace App\Services;

use GuzzleHttp\Client;
use App\Models\WhatsAppTemplate;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    private Client $client;
    private string $baseUrl = 'https://wa.snefru.cloud/api/create-message';
    private int $maxRetries = 2;
    private int $maxMessageLength = 3500;

    public function __construct()
    {
        $this->client = new Client([
            'timeout' => env('WHATSAPP_TIMEOUT', 30),
            'connect_timeout' => 10,
            'verify' => true,
            'http_errors' => false,
        ]);
    }

    /**
     * E.164 Format Validation (Egypt Only)
     */
    private function formatPhoneNumber(string $phone): string
    {
        $original = $phone;
        
        // Remove all non-digit characters
        $phone = preg_replace('/\D+/', '', $phone);
        
        // Handle Egyptian local format (01xxxxxxxxx → 201xxxxxxxxx)
        if (str_starts_with($phone, '0') && strlen($phone) === 11) {
            $phone = '2' . substr($phone, 1); // 01xxxxxxxxx → 21xxxxxxxxx (remove leading 0, add 2)
        }
        
        // Remove 00 prefix if present
        if (str_starts_with($phone, '00')) {
            $phone = substr($phone, 2);
        }
        
        // Ensure it starts with country code
        if (!str_starts_with($phone, '2')) {
            $phone = '2' . $phone;
        }
        
        // Add + prefix for E.164 format (WhatsApp standard)
        if (!str_starts_with($phone, '+')) {
            $phone = '+' . $phone;
        }
        
        // Validate length: E.164 allows +[1-15 digits]
        $digitCount = strlen(preg_replace('/\D+/', '', $phone));
        if ($digitCount < 10 || $digitCount > 15) {
            throw new \InvalidArgumentException("Invalid phone: {$original} → {$phone}");
        }
        
        Log::info('WhatsApp: Phone formatted', [
            'original' => $original,
            'formatted' => $phone
        ]);
        
        return $phone;
    }

    /**
     * Main Sender
     */
    public function sendMessage(string $to, string $message, ?string $fileUrl = null): array
    {
        $appKey  = env('WHATSAPP_APP_KEY');
        $authKey = env('WHATSAPP_AUTH_KEY');

        if (!$appKey || !$authKey) {
            return ['success' => false, 'error' => 'Missing WhatsApp API credentials'];
        }

        if (mb_strlen($message) > $this->maxMessageLength) {
            return ['success' => false, 'error' => 'Message exceeds safe length limit'];
        }

        // Format number
        $original = $to;
        $to = $this->formatPhoneNumber($to);

        Log::info('WhatsApp: Phone formatted', compact('original', 'to'));

        $payload = [
            'appkey'  => $appKey,
            'authkey' => $authKey,
            'to'      => $to,
            'message' => $message,
            'sandbox' => filter_var(env('WHATSAPP_SANDBOX', false), FILTER_VALIDATE_BOOLEAN),
        ];

        if ($fileUrl && filter_var($fileUrl, FILTER_VALIDATE_URL)) {
            $payload['file'] = $fileUrl;
        }

        $attempt = 0;

        while ($attempt < $this->maxRetries) {
            $attempt++;

            try {
                Log::info("WhatsApp: Sending Attempt {$attempt}", [
                    'to' => $to,
                    'length' => mb_strlen($message),
                    'file' => $payload['file'] ?? null
                ]);

                $response = $this->client->post($this->baseUrl, [
                    'json' => $payload
                ]);

                $status = $response->getStatusCode();
                $body = trim((string) $response->getBody());
                $result = json_decode($body, true);

                Log::info('WhatsApp: API Response', compact('status', 'body'));

                // ✅ ACCEPTED / QUEUED
                if ($status === 200 && empty($result['error'])) {
                    return [
                        'success'  => true,
                        'queued'   => true,
                        'attempts' => $attempt,
                        'response' => $result
                    ];
                }

                // ⚠ UNKNOWN DELIVERY
                if ($status === 500) {
                    return [
                        'success' => false,
                        'pending' => true,
                        'error'   => 'Provider failed after submission. Delivery unknown.',
                        'raw'     => $result
                    ];
                }

                // 🔴 AUTH ERROR
                if ($status === 401 || str_contains(strtolower($body), 'session')) {
                    return [
                        'success' => false,
                        'error'   => 'WhatsApp session invalid. Reconnect required.',
                        'code'    => 401
                    ];
                }

                // ⚠ RETRY ON 5XX
                if ($status >= 500 && $attempt < $this->maxRetries) {
                    sleep(2);
                    continue;
                }

                return [
                    'success' => false,
                    'error'   => $result['error'] ?? 'Unexpected API error',
                    'code'    => $status
                ];

            } catch (\Throwable $e) {
                Log::error('WhatsApp Exception', [
                    'attempt' => $attempt,
                    'error' => $e->getMessage()
                ]);

                if ($attempt < $this->maxRetries) {
                    sleep(2);
                    continue;
                }

                return [
                    'success' => false,
                    'error' => 'Connection failure: ' . $e->getMessage()
                ];
            }
        }

        return ['success' => false, 'error' => 'Max retry reached'];
    }

    /**
     * Template Generator
     */
    public function sendTemplateMessage(string $type, array $data = []): string
    {
        $template = WhatsAppTemplate::where([
            'whatsapp_type' => $type,
            'type' => 'user'
        ])->first();

        if (!$template) {
            return $this->getFallbackMessage($type, $data);
        }

        $message = "*{$template->title}*\n\n";
        $body = $template->body;

        foreach ($data as $k => $v) {
            $body = str_replace(["{{$k}}", "{{{$k}}}", "{".$k."}"], $v, $body);
        }

        $message .= $body . "\n\n";

        if ($template->button_name && $template->button_url) {
            $btn = $template->button_name;
            foreach ($data as $k => $v) {
                $btn = str_replace(["{{$k}}"], $v, $btn);
            }
            $message .= "👉 {$btn}: {$template->button_url}\n\n";
        }

        if ($template->footer_text) {
            $footer = $template->footer_text;
            foreach ($data as $k => $v) {
                $footer = str_replace(["{{$k}}"], $v, $footer);
            }
            $message .= "_{$footer}_\n\n";
        }

        return trim($message);
    }

    /**
     * Fallback Message
     */
    private function getFallbackMessage(string $type, array $data): string
    {
        $name = $data['user_name'] ?? 'Customer';
        $app  = env('APP_NAME', 'Application');

        return "*{$app} Notification*\n\nHello {$name},\n\nYou received a new notification.";
    }
}
