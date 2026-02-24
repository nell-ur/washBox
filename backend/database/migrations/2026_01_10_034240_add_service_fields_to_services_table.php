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
        Schema::table('services', function (Blueprint $table) {
            // Add columns without "after" clause to avoid dependency issues

            if (!Schema::hasColumn('services', 'description')) {
                $table->text('description')->nullable();
            }

            if (!Schema::hasColumn('services', 'price_per_piece')) {
                $table->decimal('price_per_piece', 8, 2)->default(0);
            }

            if (!Schema::hasColumn('services', 'min_weight')) {
                $table->decimal('min_weight', 8, 2)->nullable();
            }

            if (!Schema::hasColumn('services', 'max_weight')) {
                $table->decimal('max_weight', 8, 2)->nullable();
            }

            if (!Schema::hasColumn('services', 'turnaround_time')) {
                $table->integer('turnaround_time')->nullable();
            }

            if (!Schema::hasColumn('services', 'service_type')) {
                $table->string('service_type', 100)->nullable();
            }

            if (!Schema::hasColumn('services', 'icon_path')) {
                $table->string('icon_path')->nullable();
            }

            if (!Schema::hasColumn('services', 'is_active')) {
                $table->boolean('is_active')->default(true);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $columns = [
                'description',
                'price_per_piece',
                'min_weight',
                'max_weight',
                'turnaround_time',
                'service_type',
                'icon_path',
                'is_active'
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('services', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
