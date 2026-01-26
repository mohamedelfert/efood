<!DOCTYPE html>
<?php
$lang = \App\CentralLogics\Helpers::get_default_language();
?>
<html lang="{{ $lang }}">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ translate('Email_Template') }}</title>
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
            padding: 40px 0;
        }

        .main {
            background-color: #FDF8F3;
            margin: 0 auto;
            width: 100%;
            max-width: 600px;
            border-spacing: 0;
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

        .restaurant-logo {
            text-align: center;
            margin-bottom: 20px;
        }

        .mail-img-logo {
            max-width: 150px;
            height: auto;
        }

        .mail-title {
            font-size: 28px;
            font-weight: 700;
            text-align: center;
            margin-bottom: 20px;
            color: #5C4033;
        }

        .mail-body {
            font-size: 15px;
            line-height: 1.6;
            margin-bottom: 30px;
            text-align: center;
        }

        .order-info-box {
            background-color: rgba(160, 120, 85, 0.05);
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
        }

        .order-summary-title {
            font-size: 18px;
            font-weight: 700;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #E6D5C3;
        }

        .item-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        .item-table th {
            text-align: left;
            padding: 10px;
            background-color: #5C4033;
            color: #ffffff;
            font-size: 13px;
        }

        .item-table td {
            padding: 12px 10px;
            border-bottom: 1px solid #E6D5C3;
            font-size: 14px;
        }

        .total-table {
            width: 100%;
            border-spacing: 0;
        }

        .total-table td {
            padding: 5px 0;
        }

        .total-row-label {
            text-align: right;
            padding-right: 15px;
            font-size: 14px;
        }

        .total-row-value {
            text-align: right;
            font-weight: 700;
            font-size: 14px;
        }

        .grand-total {
            border-top: 2px solid #5C4033;
            padding-top: 10px;
            margin-top: 10px;
        }

        .grand-total td {
            font-size: 20px;
            font-weight: 700;
            color: #5C4033;
        }

        .footer {
            text-align: center;
            padding: 30px;
            border-top: 1px dashed #A07855;
            font-size: 13px;
            color: #8B735B;
        }

        .privacy-links {
            margin-bottom: 15px;
        }

        .privacy-links a {
            color: #5C4033;
            text-decoration: none;
            margin: 0 10px;
        }

        .copyright {
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

<body>
    <center class="wrapper">
        <table class="main">
            <tr>
                <td class="header-accent"></td>
            </tr>
            <tr>
                <td class="content">
                    <div class="restaurant-logo">
                        @php($logo = \App\Model\BusinessSetting::where(['key' => 'logo'])->first()->value)
                        <img class="mail-img-logo" src="{{ asset('storage/app/public/restaurant/' . $logo) }}"
                            onerror="this.src='{{ asset('public/assets/admin/img/160x160/img2.jpg') }}'" alt="Logo">
                    </div>

                    <h1 class="mail-title">✧ {{ $title ?? translate('Email_Template') }} ✧</h1>

                    <div class="mail-body">
                        {!! $body ?? '' !!}
                    </div>

                    <div class="order-info-box">
                        <div class="order-summary-title">{{ translate('Order_Summary') }}</div>
                        <table width="100%">
                            <tr>
                                <td width="50%">
                                    <strong>{{ translate('Order') }}#:</strong> {{ $order->id }}<br>
                                    <strong>{{ translate('Date') }}:</strong> {{ $order->created_at }}
                                </td>
                                <td width="50%" align="right">
                                    @if ($order->is_guest == 0 && isset($order->customer))
                                        <strong>{{ translate('Customer') }}:</strong> {{ $order->customer['name'] }}<br>
                                        {{ $order->customer['phone'] }}
                                    @elseif ($order->is_guest == 1 && isset($order->address))
                                        <strong>{{ translate('Guest') }}:</strong>
                                        {{ $order->address['contact_person_name'] }}<br>
                                        {{ $order->address['contact_person_number'] }}
                                    @endif
                                </td>
                            </tr>
                        </table>
                    </div>

                    <table class="item-table">
                        <thead>
                            <tr>
                                <th>{{ translate('Product') }}</th>
                                <th align="center" style="text-align: center;">{{ translate('QTY') }}</th>
                                <th align="right" style="text-align: right;">{{ translate('Price') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php($sub_total = 0)
                            @php($total_tax = 0)
                            @php($total_dis_on_pro = 0)
                            @php($add_ons_cost = 0)
                            @php($add_ons_tax_cost = 0)
                            @foreach($order->details as $detail)
                            @php($product_details = json_decode($detail['product_details'], true))
                            @php($add_on_qtys = json_decode($detail['add_on_qtys'], true))
                            @php($add_on_prices = json_decode($detail['add_on_prices'], true))
                            @php($add_on_taxes = json_decode($detail['add_on_taxes'], true))
                            <tr>
                                <td>
                                    <div style="font-weight: 700;">{{ $product_details['name'] }}</div>
                                    @if (isset($detail['variation']))
                                        @foreach(json_decode($detail['variation'], true) as $variation)
                                            @if (isset($variation['name']) && isset($variation['values']))
                                                @foreach ($variation['values'] as $value)
                                                    <div style="font-size: 11px; color: #8B735B;">{{ $value['label'] }}:
                                                        {{ \App\CentralLogics\Helpers::set_symbol($value['optionPrice']) }}</div>
                                                @endforeach
                                            @endif
                                        @endforeach
                                    @endif
                                    @php($addon_ids = json_decode($detail['add_on_ids'], true))
                                    @if ($addon_ids)
                                    @foreach($addon_ids as $key2 => $id)
                                    @php($addon = \App\Model\AddOn::find($id))
                                    @php($add_on_qty = $add_on_qtys[$key2] ?? 1)
                                    <div style="font-size: 11px; color: #8B735B;">+
                                        {{ $addon ? $addon['name'] : translate('Addon') }} ({{ $add_on_qty }} x
                                        {{ \App\CentralLogics\Helpers::set_symbol($add_on_prices[$key2]) }})</div>
                                    @php($add_ons_cost += $add_on_prices[$key2] * $add_on_qty)
                                    @php($add_ons_tax_cost += $add_on_taxes[$key2] * $add_on_qty)
                                    @endforeach
                                    @endif
                                </td>
                                <td align="center">{{ $detail['quantity'] }}</td>
                                <td align="right">
                                    @php($amount = $detail['price'] * $detail['quantity'])
                                    @php($tot_discount = $detail['discount_on_product'] * $detail['quantity'])
                                    @php($product_tax = $detail['tax_amount'] * $detail['quantity'])
                                    {{ \App\CentralLogics\Helpers::set_symbol($amount - $tot_discount + $product_tax) }}

                                    @php($total_dis_on_pro += $tot_discount)
                                    @php($sub_total += $amount)
                                    @php($total_tax += $product_tax)
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>

                    <table class="total-table">
                        <tr>
                            <td class="total-row-label">{{ translate('Items Price') }}:</td>
                            <td class="total-row-value">{{ \App\CentralLogics\Helpers::set_symbol($sub_total) }}</td>
                        </tr>
                        @if($add_ons_cost > 0)
                            <tr>
                                <td class="total-row-label">{{ translate('Addon Cost') }}:</td>
                                <td class="total-row-value">{{ \App\CentralLogics\Helpers::set_symbol($add_ons_cost) }}</td>
                            </tr>
                        @endif
                        <tr>
                            <td class="total-row-label">{{ translate('Discount') }}:</td>
                            <td class="total-row-value">-
                                {{ \App\CentralLogics\Helpers::set_symbol($total_dis_on_pro) }}</td>
                        </tr>
                        @if($order['coupon_discount_amount'] > 0)
                            <tr>
                                <td class="total-row-label">{{ translate('Coupon Discount') }}:</td>
                                <td class="total-row-value">-
                                    {{ \App\CentralLogics\Helpers::set_symbol($order['coupon_discount_amount']) }}</td>
                            </tr>
                        @endif
                        @if($order['extra_discount'] > 0)
                            <tr>
                                <td class="total-row-label">{{translate('Extra Discount')}}:</td>
                                <td class="total-row-value">-
                                    {{ \App\CentralLogics\Helpers::set_symbol($order['extra_discount']) }}</td>
                            </tr>
                        @endif
                        <tr>
                            <td class="total-row-label">{{ translate('Tax / VAT') }}:</td>
                            <td class="total-row-value">
                                {{ \App\CentralLogics\Helpers::set_symbol($total_tax + $add_ons_tax_cost) }}</td>
                        </tr>
                        <tr>
                            <td class="total-row-label">{{ translate('Delivery Fee') }}:</td>
                            <td class="total-row-value">
                                {{ \App\CentralLogics\Helpers::set_symbol($order['order_type'] == 'take_away' ? 0 : $order['delivery_charge']) }}
                            </td>
                        </tr>
                        @php($grand_total_val = $sub_total + $total_tax + $add_ons_cost - $total_dis_on_pro + $add_ons_tax_cost - $order['coupon_discount_amount'] - $order['extra_discount'] + ($order['order_type'] == 'take_away' ? 0 : $order['delivery_charge']))
                        <tr class="grand-total">
                            <td class="total-row-label"><strong>{{ translate('Total') }}</strong></td>
                            <td class="total-row-value">
                                <strong>{{ \App\CentralLogics\Helpers::set_symbol($grand_total_val) }}</strong></td>
                        </tr>
                    </table>

                    <div style="margin-top: 30px; text-align: left;">
                        {{ $footer_text ?? '' }}<br><br>
                        {{ translate('Thanks_&_Regards') }},<br>
                        <strong>{{ $company_name }}</strong>
                    </div>
                </td>
            </tr>
            <tr>
                <td class="footer">
                    <div class="privacy-links">
                        @if(isset($data['privacy']) && $data['privacy'] == 1)
                            <a href="{{ route('privacy-policy') }}">{{ translate('Privacy_Policy')}}</a>
                        @endif
                        @if(isset($data['contact']) && $data['contact'] == 1)
                            <a href="{{ route('about-us') }}">{{ translate('About_Us')}}</a>
                        @endif
                    </div>
                    <div class="copyright">
                        {{ $copyright_text ?? '' }}
                    </div>
                </td>
            </tr>
        </table>
    </center>
</body>

</html>