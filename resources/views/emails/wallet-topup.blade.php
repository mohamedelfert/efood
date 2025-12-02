<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wallet Top-Up Successful</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #4CAF50; color: white; padding: 20px; text-align: center; }
        .content { background: #f9f9f9; padding: 20px; border: 1px solid #ddd; }
        .details { background: white; padding: 15px; margin: 15px 0; border-radius: 5px; }
        .detail-row { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #eee; }
        .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
        .success-icon { font-size: 48px; color: #4CAF50; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="success-icon">✓</div>
            <h1>Wallet Top-Up Successful</h1>
        </div>
        
        <div class="content">
            <p>Dear {{ $data['user_name'] }},</p>
            
            <p>Your wallet has been successfully topped up!</p>
            
            <div class="details">
                <h3>Transaction Details:</h3>
                <div class="detail-row">
                    <span><strong>Transaction ID:</strong></span>
                    <span>{{ $data['transaction_id'] }}</span>
                </div>
                <div class="detail-row">
                    <span><strong>Amount:</strong></span>
                    <span>{{ number_format($data['amount'], 2) }} {{ $data['currency'] }}</span>
                </div>
                <div class="detail-row">
                    <span><strong>Payment Method:</strong></span>
                    <span>{{ ucfirst($data['gateway']) }}</span>
                </div>
                <div class="detail-row">
                    <span><strong>New Balance:</strong></span>
                    <span>{{ number_format($data['new_balance'], 2) }} {{ $data['currency'] }}</span>
                </div>
                <div class="detail-row">
                    <span><strong>Date & Time:</strong></span>
                    <span>{{ $data['timestamp'] }}</span>
                </div>
            </div>
            
            <p>Thank you for using our service!</p>
        </div>
        
        <div class="footer">
            <p>This is an automated email. Please do not reply.</p>
            <p>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
        </div>
    </div>
</body>
</html>