<?php

namespace App\Mail;

use App\CentralLogics\Helpers;
use App\Model\BusinessSetting;
use App\Models\EmailTemplate;
use Illuminate\Bus\Queueable;
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
        // Ensure notificationData is always an array
        $this->notificationData = is_array($notificationData) ? $notificationData : [];
        $this->language_code = $language_code;
        $this->notificationType = $notificationType;
    }

    public function build()
    {
        // Safely access data with defaults
        $data = $this->notificationData;
        
        // Add defaults if missing
        $defaults = [
            'transaction_id' => 'N/A',
            'amount' => '0.00',
            'currency' => 'SAR',
            'user_name' => 'User',
            'sender_name' => 'Sender',
            'recipient_name' => 'Recipient',
            'receiver_name' => 'Recipient',
            'balance' => '0.00',
            'new_balance' => '0.00',
            'note' => '',
            'type' => $this->notificationType,
        ];
        
        $data = array_merge($defaults, $data);

        // Fetch email template from database
        $emailTemplate = EmailTemplate::with('translations')
            ->where('type', 'user')
            ->where('email_type', 'money_transfer')
            ->first();
            
        $local = $this->language_code ?? 'en';

        // Initialize content with safe defaults
        $content = [
            'title' => $emailTemplate->title ?? 'Money Transfer Notification',
            'body' => $emailTemplate->body ?? 'Hello {receiver_name},<br><br>You have received a money transfer of {amount} {currency} from {sender_name}.<br><br>Transaction ID: {transaction_id}<br>Your Balance: {balance} {currency}',
            'footer_text' => $emailTemplate->footer_text ?? 'Thank you for choosing us',
            'copyright_text' => $emailTemplate->copyright_text ?? '© {year} All rights reserved',
        ];

        // Apply translations if available
        if ($emailTemplate && $local != 'en' && isset($emailTemplate->translations)) {
            foreach ($emailTemplate->translations as $translation) {
                if ($local == $translation->locale && isset($content[$translation->key])) {
                    $content[$translation->key] = $translation->value;
                }
            }
        }

        // Prepare variables for replacement
        $variables = [
            'sender_name' => $data['sender_name'],
            'receiver_name' => $data['receiver_name'] ?? $data['recipient_name'] ?? $data['user_name'],
            'amount' => $data['amount'],
            'currency' => $data['currency'],
            'transaction_id' => $data['transaction_id'],
            'note' => $data['note'],
            'balance' => $data['balance'] ?? $data['new_balance'],
            'year' => date('Y'),
        ];

        // Replace variables in content
        foreach ($content as $key => $text) {
            foreach ($variables as $varKey => $value) {
                $text = str_replace('{' . $varKey . '}', $value, $text);
            }
            $content[$key] = $text;
        }

        $template = $emailTemplate ? $emailTemplate->email_template : 4;
        $company_name = BusinessSetting::where('key', 'restaurant_name')->first()->value ?? config('app.name');

        // Determine subject based on type
        $subject = $this->notificationType === 'sent' 
            ? translate('Money Transfer Sent')
            : translate('Money Transfer Received');

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