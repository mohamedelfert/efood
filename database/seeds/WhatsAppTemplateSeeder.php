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
                'title' => 'eFood - (تأكيد الدخول)',
                'body' => "أهلا {user_name},\n\nكود تحقيق الدخول : *{otp}*\n\nهذا الكود سيتنهي خلال {expiry_minutes} minutes.\n\nلو ما طلبت هذا الكود، يرجى تجاهل هذه الرسالة وعدم مشاركة هذا الكود مع أي شخص.",
                'footer_text' => 'تأكد من أمان حسابك',
                'copyright_text' => 'حقوق النشر © 2025 eFood. جميع الحقوق محفوظة.',
                'privacy' => 1,
                'refund' => 0,
                'cancelation' => 0,
                'contact' => 1,
            ],
            [
                'type' => 'user',
                'whatsapp_type' => 'wallet_topup',
                'whatsapp_template' => 1,
                'title' => 'eFood - (إعادة شحن المحفظة)',
                'body' => "أهلا {user_name},\n\nتم إعادة شحن محفظتك بنجاح!\n\n*تفاصيل المعاملة:*\n• رقم المعاملة: {transaction_id}\n• التاريخ: {date} الساعة {time}\n• المبلغ المضاف: {amount} {currency}\n• الرصيد السابق: {previous_balance} {currency}\n• الرصيد الجديد: {new_balance} {currency}\n\nشكراً لك على استخدام خدمتنا!",
                'footer_text' => 'إذا كنت لم تقم بهذا، يرجى الاتصال بالدعم فوراً',
                'copyright_text' => 'حقوق النشر © 2025 eFood. جميع الحقوق محفوظة.',
                'privacy' => 1,
                'refund' => 1,
                'cancelation' => 0,
                'contact' => 1,
            ],
            [
                'type' => 'user',
                'whatsapp_type' => 'transfer_sent',
                'whatsapp_template' => 1,
                'title' => 'eFood - (تم إرسال الأموال)',
                'body' => "أهلا {user_name},\n\nلقد تم إرسال الأموال بنجاح!\n\n*تفاصيل التحويل:*\n• رقم المعاملة: {transaction_id}\n• المبلغ: {amount} {currency}\n• المستلم: {recipient_name}\n• التاريخ: {date} الساعة {time}\n• الرصيد الجديد: {new_balance} {currency}\n\nشكراً لك على استخدام محفظة eFood!",
                'footer_text' => 'احتفظ بـ PIN آمن',
                'copyright_text' => 'حقوق النشر © 2025 eFood. جميع الحقوق محفوظة.',
                'privacy' => 1,
                'refund' => 0,
                'cancelation' => 0,
                'contact' => 1,
            ],
            [
                'type' => 'user',
                'whatsapp_type' => 'transfer_received',
                'whatsapp_template' => 1,
                'title' => 'eFood - (تم استلام الأموال)',
                'body' => "أهلا {user_name},\n\nلقد تم استلام الأموال!\n\n*تفاصيل التحويل:*\n• رقم المعاملة: {transaction_id}\n• المبلغ: {amount} {currency}\n• من: {sender_name}\n• التاريخ: {date} الساعة {time}\n• الرصيد الجديد: {new_balance} {currency}\n\nتم إضافة الأموال إلى محفظتك.",
                'footer_text' => 'استمتع بأموالك!',
                'copyright_text' => 'حقوق النشر © 2025 eFood. جميع الحقوق محفوظة.',
                'privacy' => 1,
                'refund' => 0,
                'cancelation' => 0,
                'contact' => 1,
            ],
            [
                'type' => 'user',
                'whatsapp_type' => 'transfer_otp',
                'whatsapp_template' => 1,
                'title' => 'eFood - (تحقق من التحويل)',
                'body' => "أهلا {user_name},\n\nرمز التحقق من التحويل هو: *{otp}*\n\n*تفاصيل التحويل:*\n• المبلغ: {amount} {currency}\n• المستلم: {receiver_name}\n\nهذا الرمز سينتهي في {expiry_minutes} دقائق.\n\nلا تشارك هذا الرمز مع أي شخص.",
                'footer_text' => 'احتفظ بمعاملاتك آمنة',
                'copyright_text' => 'حقوق النشر © 2025 eFood. جميع الحقوق محفوظة.',
                'privacy' => 1,
                'refund' => 0,
                'cancelation' => 0,
                'contact' => 1,
            ],
            [
                'type' => 'user',
                'whatsapp_type' => 'order_placed',
                'whatsapp_template' => 1,
                'title' => 'eFood - (تم تأكيد الطلب)',
                'body' => "أهلا {user_name},\n\nتم تأكيد طلبك بنجاح!\n\n*تفاصيل الطلب:*\n• رقم الطلب: #{order_id}\n• المبلغ: {order_amount} {currency}\n• العناصر: {items_count}\n• النوع: {order_type}\n• الفرع: {branch_name}\n• التسليم: {delivery_date} الساعة {delivery_time}\n\nنحن نقوم بإعداد طلبك!",
                'footer_text' => 'شكراً لك على اختيار eFood',
                'copyright_text' => 'حقوق النشر © 2025 eFood. جميع الحقوق محفوظة.',
                'privacy' => 1,
                'refund' => 1,
                'cancelation' => 1,
                'contact' => 1,
            ],
            [
                'type' => 'user',
                'whatsapp_type' => 'loyalty_conversion',
                'whatsapp_template' => 1,
                'title' => 'eFood - (تم تحويل نقاط الولاء)',
                'body' => "أهلا {user_name},\n\nلقد تم تحويل نقاط الولاء بنجاح!\n\n*تفاصيل التحويل:*\n• رقم المعاملة: {transaction_id}\n• النقاط المستخدمة: {points_used}\n• المبلغ المُضاف: {converted_amount} {currency}\n• الرصيد الجديد: {new_balance} {currency}\n• النقاط المتبقية: {remaining_points}\n\nاستمر في كسب المزيد من النقاط مع كل طلب!",
                'footer_text' => 'شكراً لك على كونك عميل مخلص',
                'copyright_text' => 'حقوق النشر © 2025 eFood. جميع الحقوق محفوظة.',
                'privacy' => 1,
                'refund' => 0,
                'cancelation' => 0,
                'contact' => 1,
            ],
            [
                'type' => 'user',
                'whatsapp_type' => 'pin_reset_otp',
                'whatsapp_template' => 1,
                'title' => 'eFood - (رمز تحقق إعادة تعيين PIN)',
                'body' => "أهلا {user_name},\n\nرمز تحقق إعادة تعيين PIN الخاص بك هو:\n\n*{otp}*\n\nسينتهي هذا الرمز في {expiry_minutes} دقائق.\n\n⚠️ إذا لم تطلب إعادة تعيين PIN، يرجى تجاهل هذه الرسالة والاتصال بالدعم فوراً.\n\n🔒 لا تشارك هذا الرمز مع أي شخص، بما في ذلك فريق الدعم.",
                'footer_text' => 'احتفظ بحسابك آمناً',
                'copyright_text' => 'حقوق النشر © 2025 eFood. جميع الحقوق محفوظة.',
                'privacy' => 1,
                'refund' => 0,
                'cancelation' => 0,
                'contact' => 1,
            ],
            [
                'type' => 'user',
                'whatsapp_type' => 'pin_reset_success',
                'whatsapp_template' => 1,
                'title' => 'eFood - (إعادة تعيين PIN ناجحة)',
                'body' => "أهلا {user_name},\n\n✅ تم إعادة تعيين PIN المحفظة بنجاح!\n\n*تفاصيل إعادة التعيين:*\n• التاريخ: {date}\n• الوقت: {time}\n\nPIN الجديد الآن نشط.\n\n*نصائح الأمان:*\n• احتفظ بـ PIN سري\n• استخدم PIN فريد\n• غيّره بشكل دوري\n• لا تستخدم PIN قابلة للتخمين بسهولة\n\n⚠️ إذا لم تقم بتفعيل هذا التغيير، يرجى الاتصال بالدعم فوراً.",
                'footer_text' => 'ابقَ آمناً مع محفظة eFood',
                'copyright_text' => 'حقوق النشر © 2025 eFood. جميع الحقوق محفوظة.',
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
            'pin_reset_otp_whatsapp_status_user' => 1,
            'pin_reset_success_whatsapp_status_user' => 1,
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