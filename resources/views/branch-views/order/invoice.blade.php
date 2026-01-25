@extends('layouts.branch.app')

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

        .info-grid {
            margin-bottom: 30px;
            padding: 20px;
            background: rgba(160, 120, 85, 0.05);
            border-radius: 8px;
            display: grid;
            grid-template-columns: 1fr 1.5fr;
            gap: 15px;
        }

        .info-item { font-size: 14px; line-height: 1.6; }
        .info-label { font-weight: 700; color: var(--dark-coffee); }

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

        .item-desc { font-weight: 700; margin-bottom: 5px; }
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

        .footer-logo { font-size: 20px; color: var(--dark-coffee); margin-bottom: 15px; }

        [dir="rtl"] .invoice-table th, [dir="rtl"] .invoice-table td { text-align: right; }
        [dir="ltr"] .invoice-table th, [dir="ltr"] .invoice-table td { text-align: left; }
        
        .c-qty { width: 10%; text-align: center !important; }
        .c-price { width: 25%; text-align: left !important; }
        [dir="rtl"] .c-price { text-align: right !important; }
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

                <div class="info-grid">
                    <div class="info-item">
                        <span class="info-label">{{translate('الرقم الحساب')}}:</span> {{$order['id']}}
                    </div>
                    <div class="info-item" style="text-align: left;">
                        {{date('M/Y h:i a/d',strtotime($order['created_at']))}}
                    </div>
                    <div class="info-item">
                        <span class="info-label">{{translate('اسم العميل')}}:</span> 
                        @if($order->is_guest == 0)
                            {{$order->customer ? $order->customer['name'] : translate('Customer')}}
                        @else
                            {{$order->address ? $order->address['contact_person_name'] : translate('Guest')}}
                        @endif
                    </div>
                    <div class="info-item">
                        <span class="info-label">{{translate('الهاتف')}}:</span> 
                        @if($order->is_guest == 0)
                            {{$order->customer ? $order->customer['phone'] : ''}}
                        @else
                            {{$order->address ? $order->address['contact_person_number'] : ''}}
                        @endif
                    </div>
                    @if(isset($order->address))
                    <div class="info-item" style="grid-column: span 2;">
                        <span class="info-label">{{translate('الموقع')}}:</span> {{$order->address['address']}}
                    </div>
                    @endif
                </div>

                <table class="invoice-table">
                    <thead>
                        <tr>
                            <th class="c-qty">{{translate('الكمية')}}</th>
                            <th>{{translate('الوصف')}}</th>
                            <th class="c-price">{{translate('التسعير')}}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php($subTotal=0)
                        @php($totalTax=0)
                        @php($addOnsCost=0)
                        @php($add_ons_tax_cost=0)
                        @foreach($order->details as $detail)
                            @if($detail->product)
                                @php($addOnQtys=json_decode($detail['add_on_qtys'],true))
                                @php($addOnPrices=json_decode($detail['add_on_prices'],true))
                                @php($addOnTaxes=json_decode($detail['add_on_taxes'],true))

                                <tr>
                                    <td class="c-qty">{{$detail['quantity']}}</td>
                                    <td>
                                        <div class="item-desc">{{$detail->product['name']}}</div>
                                        @if (count(json_decode($detail['variation'], true)) > 0)
                                            <div class="item-meta">
                                                @foreach(json_decode($detail['variation'],true) as $variation)
                                                    @if (isset($variation['name']) && isset($variation['values']))
                                                        @foreach ($variation['values'] as $value)
                                                            <span>{{$value['label']}} : {{\App\CentralLogics\Helpers::set_symbol($value['optionPrice'])}}</span>
                                                        @endforeach
                                                    @endif
                                                @endforeach
                                            </div>
                                        @endif
                                        @foreach(json_decode($detail['add_on_ids'],true) as $key2 => $id)
                                            @php($addon=\App\Model\AddOn::find($id))
                                            <div class="item-meta">
                                                + {{$addon ? $addon['name'] : translate('Addon')}} ({{$addOnQtys[$key2] ?? 1}} x {{\App\CentralLogics\Helpers::set_symbol($addOnPrices[$key2] ?? 0)}})
                                            </div>
                                            @php($addOnsCost+=($addOnPrices[$key2] ?? 0) * ($addOnQtys[$key2] ?? 1))
                                            @php($add_ons_tax_cost += ($addOnTaxes[$key2] ?? 0) * ($addOnQtys[$key2] ?? 1))
                                        @endforeach
                                    </td>
                                    <td class="c-price">
                                        @php($amount=($detail['price']-$detail['discount_on_product'])*$detail['quantity'])
                                        {{\App\CentralLogics\Helpers::set_symbol($amount)}}
                                    </td>
                                </tr>
                                @php($subTotal+=$amount)
                                @php($totalTax+=($detail['tax_amount']*$detail['quantity']))
                            @endif
                        @endforeach
                    </tbody>
                </table>

                <div class="summary-section">
                    <div class="summary-row">
                        <span>{{translate('سعر التوصيل')}}:</span>
                        <span>{{\App\CentralLogics\Helpers::set_symbol($subTotal)}}</span>
                    </div>
                    @if($addOnsCost > 0)
                    <div class="summary-row">
                        <span>{{translate('تكلفة الإضافات')}}:</span>
                        <span>{{\App\CentralLogics\Helpers::set_symbol($addOnsCost)}}</span>
                    </div>
                    @endif
                    @if($order['coupon_discount_amount'] > 0)
                    <div class="summary-row">
                        <span>{{translate('خصم القسيمة')}}:</span>
                        <span>- {{\App\CentralLogics\Helpers::set_symbol($order['coupon_discount_amount'])}}</span>
                    </div>
                    @endif
                    @if($order['extra_discount'] > 0)
                    <div class="summary-row">
                        <span>{{translate('خصم إضافي')}}:</span>
                        <span>- {{\App\CentralLogics\Helpers::set_symbol($order['extra_discount'])}}</span>
                    </div>
                    @endif
                    <div class="summary-row">
                        <span>{{translate('ضريبة القيمة المضافة')}}:</span>
                        <span>{{\App\CentralLogics\Helpers::set_symbol($totalTax + $add_ons_tax_cost)}}</span>
                    </div>
                    <div class="summary-row">
                        <span>{{translate('رسوم التوصيل')}}:</span>
                        <span>{{\App\CentralLogics\Helpers::set_symbol($order['order_type']=='take_away' ? 0 : $order['delivery_charge'])}}</span>
                    </div>

                    @php($total_amount = $subTotal + $addOnsCost + $totalTax + $add_ons_tax_cost - $order['coupon_discount_amount'] - $order['extra_discount'] + ($order['order_type']=='take_away' ? 0 : $order['delivery_charge']))
                    
                    <div class="summary-row total">
                        <span>{{translate('الإجمالي')}}</span>
                        <span>{{\App\CentralLogics\Helpers::set_symbol($total_amount)}}</span>
                    </div>

                    @if ($order->order_partial_payments->isNotEmpty())
                        @foreach($order->order_partial_payments as $partial)
                            <div class="summary-row" style="font-size: 14px; color: var(--text-muted);">
                                <span>{{translate('تم الدفع بواسطة')}} ({{str_replace('_', ' ',$partial->paid_with)}}):</span>
                                <span>{{\App\CentralLogics\Helpers::set_symbol($partial->paid_amount)}}</span>
                            </div>
                        @endforeach
                        <div class="summary-row" style="font-weight: 700;">
                            <span>{{translate('المبلغ المتبقي')}}:</span>
                            <span>{{\App\CentralLogics\Helpers::set_symbol($order->order_partial_payments->first()?->due_amount)}}</span>
                        </div>
                    @endif
                </div>

                <div class="footer-section">
                    <div class="footer-logo">✧ {{translate('شكراً لك')}} ✧</div>
                    <div class="footer-contact">
                        <div class="contact-item">
                            <span>{{\App\Model\BusinessSetting::where(['key'=>'phone'])->first()->value}}</span>
                        </div>
                        <div class="contact-item">
                            <span>{{\App\Model\BusinessSetting::where(['key'=>'email_address'])->first()->value ?? ''}}</span>
                        </div>
                        @if(isset($order->address))
                        <div class="contact-item" style="width: 100%;">
                            <span>{{$order->address['address']}}</span>
                        </div>
                        @endif
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
