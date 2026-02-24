<?php
// database/migrations/2026_02_22_105314_make_laundry_id_nullable_in_customer_ratings.php

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
        // First, drop the existing unique constraint
        Schema::table('customer_ratings', function (Blueprint $table) {
            $table->dropUnique(['laundry_id', 'customer_id']);
        });

        // Make laundry_id nullable
        Schema::table('customer_ratings', function (Blueprint $table) {
            $table->foreignId('laundry_id')->nullable()->change();
        });

        // For MySQL 5.7+ / MariaDB 10.2+, we need to handle the unique constraint differently
        // We'll create a unique constraint that allows multiple NULLs
        // This is the standard behavior - MySQL allows multiple NULLs in unique constraints
        // So we can just add back the unique constraint and it will work with NULLs
        
        // Re-add the unique constraint (MySQL allows multiple NULLs by default)
        Schema::table('customer_ratings', function (Blueprint $table) {
            $table->unique(['laundry_id', 'customer_id'], 'customer_ratings_laundry_customer_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop the new unique constraint
        Schema::table('customer_ratings', function (Blueprint $table) {
            $table->dropUnique('customer_ratings_laundry_customer_unique');
        });

        // Make laundry_id not nullable again
        Schema::table('customer_ratings', function (Blueprint $table) {
            $table->foreignId('laundry_id')->nullable(false)->change();
        });

        // Re-add the original unique constraint
        Schema::table('customer_ratings', function (Blueprint $table) {
            $table->unique(['laundry_id', 'customer_id']);
        });
    }
};