<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Update banners table
        Schema::table('banners', function (Blueprint $table) {
            // Add banner type if not exists
            if (!Schema::hasColumn('banners', 'banner_type')) {
                $table->enum('banner_type', ['single_product', 'multiple_products', 'category'])
                    ->default('single_product')
                    ->after('title');
            }
            
            // Remove old individual product pricing if exists
            if (Schema::hasColumn('banners', 'offer_price')) {
                $table->dropColumn('offer_price');
            }
            if (Schema::hasColumn('banners', 'discount_percentage')) {
                $table->dropColumn('discount_percentage');
            }
            
            // Add total offer pricing
            $table->decimal('total_offer_price', 24, 2)->nullable()->after('banner_type');
            $table->decimal('total_discount_amount', 24, 2)->nullable()->after('total_offer_price');
            $table->decimal('total_discount_percentage', 5, 2)->nullable()->after('total_discount_amount');
            $table->enum('discount_type', ['fixed', 'percentage'])->default('fixed')->after('total_discount_percentage');
            
            // Add date range if not exists
            if (!Schema::hasColumn('banners', 'start_date')) {
                $table->date('start_date')->nullable();
            }
            if (!Schema::hasColumn('banners', 'end_date')) {
                $table->date('end_date')->nullable();
            }
        });

        // Update banner_products pivot table - remove individual pricing
        if (Schema::hasTable('banner_products')) {
            Schema::table('banner_products', function (Blueprint $table) {
                if (Schema::hasColumn('banner_products', 'offer_price')) {
                    $table->dropColumn('offer_price');
                }
                if (Schema::hasColumn('banner_products', 'discount_percentage')) {
                    $table->dropColumn('discount_percentage');
                }
            });
        } else {
            // Create pivot table if it doesn't exist
            Schema::create('banner_products', function (Blueprint $table) {
                $table->id();
                $table->foreignId('banner_id')->constrained('banners')->onDelete('cascade');
                $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
                $table->timestamps();
                
                $table->unique(['banner_id', 'product_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::table('banners', function (Blueprint $table) {
            $table->dropColumn([
                'banner_type',
                'total_offer_price',
                'total_discount_amount',
                'total_discount_percentage',
                'discount_type'
            ]);
        });
    }
};