<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('admin_roles', function (Blueprint $table) {
            $table->foreignId('branch_id')->nullable()->after('name')->constrained('branches')->nullOnDelete();
        });

        Schema::table('admins', function (Blueprint $table) {
            $table->foreignId('branch_id')->nullable()->after('admin_role_id')->constrained('branches')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('admin_roles', function (Blueprint $table) {
            $table->dropForeign(['branch_id']);
            $table->dropColumn('branch_id');
        });

        Schema::table('admins', function (Blueprint $table) {
            $table->dropForeign(['branch_id']);
            $table->dropColumn('branch_id');
        });
    }
};