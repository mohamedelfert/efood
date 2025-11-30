<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Successful</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; background-color: #f4f4f4; margin: 0; padding: 0; }
        .container { max-width: 600px; margin: 20px auto; background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        .header { background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: #fff; padding: 30px 20px; text-align: center; }
        .header h1 { margin: 0; font-size: 24px; }
        .success-icon { font-size: 48px; margin-bottom: 10px; }
        .content { padding: 30px 20px; }
        .info-box { background: #f8f9fa; border-left: 4px solid #10b981; padding: 15px; margin: 20px 0; border-radius: 4px; }
        .info-row { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #e9ecef; }
        .info-row:last-child { border-bottom: none; }
        .info-label { font-weight: bold; color: #495057; }
        .info-value { color: #212529; }
        .balance-box { background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: #fff; padding: 20px; margin: 20px 0; border-radius: 8px; text-align: center; }
        .balance-box h3 { margin: 0 0 10px 0; font-size: 16px; opacity: 0.9; }
        .balance-box .amount { font-size: 32px; font-weight: bold; margin: 0; }
        .footer { background: #f8f9fa; padding: 20px; text-align: center; font-size: 12px; color: #6c757d; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="success-icon">✅</div>
            <h1>Payment Successful!</h1>
            <p style="margin: 5px 0 0 0; opacity: 0.9;">{{ $app_name }}</p>
        </div>
        
        <div class="content">
            <p>Dear <strong>{{ $user_name }}</strong>,</p>
            
            <p>Great news! Your wallet has been topped up successfully.</p>
            
            <div class="balance-box">
                <h3>💰 New Wallet Balance</h3>
                <p class="amount">{{ $new_balance }} {{ $currency }}</p>
            </div>
            
            <div class="info-box">
                <h3 style="margin-top: 0; color: #10b981;">Transaction Details</h3>
                <div class="info-row">
                    <span class="info-label">Amount Added:</span>
                    <span class="info-value"><strong>{{ $amount }} {{ $currency }}</strong></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Transaction ID:</span>
                    <span class="info-value">{{ $transaction_id }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Date & Time:</span>
                    <span class="info-value">{{ $date }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Status:</span>
                    <span class="info-value" style="color: #10b981;"><strong>✓ Completed</strong></span>
                </div>
            </div>
            
            <p>You can now use your wallet balance to place orders and enjoy our services.</p>
            
            <p style="margin-top: 30px;">Thank you for choosing {{ $app_name }}!</p>
        </div>
        
        <div class="footer">
            <p>For any questions or concerns, please contact our support team.</p>
            <p>&copy; {{ date('Y') }} {{ $app_name }}. All rights reserved.</p>
        </div>
    </div>
</body>
</html>