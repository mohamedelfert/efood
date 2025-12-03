<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name',100)->nullable();
            $table->string('phone',20)->unique();
            $table->string('email',100)->nullable();
            $table->string('image',100)->nullable();
            $table->boolean('is_phone_verified')->default(0);
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password',100);
            $table->boolean('wallet_email_notifications')->default(true);
            $table->boolean('wallet_sms_notifications')->default(false);
            $table->boolean('wallet_push_notifications')->default(true);
            $table->rememberToken();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('users');
    }
}
