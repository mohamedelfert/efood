<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('product_by_branches', function (Blueprint $table) {
            DB::statement('ALTER TABLE `product_by_branches` MODIFY COLUMN `sold_quantity` int(11) NOT NULL DEFAULT 0;');

            // Add indexes
            $table->index(['product_id', 'branch_id'], 'idx_product_branch');
            $table->index('is_available', 'idx_availability');
            $table->index('stock_type', 'idx_stock_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_by_branches', function (Blueprint $table) {
            // Drop added indexes if rolled back
            $table->dropIndex('idx_product_branch');
            $table->dropIndex('idx_availability');
            $table->dropIndex('idx_stock_type');
        });
    }
};
