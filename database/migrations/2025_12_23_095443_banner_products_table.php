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
        // Create banner_products pivot table if it doesn't exist
        if (!Schema::hasTable('banner_products')) {
            Schema::create('banner_products', function (Blueprint $table) {
                $table->id();
                $table->foreignId('banner_id')->constrained('banners')->onDelete('cascade');
                $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
                $table->timestamps();
                
                $table->unique(['banner_id', 'product_id']);
            });
        }

        // Update banners table with missing columns
        Schema::table('banners', function (Blueprint $table) {
            // Add banner_type if it doesn't exist
            if (!Schema::hasColumn('banners', 'banner_type')) {
                $table->enum('banner_type', ['single_product', 'multiple_products', 'category'])
                    ->default('single_product')
                    ->after('image');
            }
            
            // Add total pricing columns
            if (!Schema::hasColumn('banners', 'total_offer_price')) {
                $table->decimal('total_offer_price', 24, 2)->nullable()->after('category_id');
            }
            if (!Schema::hasColumn('banners', 'total_discount_amount')) {
                $table->decimal('total_discount_amount', 24, 2)->nullable()->after('total_offer_price');
            }
            if (!Schema::hasColumn('banners', 'total_discount_percentage')) {
                $table->decimal('total_discount_percentage', 5, 2)->nullable()->after('total_discount_amount');
            }
            if (!Schema::hasColumn('banners', 'discount_type')) {
                $table->enum('discount_type', ['fixed', 'percentage'])->default('fixed')->after('total_discount_percentage');
            }
            
            // Add date range columns
            if (!Schema::hasColumn('banners', 'start_date')) {
                $table->date('start_date')->nullable()->after('discount_type');
            }
            if (!Schema::hasColumn('banners', 'end_date')) {
                $table->date('end_date')->nullable()->after('start_date');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('banner_products');
        
        Schema::table('banners', function (Blueprint $table) {
            $columns = [
                'banner_type',
                'total_offer_price',
                'total_discount_amount',
                'total_discount_percentage',
                'discount_type',
                'start_date',
                'end_date'
            ];
            
            foreach ($columns as $column) {
                if (Schema::hasColumn('banners', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};