<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Create banner_branches pivot table for many-to-many relationship
        Schema::create('banner_branches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('banner_id')->constrained('banners')->onDelete('cascade');
            $table->foreignId('branch_id')->constrained('branches')->onDelete('cascade');
            $table->timestamps();
            
            $table->unique(['banner_id', 'branch_id']);
            $table->index('banner_id');
            $table->index('branch_id');
        });

        // Add is_global column to banners table
        Schema::table('banners', function (Blueprint $table) {
            $table->boolean('is_global')->default(false)->after('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('banner_branches');
        
        Schema::table('banners', function (Blueprint $table) {
            $table->dropColumn('is_global');
        });
    }
};