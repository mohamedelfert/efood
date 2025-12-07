<?php

namespace App\Mail;

use App\CentralLogics\Helpers;
use App\Model\BusinessSetting;
use App\Models\EmailTemplate;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class EmailVerification extends Mailable
{
    use Queueable, SerializesModels;

    protected $token;
    protected $language_code;

    public function __construct($token = '', $language_code)
    {
        $this->token = $token;
        $this->language_code = $language_code;
    }

    public function build()
    {
        $code = $this->token;
        $local = $this->language_code ?? 'en';

        // Get email template safely with null check
        $data = EmailTemplate::with('translations')
            ->where('type', 'user')
            ->where('email_type', 'registration_otp')
            ->first();

        // If template doesn't exist, create default content
        if (!$data) {
            $company_name = BusinessSetting::where('key', 'restaurant_name')->first()->value ?? config('app.name');
            
            $content = [
                'title' => 'Email Verification Code',
                'body' => 'Your email verification code is: {code}. This code will expire in 5 minutes.',
                'footer_text' => 'Thank you for using our service.',
                'copyright_text' => 'Copyright © ' . date('Y') . ' ' . $company_name . '. All rights reserved.'
            ];
            
            $template = 4; // Default template ID
        } else {
            // Use template data with safe null checks
            $content = [
                'title' => $data->title ?? 'Email Verification Code',
                'body' => $data->body ?? 'Your email verification code is: {code}',
                'footer_text' => $data->footer_text ?? 'Thank you for using our service.',
                'copyright_text' => $data->copyright_text ?? 'Copyright © ' . date('Y') . ' ' . (BusinessSetting::where('key', 'restaurant_name')->first()->value ?? config('app.name')) . '. All rights reserved.'
            ];

            // Apply translations if available
            if ($local != 'en' && isset($data->translations)) {
                foreach ($data->translations as $translation) {
                    if ($local == $translation->locale) {
                        $content[$translation->key] = $translation->value;
                    }
                }
            }
            
            $template = $data->email_template ?? 4;
        }

        // Get company name (this should be after we might have fetched it above)
        $company_name = BusinessSetting::where('key', 'restaurant_name')->first()->value ?? config('app.name');
        
        // Format variables
        $title = Helpers::text_variable_data_format(value: $content['title'] ?? '', code: $code);
        $body = Helpers::text_variable_data_format(value: $content['body'] ?? '', code: $code);
        $footer_text = Helpers::text_variable_data_format(value: $content['footer_text'] ?? '', code: $code);
        $copyright_text = Helpers::text_variable_data_format(value: $content['copyright_text'] ?? '', code: $code);

        // Fix the subject - it was using password reset instead of email verification
        return $this->subject(translate('Email Verification Code'))
            ->view('email-templates.new-email-format-'.$template, [
                'company_name' => $company_name,
                'data' => $data, // This can be null, but the view should handle it
                'title' => $title,
                'body' => $body,
                'footer_text' => $footer_text,
                'copyright_text' => $copyright_text,
                'url' => '',
                'code' => $code
            ]);
    }
}