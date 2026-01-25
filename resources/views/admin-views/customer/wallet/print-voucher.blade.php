@extends('layouts.admin.app')

@section('title', translate('Print Voucher'))

@push('css_or_js')
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;700&family=Inter:wght@400;700&display=swap"
        rel="stylesheet">
    <style>
        :root {
            --light-bg: #FDF8F3;
            --dark-coffee: #5C4033;
            --accent-gold: #A07855;
            --border-color: #E6D5C3;
            --text-muted: #8B735B;
        }

        body {
            font-family: 'Inter', 'Cairo', sans-serif !important;
            background-color: #f4f4f4;
            color: var(--dark-coffee);
        }

        @media print {
            .non-printable {
                display: none;
            }

            .printable {
                display: block;
            }

            body {
                background-color: white !important;
                margin: 0 !important;
                padding: 0 !important;
            }

            .invoice-card {
                box-shadow: none !important;
                border: none !important;
                width: 100% !important;
                margin: 0 !important;
            }
        }

        .invoice-card {
            background-color: var(--light-bg);
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
            margin: 20px auto;
            max-width: 600px;
            padding: 40px;
            border: 1px solid var(--border-color);
            position: relative;
        }

        .invoice-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 8px;
            background: linear-gradient(90deg, var(--dark-coffee), var(--accent-gold));
            border-radius: 12px 12px 0 0;
        }

        .header-section {
            text-align: center;
            margin-bottom: 30px;
        }

        .restaurant-logo {
            max-width: 120px;
            margin-bottom: 15px;
        }

        .invoice-title {
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 20px;
            position: relative;
            display: inline-block;
            padding: 0 40px;
        }

        .invoice-title::before,
        .invoice-title::after {
            content: '✧';
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            color: var(--accent-gold);
            font-size: 20px;
        }

        .invoice-title::before {
            left: 0;
        }

        .invoice-title::after {
            right: 0;
        }

        .info-grid {
            margin-bottom: 30px;
            padding: 20px;
            background: rgba(160, 120, 85, 0.05);
            border-radius: 8px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        .info-item {
            font-size: 14px;
            line-height: 1.6;
        }

        .info-label {
            font-weight: 700;
            color: var(--dark-coffee);
        }

        .invoice-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }

        .invoice-table th {
            background-color: var(--dark-coffee);
            color: white;
            padding: 12px;
            text-align: right;
            font-weight: 700;
            font-size: 14px;
        }

        .invoice-table td {
            padding: 15px 12px;
            border-bottom: 1px solid var(--border-color);
            vertical-align: top;
        }

        .summary-section {
            margin-top: 20px;
            border-top: 2px solid var(--dark-coffee);
            padding-top: 20px;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            font-size: 15px;
        }

        .summary-row.total {
            font-size: 24px;
            font-weight: 700;
            color: var(--dark-coffee);
            border-top: 1px solid var(--border-color);
            margin-top: 15px;
            padding-top: 15px;
        }

        .footer-section {
            text-align: center;
            margin-top: 40px;
            padding-top: 30px;
            border-top: 1px dashed var(--accent-gold);
            font-size: 13px;
            color: var(--text-muted);
        }

        .footer-logo {
            font-size: 20px;
            color: var(--dark-coffee);
            margin-bottom: 15px;
        }

        [dir="rtl"] .invoice-table th,
        [dir="rtl"] .invoice-table td {
            text-align: right;
        }

        [dir="ltr"] .invoice-table th,
        [dir="ltr"] .invoice-table td {
            text-align: left;
        }
    </style>
@endpush

@section('content')
<div class="content container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-12 mb-3">
            <div class="text-center">
                <input type="button" class="btn btn-primary non-printable" onclick="printDiv('printableArea')"
                    value="{{translate('Print Voucher')}}" />
                <a href="{{url()->previous()}}" class="btn btn-secondary non-printable">{{translate('Back')}}</a>
            </div>
        </div>

        <div class="invoice-card" id="printableArea">
            <div class="header-section">
                @php($logo = \App\Model\BusinessSetting::where(['key' => 'logo'])->first()->value)
                <img class="restaurant-logo" src="{{asset('storage/app/public/restaurant/' . $logo)}}"
                    onerror="this.src='{{asset('public/assets/admin/img/160x160/img2.jpg')}}'" alt="logo">
                <div>
                    <h1 class="invoice-title">{{translate('إيصال المحفظة')}}</h1>
                </div>
            </div>

            <div class="info-grid">
                <div class="info-item">
                    <span class="info-label">{{translate('رقم المعاملة')}}:</span> {{$transaction['transaction_id']}}
                </div>
                <div class="info-item" style="text-align: left;">
                    {{date('d M, Y h:i a', strtotime($transaction['created_at']))}}
                </div>
                <div class="info-item">
                    <span class="info-label">{{translate('اسم العميل')}}:</span>
                    {{$transaction->user ? $transaction->user['name'] : translate('Customer')}}
                </div>
                <div class="info-item">
                    <span class="info-label">{{translate('الهاتف')}}:</span>
                    {{$transaction->user ? $transaction->user['phone'] : ''}}
                </div>
            </div>

            <table class="invoice-table">
                <thead>
                    <tr>
                        <th>{{translate('الوصف')}}</th>
                        <th style="width: 25%; text-align: left;">{{translate('المبلغ')}}</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>
                            {{translate('إضافة رصيد للمحفظة')}}
                            @if($transaction->reference)
                                <div style="font-size: 12px; color: var(--text-muted);">{{translate('Reference')}}:
                                    {{$transaction->reference}}</div>
                            @endif
                        </td>
                        <td style="text-align: left;">
                            {{ \App\CentralLogics\Helpers::set_symbol($transaction->credit) }}
                        </td>
                    </tr>
                </tbody>
            </table>

            <div class="summary-section">
                <div class="summary-row total">
                    <span>{{translate('الإجمالي')}}</span>
                    <span>{{ \App\CentralLogics\Helpers::set_symbol($transaction->credit) }}</span>
                </div>
                <div class="summary-row" style="margin-top: 10px; font-weight: 700;">
                    <span>{{translate('الرصيد المحفظة الحالي')}}</span>
                    <span>{{ \App\CentralLogics\Helpers::set_symbol($transaction->balance) }}</span>
                </div>
            </div>

            <div class="footer-section">
                <div class="footer-logo">✧ {{translate('شكراً لك')}} ✧</div>
                <div style="font-size: 12px; color: var(--text-muted);">
                    {{\App\Model\BusinessSetting::where(['key' => 'restaurant_name'])->first()->value}}<br>
                    {{\App\Model\BusinessSetting::where(['key' => 'footer_text'])->first()->value}}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('script')
    <script>
        "use strict";
        function printDiv(divName) {
            var printContents = document.getElementById(divName).innerHTML;
            var originalContents = document.body.innerHTML;
            document.body.innerHTML = printContents;
            window.print();
            document.body.innerHTML = originalContents;
            window.location.reload();
        }
    </script>
@endpush