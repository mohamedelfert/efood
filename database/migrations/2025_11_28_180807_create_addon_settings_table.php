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
        Schema::create('addon_settings', function (Blueprint $table) {
            $table->char('id', 36)->primary();
            $table->string('key_name', 191)->nullable();
            $table->longText('live_values')->nullable();
            $table->longText('test_values')->nullable();
            $table->string('settings_type', 255)->nullable();
            $table->string('mode', 20)->default('live');
            $table->boolean('is_active')->default(1);
            $table->timestamps();

            $table->json('additional_data')->nullable();

            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('addon_settings');
    }
};
