<!DOCTYPE html>
<html lang="{{ $language_code ?? 'en' }}">

<head>
    <meta charset="utf-8">
    <title>{{ translate('Invoice') }}</title>
    <style>
        body {
            font-family: 'dejavusans', 'Cairo', sans-serif;
            font-size: 11px;
            color: #5C4033;
            margin: 0;
            padding: 0;
            background-color: #ffffff;
        }

        .invoice-box {
            max-width: 800px;
            margin: auto;
            border: 1px solid #E6D5C3;
            padding: 30px;
            background-color: #FDF8F3;
            border-radius: 8px;
        }

        .header-section {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #5C4033;
            padding-bottom: 20px;
        }

        .restaurant-name {
            font-size: 24px;
            font-weight: bold;
            color: #5C4033;
            margin-bottom: 5px;
        }

        .invoice-title {
            font-size: 20px;
            font-weight: bold;
            color: #A07855;
            margin-top: 10px;
            text-transform: uppercase;
        }

        .info-table {
            width: 100%;
            margin-bottom: 20px;
            border-collapse: collapse;
        }

        .info-table td {
            padding: 5px;
            vertical-align: top;
        }

        .label {
            font-weight: bold;
            color: #5C4033;
        }

        .item-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }

        .item-table th {
            background-color: #5C4033;
            color: #ffffff;
            padding: 10px;
            text-align: right;
            border: 1px solid #5C4033;
        }

        .item-table td {
            padding: 10px;
            border-bottom: 1px solid #E6D5C3;
            text-align: right;
        }

        .total-section {
            width: 40%;
            margin-left: 60%;
        }

        .total-table {
            width: 100%;
            border-collapse: collapse;
        }

        .total-table td {
            padding: 5px;
            text-align: left;
        }

        .total-table .val {
            text-align: right;
            font-weight: bold;
        }

        .grand-total {
            font-size: 16px;
            font-weight: bold;
            color: #5C4033;
            border-top: 2px solid #5C4033;
            padding-top: 10px;
        }

        .footer {
            text-align: center;
            margin-top: 40px;
            font-size: 10px;
            color: #8B735B;
            border-top: 1px dashed #A07855;
            padding-top: 20px;
        }

        .rtl {
            direction: rtl;
        }

        .ltr {
            direction: ltr;
        }
    </style>
</head>

<body class="{{ $language_code == 'ar' ? 'rtl' : 'ltr' }}">
    <div class="invoice-box">
        <div class="header-section">
            <div class="restaurant-name">
                {{ \App\Model\BusinessSetting::where(['key' => 'restaurant_name'])->first()->value }}
            </div>
            <div style="font-size: 12px; color: #8B735B;">
                {{ \App\Model\BusinessSetting::where(['key' => 'address'])->first()->value }} |
                {{ translate('Phone') }}: {{ \App\Model\BusinessSetting::where(['key' => 'phone'])->first()->value }}
            </div>
            <div class="invoice-title">✧ {{ translate('Invoice') }} ✧</div>
        </div>

        <table class="info-table">
            <tr>
                <td width="50%">
                    <span class="label">{{ translate('Order ID') }}:</span> #{{ $order->id }}<br>
                    <span class="label">{{ translate('Date') }}:</span>
                    {{ date('d M, Y h:i A', strtotime($order->created_at)) }}
                </td>
                <td width="50%" style="text-align: right;">
                    <span class="label">{{ translate('Customer') }}:</span><br>
                    @if($order->is_guest == 0)
                        {{ $order->customer ? $order->customer['name'] : translate('Customer') }}<br>
                        {{ $order->customer ? $order->customer['phone'] : '' }}
                    @else
                        {{ $order->address ? $order->address['contact_person_name'] : translate('Guest') }}<br>
                        {{ $order->address ? $order->address['contact_person_number'] : '' }}
                    @endif
                </td>
            </tr>
            @if(isset($order->address))
                <tr>
                    <td colspan="2">
                        <span class="label">{{ translate('Delivery Address') }}:</span> {{ $order->address['address'] }}
                    </td>
                </tr>
            @endif
        </table>

        <table class="item-table">
            <thead>
                <tr>
                    <th width="10%" style="text-align: center;">{{ translate('SL') }}</th>
                    <th style="text-align: left;">{{ translate('Description') }}</th>
                    <th width="15%" style="text-align: center;">{{ translate('QTY') }}</th>
                    <th width="20%">{{ translate('Price') }}</th>
                </tr>
            </thead>
            <tbody>
                @php($subTotal = 0)
                @php($totalTax = 0)
                @php($addOnsCost = 0)
                @php($addOnsTaxCost = 0)
                @foreach($order->details as $key => $detail)
                @if($detail->product)
                @php($addOnQtys = json_decode($detail['add_on_qtys'], true))
                @php($addOnPrices = json_decode($detail['add_on_prices'], true))
                @php($addOnTaxes = json_decode($detail['add_on_taxes'], true))
                <tr>
                    <td style="text-align: center;">{{ $key + 1 }}</td>
                    <td style="text-align: left;">
                        <div style="font-weight: bold;">{{ $detail->product['name'] }}</div>
                        @if (count(json_decode($detail['variation'], true)) > 0)
                            @foreach(json_decode($detail['variation'], true) as $variation)
                                @if (isset($variation['name']) && isset($variation['values']))
                                    @foreach ($variation['values'] as $value)
                                        <div style="font-size: 9px; color: #8B735B;">{{ $value['label'] }}:
                                            {{ \App\CentralLogics\Helpers::set_symbol($value['optionPrice']) }}</div>
                                    @endforeach
                                @endif
                            @endforeach
                        @endif
                        @foreach(json_decode($detail['add_on_ids'], true) as $key2 => $id)
                        @php($addon = \App\Model\AddOn::find($id))
                        <div style="font-size: 9px; color: #8B735B;">+
                            {{ $addon ? $addon['name'] : translate('Addon') }} ({{ $addOnQtys[$key2] ?? 1 }} x
                            {{ \App\CentralLogics\Helpers::set_symbol($addOnPrices[$key2] ?? 0) }})</div>
                        @php($addOnsCost += ($addOnPrices[$key2] ?? 0) * ($addOnQtys[$key2] ?? 1))
                        @php($addOnsTaxCost += ($addOnTaxes[$key2] ?? 0) * ($addOnQtys[$key2] ?? 1))
                        @endforeach
                    </td>
                    <td style="text-align: center;">{{ $detail['quantity'] }}</td>
                    <td>
                        @php($amount = ($detail['price'] - $detail['discount_on_product']) * $detail['quantity'])
                        {{ \App\CentralLogics\Helpers::set_symbol($amount) }}
                    </td>
                </tr>
                @php($subTotal += $amount)
                @php($totalTax += $detail['tax_amount'] * $detail['quantity'])
                @endif
                @endforeach
            </tbody>
        </table>

        <div class="total-section">
            <table class="total-table">
                <tr>
                    <td>{{ translate('Items Price') }}:</td>
                    <td class="val">{{ \App\CentralLogics\Helpers::set_symbol($subTotal) }}</td>
                </tr>
                @if($addOnsCost > 0)
                    <tr>
                        <td>{{ translate('Addon Cost') }}:</td>
                        <td class="val">{{ \App\CentralLogics\Helpers::set_symbol($addOnsCost) }}</td>
                    </tr>
                @endif
                <tr>
                    <td>{{ translate('Tax / VAT') }}:</td>
                    <td class="val">{{ \App\CentralLogics\Helpers::set_symbol($totalTax + $addOnsTaxCost) }}</td>
                </tr>
                @if($order['coupon_discount_amount'] > 0)
                    <tr>
                        <td>{{ translate('Coupon Discount') }}:</td>
                        <td class="val">- {{ \App\CentralLogics\Helpers::set_symbol($order['coupon_discount_amount']) }}
                        </td>
                    </tr>
                @endif
                @if($order['extra_discount'] > 0)
                    <tr>
                        <td>{{ translate('Extra Discount') }}:</td>
                        <td class="val">- {{ \App\CentralLogics\Helpers::set_symbol($order['extra_discount']) }}</td>
                    </tr>
                @endif
                <tr>
                    <td>{{ translate('Delivery Fee') }}:</td>
                    <td class="val">
                        {{ \App\CentralLogics\Helpers::set_symbol($order['order_type'] == 'take_away' ? 0 : $order['delivery_charge']) }}
                    </td>
                </tr>
                @php($grandTotal = $subTotal + $addOnsCost + $totalTax + $addOnsTaxCost - $order['coupon_discount_amount'] - $order['extra_discount'] + ($order['order_type'] == 'take_away' ? 0 : $order['delivery_charge']))
                <tr class="grand-total">
                    <td>{{ translate('Total') }}</td>
                    <td class="val">{{ \App\CentralLogics\Helpers::set_symbol($grandTotal) }}</td>
                </tr>
            </table>
        </div>

        <div class="footer">
            <div style="font-size: 14px; margin-bottom: 10px;">✧ {{ translate('THANK YOU') }} ✧</div>
            <div>{{ \App\Model\BusinessSetting::where(['key' => 'footer_text'])->first()->value }}</div>
            <div style="margin-top: 10px;">
                {{ \App\Model\BusinessSetting::where(['key' => 'restaurant_name'])->first()->value }}</div>
        </div>
    </div>
</body>

</html>