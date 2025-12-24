<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('service_type'); // e.g., 'delivery', 'dine_in', 'takeaway'
            $table->foreignId('order_id')->constrained('orders')->onDelete('cascade');
            $table->decimal('rating', 3, 2)->default(0);
            $table->text('comment')->nullable();
            $table->json('attachment')->nullable();
            $table->json('service_ratings')->nullable(); // For multiple service aspects
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['service_type', 'rating']);
            $table->index(['user_id', 'order_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_reviews');
    }
};