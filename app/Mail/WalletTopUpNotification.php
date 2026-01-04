<?php

namespace App\Mail;

use App\CentralLogics\Helpers;
use App\Model\BusinessSetting;
use App\Models\EmailTemplate;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class WalletTopUpNotification extends Mailable
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
            ->where('email_type', 'wallet_topup')
            ->first();

        $local = $this->language_code ?? 'en';

        // Check if added by admin
        $isAdminAdded = isset($data['added_by']) && $data['added_by'] === 'admin';
        
        $defaultBody = $isAdminAdded 
            ? 'Hello {user_name},<br><br>Your wallet has been credited with {amount} {currency} by our administrator.<br><br>Transaction ID: {transaction_id}<br>Gateway: {gateway}<br>Previous Balance: {previous_balance} {currency}<br>New Balance: {new_balance} {currency}<br>Date: {date} {time}'
            : 'Hello {user_name},<br><br>Your wallet has been topped up with {amount} {currency}.<br><br>Transaction ID: {transaction_id}<br>Gateway: {gateway}<br>Previous Balance: {previous_balance} {currency}<br>New Balance: {new_balance} {currency}<br>Date: {date} {time}';

        $content = [
            'title' => $emailTemplate->title ?? 'Wallet Top-Up Successful',
            'body' => $emailTemplate->body ?? $defaultBody,
            'footer_text' => $emailTemplate->footer_text ?? 'Thank you for using our service',
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
            'amount' => $data['amount'] ?? '0.00',
            'currency' => $data['currency'] ?? 'SAR',
            'transaction_id' => $data['transaction_id'] ?? 'N/A',
            'gateway' => $data['gateway'] ?? 'N/A',
            'previous_balance' => $data['previous_balance'] ?? '0.00',
            'new_balance' => $data['new_balance'] ?? '0.00',
            'date' => $data['date'] ?? now()->format('d/m/Y'),
            'time' => $data['time'] ?? now()->format('h:i A'),
            'year' => date('Y'),
        ];

        // Add admin reference if available
        if (isset($data['admin_reference']) && !empty($data['admin_reference'])) {
            $variables['admin_reference'] = $data['admin_reference'];
        }

        foreach ($content as $key => $text) {
            foreach ($variables as $varKey => $value) {
                $text = str_replace('{' . $varKey . '}', $value, $text);
            }
            $content[$key] = $text;
        }

        $subject = $isAdminAdded 
            ? translate('Wallet Credited by Admin')
            : translate('Wallet Top-Up Successful');

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