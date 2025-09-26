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
        Schema::create('wallet_limits', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('user_id');
            $table->string('limit_type'); // daily_topup, daily_transfer, monthly_topup, monthly_transfer
            $table->decimal('limit_amount', 24, 3);
            $table->decimal('used_amount', 24, 3)->default(0);
            $table->date('period_start');
            $table->date('period_end');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['user_id', 'limit_type']);
            $table->index(['period_start', 'period_end']);
            $table->unique(['user_id', 'limit_type', 'period_start']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wallet_limits');
    }
};
