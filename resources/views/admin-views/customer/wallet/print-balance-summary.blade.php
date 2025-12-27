<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{translate('Wallet Balance Summary Report')}}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Arial', sans-serif;
            font-size: 12px;
            line-height: 1.6;
            color: #333;
            padding: 20px;
        }

        .print-header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 3px solid #000;
            padding-bottom: 20px;
        }

        .print-header h1 {
            font-size: 24px;
            margin-bottom: 5px;
            color: #000;
        }

        .print-header h2 {
            font-size: 18px;
            color: #666;
            margin-bottom: 10px;
        }

        .company-info {
            text-align: center;
            margin-bottom: 10px;
            color: #666;
        }

        .report-meta {
            text-align: right;
            margin-bottom: 20px;
            font-size: 11px;
            color: #666;
        }

        .statistics-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
            margin-bottom: 30px;
        }

        .stat-box {
            border: 1px solid #ddd;
            padding: 15px;
            text-align: center;
            border-radius: 5px;
        }

        .stat-box h4 {
            font-size: 11px;
            color: #666;
            margin-bottom: 8px;
            text-transform: uppercase;
        }

        .stat-box .value {
            font-size: 20px;
            font-weight: bold;
            color: #000;
        }

        .stat-box.success .value {
            color: #28a745;
        }

        .stat-box.danger .value {
            color: #dc3545;
        }

        .stat-box.info .value {
            color: #17a2b8;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        table thead th {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            padding: 10px 8px;
            text-align: left;
            font-weight: bold;
            font-size: 11px;
            text-transform: uppercase;
        }

        table tbody td {
            border: 1px solid #dee2e6;
            padding: 8px;
            font-size: 11px;
        }

        table tbody tr:nth-child(even) {
            background-color: #f8f9fa;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .text-success {
            color: #28a745;
            font-weight: bold;
        }

        .text-danger {
            color: #dc3545;
            font-weight: bold;
        }

        .text-muted {
            color: #6c757d;
        }

        .badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 10px;
            font-weight: 600;
        }

        .badge-success {
            background-color: #d4edda;
            color: #155724;
        }

        .badge-danger {
            background-color: #f8d7da;
            color: #721c24;
        }

        .badge-secondary {
            background-color: #e2e3e5;
            color: #383d41;
        }

        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 2px solid #000;
            text-align: center;
            font-size: 10px;
            color: #666;
        }

        .signature-section {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 30px;
            margin-top: 60px;
            text-align: center;
        }

        .signature-box {
            border-top: 1px solid #000;
            padding-top: 10px;
            margin-top: 50px;
        }

        @media print {
            body {
                padding: 10px;
            }

            .no-print {
                display: none;
            }

            @page {
                size: A4 landscape;
                margin: 10mm;
            }
        }

        .print-button {
            position: fixed;
            top: 10px;
            right: 10px;
            padding: 10px 20px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
        }

        .print-button:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>
    <button class="print-button no-print" onclick="window.print()">
        🖨️ {{translate('Print Report')}}
    </button>

    {{-- Header --}}
    <div class="print-header">
        <h1>{{$businessInfo['restaurant_name'] ?? config('app.name')}}</h1>
        <div class="company-info">
            @if(isset($businessInfo['restaurant_address']))
            <div>{{$businessInfo['restaurant_address']}}</div>
            @endif
            @if(isset($businessInfo['restaurant_phone']))
            <div>{{translate('Phone')}}: {{$businessInfo['restaurant_phone']}}</div>
            @endif
            @if(isset($businessInfo['restaurant_email']))
            <div>{{translate('Email')}}: {{$businessInfo['restaurant_email']}}</div>
            @endif
        </div>
        <h2>{{translate('Customer Wallet Balance Summary Report')}}</h2>
    </div>

    {{-- Report Meta Information --}}
    <div class="report-meta">
        <div><strong>{{translate('Report Generated')}}:</strong> {{now()->format('d M Y, h:i A')}}</div>
        <div><strong>{{translate('Generated By')}}:</strong> {{auth()->user()->name ?? 'Admin'}}</div>
    </div>

    {{-- Statistics Summary --}}
    <div class="statistics-grid">
        <div class="stat-box">
            <h4>{{translate('Total Customers')}}</h4>
            <div class="value">{{$statistics['total_customers']}}</div>
        </div>
        <div class="stat-box success">
            <h4>{{translate('Total Balance')}}</h4>
            <div class="value">{{Helpers::set_symbol($statistics['total_balance'])}}</div>
        </div>
        <div class="stat-box info">
            <h4>{{translate('Average Balance')}}</h4>
            <div class="value">{{Helpers::set_symbol($statistics['average_balance'])}}</div>
        </div>
        <div class="stat-box success">
            <h4>{{translate('Positive Balances')}}</h4>
            <div class="value">{{$statistics['positive_balance_count']}}</div>
            <small>{{Helpers::set_symbol($statistics['positive_balance_sum'])}}</small>
        </div>
    </div>

    {{-- Customer Balance Table --}}
    <table>
        <thead>
            <tr>
                <th width="5%">{{translate('SL')}}</th>
                <th width="20%">{{translate('Customer Name')}}</th>
                <th width="15%">{{translate('Phone')}}</th>
                <th width="20%">{{translate('Email')}}</th>
                <th width="12%" class="text-right">{{translate('Wallet Balance')}}</th>
                <th width="10%" class="text-center">{{translate('Status')}}</th>
                <th width="8%" class="text-center">{{translate('Orders')}}</th>
                <th width="10%">{{translate('Last Transaction')}}</th>
            </tr>
        </thead>
        <tbody>
            @foreach($customers as $key => $customer)
            <tr>
                <td class="text-center">{{$key + 1}}</td>
                <td>
                    <strong>{{$customer->name}}</strong><br>
                    <small class="text-muted">ID: #{{$customer->id}}</small>
                </td>
                <td>{{$customer->phone}}</td>
                <td>{{$customer->email ?? 'N/A'}}</td>
                <td class="text-right">
                    <strong class="
                        {{$customer->wallet_balance > 0 ? 'text-success' : 
                          ($customer->wallet_balance < 0 ? 'text-danger' : 'text-muted')}}">
                        {{Helpers::set_symbol($customer->wallet_balance)}}
                    </strong>
                </td>
                <td class="text-center">
                    @if($customer->wallet_balance > 0)
                        <span class="badge badge-success">{{translate('Credit')}}</span>
                    @elseif($customer->wallet_balance < 0)
                        <span class="badge badge-danger">{{translate('Debit')}}</span>
                    @else
                        <span class="badge badge-secondary">{{translate('Zero')}}</span>
                    @endif
                </td>
                <td class="text-center">{{$customer->orders_count ?? 0}}</td>
                <td>
                    @php
                        $lastTransaction = $customer->walletTransactions->first();
                    @endphp
                    @if($lastTransaction && $lastTransaction->created_at)
                        {{date('d M Y', strtotime($lastTransaction->created_at))}}
                    @else
                        N/A
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr style="background-color: #e9ecef; font-weight: bold;">
                <td colspan="4" class="text-right">{{translate('TOTAL')}}:</td>
                <td class="text-right">{{Helpers::set_symbol($customers->sum('wallet_balance'))}}</td>
                <td colspan="3"></td>
            </tr>
        </tfoot>
    </table>

    {{-- Signature Section --}}
    <div class="signature-section">
        <div class="signature-box">
            <div>{{translate('Prepared By')}}</div>
        </div>
        <div class="signature-box">
            <div>{{translate('Checked By')}}</div>
        </div>
        <div class="signature-box">
            <div>{{translate('Approved By')}}</div>
        </div>
    </div>

    {{-- Footer --}}
    <div class="footer">
        <p>{{translate('This is a computer generated report and does not require a signature')}}</p>
        <p>{{translate('Printed on')}}: {{now()->format('d M Y, h:i A')}}</p>
    </div>

    <script>
        // Auto print on load
        window.onload = function() {
            // Uncomment the next line to auto-print
            // window.print();
        }
    </script>
</body>
</html>