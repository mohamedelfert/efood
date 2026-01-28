<!DOCTYPE html>
<html lang="{{ $language_code ?? 'ar' }}" dir="{{ ($language_code ?? 'ar') == 'ar' ? 'rtl' : 'ltr' }}">

<head>
    <meta charset="utf-8">
    <title>{{ translate('Wallet Top-Up Receipt') }}</title>
    <style>
        body {
            font-family: 'dejavusans', 'Cairo', sans-serif;
            font-size: 12px;
            color: #5C4033;
            margin: 0;
            padding: 0;
            background-color: #f5f5f5;
        }

        .receipt-container {
            max-width: 600px;
            margin: auto;
            background-color: #ffffff;
            border-radius: 0;
        }

        .top-bar {
            height: 10px;
            background: linear-gradient(to right, #A07855, #5C4033, #A07855);
        }

        .content-wrapper {
            padding: 30px 40px;
        }

        .logo-section {
            text-align: center;
            margin-bottom: 25px;
        }

        .logo-box {
            display: inline-block;
            width: 80px;
            height: 80px;
            border: 2px solid #E6D5C3;
            border-radius: 8px;
            background-color: #FDF8F3;
            line-height: 80px;
        }

        .logo-box img {
            max-width: 60px;
            max-height: 60px;
            vertical-align: middle;
        }

        .receipt-title {
            text-align: center;
            font-size: 28px;
            font-weight: bold;
            color: #5C4033;
            margin-bottom: 30px;
        }

        .receipt-title span {
            color: #A07855;
        }

        .info-section {
            background-color: #FDF8F3;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 25px;
        }

        .info-row {
            display: table;
            width: 100%;
            margin-bottom: 12px;
        }

        .info-row:last-child {
            margin-bottom: 0;
        }

        .info-cell {
            display: table-cell;
            width: 50%;
            vertical-align: top;
        }

        .info-cell.left {
            text-align:
                {{ ($language_code ?? 'ar') == 'ar' ? 'right' : 'left' }}
            ;
        }

        .info-cell.right {
            text-align:
                {{ ($language_code ?? 'ar') == 'ar' ? 'left' : 'right' }}
            ;
        }

        .info-label {
            font-weight: bold;
            color: #5C4033;
        }

        .info-value {
            color: #333;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 25px;
        }

        .items-table thead {
            background-color: #5C4033;
            color: #ffffff;
        }

        .items-table th {
            padding: 12px 15px;
            font-weight: bold;
            text-align:
                {{ ($language_code ?? 'ar') == 'ar' ? 'right' : 'left' }}
            ;
        }

        .items-table th:first-child {
            border-radius:
                {{ ($language_code ?? 'ar') == 'ar' ? '0 8px 0 0' : '8px 0 0 0' }}
            ;
        }

        .items-table th:last-child {
            border-radius:
                {{ ($language_code ?? 'ar') == 'ar' ? '8px 0 0 0' : '0 8px 0 0' }}
            ;
            text-align:
                {{ ($language_code ?? 'ar') == 'ar' ? 'left' : 'right' }}
            ;
        }

        .items-table td {
            padding: 15px;
            border-bottom: 1px solid #E6D5C3;
        }

        .items-table td:last-child {
            text-align:
                {{ ($language_code ?? 'ar') == 'ar' ? 'left' : 'right' }}
            ;
        }

        .item-description {
            font-weight: 500;
            color: #5C4033;
        }

        .item-reference {
            font-size: 11px;
            color: #8B735B;
            margin-top: 5px;
        }

        .item-amount {
            font-weight: bold;
            color: #5C4033;
        }

        .divider {
            border: none;
            border-top: 1px solid #E6D5C3;
            margin: 25px 0;
        }

        .total-section {
            margin-bottom: 25px;
        }

        .total-row {
            display: table;
            width: 100%;
            margin-bottom: 15px;
        }

        .total-cell {
            display: table-cell;
            width: 50%;
            vertical-align: middle;
        }

        .total-cell.label {
            text-align:
                {{ ($language_code ?? 'ar') == 'ar' ? 'right' : 'left' }}
            ;
            font-size: 24px;
            font-weight: bold;
            color: #5C4033;
        }

        .total-cell.value {
            text-align:
                {{ ($language_code ?? 'ar') == 'ar' ? 'left' : 'right' }}
            ;
            font-size: 24px;
            font-weight: bold;
            color: #5C4033;
        }

        .balance-row {
            display: table;
            width: 100%;
        }

        .balance-cell {
            display: table-cell;
            width: 50%;
            vertical-align: middle;
        }

        .balance-cell.label {
            text-align:
                {{ ($language_code ?? 'ar') == 'ar' ? 'right' : 'left' }}
            ;
            font-size: 14px;
            color: #5C4033;
        }

        .balance-cell.value {
            text-align:
                {{ ($language_code ?? 'ar') == 'ar' ? 'left' : 'right' }}
            ;
            font-size: 14px;
            color: #5C4033;
        }

        .dashed-divider {
            border: none;
            border-top: 2px dashed #E6D5C3;
            margin: 25px 0;
        }

        .thank-you-section {
            text-align: center;
            margin-bottom: 20px;
        }

        .thank-you-text {
            font-size: 18px;
            color: #5C4033;
        }

        .thank-you-text span {
            color: #A07855;
        }

        .footer-section {
            text-align: center;
            color: #8B735B;
            font-size: 11px;
        }

        .footer-company {
            margin-bottom: 5px;
        }

        .footer-copyright {
            color: #aaa;
        }

        .cashback-row {
            background-color: #fff3cd;
            border-radius: 5px;
        }

        .cashback-row td {
            color: #856404;
        }

        /* RTL Support */
        .rtl {
            direction: rtl;
        }

        .ltr {
            direction: ltr;
        }

        /* Table for better email compatibility */
        table.info-table {
            width: 100%;
            border-collapse: collapse;
        }

        table.info-table td {
            padding: 5px 0;
            vertical-align: top;
        }
    </style>
</head>

<body class="{{ ($language_code ?? 'ar') == 'ar' ? 'rtl' : 'ltr' }}">
    <div class="receipt-container">
        <div class="top-bar"></div>

        <div class="content-wrapper">
            <!-- Logo Section -->
            <div class="logo-section">
                <div class="logo-box">
                    @if(isset($company_logo) && $company_logo)
                        <img src="{{ $company_logo }}" alt="{{ $company_name ?? 'Logo' }}">
                    @else
                        <span style="font-size: 24px; color: #7FBCD2;">☕</span>
                    @endif
                </div>
            </div>

            <!-- Receipt Title -->
            <div class="receipt-title">
                <span>✦</span> {{ translate('Wallet Receipt') }} <span>✦</span>
            </div>

            <!-- Info Section -->
            <div class="info-section">
                <table class="info-table">
                    <tr>
                        <td style="text-align: {{ ($language_code ?? 'ar') == 'ar' ? 'right' : 'left' }};">
                            <span class="info-label">{{ translate('Transaction ID') }}:</span>
                            <span class="info-value">{{ $transaction_id ?? 'N/A' }}</span>
                        </td>
                        <td style="text-align: {{ ($language_code ?? 'ar') == 'ar' ? 'left' : 'right' }};">
                            <span class="info-value">{{ $date ?? '' }} {{ $time ?? '' }}</span>
                        </td>
                    </tr>
                    <tr>
                        <td style="text-align: {{ ($language_code ?? 'ar') == 'ar' ? 'right' : 'left' }};">
                            <span class="info-label">{{ translate('Customer Name') }}:</span>
                            <span class="info-value">{{ $customer_name ?? 'N/A' }}</span>
                        </td>
                        <td style="text-align: {{ ($language_code ?? 'ar') == 'ar' ? 'left' : 'right' }};">
                            <span class="info-label">{{ translate('Phone') }}:</span>
                            <span class="info-value">{{ $customer_phone ?? 'N/A' }}</span>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- Items Table -->
            <table class="items-table">
                <thead>
                    <tr>
                        <th>{{ translate('Description') }}</th>
                        <th>{{ translate('Amount') }}</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>
                            <div class="item-description">{{ translate('Add wallet balance') }}</div>
                            @if(isset($reference) && $reference)
                                <div class="item-reference">{{ translate('Reference') }}: [{{ $reference }}]</div>
                            @elseif(isset($branch_name) && $branch_name)
                                <div class="item-reference">{{ translate('Reference') }}: [Branch ID:
                                    {{ $branch_id ?? '' }}]</div>
                            @endif
                        </td>
                        <td>
                            <span class="item-amount">{{ $amount ?? '0.00' }}{{ $currency ?? translate('SAR') }}</span>
                        </td>
                    </tr>
                    @if(isset($cashback_amount) && $cashback_amount > 0)
                        <tr class="cashback-row">
                            <td>
                                <div class="item-description">🎁 {{ translate('Cashback Earned') }}</div>
                            </td>
                            <td>
                                <span class="item-amount"
                                    style="color: #856404;">+{{ $cashback_amount }}{{ $currency ?? translate('SAR') }}</span>
                            </td>
                        </tr>
                    @endif
                </tbody>
            </table>

            <hr class="divider">

            <!-- Total Section -->
            <div class="total-section">
                <table style="width: 100%;">
                    <tr>
                        <td
                            style="text-align: {{ ($language_code ?? 'ar') == 'ar' ? 'right' : 'left' }}; font-size: 24px; font-weight: bold; color: #5C4033;">
                            {{ translate('Total') }}
                        </td>
                        <td
                            style="text-align: {{ ($language_code ?? 'ar') == 'ar' ? 'left' : 'right' }}; font-size: 24px; font-weight: bold; color: #5C4033;">
                            {{ $amount ?? '0.00' }}{{ $currency ?? translate('SAR') }}
                        </td>
                    </tr>
                    <tr>
                        <td
                            style="text-align: {{ ($language_code ?? 'ar') == 'ar' ? 'right' : 'left' }}; font-size: 14px; color: #5C4033; padding-top: 10px;">
                            {{ translate('Current Wallet Balance') }}
                        </td>
                        <td
                            style="text-align: {{ ($language_code ?? 'ar') == 'ar' ? 'left' : 'right' }}; font-size: 14px; color: #5C4033; padding-top: 10px;">
                            {{ $new_balance ?? '0.00' }}{{ $currency ?? translate('SAR') }}
                        </td>
                    </tr>
                </table>
            </div>

            <hr class="dashed-divider">

            <!-- Thank You Section -->
            <div class="thank-you-section">
                <div class="thank-you-text">
                    <span>✦</span> {{ translate('Thank You') }} <span>✦</span>
                </div>
            </div>

            <!-- Footer Section -->
            <div class="footer-section">
                <div class="footer-company">{{ $company_name ?? 'Coffee' }}</div>
                <div class="footer-copyright">Copyright © {{ date('Y') }} All rights reserved.</div>
            </div>
        </div>
    </div>
</body>

</html>