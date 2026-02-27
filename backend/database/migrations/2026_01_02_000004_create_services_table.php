<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255);
            $table->string('slug', 255)->unique();
            $table->text('description')->nullable();
            $table->integer('duration_minutes')->default(60);
            $table->tinyInteger('is_active')->default(1);
            $table->integer('display_laundry')->default(0);
            $table->timestamps();
            $table->softDeletes();

            // Additional Pricing & Restriction Columns found in DB
            $table->decimal('price_per_piece', 10, 2)->nullable();
            $table->string('pricing_type', 20)->default('per_load');
            $table->decimal('price_per_load', 10, 2)->nullable();
            $table->decimal('min_weight', 8, 2)->nullable();
            $table->decimal('max_weight', 8, 2)->nullable();
            $table->integer('turnaround_time')->nullable();
            $table->string('service_type', 255)->nullable();
            $table->string('category', 50)->default('drop_off');
            $table->unsignedBigInteger('service_type_id')->nullable();
            $table->string('icon_path', 255)->nullable();

            // Indexes
            $table->index('slug', 'services_slug_index');
            $table->index('is_active', 'services_is_active_index');
            $table->foreign('service_type_id', 'services_service_type_id_foreign')->references('id')->on('service_types')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('services');
    }
};
