<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('system_payment_methods', function (Blueprint $table) {
            $table->id();
            $table->string('method_name'); // e.g., "Stripe", "My Stripe"
            $table->string('slug')->unique(); // e.g., "stripe", "stripe_2"
            $table->string('driver_name'); // e.g., "stripe", "qib"
            $table->json('settings')->nullable(); // stored keys and configs
            $table->string('image')->nullable();
            $table->boolean('is_active')->default(true);
            $table->string('mode')->default('test'); // 'live' or 'test'
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('system_payment_methods');
    }
};
