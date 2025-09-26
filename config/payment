<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Payment Gateway Configuration
    |--------------------------------------------------------------------------
    | Configuration for various payment gateways and banks
    */

    // ===============================
    // AlQutaibi Bank Configuration
    // ===============================
    'alqutaibi' => [
        'enabled' => env('ALQUTAIBI_BANK_ENABLED', true),
        'is_production' => env('ALQUTAIBI_BANK_PRODUCTION', false),
        'base_url' => env('ALQUTAIBI_BANK_URL', 'https://newdc.qtb-bank.com:5052/PayBills'),
        'production_url' => env('ALQUTAIBI_BANK_PRODUCTION_URL', 'https://prod.qtb-bank.com/PayBills'),
        'api_key' => env('ALQUTAIBI_BANK_API_KEY'),
        'app_key' => env('ALQUTAIBI_BANK_APP_KEY'),
        'timeout' => env('ALQUTAIBI_BANK_TIMEOUT', 30),
        'max_amount' => env('ALQUTAIBI_BANK_MAX_AMOUNT', 50000),
        'min_amount' => env('ALQUTAIBI_BANK_MIN_AMOUNT', 1),
        'supported_currencies' => ['YER', 'SAR', 'USD'],
        'fees' => [
            'fixed' => 0,
            'percentage' => 0
        ]
    ],

    // ===============================
    // International Cards (PayMob)
    // ===============================

    'paymob' => [
        'enabled' => env('PAYMOB_ENABLED', true),
        'api_key' => env('PAYMOB_API_KEY'),
        'integration_id' => env('PAYMOB_INTEGRATION_ID'),
        'iframe_id' => env('PAYMOB_IFRAME_ID'),
        'hmac_secret' => env('PAYMOB_HMAC_SECRET'),
        'public_key' => env('PAYMOB_PUBLIC_KEY'),
        'secret_key' => env('PAYMOB_SECRET_KEY'),
        'base_url' => env('PAYMOB_BASE_URL', 'https://accept.paymob.com/api'),
        'mode' => env('PAYMOB_MODE', 'test'),
        'supported_currencies' => ['EGP', 'USD', 'EUR', 'SAR'],
        'max_amount' => env('PAYMOB_MAX_AMOUNT', 100000),
        'min_amount' => env('PAYMOB_MIN_AMOUNT', 1),
        'fees' => [
            'fixed' => 0,
            'percentage' => 2.5
        ]
    ],

    // ===============================
    // General Payment Settings
    // ===============================
    'general' => [
        'default_currency' => env('DEFAULT_CURRENCY', 'SAR'),
        'currency_conversion_api' => env('CURRENCY_API_KEY'),
        'transaction_timeout' => env('PAYMENT_TIMEOUT', 900), // 15 minutes
        'otp_timeout' => env('OTP_TIMEOUT', 300), // 5 minutes
        'max_retry_attempts' => env('PAYMENT_MAX_RETRIES', 3),
        'webhook_timeout' => env('WEBHOOK_TIMEOUT', 30),
        'enable_logging' => env('PAYMENT_LOGGING', true),
        'log_level' => env('PAYMENT_LOG_LEVEL', 'info'), // debug, info, warning, error
        
        // Security settings
        'enable_ip_whitelist' => env('PAYMENT_IP_WHITELIST', false),
        'allowed_ips' => explode(',', env('PAYMENT_ALLOWED_IPS', '')),
        'enable_rate_limiting' => env('PAYMENT_RATE_LIMITING', true),
        'rate_limit_per_minute' => env('PAYMENT_RATE_LIMIT', 10),
        
        // Notification settings
        'send_sms_notifications' => env('PAYMENT_SMS_NOTIFICATIONS', true),
        'send_email_notifications' => env('PAYMENT_EMAIL_NOTIFICATIONS', true),
        'admin_notification_email' => env('PAYMENT_ADMIN_EMAIL'),
    ],

    // ===============================
    // Error Messages & Codes
    // ===============================
    'error_messages' => [
        'insufficient_balance' => [
            'ar' => 'الرصيد غير كافي',
            'en' => 'Insufficient balance'
        ],
        'invalid_amount' => [
            'ar' => 'المبلغ غير صحيح',
            'en' => 'Invalid amount'
        ],
        'payment_failed' => [
            'ar' => 'فشل في عملية الدفع',
            'en' => 'Payment failed'
        ],
        'invalid_otp' => [
            'ar' => 'رمز التحقق غير صحيح',
            'en' => 'Invalid OTP'
        ],
        'expired_otp' => [
            'ar' => 'رمز التحقق منتهي الصلاحية',
            'en' => 'OTP expired'
        ],
        'transaction_not_found' => [
            'ar' => 'المعاملة غير موجودة',
            'en' => 'Transaction not found'
        ],
        'service_unavailable' => [
            'ar' => 'الخدمة غير متاحة حالياً',
            'en' => 'Service temporarily unavailable'
        ],
        'daily_limit_exceeded' => [
            'ar' => 'تم تجاوز الحد اليومي للمعاملات',
            'en' => 'Daily transaction limit exceeded'
        ],
        'monthly_limit_exceeded' => [
            'ar' => 'تم تجاوز الحد الشهري للمعاملات',
            'en' => 'Monthly transaction limit exceeded'
        ]
    ],

    // ===============================
    // Default Transaction Limits
    // ===============================
    'transaction_limits' => [
        'daily' => [
            'charge' => env('DAILY_CHARGE_LIMIT', 50000),
            'transfer' => env('DAILY_TRANSFER_LIMIT', 25000),
            'withdrawal' => env('DAILY_WITHDRAWAL_LIMIT', 10000)
        ],
        'monthly' => [
            'charge' => env('MONTHLY_CHARGE_LIMIT', 500000),
            'transfer' => env('MONTHLY_TRANSFER_LIMIT', 250000),
            'withdrawal' => env('MONTHLY_WITHDRAWAL_LIMIT', 100000)
        ],
        'per_transaction' => [
            'min_charge' => env('MIN_CHARGE_AMOUNT', 1),
            'max_charge' => env('MAX_CHARGE_AMOUNT', 100000),
            'min_transfer' => env('MIN_TRANSFER_AMOUNT', 1),
            'max_transfer' => env('MAX_TRANSFER_AMOUNT', 50000)
        ]
    ]
];