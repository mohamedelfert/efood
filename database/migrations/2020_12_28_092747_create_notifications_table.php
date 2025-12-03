<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNotificationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('title',100)->nullable();
            $table->string('description')->nullable();
            $table->string('notification_type')->nullable(); // wallet_topup, money_transfer, etc.
            $table->string('reference_id')->nullable();
            $table->boolean('status')->default(1);
            $table->boolean('is_read')->default(false);
            $table->string('image',50)->nullable();
            $table->json('data')->nullable();
            $table->timestamps();
            
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('notifications');
    }
}
