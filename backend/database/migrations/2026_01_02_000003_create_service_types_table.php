<?php
// database/migrations/xxxx_xx_xx_xxxxxx_create_service_types_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_types', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // e.g., "Regular Clothes", "Comforter / Blanket"
            $table->string('slug')->unique(); // e.g., "regular-clothes", "comforter-blanket"
            $table->string('category'); // drop_off, self_service, addon
            $table->text('description')->nullable();
            $table->json('defaults')->nullable(); // Store default price, max_weight, turnaround
            $table->string('icon')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('display_order')->default(0);
            $table->timestamps();
            
            $table->index(['category', 'is_active']);
            $table->index('display_order');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_types');
    }
};