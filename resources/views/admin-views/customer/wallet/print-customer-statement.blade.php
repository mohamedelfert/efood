<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{translate('Customer Wallet Statement')}} - {{$customer->name}}</title>
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
        }

        .print-header h2 {
            font-size: 18px;
            color: #666;
        }

        .company-info {
            text-align: center;
            margin-bottom: 10px;
            color: #666;
        }

        .customer-info {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 30px;
            border: 1px solid #dee2e6;
        }

        .customer-info h3 {
            margin-bottom: 15px;
            color: #000;
            border-bottom: 2px solid #007bff;
            padding-bottom: 10px;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
        }

        .info-item {
            display: flex;
            justify-content: space-between;
        }

        .info-label {
            font-weight: bold;
            color: #666;
        }

        .info-value {
            color: #000;
        }

        .period-stats {
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
            font-size: 18px;
            font-weight: bold;
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

        .breakdown-section {
            margin-bottom: 30px;
            padding: 20px;
            background-color: #f8f9fa;
            border-radius: 5px;
        }

        .breakdown-section h3 {
            margin-bottom: 15px;
        }

        .breakdown-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
        }

        .breakdown-item {
            border: 1px solid #dee2e6;
            padding: 10px;
            background-color: white;
            border-radius: 3px;
        }

        .breakdown-item h4 {
            font-size: 11px;
            color: #666;
            margin-bottom: 8px;
        }

        .breakdown-item .amount {
            font-size: 14px;
            font-weight: bold;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        table thead th {
            background-color: #343a40;
            color: white;
            border: 1px solid #343a40;
            padding: 10px 8px;
            text-align: left;
            font-size: 11px;
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

        .badge-info {
            background-color: #d1ecf1;
            color: #0c5460;
        }

        .badge-warning {
            background-color: #fff3cd;
            color: #856404;
        }

        .badge-primary {
            background-color: #cce5ff;
            color: #004085;
        }

        .summary-box {
            margin-top: 20px;
            padding: 15px;
            background-color: #e9ecef;
            border-radius: 5px;
            border-left: 4px solid #007bff;
        }

        .summary-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
        }

        .summary-item {
            display: flex;
            justify-content: space-between;
            padding: 5px 0;
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
                size: A4;
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
        🖨️ {{translate('Print Statement')}}
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
        <h2>{{translate('Customer Wallet Statement')}}</h2>
    </div>

    {{-- Customer Information --}}
    <div class="customer-info">
        <h3>{{translate('Customer Information')}}</h3>
        <div class="info-grid">
            <div class="info-item">
                <span class="info-label">{{translate('Customer ID')}}:</span>
                <span class="info-value">#{{$customer->id}}</span>
            </div>
            <div class="info-item">
                <span class="info-label">{{translate('Name')}}:</span>
                <span class="info-value">{{$customer->name}}</span>
            </div>
            <div class="info-item">
                <span class="info-label">{{translate('Phone')}}:</span>
                <span class="info-value">{{$customer->phone}}</span>
            </div>
            <div class="info-item">
                <span class="info-label">{{translate('Email')}}:</span>
                <span class="info-value">{{$customer->email ?? 'N/A'}}</span>
            </div>
            <div class="info-item">
                <span class="info-label">{{translate('Current Balance')}}:</span>
                <span class="info-value" style="font-size: 16px; font-weight: bold; 
                    color: {{$customer->wallet_balance >= 0 ? '#28a745' : '#dc3545'}}">
                    {{Helpers::set_symbol($customer->wallet_balance)}}
                </span>
            </div>
            <div class="info-item">
                <span class="info-label">{{translate('Total Orders')}}:</span>
                <span class="info-value">{{$customer->orders_count ?? 0}}</span>
            </div>
            @if($fromDate && $toDate)
            <div class="info-item">
                <span class="info-label">{{translate('Period From')}}:</span>
                <span class="info-value">{{date('d M Y', strtotime($fromDate))}}</span>
            </div>
            <div class="info-item">
                <span class="info-label">{{translate('Period To')}}:</span>
                <span class="info-value">{{date('d M Y', strtotime($toDate))}}</span>
            </div>
            @endif
        </div>
    </div>

    {{-- Period Statistics --}}
    <div class="period-stats">
        <div class="stat-box">
            <h4>{{translate('Opening Balance')}}</h4>
            <div class="value">{{Helpers::set_symbol($periodStats['opening_balance'])}}</div>
        </div>
        <div class="stat-box success">
            <h4>{{translate('Total Credit')}}</h4>
            <div class="value">{{Helpers::set_symbol($periodStats['total_credit'])}}</div>
        </div>
        <div class="stat-box danger">
            <h4>{{translate('Total Debit')}}</h4>
            <div class="value">{{Helpers::set_symbol($periodStats['total_debit'])}}</div>
        </div>
        <div class="stat-box info">
            <h4>{{translate('Transactions')}}</h4>
            <div class="value">{{$periodStats['transaction_count']}}</div>
        </div>
    </div>

    {{-- Transaction Breakdown --}}
    @if($transactionBreakdown->count() > 0)
    <div class="breakdown-section">
        <h3>{{translate('Transaction Breakdown by Type')}}</h3>
        <div class="breakdown-grid">
            @foreach($transactionBreakdown as $breakdown)
            <div class="breakdown-item">
                <h4>{{translate($breakdown['type'])}}</h4>
                <div style="margin-bottom: 5px;">
                    <small>{{translate('Credit')}}:</small>
                    <span class="amount text-success">{{Helpers::set_symbol($breakdown['total_credit'])}}</span>
                </div>
                <div style="margin-bottom: 5px;">
                    <small>{{translate('Debit')}}:</small>
                    <span class="amount text-danger">{{Helpers::set_symbol($breakdown['total_debit'])}}</span>
                </div>
                <div>
                    <small>{{translate('Count')}}:</small>
                    <span class="amount">{{$breakdown['count']}}</span>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Transaction Table --}}
    <h3 style="margin-bottom: 15px;">{{translate('Transaction History')}}</h3>
    <table>
        <thead>
            <tr>
                <th width="5%">{{translate('SL')}}</th>
                <th width="15%">{{translate('Transaction ID')}}</th>
                <th width="15%">{{translate('Date & Time')}}</th>
                <th width="15%">{{translate('Type')}}</th>
                <th width="15%">{{translate('Reference')}}</th>
                <th width="12%" class="text-right">{{translate('Credit')}}</th>
                <th width="12%" class="text-right">{{translate('Debit')}}</th>
                <th width="11%" class="text-right">{{translate('Balance')}}</th>
            </tr>
        </thead>
        <tbody>
            @foreach($transactions as $key => $transaction)
            <tr>
                <td class="text-center">{{$key + 1}}</td>
                <td>{{$transaction->transaction_id}}</td>
                <td>
                    {{date('d M Y', strtotime($transaction->created_at))}}
                    <br>
                    <small>{{date('h:i A', strtotime($transaction->created_at))}}</small>
                </td>
                <td>
                    <span class="badge badge-{{
                        $transaction->transaction_type == 'add_fund_by_admin' ? 'success' :
                        ($transaction->transaction_type == 'order_place' ? 'info' :
                        ($transaction->transaction_type == 'loyalty_point_to_wallet' ? 'warning' : 'primary'))
                    }}">
                        {{translate($transaction->transaction_type)}}
                    </span>
                </td>
                <td><small>{{$transaction->reference ?? 'N/A'}}</small></td>
                <td class="text-right">
                    @if($transaction->credit > 0)
                    <span class="text-success">+{{Helpers::set_symbol($transaction->credit)}}</span>
                    @else
                    <span>-</span>
                    @endif
                </td>
                <td class="text-right">
                    @if($transaction->debit > 0)
                    <span class="text-danger">-{{Helpers::set_symbol($transaction->debit)}}</span>
                    @else
                    <span>-</span>
                    @endif
                </td>
                <td class="text-right">
                    <strong>{{Helpers::set_symbol($transaction->balance)}}</strong>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    {{-- Summary Box --}}
    <div class="summary-box">
        <h3 style="margin-bottom: 15px;">{{translate('Statement Summary')}}</h3>
        <div class="summary-grid">
            <div class="summary-item">
                <strong>{{translate('Opening Balance')}}:</strong>
                <span>{{Helpers::set_symbol($periodStats['opening_balance'])}}</span>
            </div>
            <div class="summary-item">
                <strong>{{translate('Total Credit')}}:</strong>
                <span class="text-success">{{Helpers::set_symbol($periodStats['total_credit'])}}</span>
            </div>
            <div class="summary-item">
                <strong>{{translate('Total Debit')}}:</strong>
                <span class="text-danger">{{Helpers::set_symbol($periodStats['total_debit'])}}</span>
            </div>
            <div class="summary-item">
                <strong>{{translate('Closing Balance')}}:</strong>
                <span style="font-size: 16px; font-weight: bold;">
                    {{Helpers::set_symbol($periodStats['closing_balance'])}}
                </span>
            </div>
        </div>
    </div>

    {{-- Signature Section --}}
    <div class="signature-section">
        <div class="signature-box">
            <div>{{translate('Prepared By')}}</div>
        </div>
        <div class="signature-box">
            <div>{{translate('Checked By')}}</div>
        </div>
        <div class="signature-box">
            <div>{{translate('Customer Signature')}}</div>
        </div>
    </div>

    {{-- Footer --}}
    <div class="footer">
        <p>{{translate('This is a computer generated statement and does not require a signature')}}</p>
        <p>{{translate('Statement Generated on')}}: {{now()->format('d M Y, h:i A')}}</p>
    </div>
</body>
</html>