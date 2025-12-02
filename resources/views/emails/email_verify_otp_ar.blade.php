<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>رمز تأكيد البريد الإلكتروني</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; background-color: #f4f4f4; margin: 0; padding: 0; direction: rtl; text-align: right; }
        .container { max-width: 600px; margin: 20px auto; background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: #fff; padding: 30px 20px; text-align: center; }
        .header h1 { margin: 0; font-size: 24px; }
        .content { padding: 30px 20px; }
        .info-box { background: #f8f9fa; border-right: 4px solid #667eea; padding: 15px; margin: 20px 0; border-radius: 4px; }
        .info-row { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #e9ecef; }
        .info-row:last-child { border-bottom: none; }
        .info-label { font-weight: bold; color: #495057; }
        .info-value { color: #212529; }
        .warning-box { background: #fff3cd; border-right: 4px solid #ffc107; padding: 15px; margin: 20px 0; border-radius: 4px; }
        .otp-notice { background: #d1ecf1; border-right: 4px solid #0c5460; padding: 15px; margin: 20px 0; border-radius: 4px; text-align: center; }
        .otp-notice h3 { margin: 0 0 10px 0; color: #0c5460; }
        .footer { background: #f8f9fa; padding: 20px; text-align: center; font-size: 12px; color: #6c757d; }
        .button { display: inline-block; padding: 12px 30px; background: #667eea; color: #fff; text-decoration: none; border-radius: 5px; margin: 20px 0; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔔 إشعار رمز OTP</h1>
            <p style="margin: 5px 0 0 0; opacity: 0.9;">للمطعم</p>
        </div>
        
        <div class="content">
            <p>عزيزي المستخدم،</p>
            
            <p>تم إرسال رمز OTP (كلمة مرور لمرة واحدة) إلى بريدك الإلكتروني لتأكيد البريد الإلكتروني.</p>
            
            <div class="otp-notice">
                <h3>📱 تحقق من بريدك الإلكتروني</h3>
                <p style="margin: 0;">يرجى التحقق من رسائل بريدك الإلكتروني للحصول على رمز OTP.</p>
                <p style="margin: 5px 0 0 0; font-size: 14px; color: #6c757d;">ينتهي صلاحية OTP في 10 دقائق</p>
            </div>
            
            <div class="info-box">
                <h3 style="margin-top: 0; color: #667eea;">🔑 تفاصيل الرمز</h3>
                <div class="info-row">
                    <span class="info-label">رمز OTP:</span>
                    <span class="info-value"><strong>{{ $otp }}</strong></span>
                </div>
            </div>
            
            <div class="warning-box">
                <strong>⚠️ إشعار أمان:</strong>
                <ul style="margin: 10px 0 0 0; padding-right: 20px;">
                    <li>لا تشارك رمز OTP مع أي شخص</li>
                    <li>لن يطلب موظفونا رمز OTP الخاص بك</li>
                    <li>أكمل العملية في غضون 10 دقائق</li>
                    <li>إذا لم تبدأ هذه العملية، اتصل بالدعم فورًا</li>
                </ul>
            </div>
            
            <p style="margin-top: 30px;">شكرًا لاستخدامك للمطعم!</p>
        </div>
        
        <div class="footer">
            <p>هذه رسالة آلية. يرجى عدم الرد على هذا البريد الإلكتروني.</p>
            <p>&copy; {{ date('Y') }} للمطعم. جميع الحقوق محفوظة.</p>
            <div class="social-links" style="margin-top: 10px;">
                <a href="#" style="color: #6c757d; margin: 0 10px;">فيسبوك</a> |
                <a href="#" style="color: #6c757d; margin: 0 10px;">انستغرام</a> |
                <a href="#" style="color: #6c757d; margin: 0 10px;">يوتيوب</a>
            </div>
            <div class="legal-links" style="margin-top: 10px;">
                <a href="https://modernhome-ye.com/privacy" style="color: #6c757d; margin: 0 10px;">سياسة الخصوصية</a> |
                <a href="https://modernhome-ye.com/terms" style="color: #6c757d; margin: 0 10px;">شروط الخدمة</a> |
                <a href="https://modernhome-ye.com/contact" style="color: #6c757d; margin: 0 10px;">اتصل بنا</a>
            </div>
        </div>
    </div>
</body>
</html>