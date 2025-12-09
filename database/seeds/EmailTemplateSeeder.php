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
            // PASSWORD RESET
            [
                'type' => 'user',
                'email_type' => 'forget_password',
                'email_template' => 4,
                'title' => 'Password Reset Request',
                'body' => 'Hello {user_name},<br><br>We received a request to reset your password.<br><br>Your verification code is: <strong style="font-size:20px;color:#00AA6D;">{code}</strong><br><br>This code will expire in 5 minutes.<br><br>If you did not request this password reset, please ignore this email.',
                'button_name' => null,
                'button_url' => null,
                'footer_text' => 'For security reasons, never share your verification code with anyone.',
                'copyright_text' => 'Copyright © {year} eFood. All rights reserved.',
                'icon' => null,
                'privacy' => 0,
                'refund' => 0,
                'cancelation' => 0,
                'contact' => 1,
                'status' => 1
            ],
            
            // Transfer OTP
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
                'icon' => null,
                'privacy' => 0,
                'refund' => 0,
                'cancelation' => 0,
                'contact' => 1,
                'status' => 1
            ],
            
            // Wallet Top-Up
            [
                'type' => 'user',
                'email_type' => 'wallet_topup',
                'email_template' => 4,
                'title' => 'Wallet Top-Up Successful',
                'body' => 'Hello {user_name},<br><br>Your wallet has been topped up successfully!<br><br><strong>Transaction Details:</strong><br>• Transaction ID: {transaction_id}<br>• Amount: {amount} {currency}<br>• Previous Balance: {previous_balance} {currency}<br>• New Balance: {new_balance} {currency}<br><br>Thank you for using our service!',
                'button_name' => 'View Wallet',
                'button_url' => '/wallet',
                'footer_text' => 'If you did not make this transaction, contact support immediately.',
                'copyright_text' => 'Copyright © {year} eFood. All rights reserved.',
                'icon' => null,
                'privacy' => 0,
                'refund' => 0,
                'cancelation' => 0,
                'contact' => 1,
                'status' => 1
            ],
            
            // Money Transfer
            [
                'type' => 'user',
                'email_type' => 'money_transfer',
                'email_template' => 4,
                'title' => 'Money Transfer Notification',
                'body' => 'Hello {receiver_name},<br><br>You received {amount} {currency} from {sender_name}.<br><br><strong>Transaction Details:</strong><br>• Transaction ID: {transaction_id}<br>• Your New Balance: {balance} {currency}<br><br>The money has been added to your wallet.',
                'button_name' => 'View Transaction',
                'button_url' => '/wallet/transactions',
                'footer_text' => 'Keep your account secure.',
                'copyright_text' => 'Copyright © {year} eFood. All rights reserved.',
                'icon' => null,
                'privacy' => 0,
                'refund' => 0,
                'cancelation' => 0,
                'contact' => 1,
                'status' => 1
            ],
            
            // Loyalty Conversion
            [
                'type' => 'user',
                'email_type' => 'loyalty_conversion',
                'email_template' => 4,
                'title' => 'Loyalty Points Converted',
                'body' => 'Hello {user_name},<br><br>Successfully converted {points_used} loyalty points to {converted_amount} {currency}.<br><br><strong>Details:</strong><br>• Transaction ID: {transaction_id}<br>• New Wallet Balance: {new_balance} {currency}<br>• Remaining Points: {remaining_points}<br><br>Thank you for being a loyal customer!',
                'button_name' => 'View Wallet',
                'button_url' => '/wallet',
                'footer_text' => 'Keep earning points with every order!',
                'copyright_text' => 'Copyright © {year} eFood. All rights reserved.',
                'icon' => null,
                'privacy' => 0,
                'refund' => 0,
                'cancelation' => 0,
                'contact' => 1,
                'status' => 1
            ],
            
            // Login OTP
            [
                'type' => 'user',
                'email_type' => 'login_otp',
                'email_template' => 4,
                'title' => 'Login Verification Code',
                'body' => 'Hello {user_name},<br><br>Your login verification code is: <strong style="font-size:20px;color:#00AA6D;">{otp}</strong><br><br>This code will expire in {expiry_minutes} minutes.<br><br>If you did not attempt to login, please secure your account immediately.',
                'button_name' => null,
                'button_url' => null,
                'footer_text' => 'Never share your OTP with anyone, including our support team.',
                'copyright_text' => 'Copyright © {year} eFood. All rights reserved.',
                'icon' => null,
                'privacy' => 0,
                'refund' => 0,
                'cancelation' => 0,
                'contact' => 1,
                'status' => 1
            ],
        ];

        $createdCount = 0;
        $skippedCount = 0;
        $updatedCount = 0;

        foreach ($templates as $template) {
            $exists = EmailTemplate::where('type', $template['type'])
                ->where('email_type', $template['email_type'])
                ->first();
                
            if (!$exists) {
                EmailTemplate::create($template);
                echo "✅ Created: {$template['email_type']}\n";
                $createdCount++;
            } else {
                echo "⏭️  Skipped: {$template['email_type']} (already exists)\n";
                $skippedCount++;
            }
        }

        echo "\n🔧 Updating email status settings...\n\n";

        $settings = [
            'forget_password_mail_status_user' => 1,
            'transfer_otp_mail_status_user' => 1,
            'wallet_topup_mail_status_user' => 1,
            'money_transfer_mail_status_user' => 1,
            'loyalty_conversion_mail_status_user' => 1,
            'login_otp_mail_status_user' => 1,
        ];

        foreach ($settings as $key => $value) {
            $setting = BusinessSetting::updateOrCreate(
                ['key' => $key],
                ['value' => $value]
            );
            echo "⚙️  Set: {$key} = {$value}\n";
            $updatedCount++;
        }

        echo "\n" . str_repeat("=", 50) . "\n";
        echo "📊 Summary:\n";
        echo "   • Templates Created: {$createdCount}\n";
        echo "   • Templates Skipped: {$skippedCount}\n";
        echo "   • Settings Updated: {$updatedCount}\n";
        echo str_repeat("=", 50) . "\n";
        echo "\n✨ All email templates are ready!\n\n";
    }
}