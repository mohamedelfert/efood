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

    private function formatPhoneNumber(string $phone): string
    {
        $original = $phone;
        $phone = preg_replace('/\D+/', '', $phone);

        // Egyptian local format: 01xxxxxxxxx → 201xxxxxxxxx
        if (str_starts_with($phone, '0') && strlen($phone) === 11) {
            $phone = '2' . $phone;
        }

        // International prefix 00 → remove it
        if (str_starts_with($phone, '00')) {
            $phone = substr($phone, 2);
        }

        if (strlen($phone) < 10 || strlen($phone) > 15) {
            throw new \InvalidArgumentException("Invalid phone number: {$original} → {$phone}");
        }

        Log::info('WhatsApp: Phone formatted', ['original' => $original, 'formatted' => $phone]);

        return $phone;
    }

    public function sendMessage(string $to, string $message, ?string $fileUrl = null): array
    {
        $appKey  = env('WHATSAPP_APP_KEY');
        $authKey = env('WHATSAPP_AUTH_KEY');

        if (!$appKey || !$authKey) {
            return ['success' => false, 'error' => 'Missing WHATSAPP_APP_KEY or WHATSAPP_AUTH_KEY'];
        }

        if (mb_strlen($message) > $this->maxMessageLength) {
            return ['success' => false, 'error' => 'Message too long (>3500 chars)'];
        }

        try {
            $to = $this->formatPhoneNumber($to);
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }

        $attempt = 0;

        while ($attempt < $this->maxRetries) {
            $attempt++;

            // Build payload differently if we have a file
            $options = ['multipart' => []];

            if ($fileUrl && filter_var($fileUrl, FILTER_VALIDATE_URL)) {
                $options['multipart'] = [
                    ['name' => 'appkey',  'contents' => $appKey],
                    ['name' => 'authkey', 'contents' => $authKey],
                    ['name' => 'to',      'contents' => $to],
                    ['name' => 'message', 'contents' => $message],
                    ['name' => 'file',    'contents' => fopen($fileUrl, 'r'), 'filename' => basename($fileUrl)],
                    ['name' => 'sandbox', 'contents' => env('WHATSAPP_SANDBOX', false) ? 'true' : 'false'],
                ];
            } else {
                $options = [
                    'json' => [
                        'appkey'  => $appKey,
                        'authkey' => $authKey,
                        'to'      => $to,
                        'message' => $message,
                        'sandbox' => filter_var(env('WHATSAPP_SANDBOX', false), FILTER_VALIDATE_BOOLEAN),
                    ]
                ];
            }

            try {
                Log::info("WhatsApp: Sending message (Attempt {$attempt})", [
                    'to' => $to,
                    'has_file' => !empty($fileUrl),
                ]);

                $response = $this->client->post($this->baseUrl, $options);

                $status = $response->getStatusCode();
                $body   = trim($response->getBody()->getContents());
                $result = json_decode($body, true) ?? ['raw' => $body];

                Log::info('WhatsApp: API Response', ['status' => $status, 'body' => $result]);

                // Success responses from Snefru
                if ($status === 200) {
                    if (isset($result['message_status']) && $result['message_status'] === 'Success') {
                        return ['success' => true, 'response' => $result];
                    }
                    if (isset($result['status']) && $result['status'] === 'queued') {
                        return ['success' => true, 'response' => $result];
                    }
                    if (empty($result['error'])) {
                        return ['success' => true, 'response' => $result];
                    }
                }

                // 401 = Session expired → most important case
                if ($status === 401) {
                    return [
                        'success' => false,
                        'error'  => 'WhatsApp session expired! Go to https://wa.snefru.cloud and scan QR code again.',
                        'code'    => 401,
                    ];
                }

                // Retry on server errors
                if ($status >= 500 && $attempt < $this->maxRetries) {
                    sleep(3);
                    continue;
                }

                return [
                    'success' => false,
                    'error'  => $result['error'] ?? 'Failed to send message',
                    'code'    => $status,
                    'raw'     => $result,
                ];

            } catch (\Throwable $e) {
                Log::error('WhatsApp: Request exception', [
                    'attempt' => $attempt,
                    'error'   => $e->getMessage()
                ]);

                if ($attempt < $this->maxRetries) {
                    sleep(3);
                    continue;
                }

                return ['success' => false, 'error' => 'Connection failed: ' . $e->getMessage()];
            }
        }

        return ['success' => false, 'error' => 'Max retries reached'];
    }

    // Rest of the class (template methods) unchanged
    public function sendTemplateMessage(string $type, array $data = []): string
    {
        $template = WhatsAppTemplate::where([
            'whatsapp_type' => $type,
            'type'          => 'user'
        ])->firstOrFail();

        $message = "*{$template->title}*\n\n";
        $body = $template->body;

        foreach ($data as $key => $value) {
            $body = str_replace(["{{$key}}", "{{$key}}", "{".$key."}"], $value, $body);
        }

        $message .= $body . "\n\n";

        if ($template->button_name && $template->button_url) {
            $btn = str_replace(array_keys($data), array_values($data), $template->button_name);
            $message .= "$btn: {$template->button_url}\n\n";
        }

        if ($template->footer_text) {
            $footer = str_replace(array_keys($data), array_values($data), $template->footer_text);
            $message .= "_{$footer}_\n\n";
        }

        return trim($message);
    }

    private function getFallbackMessage(string $type, array $data): string
    {
        $name = $data['user_name'] ?? 'العميل';
        return "*Efood*\n\nمرحباً {$name}،\n\nتم إضافة رصيد جديد لحسابك بنجاح!";
    }
}