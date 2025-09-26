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
        Schema::table('wallet_transactions', function (Blueprint $table) {
            $table->json('gateway_data')->nullable();
            $table->string('currency', 3)->default('SAR');
            $table->decimal('exchange_rate', 10, 4)->default(1);
            $table->decimal('original_amount', 24, 3)->nullable();
            $table->decimal('fee_amount', 24, 3)->default(0);
            $table->string('gateway')->nullable();
            $table->unsignedBigInteger('order_id')->nullable();
            $table->enum('status', ['pending', 'completed', 'failed'])->default('completed')->index();
            $table->string('payment_gateway', 50)->nullable()->index();
            $table->json('metadata')->nullable();

            // Foreign key constraints
            $table->foreign('order_id')->references('id')->on('orders')->onDelete('set null');

            // Indexes for performance
            $table->index(['user_id', 'created_at'], 'wallet_transactions_user_id_created_at_index');
            $table->index(['user_id', 'status'], 'wallet_transactions_user_id_status_index');
            $table->index(['user_id', 'transaction_type'], 'wallet_transactions_user_id_transaction_type_index');
            $table->index(['status', 'created_at'], 'wallet_transactions_status_created_at_index');
            $table->index('order_id', 'wallet_transactions_order_id_index');
        });

        Schema::table('wallet_transactions', function (Blueprint $table) {
            // Modify admin_bonus only if necessary
            if (Schema::hasColumn('wallet_transactions', 'admin_bonus')) {
                $table->string('admin_bonus', 255)->nullable()->change();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_transactions', function (Blueprint $table) {
            $table->dropColumn([
                'gateway_data',
                'currency',
                'exchange_rate',
                'original_amount',
                'fee_amount',
                'gateway',
                'order_id',
                'status',
                'payment_gateway',
                'metadata'
            ]);
        });

        Schema::table('wallet_transactions', function (Blueprint $table) {
            if (Schema::hasColumn('wallet_transactions', 'admin_bonus')) {
                $table->string('admin_bonus', 255)->nullable()->change();
            }
        });
    }
};
