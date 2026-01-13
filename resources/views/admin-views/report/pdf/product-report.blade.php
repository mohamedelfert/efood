<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ translate('product_report') }}</title>
    <style>
        @page {
            margin: 20px;
        }
        body {
            font-family: 'DejaVu Sans', 'Arial Unicode MS', sans-serif;
            font-size: 11px;
            line-height: 1.6;
            direction: rtl;
            text-align: right;
            unicode-bidi: bidi-override;
        }
        * {
            unicode-bidi: embed;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
        }
        .header h1 {
            margin: 10px 0;
            font-size: 18px;
            font-weight: bold;
        }
        .header p {
            margin: 5px 0;
            color: #555;
            font-size: 11px;
        }
        .stats-container {
            width: 100%;
            margin: 20px 0;
        }
        .stats-row {
            width: 100%;
            display: table;
            margin-bottom: 10px;
        }
        .stat-box {
            display: table-cell;
            width: 33.33%;
            border: 1px solid #ddd;
            padding: 10px;
            text-align: center;
            vertical-align: middle;
        }
        .stat-box h3 {
            margin: 5px 0;
            font-size: 18px;
            color: #333;
            font-weight: bold;
        }
        .stat-box p {
            margin: 5px 0;
            font-size: 10px;
            color: #666;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            direction: rtl;
        }
        th, td {
            border: 1px solid #ccc;
            padding: 8px 6px;
            text-align: right;
            font-size: 10px;
            vertical-align: middle;
        }
        th {
            background-color: #f5f5f5;
            font-weight: bold;
            color: #333;
        }
        tr:nth-child(even) {
            background-color: #fafafa;
        }
        .total-row {
            background-color: #e8f5e9 !important;
            font-weight: bold;
            border-top: 2px solid #333 !important;
        }
        .order-badge {
            background-color: #2196f3;
            color: #fff;
            padding: 3px 8px;
            border-radius: 3px;
            font-weight: bold;
            display: inline-block;
            font-size: 10px;
        }
        .quantity-badge {
            background-color: #4caf50;
            color: #fff;
            padding: 3px 8px;
            border-radius: 3px;
            font-weight: bold;
            display: inline-block;
            font-size: 10px;
        }
        .customer-name {
            font-weight: bold;
            color: #333;
        }
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 9px;
            color: #666;
            border-top: 1px solid #ddd;
            padding-top: 10px;
        }
        .text-center {
            text-align: center;
        }
        .amount-cell {
            font-weight: bold;
            color: #1976d2;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{\App\CentralLogics\Helpers::get_business_settings('restaurant_name')}}</h1>
        <h1>{{ translate('product_report') }}</h1>
        <p><strong>{{ translate('generated') }}:</strong> {{ date('d M Y, h:i A') }}</p>
        <p><strong>{{ translate('generated_by') }}:</strong> {{ auth('admin')->user()->name }}</p>
    </div>

    @php
        $totalOrders = isset($data) ? count($data) : 0;
        $totalQty = isset($data) ? array_sum(array_column($data, 'quantity')) : 0;
        $totalAmount = isset($data) ? array_sum(array_column($data, 'price')) : 0;
    @endphp

    <div class="stats-container">
        <div class="stats-row">
            <div class="stat-box">
                <h3>{{ $totalOrders }}</h3>
                <p>{{ translate('total_orders') }}</p>
            </div>
            <div class="stat-box">
                <h3>{{ $totalQty }}</h3>
                <p>{{ translate('total_items') }}</p>
            </div>
            <div class="stat-box">
                <h3>{{ \App\CentralLogics\Helpers::set_symbol($totalAmount) }}</h3>
                <p>{{ translate('total_amount') }}</p>
            </div>
        </div>
    </div>

    @if(isset($data) && count($data) > 0)
    <table>
        <thead>
            <tr>
                <th style="width: 5%;">{{ translate('serial') }}</th>
                <th style="width: 15%;">{{ translate('order_number') }}</th>
                <th style="width: 25%;">{{ translate('customer') }}</th>
                <th style="width: 25%;">{{ translate('date') }}</th>
                <th style="width: 15%;" class="text-center">{{ translate('quantity') }}</th>
                <th style="width: 15%;" class="text-center">{{ translate('amount') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach($data as $key => $row)
            <tr>
                <td class="text-center">{{ $key + 1 }}</td>
                <td><span class="order-badge">#{{ $row['order_id'] }}</span></td>
                <td>
                    @if($row['customer'])
                        <span class="customer-name">{{ $row['customer']->f_name }} {{ $row['customer']->l_name }}</span>
                    @else
                        <span style="color: #f44336;">{{ translate('invalid_customer') }}</span>
                    @endif
                </td>
                <td>{{ date('d M Y, h:i A', strtotime($row['date'])) }}</td>
                <td class="text-center">
                    <span class="quantity-badge">{{ $row['quantity'] }}</span>
                </td>
                <td class="text-center">
                    <span class="amount-cell">{{ \App\CentralLogics\Helpers::set_symbol($row['price']) }}</span>
                </td>
            </tr>
            @endforeach
            <tr class="total-row">
                <td colspan="4" class="text-center">{{ translate('total') }}</td>
                <td class="text-center">{{ $totalQty }}</td>
                <td class="text-center">{{ \App\CentralLogics\Helpers::set_symbol($totalAmount) }}</td>
            </tr>
        </tbody>
    </table>
    @else
    <p style="text-align: center; padding: 20px; color: #666;">{{ translate('no_data_available') }}</p>
    @endif

    <div class="footer">
        <p>{{ translate('system_generated_report') }} {{ date('d M Y, h:i A') }}</p>
    </div>
</body>
</html>