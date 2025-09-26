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
        Schema::create('payment_methods', function (Blueprint $table) {
            $table->id('id');
            $table->unsignedBigInteger('user_id');
            $table->string('type', 50); // 'card', 'bank_account', 'paypal', 'mobile_money'
            $table->string('provider', 50); // 'stripe', 'paypal', 'razorpay', 'flutterwave'
            $table->string('provider_id')->nullable();
            $table->string('last_four', 4)->nullable();
            $table->string('brand', 50)->nullable(); // 'visa', 'mastercard', 'vodafone_cash'
            $table->json('metadata')->nullable();
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->index(['user_id', 'is_default']);
            $table->index(['user_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_methods');
    }
};
