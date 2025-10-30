<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: 'DejaVu Sans', sans-serif; margin: 0; padding: 20px; max-width: 400px; background: white; }
        .receipt { border: 1px solid #ddd; padding: 15px; text-align: center; }
        .logo { width: 80px; margin-bottom: 10px; }
        .header { font-size: 18px; font-weight: bold; margin: 10px 0; }
        .info { font-size: 12px; margin: 5px 0; text-align: right; }
        .amount { font-size: 16px; font-weight: bold; color: green; }
        .footer { font-size: 10px; margin-top: 20px; text-align: center; border-top: 1px solid #eee; padding-top: 10px; }
        .green-box { background: #28a745; color: white; padding: 10px; margin: 10px 0; }
    </style>
</head>
<body>
    <div class="receipt">
        @if($company_logo)
            <img src="{{ $company_logo }}" alt="Logo" class="logo">
        @endif
        <div class="header">{{ $company_name ?? 'QNB Alahli Bank' }}</div>
        <div class="info"><strong>رقم الإيصال:</strong> {{ $transaction_id }}</div>
        <div class="info"><strong>رقم الحساب:</strong> {{ $account_number }}</div>
        <hr style="border: none; height: 1px; background: #ddd;">
        <div class="info"><strong>التاريخ:</strong> {{ $date }} {{ $time }}</div>
        <div class="info"><strong>المبلغ:</strong> <span class="amount">{{ $amount }} {{ $currency }}</span></div>
        <div class="info"><strong>الرصيد السابق:</strong> {{ $previous_balance }} {{ $currency }}</div>
        <div class="info"><strong>الرصيد الجديد:</strong> {{ $new_balance }} {{ $currency }}</div>
        <div class="info"><strong>الضريبة:</strong> {{ $tax }} {{ $currency }}</div>
        <hr style="border: none; height: 1px; background: #ddd;">
        <div class="green-box">
            <strong>إيصال شحن محفظة ناجح!</strong>
        </div>
        <div class="footer">
            <div>العميل: {{ $customer_name }}</div>
            <div>الفرع: {{ $branch }}</div>
            <div>{{ $footer_text ?? 'شكراً لاستخدام خدماتنا' }}</div>
            <p>{{ \Carbon\Carbon::now()->locale('ar')->formatLocalized('%A، %d %B %Y') }}</p> {{-- ديناميكي: الثلاثاء، 14 أكتوبر 2025 --}}
            <p>هاتف: {{ $company_phone }} | بريد: {{ $company_email }}</p>
            <p>{{ $company_address }}</p>
        </div>
    </div>
</body>
</html>