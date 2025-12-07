<?php

namespace App\Mail;

use App\CentralLogics\Helpers;
use App\Model\BusinessSetting;
use App\Models\EmailTemplate;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class LoyaltyConversionNotification extends Mailable
{
    use Queueable, SerializesModels;

    protected $notificationData;
    protected $language_code;

    public function __construct($notificationData, $language_code = 'en')
    {
        $this->notificationData = $notificationData;
        $this->language_code = $language_code;
    }

    public function build()
    {
        $data = $this->notificationData;
        
        $emailTemplate = EmailTemplate::with('translations')
            ->where('type', 'user')
            ->where('email_type', 'loyalty_conversion')
            ->first();
            
        $local = $this->language_code ?? 'en';

        $content = [
            'title' => $emailTemplate->title ?? 'Loyalty Points Converted',
            'body' => $emailTemplate->body ?? 'Hello {user_name},<br><br>You converted {points_used} points to {converted_amount} {currency}.<br><br>Transaction ID: {transaction_id}<br>New Balance: {new_balance} {currency}<br>Remaining Points: {remaining_points}',
            'footer_text' => $emailTemplate->footer_text ?? 'Thank you for being loyal',
            'copyright_text' => $emailTemplate->copyright_text ?? '© {year} All rights reserved',
        ];

        if ($local != 'en' && isset($emailTemplate->translations)) {
            foreach ($emailTemplate->translations as $translation) {
                if ($local == $translation->locale) {
                    $content[$translation->key] = $translation->value;
                }
            }
        }

        $template = $emailTemplate ? $emailTemplate->email_template : 4;
        $company_name = BusinessSetting::where('key', 'restaurant_name')->first()->value ?? config('app.name');

        $variables = [
            'user_name' => $data['user_name'] ?? 'User',
            'points_used' => $data['points_used'] ?? '0',
            'converted_amount' => $data['converted_amount'] ?? '0.00',
            'currency' => $data['currency'] ?? 'SAR',
            'transaction_id' => $data['transaction_id'] ?? 'N/A',
            'new_balance' => $data['new_balance'] ?? '0.00',
            'remaining_points' => $data['remaining_points'] ?? '0',
            'year' => date('Y'),
        ];

        foreach ($content as $key => $text) {
            foreach ($variables as $varKey => $value) {
                $text = str_replace('{' . $varKey . '}', $value, $text);
            }
            $content[$key] = $text;
        }

        return $this->subject(translate('Loyalty Points Converted'))
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