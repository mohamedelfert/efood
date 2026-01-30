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
        'production_url' => env('ALQUTAIBI_BANK_PRODUCTION_URL', env('ALQUTAIBI_BANK_URL', 'https://prod.qtb-bank.com/PayBills')),
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
    // Kuraimi Bank Configuration
    // ===============================
    'kuraimi' => [
        // Basic Settings
        'enabled' => env('KURAIMI_BANK_ENABLED', true),
        'is_production' => env('KURAIMI_BANK_PRODUCTION', false),

        // API URLs
        'uat_url' => env('KURAIMI_BANK_UAT_URL', 'https://web.krmbank.net.ye:44746/alk-paymentsexp'),
        'production_url' => env('KURAIMI_BANK_PRODUCTION_URL', 'https://web.krmbank.net.ye/alk-payments'),

        // Authentication
        'username' => env('KURAIMI_BANK_USERNAME'),
        'password' => env('KURAIMI_BANK_PASSWORD'),

        // Connection Settings
        'timeout' => env('KURAIMI_BANK_TIMEOUT', 30),
        'verify_ssl' => env('KURAIMI_VERIFY_SSL', true),
        'max_retries' => env('KURAIMI_MAX_RETRIES', 3),
        'retry_delay' => env('KURAIMI_RETRY_DELAY', 2), // seconds

        // Currency & Limits
        'supported_currencies' => ['YER', 'SAR', 'USD'],
        'default_currency' => env('KURAIMI_DEFAULT_CURRENCY', 'YER'),
        'min_amount' => env('KURAIMI_MIN_AMOUNT', 1),
        'max_amount' => env('KURAIMI_MAX_AMOUNT', 100000),

        // Merchant Settings
        'default_customer_zone' => env('KURAIMI_CUSTOMER_ZONE', 'YE0012004'),
        'merchant_name' => env('KURAIMI_MERCHANT_NAME', config('app.name', 'eFOOD')),

        // Fees
        'fees' => [
            'fixed' => 0,
            'percentage' => 0
        ],

        // Features
        'supports_refund' => true,
        'supports_partial_refund' => true,
        'supports_reversal' => true,
        'supports_status_check' => true,
        'requires_otp' => false, // Kuraimi is synchronous, no OTP
        'is_synchronous' => true,

        // Logging
        'log_requests' => env('KURAIMI_LOG_REQUESTS', true),
        'log_responses' => env('KURAIMI_LOG_RESPONSES', true),

        // Testing
        'test_mode' => env('KURAIMI_TEST_MODE', !env('KURAIMI_BANK_PRODUCTION', false)),
        'test_customer_id' => env('KURAIMI_TEST_CUSTOMER_ID', 'TEST_CUST_001'),
        'test_pin' => env('KURAIMI_TEST_PIN', '1234'),

        // Error Code Mapping (from Kuraimi API documentation)
        'error_codes' => [
            1 => 'Success',
            3 => 'System exception occurred',
            35 => 'Invalid data input',
            36 => 'Undefined error',
            86 => 'User found in blacklist',
            111 => 'Transaction not allowed - frozen account',
            190 => 'Invalid customer account',
            232 => 'User must be registered in Kuraimi app first',
            233 => 'Supplier zone not available',
            238 => 'Reference number already exists',
            260 => 'Transaction limit exceeded',
            270 => 'Wrong PIN, please check and try again',
            271 => 'Insufficient balance',
            272 => 'Duplicate request detected',
        ],

        // Status Mapping
        'status_map' => [
            'INITIATED' => 'pending',
            'PAID' => 'completed',
            'REFUNDED' => 'refunded',
            'REVERSED' => 'reversed',
            'PARTIALREFUNDED' => 'partial_refunded',
        ],

        // Callback Settings (not used by Kuraimi, but kept for interface compatibility)
        'callback_enabled' => false,
        'callback_url' => null,
    ],

    // ===============================
    // International Cards (Stripe)
    // ===============================

    'stripe' => [
        'enabled' => env('STRIPE_ENABLED', true),
        'secret_key' => env('STRIPE_SECRET_KEY'),
        'publishable_key' => env('STRIPE_PUBLISHABLE_KEY'),
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
        'mode' => env('STRIPE_MODE', 'test'),
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