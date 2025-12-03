<?php

namespace App\Mail;

use App\CentralLogics\Helpers;
use App\Model\BusinessSetting;
use App\Models\EmailTemplate;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PaymentSuccessNotification extends Mailable
{
    use Queueable, SerializesModels;

    protected $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function build()
    {
        $email_template = EmailTemplate::with('translations')->where('type', 'user')->where('email_type', 'payment_success')->first();
        $local = $this->data['language_code'] ?? 'en';

        $content = [
            'title' => $email_template->title,
            'body' => $email_template->body,
            'footer_text' => $email_template->footer_text,
            'copyright_text' => $email_template->copyright_text
        ];

        if ($local != 'en') {
            if (isset($email_template->translations)) {
                foreach ($email_template->translations as $translation) {
                    if ($local == $translation->locale) {
                        $content[$translation->key] = $translation->value;
                    }
                }
            }
        }

        $template = $email_template ? $email_template->email_template : 3;
        $url = '';
        $company_name = BusinessSetting::where('key', 'restaurant_name')->first()->value;
        $user_name = $this->data['user_name'] ?? '';
        $amount = $this->data['amount'] ?? '';
        $transaction_id = $this->data['transaction_id'] ?? '';
        $title = Helpers::text_variable_data_format(value: $content['title'] ?? '', user_name: $user_name, amount: $amount, transaction_id: $transaction_id);
        $body = Helpers::text_variable_data_format(value: $content['body'] ?? '', user_name: $user_name, amount: $amount, transaction_id: $transaction_id);
        $footer_text = Helpers::text_variable_data_format(value: $content['footer_text'] ?? '', user_name: $user_name, amount: $amount, transaction_id: $transaction_id);
        $copyright_text = Helpers::text_variable_data_format(value: $content['copyright_text'] ?? '', user_name: $user_name, amount: $amount, transaction_id: $transaction_id);
        return $this->subject(translate('Payment_Success_Mail'))->view('email-templates.new-email-format-' . $template, ['company_name' => $company_name, 'data' => $email_template, 'title' => $title, 'body' => $body, 'footer_text' => $footer_text, 'copyright_text' => $copyright_text, 'url' => $url]);
    }
}