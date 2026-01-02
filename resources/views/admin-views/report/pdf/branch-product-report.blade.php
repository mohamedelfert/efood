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
            font-size: 9px;
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
            width: 33.33%;
            padding: 3px;
        }
        
        .stat-content {
            padding: 11px 8px;
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
            font-size: 19px;
            font-weight: bold;
        }
        
        .stat-cell:nth-child(1) .stat-content {
            background-color: #e6e6fa;
            border-color: #667eea;
            color: #4c51bf;
        }
        
        .stat-cell:nth-child(2) .stat-content {
            background-color: #fef5e7;
            border-color: #f6ad55;
            color: #975a16;
        }
        
        .stat-cell:nth-child(3) .stat-content {
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
            padding: 7px 4px;
            font-size: 8.5px;
            font-weight: bold;
            border: 1px solid #1a2f6a;
            text-align: center;
        }
        
        .main-table td {
            padding: 5px 4px;
            border: 1px solid #e2e8f0;
            font-size: 8px;
            text-align: center;
            vertical-align: middle;
            background-color: #ffffff;
        }
        
        .main-table tbody tr:nth-child(even) td {
            background-color: #f7fafc;
        }
        
        .total-row td {
            background-color: #b2f5ea !important;
            font-weight: bold;
            border-top: 2px solid #1e3a8a !important;
            padding: 7px 4px;
            font-size: 8.5px;
        }
        
        .order-id {
            font-weight: bold;
            color: #1e3a8a;
            font-size: 8px;
            background-color: #ebf4ff;
            padding: 2px 5px;
            border-radius: 3px;
            display: inline-block;
        }
        
        .product-name {
            color: #2d3748;
            font-weight: 600;
            font-size: 8px;
        }
        
        .customer-cell {
            text-align: center;
            line-height: 1.4;
        }
        
        .customer-name {
            color: #2d3748;
            font-weight: 600;
            display: block;
            font-size: 8px;
        }
        
        .phone {
            color: #718096;
            font-size: 7px;
            display: block;
            margin-top: 1px;
        }
        
        .date-cell {
            color: #4a5568;
            font-size: 7.5px;
        }
        
        .amount-cell {
            font-weight: bold;
            color: #1e3a8a;
            font-size: 8.5px;
        }
        
        .badge {
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 7.5px;
            font-weight: bold;
            display: inline-block;
        }
        
        .badge-info {
            background-color: #bee3f8;
            color: #2c5282;
        }
        
        .badge-success {
            background-color: #c6f6d5;
            color: #22543d;
        }
        
        .footer {
            margin-top: 18px;
            text-align: center;
            font-size: 8px;
            color: #a0aec0;
            border-top: 2px solid #e2e8f0;
            padding-top: 10px;
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
                <td class="info-value" style="text-align: right;">{{$dateRange}}</td>
            </tr>
            <tr>
                <td class="info-label">Generated On:</td>
                <td class="info-value" style="text-align: right;">{{date('d M Y, h:i A')}}</td>
            </tr>
            <tr>
                <td class="info-label">Generated By:</td>
                <td class="info-value" style="text-align: right;">{{auth('admin')->user()->name}}</td>
            </tr>
        </table>
    </div>

    @php
        $totalOrders = isset($data) ? count($data) : 0;
        $totalQty = isset($data) ? array_sum(array_column($data, 'quantity')) : 0;
        $totalAmount = isset($data) ? array_sum(array_column($data, 'price')) : 0;
    @endphp

    <div class="stats-box">
        <table>
            <tr>
                <td class="stat-cell">
                    <div class="stat-content">
                        <div class="stat-label">Total Orders</div>
                        <div class="stat-value">{{$totalOrders}}</div>
                    </div>
                </td>
                <td class="stat-cell">
                    <div class="stat-content">
                        <div class="stat-label">Total Items Sold</div>
                        <div class="stat-value">{{$totalQty}}</div>
                    </div>
                </td>
                <td class="stat-cell">
                    <div class="stat-content">
                        <div class="stat-label">Total Revenue</div>
                        <div class="stat-value">{{\App\CentralLogics\Helpers::set_symbol($totalAmount)}}</div>
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <table class="main-table">
        <thead>
            <tr>
                <th style="width: 5%;">SL</th>
                <th style="width: 10%;">Order ID</th>
                <th style="width: 18%;">Product</th>
                <th style="width: 12%;">Branch</th>
                <th style="width: 18%;">Customer</th>
                <th style="width: 15%;">Date</th>
                <th style="width: 10%;" class="text-center">Qty</th>
                <th style="width: 12%;" class="text-right">Amount</th>
            </tr>
        </thead>
        <tbody>
            @if(isset($data) && count($data) > 0)
                @foreach($data as $key => $row)
                <tr>
                    <td>{{$key + 1}}</td>
                    <td><span class="order-id">#{{$row['order_id']}}</span></td>
                    <td><span class="product-name">{{$row['product_name']}}</span></td>
                    <td>
                        @if(isset($row['branch']) && $row['branch'])
                            <span class="badge badge-info">{{$row['branch']->name}}</span>
                        @else
                            <span style="color: #a0aec0;">N/A</span>
                        @endif
                    </td>
                    <td class="customer-cell">
                        @if(isset($row['customer']) && $row['customer'])
                            <span class="customer-name">{{$row['customer']->f_name}} {{$row['customer']->l_name}}</span>
                            <span class="phone">{{$row['customer']->phone}}</span>
                        @else
                            <span style="color: #f56565; font-size: 7.5px;">Invalid Customer</span>
                        @endif
                    </td>
                    <td><span class="date-cell">{{date('d M Y, h:i A', strtotime($row['date']))}}</span></td>
                    <td class="text-center">
                        <span class="badge badge-success">{{$row['quantity']}}</span>
                    </td>
                    <td class="text-right"><span class="amount-cell">{{\App\CentralLogics\Helpers::set_symbol($row['price'])}}</span></td>
                </tr>
                @endforeach
                <tr class="total-row">
                    <td colspan="6" class="text-right" style="padding-right: 10px;">TOTAL:</td>
                    <td class="text-center">{{$totalQty}}</td>
                    <td class="text-right">{{\App\CentralLogics\Helpers::set_symbol($totalAmount)}}</td>
                </tr>
            @else
                <tr>
                    <td colspan="8" style="padding: 30px; color: #a0aec0;">No data available</td>
                </tr>
            @endif
        </tbody>
    </table>

    <div class="footer">
        This is a computer-generated report. Generated on {{date('d M Y, h:i A')}} by {{\App\CentralLogics\Helpers::get_business_settings('restaurant_name')}}
    </div>
</body>
</html>