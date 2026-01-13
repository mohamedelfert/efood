<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ translate('order_report') }}</title>
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
            width: 25%;
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
        .order-badge {
            background-color: #2196f3;
            color: #fff;
            padding: 3px 8px;
            border-radius: 3px;
            font-weight: bold;
            display: inline-block;
            font-size: 10px;
        }
        .branch-name {
            font-weight: bold;
            color: #333;
        }
        .customer-name {
            font-weight: bold;
            color: #333;
        }
        .phone-number {
            color: #666;
            font-size: 9px;
        }
        .amount-cell {
            font-weight: bold;
            color: #1976d2;
        }
        .status-badge {
            padding: 3px 8px;
            border-radius: 3px;
            font-weight: bold;
            display: inline-block;
            font-size: 10px;
        }
        .status-delivered { background-color: #c6f6d5; color: #22543d; }
        .status-pending { background-color: #fef5e7; color: #975a16; }
        .status-confirmed { background-color: #bee3f8; color: #2c5282; }
        .status-processing { background-color: #e9d8fd; color: #553c9a; }
        .status-out_for_delivery { background-color: #bee3f8; color: #2a4365; }
        .status-canceled { background-color: #fed7d7; color: #742a2a; }
        .status-returned { background-color: #feebc8; color: #7c2d12; }
        .status-failed { background-color: #fed7d7; color: #742a2a; }
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
    </style>
</head>
<body>
    <div class="header">
        <h1>{{\App\CentralLogics\Helpers::get_business_settings('restaurant_name')}}</h1>
        <h1>{{ translate('order_report') }}</h1>
        @if($branch)
            <p><strong>{{ translate('branch') }}:</strong> {{ $branch->name }}</p>
        @else
            <p><strong>{{ translate('branch') }}:</strong> {{ translate('all_branches') }}</p>
        @endif
        <p><strong>{{ translate('date_range') }}:</strong> {{ $dateRange }}</p>
        <p><strong>{{ translate('generated') }}:</strong> {{ date('d M Y, h:i A') }}</p>
        <p><strong>{{ translate('generated_by') }}:</strong> {{ auth('admin')->user()->name }}</p>
    </div>

    @if(isset($data['stats']))
    <div class="stats-container">
        <div class="stats-row">
            <div class="stat-box">
                <h3>{{ $data['stats']['total'] }}</h3>
                <p>{{ translate('total_orders') }}</p>
            </div>
            <div class="stat-box">
                <h3>{{ $data['stats']['delivered'] }}</h3>
                <p>{{ translate('delivered') }}</p>
            </div>
            <div class="stat-box">
                <h3>{{ $data['stats']['canceled'] }}</h3>
                <p>{{ translate('canceled') }}</p>
            </div>
            <div class="stat-box">
                <h3>{{ \App\CentralLogics\Helpers::set_symbol($data['stats']['total_amount']) }}</h3>
                <p>{{ translate('total_amount') }}</p>
            </div>
        </div>
    </div>
    @endif

    @if(isset($data['orders']) && count($data['orders']) > 0)
    <table>
        <thead>
            <tr>
                <th style="width: 4%;">{{ translate('serial') }}</th>
                <th style="width: 10%;">{{ translate('order_number') }}</th>
                <th style="width: 12%;">{{ translate('branch') }}</th>
                <th style="width: 18%;">{{ translate('customer') }}</th>
                <th style="width: 16%;">{{ translate('date') }}</th>
                <th style="width: 12%;" class="text-center">{{ translate('amount') }}</th>
                <th style="width: 14%;" class="text-center">{{ translate('payment_method') }}</th>
                <th style="width: 14%;" class="text-center">{{ translate('status') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach($data['orders'] as $key => $order)
            <tr>
                <td class="text-center">{{ $key + 1 }}</td>
                <td><span class="order-badge">#{{ $order->id }}</span></td>
                <td>
                    <span class="branch-name">
                        @if(isset($order->branch) && $order->branch)
                            {{ $order->branch->name }}
                        @elseif(isset($order->branch_id) && $order->branch_id)
                            {{ translate('branch') }} #{{ $order->branch_id }}
                        @else
                            {{ translate('main_branch') }}
                        @endif
                    </span>
                </td>
                <td>
                    @if($order->customer)
                        <div>
                            <span class="customer-name">{{ $order->customer->f_name }} {{ $order->customer->l_name }}</span><br>
                            <span class="phone-number">{{ $order->customer->phone }}</span>
                        </div>
                    @else
                        <span style="color: #f44336;">{{ translate('invalid_customer') }}</span>
                    @endif
                </td>
                <td>{{ date('d M Y, h:i A', strtotime($order->created_at)) }}</td>
                <td class="text-center">
                    <span class="amount-cell">{{ \App\CentralLogics\Helpers::set_symbol($order->order_amount) }}</span>
                </td>
                <td class="text-center">{{ translate(str_replace('_', ' ', $order->payment_method)) }}</td>
                <td class="text-center">
                    <span class="status-badge status-{{ $order->order_status }}">
                        {{ translate(str_replace('_', ' ', $order->order_status)) }}
                    </span>
                </td>
            </tr>
            @endforeach
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