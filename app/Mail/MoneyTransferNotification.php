<?php

namespace App\Mail;

use App\CentralLogics\Helpers;
use App\Model\BusinessSetting;
use App\Models\EmailTemplate;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class MoneyTransferNotification extends Mailable
{
    use Queueable, SerializesModels;

    protected $notificationData;
    protected $language_code;
    protected $notificationType;

    public function __construct($notificationData, $language_code = 'en', $notificationType = 'received')
    {
        $this->notificationData = $notificationData;
        $this->language_code = $language_code;
        $this->notificationType = $notificationType;
    }

    public function build()
    {
        // Fetch email template from database
        $data = EmailTemplate::with('translations')
            ->where('type', 'user')
            ->where('email_type', 'money_transfer')
            ->first();
            
        $local = $this->language_code ?? 'en';

        // Initialize content array with defaults
        $content = [
            'title' => $data->title ?? 'Money Transfer Notification',
            'body' => $data->body ?? 'Hello {receiver_name},<br><br>You have received a money transfer of {amount} {currency} from {sender_name}.',
            'footer_text' => $data->footer_text ?? 'Thank you for choosing us',
            'copyright_text' => $data->copyright_text ?? '© {year} All rights reserved',
        ];

        // Apply translations if available
        if ($local != 'en' && isset($data->translations)) {
            foreach ($data->translations as $translation) {
                if ($local == $translation->locale) {
                    $content[$translation->key] = $translation->value;
                }
            }
        }

        // Prepare variables for replacement based on notification type
        if ($this->notificationType === 'sent') {
            $variables = [
                'sender_name' => $this->notificationData['user_name'] ?? 'You',
                'receiver_name' => $this->notificationData['recipient_name'] ?? 'Recipient',
                'amount' => number_format($this->notificationData['amount'] ?? 0, 2),
                'currency' => $this->notificationData['currency'] ?? 'SAR',
                'transaction_id' => $this->notificationData['transaction_id'] ?? 'N/A',
                'note' => $this->notificationData['note'] ?? 'No note provided',
                'balance' => number_format($this->notificationData['new_balance'] ?? 0, 2),
                'year' => date('Y'),
            ];
            // Adjust subject for sender
            $subject = translate('Money_Transfer_Sent');
        } else {
            $variables = [
                'sender_name' => $this->notificationData['sender_name'] ?? 'Sender',
                'receiver_name' => $this->notificationData['user_name'] ?? 'You',
                'amount' => number_format($this->notificationData['amount'] ?? 0, 2),
                'currency' => $this->notificationData['currency'] ?? 'SAR',
                'transaction_id' => $this->notificationData['transaction_id'] ?? 'N/A',
                'note' => $this->notificationData['note'] ?? 'No note provided',
                'balance' => number_format($this->notificationData['new_balance'] ?? 0, 2),
                'year' => date('Y'),
            ];
            $subject = translate('Money_Transfer_Notification');
        }

        foreach ($content as $key => $text) {
            $content[$key] = $this->replaceVariables($text, $variables);
        }

        $template = $data ? $data->email_template : 4;
        $url = '';
        $company_name = BusinessSetting::where('key', 'restaurant_name')->first()->value ?? config('app.name');

        return $this->subject($subject)
            ->view('email-templates.new-email-format-' . $template, [
                'company_name' => $company_name,
                'data' => $data,
                'title' => $content['title'],
                'body' => $content['body'],
                'footer_text' => $content['footer_text'],
                'copyright_text' => $content['copyright_text'],
                'url' => $url,
                'code' => null,
            ]);
    }

    private function replaceVariables($text, $variables)
    {
        foreach ($variables as $key => $value) {
            $text = str_replace('{' . $key . '}', $value, $text);
        }
        return $text;
    }
}