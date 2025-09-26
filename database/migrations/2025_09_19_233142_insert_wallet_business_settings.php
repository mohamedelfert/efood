<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
       DB::table('business_settings')->insertOrIgnore([
            ['key' => 'wallet_daily_top_up_limit', 'value' => '50000'],
            ['key' => 'wallet_daily_transfer_limit', 'value' => '25000'],
            ['key' => 'wallet_monthly_top_up_limit', 'value' => '500000'],
            ['key' => 'wallet_monthly_transfer_limit', 'value' => '250000'],
            ['key' => 'wallet_min_transfer_amount', 'value' => '1'],
            ['key' => 'wallet_max_transfer_amount', 'value' => '5000'],
            ['key' => 'wallet_min_top_up_amount', 'value' => '1'],
            ['key' => 'wallet_max_top_up_amount', 'value' => '50000'],
            ['key' => 'wallet_transfer_fee_percentage', 'value' => '0'],
            ['key' => 'wallet_transfer_fee_fixed', 'value' => '0'],
            ['key' => 'wallet_top_up_bonus_percentage', 'value' => '0'],
            ['key' => 'wallet_otp_expiry_minutes', 'value' => '5'],
            ['key' => 'wallet_pin_max_attempts', 'value' => '3'],
            ['key' => 'wallet_enabled', 'value' => '1'],
            ['key' => 'wallet_two_factor_enabled', 'value' => '0'],
            ['key' => 'paytabs_enabled', 'value' => '1'],
            ['key' => 'paypal_enabled', 'value' => '1'],
            ['key' => 'qib_bank_enabled', 'value' => '1'],
            ['key' => 'stripe_enabled', 'value' => '0'],
            ['key' => 'flutterwave_enabled', 'value' => '0'],
            ['key' => 'paystack_enabled', 'value' => '0'],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('business_settings')->whereIn('key', [
            'wallet_daily_top_up_limit',
            'wallet_daily_transfer_limit',
            'wallet_monthly_top_up_limit',
            'wallet_monthly_transfer_limit',
            'wallet_min_transfer_amount',
            'wallet_max_transfer_amount',
            'wallet_min_top_up_amount',
            'wallet_max_top_up_amount',
            'wallet_transfer_fee_percentage',
            'wallet_transfer_fee_fixed',
            'wallet_top_up_bonus_percentage',
            'wallet_otp_expiry_minutes',
            'wallet_pin_max_attempts',
            'wallet_enabled',
            'wallet_two_factor_enabled',
            'paytabs_enabled',
            'paypal_enabled',
            'qib_bank_enabled',
            'stripe_enabled',
            'flutterwave_enabled',
            'paystack_enabled',
        ])->delete();
    }
};
