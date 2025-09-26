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
        Schema::create('payment_sessions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('session_id')->unique();
            $table->bigInteger('user_id')->nullable();
            $table->string('gateway');
            $table->decimal('amount', 24, 3);
            $table->string('currency', 3)->default('SAR');
            $table->string('purpose'); // wallet_topup, order_payment, bill_payment
            $table->string('status')->default('pending'); // pending, completed, failed, expired
            $table->json('customer_data')->nullable();
            $table->json('gateway_response')->nullable();
            $table->string('payment_url')->nullable();
            $table->string('transaction_id')->nullable();
            $table->string('gateway_reference')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index(['session_id']);
            $table->index(['user_id', 'status']);
            $table->index(['gateway', 'status']);
            $table->index(['status', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_sessions');
    }
};
