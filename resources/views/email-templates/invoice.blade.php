<!DOCTYPE html>
<html lang="{{ $language_code }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ translate('Invoice') }} #{{ $order->id }}</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Cairo:wght@400;700&family=Inter:wght@400;700&display=swap');

        body {
            margin: 0;
            padding: 0;
            font-family: 'Inter', 'Cairo', sans-serif;
            background-color: #f4f4f4;
            color: #5C4033;
        }

        .wrapper {
            width: 100%;
            table-layout: fixed;
            background-color: #f4f4f4;
            padding-bottom: 40px;
        }

        .main {
            background-color: #FDF8F3;
            margin: 0 auto;
            width: 100%;
            max-width: 600px;
            border-spacing: 0;
            font-family: 'Inter', 'Cairo', sans-serif;
            border-radius: 12px;
            overflow: hidden;
            border: 1px solid #E6D5C3;
        }

        .header-accent {
            height: 8px;
            background: linear-gradient(90deg, #5C4033, #A07855);
        }

        .content {
            padding: 40px;
        }

        .restaurant-name {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 5px;
            text-align: center;
        }

        .invoice-title {
            font-size: 32px;
            font-weight: 700;
            text-align: center;
            margin: 20px 0;
            color: #5C4033;
        }

        .info-box {
            background-color: rgba(160, 120, 85, 0.05);
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
        }

        .item-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }

        .item-table th {
            background-color: #5C4033;
            color: #ffffff;
            padding: 12px;
            text-align: right;
            font-size: 14px;
        }

        .item-table td {
            padding: 15px 12px;
            border-bottom: 1px solid #E6D5C3;
            font-size: 14px;
        }

        .total-row td {
            padding: 8px 12px;
            font-size: 15px;
        }

        .grand-total td {
            font-size: 22px;
            font-weight: 700;
            padding-top: 20px;
            border-top: 2px solid #5C4033;
        }

        .footer {
            text-align: center;
            padding: 30px;
            border-top: 1px dashed #A07855;
            font-size: 13px;
            color: #8B735B;
        }

        .rtl {
            direction: rtl;
            text-align: right;
        }

        .ltr {
            direction: ltr;
            text-align: left;
        }
    </style>
</head>

<body class="{{ $direction }}">
    <center class="wrapper">
        <table class="main">
            <tr>
                <td class="header-accent"></td>
            </tr>
            <tr>
                <td class="content">
                    <div class="restaurant-name">
                        {{ \App\Model\BusinessSetting::where(['key' => 'restaurant_name'])->first()->value }}
                    </div>
                    <div style="text-align: center; font-size: 14px; color: #8B735B;">
                        {{ \App\Model\BusinessSetting::where(['key' => 'address'])->first()->value }} |
                        {{ translate('Phone') }}:
                        {{ \App\Model\BusinessSetting::where(['key' => 'phone'])->first()->value }}
                    </div>

                    <div class="invoice-title">✧ {{ translate('Invoice') }} ✧</div>

                    <div class="info-box">
                        <table width="100%">
                            <tr>
                                <td><strong>{{ translate('Order ID') }}:</strong> #{{ $order->id }}</td>
                                <td align="center">{{ date('d M, Y h:i A', strtotime($order->created_at)) }}</td>
                            </tr>
                            <tr>
                                <td colspan="2" style="padding-top: 10px;">
                                    <strong>{{ translate('Customer') }}:</strong>
                                    @if($order->is_guest == 0)
                                        {{ $order->customer ? $order->customer['name'] : translate('Customer') }}
                                    @else
                                        {{ $order->address ? $order->address['contact_person_name'] : translate('Guest') }}
                                    @endif
                                </td>
                            </tr>
                        </table>
                    </div>

                    <table class="item-table">
                        <thead>
                            <tr>
                                <th width="15%" style="text-align: center;">{{ translate('QTY') }}</th>
                                <th>{{ translate('Description') }}</th>
                                <th width="25%">{{ translate('Price') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php($subTotalMail = 0)
                            @php($totalTaxMail = 0)
                            @php($addOnsCostMail = 0)
                            @php($addOnsTaxCostMail = 0)
                            @foreach($order->details as $detail)
                            @if($detail->product)
                            @php($addOnQtys = json_decode($detail['add_on_qtys'], true))
                            @php($addOnPrices = json_decode($detail['add_on_prices'], true))
                            @php($addOnTaxes = json_decode($detail['add_on_taxes'], true))
                            <tr>
                                <td align="center">{{ $detail['quantity'] }}</td>
                                <td>
                                    <div style="font-weight: 700;">{{ $detail->product['name'] }}</div>
                                    @if (count(json_decode($detail['variation'], true)) > 0)
                                        @foreach(json_decode($detail['variation'], true) as $variation)
                                            @if (isset($variation['name']) && isset($variation['values']))
                                                @foreach ($variation['values'] as $value)
                                                    <div style="font-size: 11px; color: #8B735B;">{{ $value['label'] }}:
                                                        {{ \App\CentralLogics\Helpers::set_symbol($value['optionPrice']) }}</div>
                                                @endforeach
                                            @endif
                                        @endforeach
                                    @endif
                                    @foreach(json_decode($detail['add_on_ids'], true) as $key2 => $id)
                                    @php($addon = \App\Model\AddOn::find($id))
                                    <div style="font-size: 11px; color: #8B735B;">+
                                        {{ $addon ? $addon['name'] : translate('Addon') }} ({{ $addOnQtys[$key2] ?? 1 }}
                                        x {{ \App\CentralLogics\Helpers::set_symbol($addOnPrices[$key2] ?? 0) }})</div>
                                    @php($addOnsCostMail += ($addOnPrices[$key2] ?? 0) * ($addOnQtys[$key2] ?? 1))
                                    @php($addOnsTaxCostMail += ($addOnTaxes[$key2] ?? 0) * ($addOnQtys[$key2] ?? 1))
                                    @endforeach
                                </td>
                                <td>
                                    @php($amount = ($detail['price'] - $detail['discount_on_product']) * $detail['quantity'])
                                    {{ \App\CentralLogics\Helpers::set_symbol($amount) }}
                                </td>
                            </tr>
                            @php($subTotalMail += $amount)
                            @php($totalTaxMail += $detail['tax_amount'] * $detail['quantity'])
                            @endif
                            @endforeach
                        </tbody>
                    </table>

                    <table width="100%" class="total-row">
                        <tr>
                            <td>{{ translate('Items Price') }}:</td>
                            <td align="right">{{ \App\CentralLogics\Helpers::set_symbol($subTotalMail) }}</td>
                        </tr>
                        @if($addOnsCostMail > 0)
                            <tr>
                                <td>{{ translate('Addon Cost') }}:</td>
                                <td align="right">{{ \App\CentralLogics\Helpers::set_symbol($addOnsCostMail) }}</td>
                            </tr>
                        @endif
                        <tr>
                            <td>{{ translate('Tax / VAT') }}:</td>
                            <td align="right">
                                {{ \App\CentralLogics\Helpers::set_symbol($totalTaxMail + $addOnsTaxCostMail) }}</td>
                        </tr>
                        @if($order['coupon_discount_amount'] > 0)
                            <tr>
                                <td>{{ translate('Coupon Discount') }}:</td>
                                <td align="right">-
                                    {{ \App\CentralLogics\Helpers::set_symbol($order['coupon_discount_amount']) }}</td>
                            </tr>
                        @endif
                        <tr>
                            <td>{{ translate('Delivery Fee') }}:</td>
                            <td align="right">
                                {{ \App\CentralLogics\Helpers::set_symbol($order['order_type'] == 'take_away' ? 0 : $order['delivery_charge']) }}
                            </td>
                        </tr>

                        @php($grandTotalMail = $subTotalMail + $addOnsCostMail + $totalTaxMail + $addOnsTaxCostMail - $order['coupon_discount_amount'] - $order['extra_discount'] + ($order['order_type'] == 'take_away' ? 0 : $order['delivery_charge']))

                        <tr class="grand-total">
                            <td style="color: #5C4033;"><strong>{{ translate('Total') }}</strong></td>
                            <td align="right" style="color: #5C4033;">
                                <strong>{{ \App\CentralLogics\Helpers::set_symbol($grandTotalMail) }}</strong></td>
                        </tr>
                    </table>
                </td>
            </tr>
            <tr>
                <td class="footer">
                    <div style="font-size: 16px; margin-bottom: 10px;">✧ {{ translate('THANK YOU') }} ✧</div>
                    <div>{{ \App\Model\BusinessSetting::where(['key' => 'footer_text'])->first()->value }}</div>
                </td>
            </tr>
        </table>
    </center>
</body>

</html>