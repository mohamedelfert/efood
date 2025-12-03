<?php

namespace App\Mail;

use App\CentralLogics\Helpers;
use App\Model\BusinessSetting;
use App\Models\EmailTemplate;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class TransferOtp extends Mailable
{
    use Queueable, SerializesModels;

    protected $otp;
    protected $language_code;

    public function __construct($otp, $language_code)
    {
        $this->otp = $otp;
        $this->language_code = $language_code;
    }

    public function build()
    {
        $code = $this->otp;
        $data = EmailTemplate::with('translations')->where('type', 'user')->where('email_type', 'transfer_otp')->first();
        $local = $this->language_code ?? 'en';

        $content = [
            'title' => $data->title,
            'body' => $data->body,
            'footer_text' => $data->footer_text,
            'copyright_text' => $data->copyright_text
        ];

        if ($local != 'en') {
            if (isset($data->translations)) {
                foreach ($data->translations as $translation) {
                    if ($local == $translation->locale) {
                        $content[$translation->key] = $translation->value;
                    }
                }
            }
        }

        $template = $data ? $data->email_template : 4;
        $url = '';
        $company_name = BusinessSetting::where('key', 'restaurant_name')->first()->value;
        $title = Helpers::text_variable_data_format(value: $content['title'] ?? '', code: $code);
        $body = Helpers::text_variable_data_format(value: $content['body'] ?? '', code: $code);
        $footer_text = Helpers::text_variable_data_format(value: $content['footer_text'] ?? '', code: $code);
        $copyright_text = Helpers::text_variable_data_format(value: $content['copyright_text'] ?? '', code: $code);
        return $this->subject(translate('Transfer_OTP_Mail'))->view('email-templates.new-email-format-' . $template, ['company_name' => $company_name, 'data' => $data, 'title' => $title, 'body' => $body, 'footer_text' => $footer_text, 'copyright_text' => $copyright_text, 'url' => $url, 'code' => $code]);
    }
}