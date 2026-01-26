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
            margin-bottom: 25px;
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

        .item-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 25px;
        }

        .item-table th {
            text-align: left;
            padding: 12px;
            background-color: #5C4033;
            color: #ffffff;
            font-size: 13px;
        }

        .item-table td {
            padding: 12px;
            border-bottom: 1px solid #E6D5C3;
            font-size: 14px;
        }

        .total-table {
            width: 100%;
            border-spacing: 0;
        }

        .total-table td {
            padding: 5px 0;
            font-size: 14px;
        }

        .grand-total {
            border-top: 2px solid #5C4033;
            padding-top: 10px;
            margin-top: 10px;
            font-size: 20px !important;
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

                    <h1 class="mail-title">✧ {{ $title ?? translate('Order_Update') }} ✧</h1>

                    <div class="mail-body">
                        {!! $body ?? '' !!}
                    </div>

                    <div class="order-info-box">
                        <table width="100%">
                            <tr>
                                <td width="50%">
                                    <strong>{{ translate('Order') }}#:</strong> {{ $order->id }}<br>
                                    <strong>{{ translate('Status') }}:</strong> {{ translate($order->order_status) }}
                                </td>
                                <td width="50%" align="right">
                                    <strong>{{ translate('Delivery_Address') }}</strong><br>
                                    @if ($order->delivery_address)
                                    @php($address = json_decode($order->delivery_address, true))
                                    {{ $address['contact_person_name'] ?? $order->customer['name'] }}<br>
                                    {{ $address['address'] ?? '' }}
                                    @endif
                                </td>
                            </tr>
                        </table>
                    </div>

                    <table class="item-table">
                        <thead>
                            <tr>
                                <th>{{ translate('Item') }}</th>
                                <th align="right">{{ translate('Price') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php($sub_total = 0)
                            @php($total_addon_price = 0)
                            @foreach ($order->details as $details)
                            @php($item_details = json_decode($details->food_details, true))
                            @php($sub_val = $details['price'] * $details->quantity)
                            <tr>
                                <td>
                                    <div style="font-weight: 700;">{{ $item_details['name'] }} x
                                        {{ $details->quantity }}</div>
                                    @if (count(json_decode($details['variation'], true)) > 0)
                                        @foreach(json_decode($details['variation'], true) as $variation)
                                            @if (isset($variation['name']) && isset($variation['values']))
                                                <div style="font-size: 11px; color: #8B735B;">
                                                    {{ $variation['name'] }}:
                                                    @foreach ($variation['values'] as $v)
                                                        {{ $v['label'] }}{{ !$loop->last ? ',' : '' }}
                                                    @endforeach
                                                </div>
                                            @endif
                                        @endforeach
                                    @endif
                                    @foreach (json_decode($details['add_ons'], true) as $addon)
                                    <div style="font-size: 11px; color: #8B735B;">+ {{ $addon['name'] }}
                                        ({{ $addon['quantity'] }} x
                                        {{ \App\CentralLogics\Helpers::format_currency($addon['price']) }})</div>
                                    @php($total_addon_price += $addon['price'] * $addon['quantity'])
                                    @endforeach
                                </td>
                                <td align="right">
                                    {{ \App\CentralLogics\Helpers::format_currency($sub_val) }}
                                </td>
                            </tr>
                            @php($sub_total += $sub_val)
                            @endforeach
                        </tbody>
                    </table>

                    <table class="total-table">
                        <tr>
                            <td align="right" style="padding-right: 15px;">{{ translate('Item_Price') }}:</td>
                            <td align="right" width="100px">
                                {{ \App\CentralLogics\Helpers::format_currency($sub_total) }}</td>
                        </tr>
                        @if($total_addon_price > 0)
                            <tr>
                                <td align="right" style="padding-right: 15px;">{{ translate('Addon_Cost') }}:</td>
                                <td align="right">{{ \App\CentralLogics\Helpers::format_currency($total_addon_price) }}</td>
                            </tr>
                        @endif
                        <tr>
                            <td align="right" style="padding-right: 15px;">{{ translate('Discount') }}:</td>
                            <td align="right">-
                                {{ \App\CentralLogics\Helpers::format_currency($order->restaurant_discount_amount) }}
                            </td>
                        </tr>
                        @if($order->coupon_discount_amount > 0)
                            <tr>
                                <td align="right" style="padding-right: 15px;">{{ translate('Coupon_Discount') }}:</td>
                                <td align="right">-
                                    {{ \App\CentralLogics\Helpers::format_currency($order->coupon_discount_amount) }}</td>
                            </tr>
                        @endif
                        <tr>
                            <td align="right" style="padding-right: 15px;">{{ translate('Tax') }}:</td>
                            <td align="right">
                                {{ \App\CentralLogics\Helpers::format_currency($order->total_tax_amount) }}</td>
                        </tr>
                        <tr>
                            <td align="right" style="padding-right: 15px;">{{ translate('Delivery_Charge') }}:</td>
                            <td align="right">{{ \App\CentralLogics\Helpers::format_currency($order->delivery_charge) }}
                            </td>
                        </tr>
                        <tr class="grand-total">
                            <td align="right" style="padding-right: 15px;"><strong>{{ translate('Total') }}</strong>
                            </td>
                            <td align="right">
                                <strong>{{ \App\CentralLogics\Helpers::format_currency($order->order_amount) }}</strong>
                            </td>
                        </tr>
                    </table>

                    <div style="margin-top: 30px; text-align: left; border-top: 1px solid #E6D5C3; padding-top: 20px;">
                        <span style="font-size: 13px; color: #8B735B;">{{ $footer_text ?? '' }}</span><br><br>
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
                        @if (isset($data['contact']) && $data['contact'] == 1)
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