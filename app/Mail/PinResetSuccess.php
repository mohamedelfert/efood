<?php

namespace App\Mail;

use App\CentralLogics\Helpers;
use App\Model\BusinessSetting;
use App\Models\EmailTemplate;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PinResetSuccess extends Mailable
{
    use Queueable, SerializesModels;

    protected $data;
    protected $language_code;

    public function __construct(array $data, string $language_code = 'en')
    {
        $this->data = $data;
        $this->language_code = $language_code;
    }

    public function build()
    {
        $user_name = $this->data['user_name'];
        $timestamp = $this->data['timestamp'];
        $date = $this->data['date'];
        $time = $this->data['time'];

        // Get email template with null check
        $emailTemplate = EmailTemplate::with('translations')
            ->where('type', 'user')
            ->where('email_type', 'pin_reset_success')
            ->first();

        $local = $this->language_code ?? 'en';

        // Provide default content if template doesn't exist
        $content = [
            'title' => $emailTemplate->title ?? 'Wallet PIN Reset Successful',
            'body' => $emailTemplate->body ?? 'Hello {user_name},<br><br>Your wallet PIN has been successfully reset!<br><br><strong>Reset Details:</strong><br>• Date: {date} at {time}<br><br>Your new wallet PIN is now active and you can use it for all wallet transactions.<br><br><strong>Security Tips:</strong><br>• Keep your PIN confidential<br>• Use a unique PIN<br>• Change it regularly<br>• Don\'t use easily guessable PINs',
            'footer_text' => $emailTemplate->footer_text ?? 'If you didn\'t authorize this change, please contact our support team immediately.',
            'copyright_text' => $emailTemplate->copyright_text ?? 'Copyright © ' . date('Y') . ' eFood. All rights reserved.'
        ];

        // Apply translations if template exists
        if ($emailTemplate && $local != 'en' && isset($emailTemplate->translations)) {
            foreach ($emailTemplate->translations as $translation) {
                if ($local == $translation->locale) {
                    $content[$translation->key] = $translation->value;
                }
            }
        }

        $template = $emailTemplate ? $emailTemplate->email_template : 4;
        $company_name = BusinessSetting::where('key', 'restaurant_name')->first()->value ?? config('app.name');

        // Replace variables
        $title = Helpers::text_variable_data_format(
            value: $content['title'], 
            user_name: $user_name
        );
        
        $body = Helpers::text_variable_data_format(
            value: $content['body'], 
            user_name: $user_name,
            code: $date
        );
        
        $footer_text = Helpers::text_variable_data_format(
            value: $content['footer_text']
        );
        
        $copyright_text = Helpers::text_variable_data_format(
            value: $content['copyright_text']
        );

        return $this->subject(translate('Wallet PIN Reset Successful'))
            ->view('email-templates.new-email-format-' . $template, [
                'company_name' => $company_name,
                'data' => $emailTemplate,
                'title' => $title,
                'body' => $body,
                'footer_text' => $footer_text,
                'copyright_text' => $copyright_text,
                'url' => ''
            ]);
    }
}