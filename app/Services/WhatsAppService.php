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

    public function __construct()
    {
        $this->client = new Client();
    }

    /**
     * @param string $to
     * @param string $message
     * @param string|null $fileUrl Public URL for attachment (string)
     * @return array
     */
    public function sendMessage(string $to, string $message, ?string $fileUrl = null): array
    {
        $appKey = env('WHATSAPP_APP_KEY');
        $authKey = env('WHATSAPP_AUTH_KEY');

        if (!$appKey || !$authKey) {
            Log::error('WhatsApp keys missing');
            return ['success' => false, 'error' => 'Missing API keys'];
        }

        $data = [
            'appkey' => $appKey,
            'authkey' => $authKey,
            'to' => $to,
            'message' => $message,
            'sandbox' => 'true',
        ];

        if ($fileUrl) {
            $data['file'] = $fileUrl;  // As URL string
        }

        try {
            $response = $this->client->post($this->baseUrl, [
                'form_params' => $data,
            ]);

            $body = $response->getBody()->getContents();
            $result = json_decode($body, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::error('WhatsApp JSON decode failed', ['body' => $body]);
                return ['success' => false, 'error' => 'Invalid API response format'];
            }

            if ((isset($result['success']) && $result['success'] === true) || (isset($result['message_status']) && $result['message_status'] === 'Success')) {
                Log::info('WhatsApp sent successfully', ['to' => $to, 'response' => $result]);
                return $result ?: ['success' => true];
            } else {
                $errorMsg = $result['message'] ?? ($result['data']['file'][0] ?? null) ?? 'Unknown API error';
                Log::warning('WhatsApp API returned error', ['to' => $to, 'response' => $result, 'error' => $errorMsg]);
                return ['success' => false, 'error' => $errorMsg];
            }
        } catch (RequestException $e) {
            $error = $e->getMessage();
            Log::error('WhatsApp send failed', ['to' => $to, 'error' => $error]);
            return ['success' => false, 'error' => $error];
        }
    }

    public function sendTemplateMessage(string $whatsappType, array $data = []): string
    {
        $template = WhatsAppTemplate::where('whatsapp_type', $whatsappType)
            ->where('type', 'user')
            ->first();
        
        if (!$template) {
            Log::warning('WhatsApp template not found, using fallback', ['type' => $whatsappType]);
            $message = '*Wallet Top Up eFood*'.PHP_EOL.PHP_EOL.
                       'Congratulations! Your wallet has been topped up successfully.'.PHP_EOL.PHP_EOL.
                       '**Transaction Details:**'.PHP_EOL.
                       '- **Transaction Number:** {transaction_id}'.PHP_EOL.
                       '- **Date & Time:** {date}'.PHP_EOL.
                       '- **Amount Added:** {amount} {currency}'.PHP_EOL.
                       '- **Previous Balance:** {previous_balance} {currency}'.PHP_EOL.
                       '- **New Balance:** {new_balance} {currency}'.PHP_EOL.PHP_EOL.
                       '_Thank You For Using Our Service_'.PHP_EOL.PHP_EOL.
                       'Privacy Policy | Refund Policy | Cancelation Policy | Contact Us'.PHP_EOL.PHP_EOL.
                       'Copyright 2025. All rights reserved for eFood.';
        } else {
            // Build the message from template
            $message = "*{$template->title}*".PHP_EOL.PHP_EOL;
            
            $body = $template->body;
            foreach ($data as $key => $value) {
                $body = str_replace("{{{$key}}}", $value, $body);
                $body = str_replace("{" . $key . "}", $value, $body); // Support both {{}} and {}
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
            
            // Add links if enabled
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
        }

        // Always append transaction details if data is provided
        if (!empty($data)) {
            $message .= PHP_EOL.PHP_EOL.'**Transaction Details:**'.PHP_EOL.
                       '- **Transaction Number:** '.$data['transaction_id'].PHP_EOL.
                       '- **Date & Time:** '.$data['date'].PHP_EOL.
                       '- **Amount Added:** '.$data['amount'].' '.$data['currency'].PHP_EOL.
                       '- **Previous Balance:** '.$data['previous_balance'].' '.$data['currency'].PHP_EOL.
                       '- **New Balance:** '.$data['new_balance'].' '.$data['currency'].PHP_EOL;
        }
        
        return $message;
    }
}