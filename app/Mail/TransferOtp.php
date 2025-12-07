<?php

namespace App\Mail;

use App\CentralLogics\Helpers;
use App\Model\BusinessSetting;
use App\Models\EmailTemplate;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class TransferOtp extends Mailable
{
    use Queueable, SerializesModels;

    protected $otp;
    protected $language_code;

    public function __construct($otp, $language_code = 'en')
    {
        $this->otp = $otp;
        $this->language_code = $language_code;
    }

    public function build()
    {
        $code = $this->otp;
        
        // Get email template with null check
        $data = EmailTemplate::with('translations')
            ->where('type', 'user')
            ->where('email_type', 'transfer_otp')
            ->first();
        
        $local = $this->language_code ?? 'en';

        // Provide default content if template doesn't exist
        $content = [
            'title' => $data->title ?? 'Money Transfer Verification',
            'body' => $data->body ?? 'Hello,<br><br>Your money transfer verification code is: <strong>{code}</strong><br><br>This code will expire in 5 minutes.<br><br>Do not share this code with anyone.',
            'footer_text' => $data->footer_text ?? 'If you did not initiate this transfer, please contact support.',
            'copyright_text' => $data->copyright_text ?? 'Copyright © ' . date('Y') . ' eFood. All rights reserved.'
        ];

        // Apply translations if template exists
        if ($data && $local != 'en' && isset($data->translations)) {
            foreach ($data->translations as $translation) {
                if ($local == $translation->locale) {
                    $content[$translation->key] = $translation->value;
                }
            }
        }

        $template = $data ? $data->email_template : 4;
        $company_name = BusinessSetting::where('key', 'restaurant_name')->first()->value ?? config('app.name');
        
        // Replace variables
        $title = Helpers::text_variable_data_format(value: $content['title'], code: $code);
        $body = Helpers::text_variable_data_format(value: $content['body'], code: $code);
        $footer_text = Helpers::text_variable_data_format(value: $content['footer_text'], code: $code);
        $copyright_text = Helpers::text_variable_data_format(value: $content['copyright_text'], code: $code);
        
        return $this->subject(translate('Transfer OTP Code'))
            ->view('email-templates.new-email-format-' . $template, [
                'company_name' => $company_name,
                'data' => $data,
                'title' => $title,
                'body' => $body,
                'footer_text' => $footer_text,
                'copyright_text' => $copyright_text,
                'url' => '',
                'code' => $code
            ]);
    }
}