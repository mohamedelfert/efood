@extends('layouts.admin.app')

@section('title', translate('Invoice'))

@push('css_or_js')
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;700&family=Inter:wght@400;700&display=swap" rel="stylesheet">
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
            .non-printable { display: none; }
            .printable { display: block; }
            body { background-color: white !important; margin: 0 !important; padding: 0 !important; }
            .invoice-card { box-shadow: none !important; border: none !important; width: 100% !important; margin: 0 !important; }
        }

        .invoice-card {
            background-color: var(--light-bg);
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
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

        .invoice-title::before, .invoice-title::after {
            content: '✧';
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            color: var(--accent-gold);
            font-size: 20px;
        }

        .invoice-title::before { left: 0; }
        .invoice-title::after { right: 0; }

        .invoice-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
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
            padding: 12px;
            border-bottom: 1px solid var(--border-color);
            vertical-align: top;
            font-size: 14px;
        }

        .item-desc { font-weight: 700; }
        .item-meta { font-size: 12px; color: var(--text-muted); }

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
            font-size: 22px;
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

        .footer-logo { font-size: 20px; color: var(--dark-coffee); margin-bottom: 15px; }

        [dir="rtl"] .invoice-table th, [dir="rtl"] .invoice-table td { text-align: right; }
        [dir="ltr"] .invoice-table th, [dir="ltr"] .invoice-table td { text-align: left; }
    </style>
@endpush

@section('content')
    <div class="content container-fluid">
        <div class="row justify-content-center">
            <div class="col-md-12 mb-3">
                <div class="text-center">
                    <input type="button" class="btn btn-primary non-printable" onclick="printDiv('printableArea')"
                           value="{{translate('Print Invoice')}}"/>
                    <a href="{{url()->previous()}}" class="btn btn-secondary non-printable">{{translate('Back')}}</a>
                </div>
            </div>

            <div class="invoice-card" id="printableArea">
                <div class="header-section">
                    @php($logo = \App\Model\BusinessSetting::where(['key' => 'logo'])->first()->value)
                    <img class="restaurant-logo" src="{{asset('storage/app/public/restaurant/'.$logo)}}" 
                         onerror="this.src='{{asset('public/assets/admin/img/160x160/img2.jpg')}}'" alt="logo">
                    <div>
                        <h1 class="invoice-title">{{translate('فاتورة')}}</h1>
                    </div>
                </div>

                @php($subTotal=0)
                @php($totalTax=0)
                @php($addonCost=0)
                @php($addonTaxCost=0)
                @php($dueAmount=0)

                @foreach($orders as $order)
                    @if($order->payment_status == 'unpaid')
                        @php($dueAmount+= $order->order_amount)
                    @endif

                    <div style="font-weight: 700; margin-bottom: 10px; border-bottom: 1px solid var(--dark-coffee); padding-bottom: 5px;">
                        {{translate('Order ID')}}: {{$order['id']}} | {{date('d/M/Y h:i a',strtotime($order['created_at']))}}
                    </div>

                    <table class="invoice-table">
                        <thead>
                            <tr>
                                <th style="width: 10%; text-align: center;">{{translate('QTY')}}</th>
                                <th>{{translate('DESC')}}</th>
                                <th style="width: 25%; text-align: left;">{{translate('Price')}}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($order->details as $detail)
                                @if($detail->product)
                                    @php($addonQuantities=json_decode($detail['add_on_qtys'],true))
                                    @php($addonPrices=json_decode($detail['add_on_prices'],true))
                                    @php($addonTaxes=json_decode($detail['add_on_taxes'],true))

                                    <tr>
                                        <td style="text-align: center;">{{$detail['quantity']}}</td>
                                        <td>
                                            <div class="item-desc">{{$detail->product['name']}}</div>
                                            @if (count(json_decode($detail['variation'], true)) > 0)
                                                <div class="item-meta">
                                                    @foreach(json_decode($detail['variation'],true) as $variation)
                                                        @if (isset($variation['name']) && isset($variation['values']))
                                                            @foreach ($variation['values'] as $value)
                                                                <span>{{$value['label']}}: {{\App\CentralLogics\Helpers::set_symbol($value['optionPrice'])}}</span> 
                                                            @endforeach
                                                        @endif
                                                    @endforeach
                                                </div>
                                            @endif
                                            @foreach(json_decode($detail['add_on_ids'],true) as $key2 =>$id)
                                                @php($addon=\App\Model\AddOn::find($id))
                                                <div class="item-meta">+ {{$addon ? $addon['name'] : translate('Addon')}} ({{$addonQuantities[$key2] ?? 1}} x {{\App\CentralLogics\Helpers::set_symbol($addonPrices[$key2] ?? 0)}})</div>
                                                @php($addonCost+=($addonPrices[$key2] ?? 0) * ($addonQty=$addonQuantities[$key2] ?? 1))
                                                @php($addonTaxCost += ($addonTaxes[$key2] ?? 0) * $addonQty)
                                            @endforeach
                                        </td>
                                        <td style="text-align: left;">
                                            @php($amount=($detail['price']-$detail['discount_on_product'])*$detail['quantity'])
                                            {{\App\CentralLogics\Helpers::set_symbol($amount)}}
                                        </td>
                                    </tr>
                                    @php($subTotal+=$amount)
                                    @php($totalTax+=$detail['tax_amount']*$detail['quantity'])
                                @endif
                            @endforeach
                        </tbody>
                    </table>
                @endforeach

                <div class="summary-section">
                    <div class="summary-row">
                        <span>{{translate('Items Price')}}:</span>
                        <span>{{\App\CentralLogics\Helpers::set_symbol($subTotal)}}</span>
                    </div>
                    <div class="summary-row">
                        <span>{{translate('Tax / VAT')}}:</span>
                        <span>{{\App\CentralLogics\Helpers::set_symbol($totalTax+$addonTaxCost)}}</span>
                    </div>
                    @if($addonCost > 0)
                    <div class="summary-row">
                        <span>{{translate('Addon Cost')}}:</span>
                        <span>{{\App\CentralLogics\Helpers::set_symbol($addonCost)}}</span>
                    </div>
                    @endif
                    
                    @php($extra_discount = 0)
                    @php($coupon_discount = 0)
                    @php($delivery_charge = 0)
                    @foreach($orders as $order)
                        @php($extra_discount += $order['extra_discount'])
                        @php($coupon_discount += $order['coupon_discount_amount'])
                        @php($delivery_charge += ($order['order_type']=='take_away' ? 0 : $order['delivery_charge']))
                    @endforeach

                    @if($extra_discount > 0)
                    <div class="summary-row">
                        <span>{{translate('Extra Discount')}}:</span>
                        <span>- {{\App\CentralLogics\Helpers::set_symbol($extra_discount)}}</span>
                    </div>
                    @endif
                    @if($coupon_discount > 0)
                    <div class="summary-row">
                        <span>{{translate('Coupon Discount')}}:</span>
                        <span>- {{\App\CentralLogics\Helpers::set_symbol($coupon_discount)}}</span>
                    </div>
                    @endif
                    <div class="summary-row">
                        <span>{{translate('Delivery Fee')}}:</span>
                        <span>{{\App\CentralLogics\Helpers::set_symbol($delivery_charge)}}</span>
                    </div>

                    @php($total_amount = $subTotal + $delivery_charge + $totalTax + $addonCost - $coupon_discount - $extra_discount + $addonTaxCost)
                    
                    <div class="summary-row total">
                        <span>{{translate('Total')}}</span>
                        <span>{{\App\CentralLogics\Helpers::set_symbol($total_amount)}}</span>
                    </div>

                    <div class="summary-row" style="font-weight: 700; color: #d9534f; margin-top: 10px;">
                        <span>{{translate('Due Amount')}}</span>
                        <span>{{\App\CentralLogics\Helpers::set_symbol($dueAmount)}}</span>
                    </div>
                </div>

                <div class="footer-section">
                    <div class="footer-logo">✧ {{translate('THANK YOU')}} ✧</div>
                    <div style="font-size: 12px;">
                        {{\App\Model\BusinessSetting::where(['key'=>'restaurant_name'])->first()->value}}<br>
                        {{\App\Model\BusinessSetting::where(['key'=>'phone'])->first()->value}}
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
