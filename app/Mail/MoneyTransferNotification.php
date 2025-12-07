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
        // Ensure notificationData has required fields
        $data = $this->notificationData;
        
        // Add defaults if missing
        if (!isset($data['transaction_id'])) {
            $data['transaction_id'] = 'N/A';
        }
        if (!isset($data['amount'])) {
            $data['amount'] = 0;
        }
        if (!isset($data['currency'])) {
            $data['currency'] = 'SAR';
        }
        if (!isset($data['user_name'])) {
            $data['user_name'] = 'User';
        }
        if (!isset($data['sender_name'])) {
            $data['sender_name'] = 'Sender';
        }
        if (!isset($data['recipient_name'])) {
            $data['recipient_name'] = 'Recipient';
        }

        // Fetch email template from database
        $emailTemplate = EmailTemplate::with('translations')
            ->where('type', 'user')
            ->where('email_type', 'money_transfer')
            ->first();
            
        $local = $this->language_code ?? 'en';

        // Initialize content array with defaults
        $content = [
            'title' => $emailTemplate->title ?? 'Money Transfer Notification',
            'body' => $emailTemplate->body ?? 'Hello {receiver_name},<br><br>You have received a money transfer of {amount} {currency} from {sender_name}.',
            'footer_text' => $emailTemplate->footer_text ?? 'Thank you for choosing us',
            'copyright_text' => $emailTemplate->copyright_text ?? '© {year} All rights reserved',
        ];

        // Apply translations if available
        if ($local != 'en' && isset($emailTemplate->translations)) {
            foreach ($emailTemplate->translations as $translation) {
                if ($local == $translation->locale) {
                    $content[$translation->key] = $translation->value;
                }
            }
        }

        // Prepare variables based on notification type
        if ($this->notificationType === 'sent') {
            $variables = [
                'sender_name' => $data['user_name'] ?? 'You',
                'receiver_name' => $data['recipient_name'] ?? 'Recipient',
                'amount' => number_format($data['amount'] ?? 0, 2),
                'currency' => $data['currency'] ?? 'SAR',
                'transaction_id' => $data['transaction_id'] ?? 'N/A',
                'note' => $data['note'] ?? 'No note provided',
                'balance' => number_format($data['new_balance'] ?? 0, 2),
                'year' => date('Y'),
            ];
            $subject = translate('Money Transfer Sent');
        } else {
            $variables = [
                'sender_name' => $data['sender_name'] ?? 'Sender',
                'receiver_name' => $data['user_name'] ?? 'You',
                'amount' => number_format($data['amount'] ?? 0, 2),
                'currency' => $data['currency'] ?? 'SAR',
                'transaction_id' => $data['transaction_id'] ?? 'N/A',
                'note' => $data['note'] ?? 'No note provided',
                'balance' => number_format($data['new_balance'] ?? 0, 2),
                'year' => date('Y'),
            ];
            $subject = translate('Money Transfer Notification');
        }

        // Replace variables in content
        foreach ($content as $key => $text) {
            foreach ($variables as $varKey => $value) {
                $text = str_replace('{' . $varKey . '}', $value, $text);
            }
            $content[$key] = $text;
        }

        $template = $emailTemplate ? $emailTemplate->email_template : 4;
        $company_name = BusinessSetting::where('key', 'restaurant_name')->first()->value ?? config('app.name');

        return $this->subject($subject)
            ->view('email-templates.new-email-format-' . $template, [
                'company_name' => $company_name,
                'data' => $emailTemplate,
                'title' => $content['title'],
                'body' => $content['body'],
                'footer_text' => $content['footer_text'],
                'copyright_text' => $content['copyright_text'],
                'url' => '',
                'code' => null,
            ]);
    }
}