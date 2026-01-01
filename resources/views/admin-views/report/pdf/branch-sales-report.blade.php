<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>{{$reportType}}</title>
    <style>
        @page {
            margin: 12px;
            size: A4 landscape;
        }
        
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 10px;
            color: #2d3748;
            direction: ltr;
            line-height: 1.5;
        }
        
        .header {
            text-align: center;
            margin-bottom: 18px;
            border-bottom: 3px solid #1e3a8a;
            padding-bottom: 10px;
            background: linear-gradient(to bottom, #f7fafc 0%, #ffffff 100%);
        }
        
        .header h1 {
            margin: 0 0 6px 0;
            font-size: 22px;
            color: #1e3a8a;
            font-weight: bold;
            letter-spacing: 0.5px;
        }
        
        .header h2 {
            margin: 4px 0;
            font-size: 16px;
            color: #4a5568;
            font-weight: 600;
        }
        
        .header p {
            margin: 4px 0;
            font-size: 13px;
            color: #718096;
            font-weight: 500;
        }
        
        .info-box {
            background: linear-gradient(to right, #f7fafc 0%, #edf2f7 100%);
            padding: 10px 12px;
            margin-bottom: 14px;
            border-radius: 4px;
            border-left: 4px solid #1e3a8a;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }
        
        .info-box table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .info-box td {
            padding: 4px 8px;
            font-size: 9px;
            line-height: 1.6;
        }
        
        .info-label {
            font-weight: bold;
            color: #1e3a8a;
            width: 110px;
            text-align: left;
        }
        
        .info-value {
            color: #4a5568;
            text-align: right;
        }
        
        .stats-box {
            margin-bottom: 14px;
        }
        
        .stats-box table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .stat-cell {
            width: 33.33%;
            padding: 3px;
        }
        
        .stat-content {
            padding: 11px 8px;
            text-align: center;
            border-radius: 6px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .stat-label {
            font-size: 9px;
            color: #ffffff;
            margin-bottom: 4px;
            font-weight: 600;
            opacity: 0.95;
        }
        
        .stat-value {
            font-size: 19px;
            font-weight: bold;
            color: #ffffff;
            text-shadow: 0 1px 2px rgba(0,0,0,0.1);
        }
        
        .stat-cell:nth-child(1) .stat-content {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .stat-cell:nth-child(2) .stat-content {
            background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
        }
        
        .stat-cell:nth-child(3) .stat-content {
            background: linear-gradient(135deg, #30cfd0 0%, #330867 100%);
        }
        
        .main-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.08);
        }
        
        .main-table th {
            background: linear-gradient(to bottom, #2d4a99 0%, #1e3a8a 100%);
            color: #ffffff;
            padding: 8px 5px;
            font-size: 9px;
            font-weight: bold;
            border: 1px solid #1a2f6a;
            text-align: center;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }
        
        .main-table td {
            padding: 6px 5px;
            border: 1px solid #e2e8f0;
            font-size: 8.5px;
            text-align: center;
            vertical-align: middle;
            background-color: #ffffff;
        }
        
        .main-table tbody tr:nth-child(even) td {
            background-color: #f7fafc;
        }
        
        .total-row td {
            background: linear-gradient(to left, #e6fffa 0%, #b2f5ea 100%) !important;
            font-weight: bold;
            border-top: 2px solid #1e3a8a !important;
            padding: 8px 5px;
            font-size: 9px;
        }
        
        .order-id {
            font-weight: bold;
            color: #1e3a8a;
            font-size: 9px;
            background-color: #ebf4ff;
            padding: 2px 6px;
            border-radius: 3px;
            display: inline-block;
        }
        
        .date-cell {
            color: #4a5568;
            font-size: 8px;
            font-family: 'DejaVu Sans Mono', monospace;
        }
        
        .amount-cell {
            font-weight: bold;
            color: #1e3a8a;
            font-size: 9px;
            font-family: 'DejaVu Sans Mono', monospace;
        }
        
        .badge {
            padding: 3px 7px;
            border-radius: 3px;
            font-size: 8px;
            font-weight: bold;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            display: inline-block;
            box-shadow: 0 1px 2px rgba(0,0,0,0.1);
        }
        
        .badge-success {
            background: linear-gradient(135deg, #48bb78 0%, #38a169 100%);
        }
        
        .footer {
            margin-top: 18px;
            text-align: center;
            font-size: 8px;
            color: #a0aec0;
            border-top: 2px solid #e2e8f0;
            padding-top: 10px;
            background-color: #f7fafc;
        }
        
        .text-left { text-align: left; }
        .text-right { text-align: right; }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{\App\CentralLogics\Helpers::get_business_settings('restaurant_name')}}</h1>
        <h2>{{$reportType}}</h2>
        <p>{{$branch ? $branch->name : 'All Branches'}}</p>
    </div>

    <div class="info-section">
        <div class="info-row">
            <span class="info-label">Date Range:</span>
            <span>{{$dateRange}}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Generated On:</span>
            <span>{{date('d M Y, h:i A')}}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Generated By:</span>
            <span>{{auth('admin')->user()->name}}</span>
        </div>
    </div>

    @php
        $totalOrders = isset($data) ? count($data) : 0;
        $totalQty = isset($data) ? array_sum(array_column($data, 'quantity')) : 0;
        $totalAmount = isset($data) ? array_sum(array_column($data, 'price')) : 0;
    @endphp

    <div class="summary-section">
        <div class="summary-grid">
            <div class="summary-item">
                <div class="summary-label">Total Orders</div>
                <div class="summary-value">{{$totalOrders}}</div>
            </div>
            <div class="summary-item">
                <div class="summary-label">Total Items</div>
                <div class="summary-value">{{$totalQty}}</div>
            </div>
            <div class="summary-item">
                <div class="summary-label">Total Amount</div>
                <div class="summary-value">{{\App\CentralLogics\Helpers::set_symbol($totalAmount)}}</div>
            </div>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 8%;">SL</th>
                <th style="width: 15%;">Order ID</th>
                <th style="width: 25%;">Date & Time</th>
                <th style="width: 15%;" class="text-center">Quantity</th>
                <th style="width: 20%;" class="text-right">Amount</th>
            </tr>
        </thead>
        <tbody>
            @if(isset($data) && count($data) > 0)
                @foreach($data as $key => $row)
                <tr>
                    <td>{{$key + 1}}</td>
                    <td><strong>#{{$row['order_id']}}</strong></td>
                    <td>{{date('d M Y, h:i A', strtotime($row['date']))}}</td>
                    <td class="text-center">
                        <span class="badge">{{$row['quantity']}} items</span>
                    </td>
                    <td class="text-right"><strong>{{\App\CentralLogics\Helpers::set_symbol($row['price'])}}</strong></td>
                </tr>
                @endforeach
                <tr style="background-color: #e8f4f8; font-weight: bold;">
                    <td colspan="3" style="text-align: right; padding-right: 15px;">TOTAL:</td>
                    <td class="text-center">{{$totalQty}} items</td>
                    <td class="text-right">{{\App\CentralLogics\Helpers::set_symbol($totalAmount)}}</td>
                </tr>
            @else
                <tr>
                    <td colspan="5" style="text-align: center; padding: 30px;">No data available</td>
                </tr>
            @endif
        </tbody>
    </table>

    <div class="footer">
        <p>This is a computer-generated report. Generated on {{date('d M Y, h:i A')}} by {{\App\CentralLogics\Helpers::get_business_settings('restaurant_name')}}</p>
    </div>
</body>
</html>