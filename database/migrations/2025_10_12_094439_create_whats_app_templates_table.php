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
        Schema::create('whatsapp_templates', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('body');
            $table->string('button_name')->nullable();
            $table->text('footer_text')->nullable();
            $table->text('copyright_text')->nullable();
            $table->string('image')->nullable();
            $table->string('logo')->nullable();
            $table->string('whatsapp_type');
            $table->string('type'); 
            $table->string('button_url')->nullable();
            $table->string('whatsapp_template')->nullable();
            $table->boolean('privacy')->default(0);
            $table->boolean('refund')->default(0);
            $table->boolean('cancelation')->default(0);
            $table->boolean('contact')->default(0);
            $table->boolean('facebook')->default(0);
            $table->boolean('instagram')->default(0);
            $table->boolean('twitter')->default(0);
            $table->boolean('linkedin')->default(0);
            $table->boolean('pinterest')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('whatsapp_templates');
    }
};
