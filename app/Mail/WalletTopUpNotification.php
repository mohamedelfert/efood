<?php

namespace App\Mail;

use App\CentralLogics\Helpers;
use App\Model\BusinessSetting;
use App\Models\EmailTemplate;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Mpdf\Mpdf;

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
        $company_phone = BusinessSetting::where('key', 'phone')->first()->value ?? '';
        $company_address = BusinessSetting::where('key', 'address')->first()->value ?? '';

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

        // Prepare view data for email
        $viewData = [
            'company_name' => $company_name,
            'data' => $emailTemplate,
            'title' => $content['title'],
            'body' => $content['body'],
            'footer_text' => $content['footer_text'],
            'copyright_text' => $content['copyright_text'],
            'url' => '',
            'code' => null,
        ];

        // Try to generate and attach PDF receipt
        try {
            $pdfData = [
                'language_code' => $local,
                'company_name' => $company_name,
                'company_phone' => $company_phone,
                'company_address' => $company_address,
                'transaction_id' => $data['transaction_id'] ?? 'N/A',
                'customer_name' => $data['user_name'] ?? 'Customer',
                'amount' => $data['amount'] ?? '0.00',
                'currency' => $data['currency'] ?? 'YER',
                'previous_balance' => $data['previous_balance'] ?? '0.00',
                'new_balance' => $data['new_balance'] ?? '0.00',
                'cashback_amount' => $data['cashback_amount'] ?? 0,
                'gateway' => $data['gateway'] ?? 'Admin Panel',
                'date' => $data['date'] ?? now()->format('d/m/Y'),
                'time' => $data['time'] ?? now()->format('h:i A'),
            ];

            $html = view('email-templates.wallet-topup-receipt', $pdfData)->render();

            $mpdf = new Mpdf([
                'mode' => 'utf-8',
                'format' => 'A4',
                'default_font_size' => 11,
                'default_font' => 'dejavusans',
                'margin_left' => 10,
                'margin_right' => 10,
                'margin_top' => 10,
                'margin_bottom' => 10,
                'margin_header' => 5,
                'margin_footer' => 5,
                'orientation' => 'P',
                'directionality' => $local === 'ar' ? 'rtl' : 'ltr',
                'autoScriptToLang' => true,
                'autoLangToFont' => true,
            ]);

            $mpdf->WriteHTML($html);
            $pdfOutput = $mpdf->Output('', 'S');

            $transactionId = $data['transaction_id'] ?? time();
            $pdfFilename = 'Wallet_Receipt_' . $transactionId . '.pdf';

            Log::info('Wallet top-up PDF generated successfully', [
                'transaction_id' => $transactionId
            ]);

            return $this->subject($subject)
                ->view('email-templates.new-email-format-' . $template, $viewData)
                ->attachData($pdfOutput, $pdfFilename, [
                    'mime' => 'application/pdf',
                ]);

        } catch (\Exception $pdfError) {
            Log::warning('Wallet top-up PDF generation failed, sending email without PDF', [
                'transaction_id' => $data['transaction_id'] ?? 'unknown',
                'error' => $pdfError->getMessage()
            ]);

            // Send email without PDF if PDF generation fails
            return $this->subject($subject)
                ->view('email-templates.new-email-format-' . $template, $viewData);
        }
    }
}