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
        Schema::table('categories', function (Blueprint $table) {
            $table->boolean('all_branches')->default(0)->after('branch_ids');
        });

        Schema::table('admin_roles', function (Blueprint $table) {
            $table->boolean('all_branches')->default(0)->after('branch_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn('all_branches');
        });

        Schema::table('admin_roles', function (Blueprint $table) {
            $table->dropColumn('all_branches');
        });
    }
};
