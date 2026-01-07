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
                'title' => 'طلب إعادة تعيين كلمة المرور',
                'body' => 'أهلا {user_name},<br><br>تم طلب إعاده تعيين كلمه المرور<br><br>كود التحقق : <strong style="font-size:20px;color:#00AA6D;">{code}</strong><br><br>هذا الكود سينتهي خلال 5 دقائق<br><br>إذا لم تقم بطلب تغيير كلمه المرور , تجاهل هذه الرساله.',
                'footer_text' => 'لا تشارك كود التحقق مع أي شخص، بما في ذلك فريق الدعم الخاص بنا.',
                'copyright_text' => 'حقوق النشر © {year} eFood. جميع الحقوق محفوظة.',
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
                'title' => 'تأكيد تحويل الأموال',
                'body' => 'أهلا {user_name},<br><br>رمز التحقق من تحويل الأموال هو: <strong>{code}</strong><br><br>هذا الرمز سينتهي خلال 5 دقائق.<br><br>لا تشارك هذا الرمز مع أي شخص.',
                'footer_text' => 'إذا لم تقم بتنفيذ هذا التحويل، يرجى الاتصال بالدعم فوراً.',
                'copyright_text' => 'حقوق النشر © {year} eFood. جميع الحقوق محفوظة.',
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
                'title' => 'إعادة شحن المحفظة ناجحة',
                'body' => 'أهلا {user_name},<br><br>تم إعادة شحن محفظتك بنجاح!<br><br><strong>تفاصيل المعاملة:</strong><br>• رقم المعاملة: {transaction_id}<br>• المبلغ: {amount} {currency}<br>• الرصيد السابق: {previous_balance} {currency}<br>• الرصيد الجديد: {new_balance} {currency}<br><br>شكراً لك على استخدام خدمتنا!',
                'footer_text' => 'إذا لم تقم بتنفيذ هذه المعاملة، يرجى الاتصال بالدعم فوراً.',
                'copyright_text' => 'حقوق النشر © {year} eFood. جميع الحقوق محفوظة.',
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
                'title' => 'إشعار تحويل الأموال',
                'body' => 'أهلا {receiver_name},<br><br>لقد تلقيت {amount} {currency} من {sender_name}.<br><br><strong>تفاصيل المعاملة:</strong><br>• رقم المعاملة: {transaction_id}<br>• رصيدك الجديد: {balance} {currency}<br><br>تم إضافة الأموال إلى محفظتك.',
                'footer_text' => 'احتفظ بحسابك آمناً.',
                'copyright_text' => 'حقوق النشر © {year} eFood. جميع الحقوق محفوظة.',
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
                'title' => 'تم تحويل نقاط الولاء',
                'body' => 'أهلا {user_name},<br><br>تم تحويل {points_used} نقطة ولاء إلى {converted_amount} {currency}.<br><br><strong>تفاصيل:</strong><br>• رقم المعاملة: {transaction_id}<br>• رصيد المحفظة الجديد: {new_balance} {currency}<br>• النقاط المتبقية: {remaining_points}<br><br>شكراً لك على كونك عميل مخلص!',
                'footer_text' => 'استمر في كسب المزيد من النقاط مع كل طلب!',
                'copyright_text' => 'حقوق النشر © {year} eFood. جميع الحقوق محفوظة.',
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
                'title' => 'رمز تحقق الدخول',
                'body' => 'أهلا {user_name},<br><br>رمز تحقق الدخول هو: <strong style="font-size:20px;color:#00AA6D;">{otp}</strong><br><br>هذا الرمز سيتنهي خلال {expiry_minutes} دقائق.<br><br>لو ما طلبت هذا الكود، يرجى تأمين حسابك فوراً.',
                'footer_text' => 'لا تشارك رمز OTP مع أي شخص، بما في ذلك فريق الدعم.',
                'copyright_text' => 'حقوق النشر © {year} eFood. جميع الحقوق محفوظة.',
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