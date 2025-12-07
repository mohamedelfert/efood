<?php

namespace Database\Seeders;

use App\Models\EmailTemplate;
use App\Model\BusinessSetting;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EmailTemplateSeeder extends Seeder
{
    public function run()
    {
        echo "\n🔧 Adding ALL missing email templates...\n\n";

        $templates = [
            [
                'type' => 'user',
                'email_type' => 'transfer_otp',
                'email_template' => 4,
                'title' => 'Money Transfer Verification',
                'body' => 'Hello {user_name},<br><br>Your money transfer verification code is: <strong>{code}</strong><br><br>This code will expire in 5 minutes.<br><br>Do not share this code with anyone.',
                'button_name' => 'Verify Transfer',
                'button_url' => '/wallet/transfer',
                'footer_text' => 'If you did not initiate this transfer, please contact support immediately.',
                'copyright_text' => 'Copyright © {year} eFood. All rights reserved.',
            ],
            [
                'type' => 'user',
                'email_type' => 'wallet_topup',
                'email_template' => 4,
                'title' => 'Wallet Top-Up Successful',
                'body' => 'Hello {user_name},<br><br>Your wallet has been topped up!<br><br><strong>Details:</strong><br>• Transaction ID: {transaction_id}<br>• Amount: {amount} {currency}<br>• New Balance: {new_balance} {currency}<br><br>Thank you!',
                'button_name' => 'View Wallet',
                'button_url' => '/wallet',
                'footer_text' => 'If you did not make this transaction, contact support.',
                'copyright_text' => 'Copyright © {year} eFood. All rights reserved.',
            ],
            [
                'type' => 'user',
                'email_type' => 'money_transfer',
                'email_template' => 4,
                'title' => 'Money Transfer Notification',
                'body' => 'Hello {receiver_name},<br><br>You received {amount} {currency} from {sender_name}.<br><br>Transaction ID: {transaction_id}<br>Your Balance: {balance} {currency}',
                'button_name' => 'View Transaction',
                'button_url' => '/wallet/transactions',
                'footer_text' => 'Keep your account secure.',
                'copyright_text' => 'Copyright © {year} eFood. All rights reserved.',
            ],
            [
                'type' => 'user',
                'email_type' => 'loyalty_conversion',
                'email_template' => 4,
                'title' => 'Loyalty Points Converted',
                'body' => 'Hello {user_name},<br><br>Successfully converted {points_used} points to {converted_amount} {currency}.<br><br>New Balance: {new_balance} {currency}<br>Remaining Points: {remaining_points}',
                'button_name' => 'View Wallet',
                'button_url' => '/wallet',
                'footer_text' => 'Keep earning points!',
                'copyright_text' => 'Copyright © {year} eFood. All rights reserved.',
            ],
        ];

        foreach ($templates as $template) {
            $exists = EmailTemplate::where('type', $template['type'])
                ->where('email_type', $template['email_type'])
                ->exists();
                
            if (!$exists) {
                EmailTemplate::create($template);
                echo "Created: {$template['email_type']}\n";
            } else {
                echo "Skipped: {$template['email_type']} (exists)\n";
            }
        }

        echo "\n🔧 Updating email status settings...\n\n";

        $settings = [
            'transfer_otp_mail_status_user' => 1,
            'wallet_topup_mail_status_user' => 1,
            'money_transfer_mail_status_user' => 1,
            'loyalty_conversion_mail_status_user' => 1,
        ];

        foreach ($settings as $key => $value) {
            BusinessSetting::updateOrCreate(
                ['key' => $key],
                ['value' => $value]
            );
            echo "Set: {$key} = {$value}\n";
        }

        echo "\nAll email templates ready!\n\n";
    }
}