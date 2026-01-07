<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('cashback_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->onDelete('cascade');
            $table->string('type'); // 'wallet_topup' or 'order'
            $table->string('cashback_type'); // 'percentage' or 'fixed'
            $table->decimal('cashback_value', 24, 3)->default(0);
            $table->decimal('min_amount', 24, 3)->default(0);
            $table->boolean('status')->default(1);
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->string('title')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
            
            // Index for performance
            $table->index(['branch_id', 'type', 'status']);
        });

        // Insert default settings
        DB::table('cashback_settings')->insert([
            [
                'branch_id' => null, // Global setting
                'type' => 'wallet_topup',
                'cashback_type' => 'percentage',
                'cashback_value' => 5,
                'min_amount' => 100,
                'status' => 1,
                'start_date' => now(),
                'end_date' => now()->addYear(),
                'title' => 'Wallet Top-up Cashback',
                'description' => 'Get 5% cashback on wallet top-ups',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'branch_id' => null, // Global setting
                'type' => 'order',
                'cashback_type' => 'percentage',
                'cashback_value' => 2,
                'min_amount' => 50,
                'status' => 1,
                'start_date' => now(),
                'end_date' => now()->addYear(),
                'title' => 'Order Cashback',
                'description' => 'Get 2% cashback on orders',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cashback_settings');
    }
};