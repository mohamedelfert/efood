<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Placed Successfully</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #673AB7; color: white; padding: 20px; text-align: center; }
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
            <div class="success-icon">🎉</div>
            <h1>Order Placed Successfully</h1>
        </div>
        
        <div class="content">
            <p>Dear {{ $data['user_name'] }},</p>
            
            <p>Your order has been placed successfully!</p>
            
            <div class="details">
                <h3>Order Details:</h3>
                <div class="detail-row">
                    <span><strong>Order ID:</strong></span>
                    <span>#{{ $data['order_id'] }}</span>
                </div>
                <div class="detail-row">
                    <span><strong>Order Amount:</strong></span>
                    <span>{{ number_format($data['order_amount'], 2) }} {{ $data['currency'] }}</span>
                </div>
                <div class="detail-row">
                    <span><strong>Payment Method:</strong></span>
                    <span>{{ ucfirst(str_replace('_', ' ', $data['payment_method'])) }}</span>
                </div>
                <div class="detail-row">
                    <span><strong>Order Type:</strong></span>
                    <span>{{ ucfirst(str_replace('_', ' ', $data['order_type'])) }}</span>
                </div>
                <div class="detail-row">
                    <span><strong>Branch:</strong></span>
                    <span>{{ $data['branch_name'] }}</span>
                </div>
                <div class="detail-row">
                    <span><strong>Items:</strong></span>
                    <span>{{ $data['items_count'] }} item(s)</span>
                </div>
                <div class="detail-row">
                    <span><strong>Delivery Date:</strong></span>
                    <span>{{ $data['delivery_date'] }}</span>
                </div>
                <div class="detail-row">
                    <span><strong>Delivery Time:</strong></span>
                    <span>{{ $data['delivery_time'] }}</span>
                </div>
                <div class="detail-row">
                    <span><strong>Date & Time:</strong></span>
                    <span>{{ $data['timestamp'] }}</span>
                </div>
            </div>
            
            <p>You can track your order status in the app.</p>
            <p>Thank you for choosing us!</p>
        </div>
        
        <div class="footer">
            <p>This is an automated email. Please do not reply.</p>
            <p>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
        </div>
    </div>
</body>
</html>