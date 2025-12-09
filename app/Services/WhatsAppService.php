<?php

namespace App\Services;

use GuzzleHttp\Client;
use App\Models\WhatsAppTemplate;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    private Client $client;
    private string $baseUrl = 'https://wa.snefru.cloud/api/create-message';
    private int $maxRetries = 3;
    private int $maxMessageLength = 3500;

    public function __construct()
    {
        $this->client = new Client([
            'timeout'         => env('WHATSAPP_TIMEOUT', 30),
            'connect_timeout' => 10,
            'verify'          => env('WHATSAPP_VERIFY_SSL', true),
            'http_errors'     => false,
        ]);
    }

    /**
     * Normalize phone number to E.164 format (without +)
     * Supports Egypt, Yemen, and any country
     */
    private function formatPhoneNumber(string $phone): string
    {
        // Store original for logging
        $original = $phone;

        // Remove all non-digit characters
        $phone = preg_replace('/\D+/', '', $phone);

        // Handle common Egyptian local format (01xxxxxxxxx → 201xxxxxxxxx)
        if (str_starts_with($phone, '0') && strlen($phone) === 11) {
            $phone = '2' . $phone; // 01xxxxxxxxx → 201xxxxxxxxx
        }

        // If number starts with 00 instead of +, replace with nothing (00 201... → 201...)
        if (str_starts_with($phone, '00')) {
            $phone = substr($phone, 2);
        }

        // Final validation: 10–15 digits (standard E.164 without +)
        if (strlen($phone) < 10 || strlen($phone) > 15) {
            throw new \InvalidArgumentException("Invalid phone number length after formatting: {$original} → {$phone}");
        }

        Log::info('WhatsApp: Phone formatted successfully', [
            'original' => $original,
            'formatted' => $phone
        ]);

        return $phone;
    }

    /**
     * Send WhatsApp message (text + optional file/receipt)
     */
    public function sendMessage(string $to, string $message, ?string $fileUrl = null): array
    {
        $appKey  = env('WHATSAPP_APP_KEY');
        $authKey = env('WHATSAPP_AUTH_KEY');

        if (!$appKey || !$authKey) {
            return ['success' => false, 'error' => 'Missing WhatsApp API credentials (APP_KEY/AUTH_KEY)'];
        }

        if (mb_strlen($message) > $this->maxMessageLength) {
            return ['success' => false, 'error' => 'Message too long (>3500 chars)'];
        }

        try {
            $originalTo = $to;
            $to = $this->formatPhoneNumber($to);
        } catch (\Throwable $e) {
            Log::error('WhatsApp: Phone formatting failed', [
                'input' => $originalTo ?? $to,
                'error' => $e->getMessage()
            ]);
            return ['success' => false, 'error' => $e->getMessage()];
        }

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
                Log::info("WhatsApp: Sending message (Attempt {$attempt})", [
                    'to'     => $to,
                    'length' => mb_strlen($message),
                    'has_file' => !empty($fileUrl),
                ]);

                $response = $this->client->post($this->baseUrl, [
                    'json' => $payload
                ]);

                $status = $response->getStatusCode();
                $body   = trim($response->getBody()->getContents());
                $result = json_decode($body, true) ?? ['raw' => $body];

                Log::info('WhatsApp: API Response', [
                    'status' => $status,
                    'body'   => $result
                ]);

                // Success
                if ($status === 200 && (empty($result['error']) || isset($result['status']) && $result['status'] === 'queued')) {
                    return [
                        'success'  => true,
                        'queued'   => true,
                        'attempts' => $attempt,
                        'response' => $result
                    ];
                }

                // Provider internal error – we don’t know if delivered
                if ($status >= 500) {
                    if ($attempt < $this->maxRetries) {
                        sleep(3);
                        continue;
                    }
                    return [
                        'success' => false,
                        'pending' => true,
                        'error'   => 'Provider error. Message may have been sent.',
                        'raw'     => $result
                    ];
                }

                // Auth failed
                if ($status === 401 || str_contains(strtolower($body), 'session')) {
                    return [
                        'success' => false,
                        'error'   => 'WhatsApp session expired or invalid credentials. Reconnect required.',
                        'code'    => 401
                    ];
                }

                // Other known errors
                return [
                    'success' => false,
                    'error'   => $result['error'] ?? 'Failed to send message',
                    'code'    => $status,
                    'raw'     => $result
                ];

            } catch (\Throwable $e) {
                Log::error('WhatsApp: Request failed', [
                    'attempt' => $attempt,
                    'error'   => $e->getMessage()
                ]);

                if ($attempt < $this->maxRetries) {
                    sleep(3);
                    continue;
                }

                return [
                    'success' => false,
                    'error'   => 'Connection failed: ' . $e->getMessage()
                ];
            }
        }

        return ['success' => false, 'error' => 'Max retries reached'];
    }

    // Template & Fallback methods remain unchanged
    public function sendTemplateMessage(string $type, array $data = []): string
    {
        $template = WhatsAppTemplate::where([
            'whatsapp_type' => $type,
            'type'          => 'user'
        ])->first();

        if (!$template) {
            return $this->getFallbackMessage($type, $data);
        }

        $message = "*{$template->title}*\n\n";
        $body = $template->body;

        foreach ($data as $k => $v) {
            $body = str_replace(["{{$k}}", "{{{$k}}}", "{{ $k }}", "{".$k."}"], $v, $body);
        }

        $message .= $body . "\n\n";

        if ($template->button_name && $template->button_url) {
            $btn = $template->button_name;
            foreach ($data as $k => $v) {
                $btn = str_replace("{{$k}}", $v, $btn);
            }
            $message .= " {$btn}: {$template->button_url}\n\n";
        }

        if ($template->footer_text) {
            $footer = str_replace("{{$k}}", $v, $template->footer_text);
            foreach ($data as $k => $v) {
                $footer = str_replace("{{$k}}", $v, $footer);
            }
            $message .= "_{$footer}_\n\n";
        }

        return trim($message);
    }

    private function getFallbackMessage(string $type, array $data): string
    {
        $name = $data['user_name'] ?? 'Customer';
        $app  = env('APP_NAME', 'Efood');

        return "*{$app}*\n\nمرحبًا {$name}،\n\nتم استلام إشعار جديد.";
    }
}