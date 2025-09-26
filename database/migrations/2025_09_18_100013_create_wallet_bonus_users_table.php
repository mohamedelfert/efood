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
        Schema::create('wallet_bonus_users', function (Blueprint $table) {
             $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('wallet_bonus_id');
            $table->decimal('bonus_amount', 24, 3)->default(0);
            $table->decimal('add_amount', 24, 3)->default(0);
            $table->timestamps();
            
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('wallet_bonus_id')->references('id')->on('wallet_bonuses')->onDelete('cascade');
            $table->unique(['user_id', 'wallet_bonus_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wallet_bonus_users');
    }
};
