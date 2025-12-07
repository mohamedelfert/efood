<?php

namespace App\Mail;

use App\CentralLogics\Helpers;
use App\Model\BusinessSetting;
use App\Models\EmailTemplate;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class LoginOtp extends Mailable
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
        
        // Fetch email template
        $data = EmailTemplate::with('translations')
            ->where('type', 'user')
            ->where('email_type', 'login_otp')
            ->first();

        // Fallback to registration_otp if login_otp doesn't exist
        if (!$data) {
            $data = EmailTemplate::with('translations')
                ->where('type', 'user')
                ->where('email_type', 'registration_otp')
                ->first();
        }

        $local = $this->language_code ?? 'en';

        $content = [
            'title' => $data->title ?? 'Login OTP Verification',
            'body' => $data->body ?? 'Your login OTP is: {code}. Valid for 5 minutes.',
            'footer_text' => $data->footer_text ?? 'Thank you for using our service',
            'copyright_text' => $data->copyright_text ?? '© ' . date('Y') . ' All rights reserved'
        ];

        // Apply translations
        if ($local != 'en' && isset($data->translations)) {
            foreach ($data->translations as $translation) {
                if ($local == $translation->locale) {
                    $content[$translation->key] = $translation->value;
                }
            }
        }

        $template = $data ? $data->email_template : 4;
        $url = '';
        $company_name = BusinessSetting::where('key', 'restaurant_name')->first()->value ?? config('app.name');
        
        $title = Helpers::text_variable_data_format(value: $content['title'] ?? '', code: $code);
        $body = Helpers::text_variable_data_format(value: $content['body'] ?? '', code: $code);
        $footer_text = Helpers::text_variable_data_format(value: $content['footer_text'] ?? '', code: $code);
        $copyright_text = Helpers::text_variable_data_format(value: $content['copyright_text'] ?? '', code: $code);
        
        return $this->subject(translate('Login OTP Verification'))
            ->view('email-templates.new-email-format-' . $template, [
                'company_name' => $company_name,
                'data' => $data,
                'title' => $title,
                'body' => $body,
                'footer_text' => $footer_text,
                'copyright_text' => $copyright_text,
                'url' => $url,
                'code' => $code
            ]);
    }
}