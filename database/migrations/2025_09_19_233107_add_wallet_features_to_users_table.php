<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'wallet_pin')) {
                $table->string('wallet_pin')->nullable()->after('wallet_balance');
            }
            if (!Schema::hasColumn('users', 'wallet_pin_updated_at')) {
                $table->timestamp('wallet_pin_updated_at')->nullable()->after('wallet_pin');
            }
            if (!Schema::hasColumn('users', 'transfer_otp')) {
                $table->string('transfer_otp')->nullable()->after('wallet_pin_updated_at');
            }
            if (!Schema::hasColumn('users', 'transfer_otp_expires_at')) {
                $table->timestamp('transfer_otp_expires_at')->nullable()->after('transfer_otp');
            }
            if (!Schema::hasColumn('users', 'pending_transfer_data')) {
                $table->text('pending_transfer_data')->nullable()->after('transfer_otp_expires_at');
            }
            if (!Schema::hasColumn('users', 'two_factor_enabled')) {
                $table->boolean('two_factor_enabled')->default(false)->after('pending_transfer_data');
            }
            if (!Schema::hasColumn('users', 'two_factor_method')) {
                $table->string('two_factor_method')->nullable()->after('two_factor_enabled');
            }
            if (!Schema::hasColumn('users', 'two_factor_secret')) {
                $table->text('two_factor_secret')->nullable()->after('two_factor_method');
            }
            if (!Schema::hasColumn('users', 'notification_settings')) {
                $table->json('notification_settings')->nullable()->after('two_factor_secret');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'wallet_pin',
                'wallet_pin_updated_at',
                'transfer_otp',
                'transfer_otp_expires_at',
                'pending_transfer_data',
                'two_factor_enabled',
                'two_factor_method',
                'two_factor_secret',
                'notification_settings'
            ]);
        });
    }
};
