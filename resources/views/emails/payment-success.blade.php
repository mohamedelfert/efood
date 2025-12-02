<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Successful</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #4CAF50; color: white; padding: 20px; text-align: center; }
        .content { background: #f9f9f9; padding: 20px; border: 1px solid #ddd; }
        .details { background: white; padding: 15px; margin: 15px 0; border-radius: 5px; }
        .detail-row { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #eee; }
        .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
        .success-icon { font-size: 48px; color: white; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="success-icon">✓</div>
            <h1>Payment Successful</h1>
        </div>
        
        <div class="content">
            <p>Dear {{ $data['user_name'] }},</p>
            
            <p>Your payment has been processed successfully!</p>
            
            <div class="details">
                <h3>Payment Details:</h3>
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
                    <span>{{ ucfirst($data['payment_method']) }}</span>
                </div>
                <div class="detail-row">
                    <span><strong>Purpose:</strong></span>
                    <span>{{ ucfirst(str_replace('_', ' ', $data['purpose'])) }}</span>
                </div>
                @if(!empty($data['reference_id']))
                <div class="detail-row">
                    <span><strong>Reference:</strong></span>
                    <span>{{ $data['reference_id'] }}</span>
                </div>
                @endif
                <div class="detail-row">
                    <span><strong>Date & Time:</strong></span>
                    <span>{{ $data['timestamp'] }}</span>
                </div>
            </div>
            
            <p>Thank you for your payment!</p>
        </div>
        
        <div class="footer">
            <p>This is an automated email. Please do not reply.</p>
            <p>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
        </div>
    </div>
</body>
</html>