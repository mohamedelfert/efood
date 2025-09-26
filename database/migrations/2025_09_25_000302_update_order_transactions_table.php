<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateOrderTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('order_transactions', function (Blueprint $table) {
            // Add missing columns
            $table->bigInteger('user_id')->nullable()->index()->after('id');
            $table->string('transaction_id')->index()->after('order_id');
            $table->string('reference', 191)->nullable()->index()->after('transaction_id');
            $table->string('transaction_type', 191)->nullable()->index()->after('reference');
            $table->decimal('credit', 24, 3)->default(0)->after('transaction_type');
            $table->decimal('debit', 24, 3)->default(0)->after('credit');
            $table->decimal('balance', 24, 3)->default(0)->after('debit');
            $table->decimal('admin_commission', 24, 3)->default(0)->after('tax');
            $table->decimal('total_amount', 24, 3)->default(0)->after('admin_commission');
            $table->string('payment_gateway', 50)->nullable()->index()->after('total_amount');
            $table->json('gateway_data')->nullable()->after('payment_gateway');
            $table->string('currency', 3)->default('SAR')->after('gateway_data');
            $table->decimal('exchange_rate', 10, 4)->default(1)->after('currency');
            $table->enum('status', ['pending', 'completed', 'failed', 'refunded'])->default('completed')->index()->after('exchange_rate');
            $table->text('notes')->nullable()->after('status');

            // Modify existing columns to match specifications
            $table->decimal('order_amount', 24, 3)->default(0)->change();
            $table->decimal('delivery_charge', 24, 3)->default(0)->change();
            $table->decimal('original_delivery_charge', 24, 3)->default(0)->change();
            $table->decimal('tax', 24, 3)->default(0)->change();

            // Add additional indexes
            $table->index(['status', 'created_at']);
            $table->index(['user_id', 'created_at']);
            $table->index(['order_id', 'created_at']);
            $table->index(['user_id', 'status']);
            $table->index(['user_id', 'transaction_type']);
            $table->index(['order_id', 'status']);
            $table->index(['transaction_type', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('order_transactions', function (Blueprint $table) {
            // Drop added columns
            $table->dropColumn([
                'user_id',
                'transaction_id',
                'reference',
                'transaction_type',
                'credit',
                'debit',
                'balance',
                'admin_commission',
                'total_amount',
                'payment_gateway',
                'gateway_data',
                'currency',
                'exchange_rate',
                'status',
                'notes'
            ]);

            // Revert modified columns
            $table->decimal('order_amount')->default(0)->change();
            $table->decimal('delivery_charge')->default(0)->change();
            $table->decimal('original_delivery_charge')->default(0)->change();
            $table->decimal('tax')->default(0)->change();

            // Drop added indexes
            $table->dropIndex(['status', 'created_at']);
            $table->dropIndex(['user_id', 'created_at']);
            $table->dropIndex(['order_id', 'created_at']);
            $table->dropIndex(['user_id', 'status']);
            $table->dropIndex(['user_id', 'transaction_type']);
            $table->dropIndex(['order_id', 'status']);
            $table->dropIndex(['transaction_type', 'created_at']);
        });
    }
}