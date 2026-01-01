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
            width: 25%;
            padding: 3px;
        }
        
        .stat-content {
            padding: 10px 8px;
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
            font-size: 18px;
            font-weight: bold;
            color: #ffffff;
            text-shadow: 0 1px 2px rgba(0,0,0,0.1);
        }
        
        .stat-cell:nth-child(1) .stat-content {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .stat-cell:nth-child(2) .stat-content {
            background: linear-gradient(135deg, #48bb78 0%, #38a169 100%);
        }
        
        .stat-cell:nth-child(3) .stat-content {
            background: linear-gradient(135deg, #f56565 0%, #e53e3e 100%);
        }
        
        .stat-cell:nth-child(4) .stat-content {
            background: linear-gradient(135deg, #4299e1 0%, #3182ce 100%);
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
            vertical-align: middle;
        }
        
        .main-table td {
            padding: 6px 5px;
            border: 1px solid #e2e8f0;
            font-size: 8.5px;
            text-align: center;
            vertical-align: middle;
            background-color: #ffffff;
            word-wrap: break-word;
        }
        
        .main-table tbody tr:nth-child(even) td {
            background-color: #f7fafc;
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
        
        .branch-name {
            color: #2d3748;
            font-weight: 600;
            font-size: 9px;
        }
        
        .customer-cell {
            text-align: center;
            line-height: 1.4;
        }
        
        .customer-name {
            color: #2d3748;
            font-weight: 600;
            display: block;
        }
        
        .phone {
            color: #718096;
            font-size: 7.5px;
            display: block;
            margin-top: 2px;
            font-family: 'DejaVu Sans Mono', monospace;
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
        
        .payment-cell {
            color: #2d3748;
            font-weight: 500;
            font-size: 8.5px;
        }
        
        .status-badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 8px;
            font-weight: bold;
            display: inline-block;
            white-space: nowrap;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }
        
        .status-delivered { 
            background-color: #c6f6d5; 
            color: #22543d;
            border: 1px solid #9ae6b4;
        }
        
        .status-pending { 
            background-color: #fef5e7; 
            color: #975a16;
            border: 1px solid #fbd38d;
        }
        
        .status-confirmed { 
            background-color: #bee3f8; 
            color: #2c5282;
            border: 1px solid #90cdf4;
        }
        
        .status-processing { 
            background-color: #e9d8fd; 
            color: #553c9a;
            border: 1px solid #d6bcfa;
        }
        
        .status-out_for_delivery { 
            background-color: #bee3f8; 
            color: #2a4365;
            border: 1px solid #90cdf4;
        }
        
        .status-canceled { 
            background-color: #fed7d7; 
            color: #742a2a;
            border: 1px solid #fc8181;
        }
        
        .status-returned { 
            background-color: #feebc8; 
            color: #7c2d12;
            border: 1px solid #fbd38d;
        }
        
        .status-failed { 
            background-color: #fed7d7; 
            color: #742a2a;
            border: 1px solid #fc8181;
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
        .text-center { text-align: center; }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{\App\CentralLogics\Helpers::get_business_settings('restaurant_name')}}</h1>
        <h2>{{$reportType}}</h2>
        <p>{{$branch ? $branch->name : 'All Branches'}}</p>
    </div>

    <div class="info-box">
        <table>
            <tr>
                <td class="info-label">Date Range:</td>
                <td class="info-value">{{$dateRange}}</td>
            </tr>
            <tr>
                <td class="info-label">Generated On:</td>
                <td class="info-value">{{date('d M Y, h:i A')}}</td>
            </tr>
            <tr>
                <td class="info-label">Generated By:</td>
                <td class="info-value">{{auth('admin')->user()->name}}</td>
            </tr>
        </table>
    </div>

    @if(isset($data['stats']))
    <div class="stats-box">
        <table>
            <tr>
                <td class="stat-cell">
                    <div class="stat-content">
                        <div class="stat-label">Total Orders</div>
                        <div class="stat-value">{{$data['stats']['total']}}</div>
                    </div>
                </td>
                <td class="stat-cell">
                    <div class="stat-content">
                        <div class="stat-label">Delivered</div>
                        <div class="stat-value">{{$data['stats']['delivered']}}</div>
                    </div>
                </td>
                <td class="stat-cell">
                    <div class="stat-content">
                        <div class="stat-label">Canceled</div>
                        <div class="stat-value">{{$data['stats']['canceled']}}</div>
                    </div>
                </td>
                <td class="stat-cell">
                    <div class="stat-content">
                        <div class="stat-label">Total Amount</div>
                        <div class="stat-value">{{\App\CentralLogics\Helpers::set_symbol($data['stats']['total_amount'])}}</div>
                    </div>
                </td>
            </tr>
        </table>
    </div>
    @endif

    <table class="main-table">
        <thead>
            <tr>
                <th style="width: 3%;">SL</th>
                <th style="width: 8%;">Order ID</th>
                <th style="width: 10%;">Branch</th>
                <th style="width: 15%;">Customer</th>
                <th style="width: 14%;">Date</th>
                <th style="width: 10%;">Amount</th>
                <th style="width: 12%;">Payment</th>
                <th style="width: 11%;">Status</th>
            </tr>
        </thead>
        <tbody>
            @if(isset($data['orders']) && count($data['orders']) > 0)
                @foreach($data['orders'] as $key => $order)
                <tr>
                    <td>{{$key + 1}}</td>
                    <td><span class="order-id">#{{$order->id}}</span></td>
                    <td>
                        <span class="branch-name">
                            @if(isset($order->branch) && $order->branch)
                                {{$order->branch->name}}
                            @elseif(isset($order->branch_id) && $order->branch_id)
                                Branch #{{$order->branch_id}}
                            @else
                                Main Branch
                            @endif
                        </span>
                    </td>
                    <td class="customer-cell">
                        @if($order->customer)
                            <span class="customer-name">{{$order->customer->f_name}} {{$order->customer->l_name}}</span>
                            <span class="phone">{{$order->customer->phone}}</span>
                        @else
                            <span style="color: #f56565; font-size: 8px;">Invalid Customer</span>
                        @endif
                    </td>
                    <td><span class="date-cell">{{date('d M Y, h:i A', strtotime($order->created_at))}}</span></td>
                    <td><span class="amount-cell">{{\App\CentralLogics\Helpers::set_symbol($order->order_amount)}}</span></td>
                    <td><span class="payment-cell">{{ucfirst(str_replace('_', ' ', $order->payment_method))}}</span></td>
                    <td>
                        <span class="status-badge status-{{$order->order_status}}">
                            {{ucfirst(str_replace('_', ' ', $order->order_status))}}
                        </span>
                    </td>
                </tr>
                @endforeach
            @else
                <tr>
                    <td colspan="8" style="padding: 25px; color: #a0aec0;">No data available</td>
                </tr>
            @endif
        </tbody>
    </table>

    <div class="footer">
        This is a computer-generated report | Generated on {{date('d M Y, h:i A')}} | By {{\App\CentralLogics\Helpers::get_business_settings('restaurant_name')}}
    </div>
</body>
</html>