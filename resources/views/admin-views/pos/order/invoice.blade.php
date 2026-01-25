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

    .pos-invoice-container {
        font-family: 'Inter', 'Cairo', sans-serif !important;
        background-color: var(--light-bg);
        color: var(--dark-coffee);
        padding: 20px;
        max-width: 400px;
        margin: auto;
    }

    .header-section {
        text-align: center;
        margin-bottom: 20px;
    }

    .restaurant-logo {
        max-width: 80px;
        margin-bottom: 10px;
    }

    .invoice-title {
        font-size: 24px;
        font-weight: 700;
        margin-bottom: 10px;
        position: relative;
        display: inline-block;
        padding: 0 20px;
    }

    .invoice-title::before,
    .invoice-title::after {
        content: '✧';
        color: var(--accent-gold);
        font-size: 14px;
    }

    .info-line {
        display: flex;
        justify-content: space-between;
        font-size: 12px;
        margin-bottom: 5px;
        border-bottom: 1px dashed var(--border-color);
        padding-bottom: 5px;
    }

    .info-label {
        font-weight: 700;
    }

    .invoice-table {
        width: 100%;
        border-collapse: collapse;
        margin: 15px 0;
        font-size: 13px;
    }

    .invoice-table th {
        background-color: var(--dark-coffee);
        color: white;
        padding: 8px;
        text-align: right;
    }

    .invoice-table td {
        padding: 8px;
        border-bottom: 1px solid var(--border-color);
        vertical-align: top;
    }

    .item-desc {
        font-weight: 700;
    }

    .item-meta {
        font-size: 11px;
        color: var(--text-muted);
    }

    .summary-section {
        margin-top: 15px;
        border-top: 2px solid var(--dark-coffee);
        padding-top: 10px;
    }

    .summary-row {
        display: flex;
        justify-content: space-between;
        margin-bottom: 5px;
        font-size: 13px;
    }

    .summary-row.total {
        font-size: 18px;
        font-weight: 700;
        border-top: 1px solid var(--border-color);
        margin-top: 8px;
        padding-top: 8px;
    }

    .footer-section {
        text-align: center;
        margin-top: 20px;
        padding-top: 15px;
        border-top: 1px dashed var(--accent-gold);
        font-size: 12px;
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

<div class="pos-invoice-container" id="printableAreaContent">
    <div class="header-section">
        @php($logo = \App\Model\BusinessSetting::where(['key' => 'logo'])->first()->value)
        <img class="restaurant-logo" src="{{asset('storage/app/public/restaurant/' . $logo)}}"
            onerror="this.src='{{asset('public/assets/admin/img/160x160/img2.jpg')}}'" alt="logo">
        <div>
            <h1 class="invoice-title">{{translate('فاتورة')}}</h1>
        </div>
        <div style="font-size: 12px; color: var(--text-muted);">
            {{\App\Model\BusinessSetting::where(['key' => 'restaurant_name'])->first()->value}}<br>
            {{\App\Model\BusinessSetting::where(['key' => 'address'])->first()->value}}
        </div>
    </div>

    <div class="info-line">
        <span class="info-label">{{translate('الرقم الحساب')}}:</span>
        <span>{{$order['id']}}</span>
    </div>
    <div class="info-line">
        <span class="info-label">{{translate('التاريخ')}}:</span>
        <span>{{date('d/M/Y h:i a', strtotime($order['created_at']))}}</span>
    </div>

    @if($order->customer)
        <div class="info-line">
            <span class="info-label">{{translate('اسم العميل')}}:</span>
            <span>{{$order->customer['name']}}</span>
        </div>
        <div class="info-line">
            <span class="info-label">{{translate('الهاتف')}}:</span>
            <span>{{$order->customer['phone']}}</span>
        </div>
    @endif

    <table class="invoice-table">
        <thead>
            <tr>
                <th style="width: 15%; text-align: center;">{{translate('الكمية')}}</th>
                <th>{{translate('الوصف')}}</th>
                <th style="width: 25%; text-align: left;">{{translate('التسعير')}}</th>
            </tr>
        </thead>
        <tbody>
            @php($itemPrice = 0)
            @php($totalTax = 0)
            @php($addOnsCost = 0)
            @php($addOnsTaxCost = 0)
            @foreach($order->details as $detail)
            @if($detail->product)
            @php($addOnQtys = json_decode($detail['add_on_qtys'], true))
            @php($addOnPrices = json_decode($detail['add_on_prices'], true))
            @php($addOnTaxes = json_decode($detail['add_on_taxes'], true))
            <tr>
                <td style="text-align: center;">{{$detail['quantity']}}</td>
                <td>
                    <div class="item-desc">{{$detail->product['name']}}</div>
                    @if (count(json_decode($detail['variation'], true)) > 0)
                        @foreach(json_decode($detail['variation'], true) as $variation)
                            @if (isset($variation['name']) && isset($variation['values']))
                                @foreach ($variation['values'] as $value)
                                    <div class="item-meta">{{$value['label']}}: {{Helpers::set_symbol($value['optionPrice'])}}</div>
                                @endforeach
                            @endif
                        @endforeach
                    @endif
                    @foreach(json_decode($detail['add_on_ids'], true) as $key2 => $id)
                    @php($addon = \App\Model\AddOn::find($id))
                    <div class="item-meta">+ {{$addon ? $addon['name'] : translate('Addon')}}
                        ({{$addOnQtys[$key2] ?? 1}} x {{Helpers::set_symbol($addOnPrices[$key2] ?? 0)}})</div>
                    @php($addOnsCost += ($addOnPrices[$key2] ?? 0) * ($addOnQtys[$key2] ?? 1))
                    @php($addOnsTaxCost += ($addOnTaxes[$key2] ?? 0) * ($addOnQtys[$key2] ?? 1))
                    @endforeach
                </td>
                <td style="text-align: left;">
                    @php($amount = ($detail['price'] - $detail['discount_on_product']) * $detail['quantity'])
                    {{ Helpers::set_symbol($amount) }}
                </td>
            </tr>
            @php($itemPrice += $amount)
            @php($totalTax += $detail['tax_amount'] * $detail['quantity'])
            @endif
            @endforeach
        </tbody>
    </table>

    <div class="summary-section">
        <div class="summary-row">
            <span>{{translate('سعر التوصيل')}}:</span>
            <span>{{Helpers::set_symbol($itemPrice)}}</span>
        </div>
        @if($addOnsCost > 0)
            <div class="summary-row">
                <span>{{translate('تكلفة الإضافات')}}:</span>
                <span>{{Helpers::set_symbol($addOnsCost)}}</span>
            </div>
        @endif
        @if($order['coupon_discount_amount'] > 0)
            <div class="summary-row">
                <span>{{translate('خصم القسيمة')}}:</span>
                <span>-{{ Helpers::set_symbol($order['coupon_discount_amount']) }}</span>
            </div>
        @endif
        @if($order['extra_discount'] > 0)
            <div class="summary-row">
                <span>{{translate('خصم إضافي')}}:</span>
                <span>-{{ Helpers::set_symbol($order['extra_discount']) }}</span>
            </div>
        @endif
        <div class="summary-row">
            <span>{{translate('ضريبة القيمة المضافة')}}:</span>
            <span>{{Helpers::set_symbol($totalTax + $addOnsTaxCost)}}</span>
        </div>
        <div class="summary-row">
            <span>{{translate('رسوم التوصيل')}}:</span>
            <span>{{ Helpers::set_symbol($order['order_type'] == 'take_away' ? 0 : $order['delivery_charge']) }}</span>
        </div>

        @php($grand_total = $itemPrice + $addOnsCost + $totalTax + $addOnsTaxCost - $order['coupon_discount_amount'] - $order['extra_discount'] + ($order['order_type'] == 'take_away' ? 0 : $order['delivery_charge']))

        <div class="summary-row total">
            <span>{{translate('الإجمالي')}}:</span>
            <span>{{ Helpers::set_symbol($grand_total) }}</span>
        </div>

        @if($order->order_change_amount()->exists())
        <div class="summary-row">
            <span>{{translate('المبلغ المدفوع')}}:</span>
            <span>{{ Helpers::set_symbol($order->order_change_amount?->paid_amount) }}</span>
        </div>
        @php($changeOrDueAmount = $order->order_change_amount?->paid_amount - $order->order_change_amount?->order_amount)
        <div class="summary-row">
            <span>{{$changeOrDueAmount < 0 ? translate('المبلغ المتبقي') : translate('المبلغ المرتجع') }}:</span>
            <span>{{ Helpers::set_symbol($changeOrDueAmount) }}</span>
        </div>
        @endif
    </div>

    <div class="footer-section">
        <div style="font-weight: 700; margin-bottom: 5px;">{{translate('تم الدفع بواسطة')}}:
            {{ translate($order->payment_method)}}</div>
        <div style="font-size: 16px; margin: 10px 0;">✧ {{translate('شكراً لك')}} ✧</div>
        <div style="color: var(--text-muted);">
            {{\App\Model\BusinessSetting::where(['key' => 'footer_text'])->first()->value}}
        </div>
    </div>
</div>