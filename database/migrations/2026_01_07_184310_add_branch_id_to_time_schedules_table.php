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
        Schema::table('time_schedules', function (Blueprint $table) {
            // Add branch_id column if it doesn't exist
            if (!Schema::hasColumn('time_schedules', 'branch_id')) {
                $table->unsignedBigInteger('branch_id')->nullable()->after('id');
                $table->foreign('branch_id')->references('id')->on('branches')->onDelete('cascade');
            }
            
            // Add is_24_hours column if it doesn't exist
            if (!Schema::hasColumn('time_schedules', 'is_24_hours')) {
                $table->boolean('is_24_hours')->default(false)->after('closing_time');
            }
            
            // Add index for better performance
            if (!Schema::hasColumn('time_schedules', 'branch_id')) {
                $table->index(['branch_id', 'day']);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('time_schedules', function (Blueprint $table) {
            if (Schema::hasColumn('time_schedules', 'branch_id')) {
                $table->dropForeign(['branch_id']);
                $table->dropColumn('branch_id');
            }
            
            if (Schema::hasColumn('time_schedules', 'is_24_hours')) {
                $table->dropColumn('is_24_hours');
            }
        });
    }
};
