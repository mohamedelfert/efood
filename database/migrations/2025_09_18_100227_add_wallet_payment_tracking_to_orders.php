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
        if (!Schema::hasColumn('orders', 'wallet_transaction_id')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->unsignedBigInteger('wallet_transaction_id')->nullable();
                $table->decimal('wallet_amount_used', 24, 3)->default(0);
                
                $table->foreign('wallet_transaction_id')->references('id')->on('wallet_transactions')->onDelete('set null');
                $table->index('wallet_transaction_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['wallet_transaction_id']);
            $table->dropIndex(['wallet_transaction_id']);
            $table->dropColumn(['wallet_transaction_id', 'wallet_amount_used']);
        });
    }
};
