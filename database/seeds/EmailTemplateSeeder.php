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
        echo "\n🔧 Adding missing email templates...\n\n";

        $templates = [
            [
                'type' => 'user',
                'email_type' => 'wallet_topup',
                'email_template' => 4,
                'title' => 'Wallet Top-Up Successful',
                'body' => 'Hello {user_name},<br><br>Your wallet has been successfully topped up!<br><br><strong>Transaction Details:</strong><br>• Transaction ID: {transaction_id}<br>• Date & Time: {date} at {time}<br>• Amount Added: {amount} {currency}<br>• Payment Method: {gateway}<br>• Previous Balance: {previous_balance} {currency}<br>• New Balance: {new_balance} {currency}<br><br>Thank you for using our service!',
                'button_name' => 'View Wallet',
                'button_url' => '/wallet',
                'footer_text' => 'If you did not make this transaction, please contact support immediately.',
                'copyright_text' => 'Copyright © {year} eFood. All rights reserved.',
                'status' => 1,
            ],
            [
                'type' => 'user',
                'email_type' => 'loyalty_conversion',
                'email_template' => 4,
                'title' => 'Loyalty Points Converted Successfully',
                'body' => 'Hello {user_name},<br><br>You have successfully converted your loyalty points to wallet balance!<br><br><strong>Conversion Details:</strong><br>• Transaction ID: {transaction_id}<br>• Points Used: {points_used}<br>• Amount Credited: {converted_amount} {currency}<br>• New Wallet Balance: {new_balance} {currency}<br>• Remaining Loyalty Points: {remaining_points}<br><br>Thank you for being a loyal customer!',
                'button_name' => 'View Wallet',
                'button_url' => '/wallet',
                'footer_text' => 'Keep earning points with every order to get more rewards!',
                'copyright_text' => 'Copyright © {year} eFood. All rights reserved.',
                'status' => 1,
            ],
            [
                'type' => 'user',
                'email_type' => 'money_transfer',
                'email_template' => 4,
                'title' => 'Money Transfer Notification',
                'body' => 'Hello {receiver_name},<br><br>You have received a money transfer!<br><br><strong>Transfer Details:</strong><br>• From: {sender_name}<br>• Transaction ID: {transaction_id}<br>• Amount: {amount} {currency}<br>• Note: {note}<br>• Your New Balance: {balance} {currency}<br><br>The money has been credited to your wallet.',
                'button_name' => 'View Transaction',
                'button_url' => '/wallet/transactions',
                'footer_text' => 'Keep your account secure and never share your PIN with anyone.',
                'copyright_text' => 'Copyright © {year} eFood. All rights reserved.',
                'status' => 1,
            ],
        ];

        foreach ($templates as $template) {
            $exists = EmailTemplate::where('type', $template['type'])
                ->where('email_type', $template['email_type'])
                ->exists();
                
            if (!$exists) {
                EmailTemplate::create($template);
                echo "✅ Created: {$template['email_type']}\n";
            } else {
                echo "⏭️  Skipped: {$template['email_type']} (already exists)\n";
            }
        }

        echo "\n🔧 Updating business settings...\n\n";

        $settings = [
            'wallet_topup_mail_status_user' => 1,
            'loyalty_conversion_mail_status_user' => 1,
            'money_transfer_mail_status_user' => 1,
            'transfer_otp_mail_status_user' => 1,
        ];

        foreach ($settings as $key => $value) {
            BusinessSetting::updateOrCreate(
                ['key' => $key],
                ['value' => $value]
            );
            echo "✅ Set: {$key} = {$value}\n";
        }

        echo "\n✅ Email templates setup complete!\n\n";
    }
}