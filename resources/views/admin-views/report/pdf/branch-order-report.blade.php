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
        }
        
        .header h1 {
            margin: 0 0 6px 0;
            font-size: 22px;
            color: #1e3a8a;
            font-weight: bold;
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
        }
        
        .info-box {
            background-color: #f7fafc;
            padding: 10px 12px;
            margin-bottom: 14px;
            border-radius: 4px;
            border-left: 4px solid #1e3a8a;
        }
        
        .info-box table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .info-box td {
            padding: 4px 8px;
            font-size: 9px;
        }
        
        .info-label {
            font-weight: bold;
            color: #1e3a8a;
            width: 110px;
        }
        
        .info-value {
            color: #4a5568;
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
            border: 2px solid;
        }
        
        .stat-label {
            font-size: 9px;
            margin-bottom: 4px;
            font-weight: 600;
        }
        
        .stat-value {
            font-size: 18px;
            font-weight: bold;
        }
        
        .stat-cell:nth-child(1) .stat-content {
            background-color: #e6e6fa;
            border-color: #667eea;
            color: #4c51bf;
        }
        
        .stat-cell:nth-child(2) .stat-content {
            background-color: #c6f6d5;
            border-color: #48bb78;
            color: #22543d;
        }
        
        .stat-cell:nth-child(3) .stat-content {
            background-color: #fed7d7;
            border-color: #f56565;
            color: #742a2a;
        }
        
        .stat-cell:nth-child(4) .stat-content {
            background-color: #bee3f8;
            border-color: #4299e1;
            color: #2c5282;
        }
        
        .main-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        
        .main-table th {
            background-color: #1e3a8a;
            color: #ffffff;
            padding: 8px 5px;
            font-size: 9px;
            font-weight: bold;
            border: 1px solid #1a2f6a;
            text-align: center;
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
        }
        
        .date-cell {
            color: #4a5568;
            font-size: 8px;
        }
        
        .amount-cell {
            font-weight: bold;
            color: #1e3a8a;
            font-size: 9px;
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
        }
        
        .status-delivered { 
            background-color: #c6f6d5; 
            color: #22543d;
        }
        
        .status-pending { 
            background-color: #fef5e7; 
            color: #975a16;
        }
        
        .status-confirmed { 
            background-color: #bee3f8; 
            color: #2c5282;
        }
        
        .status-processing { 
            background-color: #e9d8fd; 
            color: #553c9a;
        }
        
        .status-out_for_delivery { 
            background-color: #bee3f8; 
            color: #2a4365;
        }
        
        .status-canceled { 
            background-color: #fed7d7; 
            color: #742a2a;
        }
        
        .status-returned { 
            background-color: #feebc8; 
            color: #7c2d12;
        }
        
        .status-failed { 
            background-color: #fed7d7; 
            color: #742a2a;
        }
        
        .footer {
            margin-top: 18px;
            text-align: center;
            font-size: 8px;
            color: #a0aec0;
            border-top: 2px solid #e2e8f0;
            padding-top: 10px;
        }
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