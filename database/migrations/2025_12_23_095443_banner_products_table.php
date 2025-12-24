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
        // Create banner_products pivot table
        Schema::create('banner_products', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('banner_id');
            $table->unsignedBigInteger('product_id');
            $table->decimal('offer_price', 10, 2)->nullable();
            $table->decimal('discount_percentage', 5, 2)->nullable();
            $table->timestamps();

            $table->foreign('banner_id')->references('id')->on('banners')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
        });

        // Add new columns to banners table
        Schema::table('banners', function (Blueprint $table) {
            $table->enum('banner_type', ['single_product', 'multiple_products', 'category'])->default('single_product')->after('title');
            $table->decimal('offer_price', 10, 2)->nullable()->after('product_id');
            $table->decimal('discount_percentage', 5, 2)->nullable()->after('offer_price');
            $table->date('start_date')->nullable()->after('discount_percentage');
            $table->date('end_date')->nullable()->after('start_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('banner_products');
        
        Schema::table('banners', function (Blueprint $table) {
            $table->dropColumn(['banner_type', 'offer_price', 'discount_percentage', 'start_date', 'end_date']);
        });
    }
};