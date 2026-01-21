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
        Schema::create('branch_wallet_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id');
            $table->string('transaction_id');
            $table->string('reference')->nullable();
            $table->string('transaction_type');
            $table->decimal('debit', 24, 2)->default(0);
            $table->decimal('credit', 24, 2)->default(0);
            $table->decimal('balance', 24, 2)->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('branch_wallet_transactions');
    }
};
