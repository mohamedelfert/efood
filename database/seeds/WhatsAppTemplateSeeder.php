<?php

namespace Database\Seeders;

use App\Model\BusinessSetting;
use Illuminate\Database\Seeder;
use App\Models\WhatsAppTemplate;
use Illuminate\Support\Facades\DB;

class WhatsAppTemplateSeeder extends Seeder
{
    public function run()
    {
        echo "\n🔧 Adding WhatsApp templates...\n\n";

        $templates = [
            [
                'type' => 'user',
                'whatsapp_type' => 'login_otp',
                'whatsapp_template' => 1,
                'title' => 'eFood - Login Verification',
                'body' => "Hello {user_name},\n\nYour verification code is: *{otp}*\n\nThis code will expire in {expiry_minutes} minutes.\n\nIf you didn't request this code, please ignore this message.\n\nDo not share this code with anyone.",
                'footer_text' => 'Keep your account secure',
                'copyright_text' => 'Copyright © 2025 eFood. All rights reserved.',
                'privacy' => 1,
                'refund' => 0,
                'cancelation' => 0,
                'contact' => 1,
                // ❌ Removed 'status' - not in your table
            ],
            [
                'type' => 'user',
                'whatsapp_type' => 'wallet_topup',
                'whatsapp_template' => 1,
                'title' => 'eFood - Wallet Top-Up Successful',
                'body' => "Hello {user_name},\n\nYour wallet has been topped up successfully!\n\n*Transaction Details:*\n• Transaction ID: {transaction_id}\n• Date: {date} at {time}\n• Amount Added: {amount} {currency}\n• Previous Balance: {previous_balance} {currency}\n• New Balance: {new_balance} {currency}\n\nThank you for using our service!",
                'button_name' => 'View Wallet',
                'button_url' => '/wallet',
                'footer_text' => 'If you did not make this transaction, please contact support.',
                'copyright_text' => 'Copyright © 2025 eFood. All rights reserved.',
                'privacy' => 1,
                'refund' => 1,
                'cancelation' => 0,
                'contact' => 1,
            ],
            [
                'type' => 'user',
                'whatsapp_type' => 'transfer_sent',
                'whatsapp_template' => 1,
                'title' => 'eFood - Money Sent',
                'body' => "Hello {user_name},\n\nYou have successfully sent money!\n\n*Transfer Details:*\n• Transaction ID: {transaction_id}\n• Amount: {amount} {currency}\n• Recipient: {recipient_name}\n• Date: {date} at {time}\n• Your New Balance: {new_balance} {currency}\n\nThank you for using eFood Wallet!",
                'button_name' => 'View Transaction',
                'button_url' => '/wallet/transactions',
                'footer_text' => 'Keep your PIN secure',
                'copyright_text' => 'Copyright © 2025 eFood. All rights reserved.',
                'privacy' => 1,
                'refund' => 0,
                'cancelation' => 0,
                'contact' => 1,
            ],
            [
                'type' => 'user',
                'whatsapp_type' => 'transfer_received',
                'whatsapp_template' => 1,
                'title' => 'eFood - Money Received',
                'body' => "Hello {user_name},\n\nYou have received money!\n\n*Transfer Details:*\n• Transaction ID: {transaction_id}\n• Amount: {amount} {currency}\n• From: {sender_name}\n• Date: {date} at {time}\n• Your New Balance: {new_balance} {currency}\n\nThe money has been credited to your wallet.",
                'button_name' => 'View Wallet',
                'button_url' => '/wallet',
                'footer_text' => 'Enjoy your funds!',
                'copyright_text' => 'Copyright © 2025 eFood. All rights reserved.',
                'privacy' => 1,
                'refund' => 0,
                'cancelation' => 0,
                'contact' => 1,
            ],
            [
                'type' => 'user',
                'whatsapp_type' => 'transfer_otp',
                'whatsapp_template' => 1,
                'title' => 'eFood - Transfer Verification',
                'body' => "Hello {user_name},\n\nYour transfer verification code is: *{otp}*\n\n*Transfer Details:*\n• Amount: {amount} {currency}\n• Recipient: {receiver_name}\n\nThis code will expire in {expiry_minutes} minutes.\n\nDo not share this code with anyone.",
                'footer_text' => 'Secure your transactions',
                'copyright_text' => 'Copyright © 2025 eFood. All rights reserved.',
                'privacy' => 1,
                'refund' => 0,
                'cancelation' => 0,
                'contact' => 1,
            ],
            [
                'type' => 'user',
                'whatsapp_type' => 'order_placed',
                'whatsapp_template' => 1,
                'title' => 'eFood - Order Confirmed',
                'body' => "Hello {user_name},\n\nYour order has been placed successfully!\n\n*Order Details:*\n• Order ID: #{order_id}\n• Amount: {order_amount} {currency}\n• Items: {items_count}\n• Type: {order_type}\n• Branch: {branch_name}\n• Delivery: {delivery_date} at {delivery_time}\n\nWe're preparing your order!",
                'button_name' => 'Track Order',
                'button_url' => '/orders',
                'footer_text' => 'Thank you for choosing eFood',
                'copyright_text' => 'Copyright © 2025 eFood. All rights reserved.',
                'privacy' => 1,
                'refund' => 1,
                'cancelation' => 1,
                'contact' => 1,
            ],
            [
                'type' => 'user',
                'whatsapp_type' => 'loyalty_conversion',
                'whatsapp_template' => 1,
                'title' => 'eFood - Loyalty Points Converted',
                'body' => "Hello {user_name},\n\nYou have successfully converted your loyalty points!\n\n*Conversion Details:*\n• Transaction ID: {transaction_id}\n• Points Used: {points_used}\n• Amount Credited: {converted_amount} {currency}\n• New Balance: {new_balance} {currency}\n• Remaining Points: {remaining_points}\n\nKeep earning more points with every order!",
                'button_name' => 'View Wallet',
                'button_url' => '/wallet',
                'footer_text' => 'Thank you for being a loyal customer',
                'copyright_text' => 'Copyright © 2025 eFood. All rights reserved.',
                'privacy' => 1,
                'refund' => 0,
                'cancelation' => 0,
                'contact' => 1,
            ],
        ];

        foreach ($templates as $template) {
            $exists = WhatsAppTemplate::where('type', $template['type'])
                ->where('whatsapp_type', $template['whatsapp_type'])
                ->exists();
                
            if (!$exists) {
                try {
                    WhatsAppTemplate::create($template);
                    echo "✅ Created: {$template['whatsapp_type']}\n";
                } catch (\Exception $e) {
                    echo "❌ Failed to create {$template['whatsapp_type']}: {$e->getMessage()}\n";
                }
            } else {
                echo "⏭️  Skipped: {$template['whatsapp_type']} (already exists)\n";
            }
        }

        echo "\n🔧 Updating WhatsApp status settings...\n\n";

        $settings = [
            'login_otp_whatsapp_status_user' => 1,
            'wallet_topup_whatsapp_status_user' => 1,
            'transfer_whatsapp_status_user' => 1,
            'order_whatsapp_status_user' => 1,
            'loyalty_conversion_whatsapp_status_user' => 1,
        ];

        foreach ($settings as $key => $value) {
            try {
                BusinessSetting::updateOrCreate(
                    ['key' => $key],
                    ['value' => $value]
                );
                echo "✅ Set: {$key} = {$value}\n";
            } catch (\Exception $e) {
                echo "❌ Failed to set {$key}: {$e->getMessage()}\n";
            }
        }

        echo "\n✅ WhatsApp templates setup complete!\n\n";
    }
}