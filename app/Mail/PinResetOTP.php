<?php

namespace App\Mail;

use App\CentralLogics\Helpers;
use App\Model\BusinessSetting;
use App\Models\EmailTemplate;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PinResetOTP extends Mailable
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
        $otp = $this->data['otp'];
        $user_name = $this->data['user_name'];
        $expiry_minutes = $this->data['expiry_minutes'];
        $timestamp = $this->data['timestamp'];

        // Get email template with null check
        $emailTemplate = EmailTemplate::with('translations')
            ->where('type', 'user')
            ->where('email_type', 'pin_reset_otp')
            ->first();

        $local = $this->language_code ?? 'en';

        // Provide default content if template doesn't exist
        $content = [
            'title' => $emailTemplate->title ?? 'Wallet PIN Reset - OTP Verification',
            'body' => $emailTemplate->body ?? 'Hello {user_name},<br><br>You have requested to reset your wallet PIN.<br><br>Your verification code is: <strong style="font-size:20px;color:#00AA6D;">{otp}</strong><br><br>This code will expire in {expiry_minutes} minutes.<br><br>If you did not request this PIN reset, please ignore this email.',
            'footer_text' => $emailTemplate->footer_text ?? 'For security reasons, never share your verification code with anyone.',
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
            code: $otp
        );
        
        $footer_text = Helpers::text_variable_data_format(
            value: $content['footer_text']
        );
        
        $copyright_text = Helpers::text_variable_data_format(
            value: $content['copyright_text']
        );

        return $this->subject(translate('Wallet PIN Reset - OTP Verification'))
            ->view('email-templates.new-email-format-' . $template, [
                'company_name' => $company_name,
                'data' => $emailTemplate,
                'title' => $title,
                'body' => $body,
                'footer_text' => $footer_text,
                'copyright_text' => $copyright_text,
                'url' => '',
                'code' => $otp
            ]);
    }
}