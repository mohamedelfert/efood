<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            if (!Schema::hasColumn('branches', 'image')) {
                $table->string('image')->nullable();
            }
            if (!Schema::hasColumn('branches', 'cover_image')) {
                $table->string('cover_image')->nullable();
            }
            if (!Schema::hasColumn('branches', 'phone')) {
                $table->string('phone')->nullable();
            }
            if (!Schema::hasColumn('branches', 'preparation_time')) {
                $table->integer('preparation_time')->default(30);
            }
            if (!Schema::hasColumn('branches', 'status')) {
                $table->boolean('status')->default(1);
            }
        });
    }

    public function down(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            $table->dropColumn(['image', 'cover_image', 'phone', 'preparation_time', 'status']);
        });
    }
};