<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Reset OTP</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; background-color: #f4f4f4; margin: 0; padding: 0; }
        .container { max-width: 600px; margin: 20px auto; background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: #fff; padding: 30px 20px; text-align: center; }
        .header h1 { margin: 0; font-size: 24px; }
        .content { padding: 30px 20px; }
        .info-box { background: #f8f9fa; border-left: 4px solid #667eea; padding: 15px; margin: 20px 0; border-radius: 4px; }
        .info-row { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #e9ecef; }
        .info-row:last-child { border-bottom: none; }
        .info-label { font-weight: bold; color: #495057; }
        .info-value { color: #212529; }
        .warning-box { background: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 20px 0; border-radius: 4px; }
        .otp-notice { background: #d1ecf1; border-left: 4px solid #0c5460; padding: 15px; margin: 20px 0; border-radius: 4px; text-align: center; }
        .otp-notice h3 { margin: 0 0 10px 0; color: #0c5460; }
        .footer { background: #f8f9fa; padding: 20px; text-align: center; font-size: 12px; color: #6c757d; }
        .button { display: inline-block; padding: 12px 30px; background: #667eea; color: #fff; text-decoration: none; border-radius: 5px; margin: 20px 0; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔔 OTP Notification</h1>
            <p style="margin: 5px 0 0 0; opacity: 0.9;">Restaurent</p>
        </div>
        
        <div class="content">
            <p>Dear User,</p>
            
            <p>An OTP (One-Time Password) has been sent to your email to reset your password.</p>
            
            <div class="otp-notice">
                <h3>📱 Check Your Email</h3>
                <p style="margin: 0;">Please check your email messages for the OTP code.</p>
                <p style="margin: 5px 0 0 0; font-size: 14px; color: #6c757d;">The OTP will expire in 10 minutes</p>
            </div>
            
            <div class="info-box">
                <h3 style="margin-top: 0; color: #667eea;">🔑 Code Details</h3>
                <div class="info-row">
                    <span class="info-label">OTP Code:</span>
                    <span class="info-value"><strong>{{ $otp }}</strong></span>
                </div>
            </div>
            
            <div class="warning-box">
                <strong>⚠️ Security Notice:</strong>
                <ul style="margin: 10px 0 0 0; padding-left: 20px;">
                    <li>Never share your OTP with anyone</li>
                    <li>Our staff will never ask for your OTP</li>
                    <li>Complete the reset within 10 minutes</li>
                    <li>If you did not initiate this, contact support immediately</li>
                </ul>
            </div>
            
            <p style="margin-top: 30px;">Thank you for using Restaurent!</p>
        </div>
        
        <div class="footer">
            <p>This is an automated message. Please do not reply to this email.</p>
            <p>&copy; {{ date('Y') }} Restaurent. All rights reserved.</p>
            <div class="social-links" style="margin-top: 10px;">
                <a href="#" style="color: #6c757d; margin: 0 10px;">Facebook</a> |
                <a href="#" style="color: #6c757d; margin: 0 10px;">Instagram</a> |
                <a href="#" style="color: #6c757d; margin: 0 10px;">YouTube</a>
            </div>
            <div class="legal-links" style="margin-top: 10px;">
                <a href="https://modernhome-ye.com/privacy" style="color: #6c757d; margin: 0 10px;">Privacy Policy</a> |
                <a href="https://modernhome-ye.com/terms" style="color: #6c757d; margin: 0 10px;">Terms of Service</a> |
                <a href="https://modernhome-ye.com/contact" style="color: #6c757d; margin: 0 10px;">Contact Us</a>
            </div>
        </div>
    </div>
</body>
</html>