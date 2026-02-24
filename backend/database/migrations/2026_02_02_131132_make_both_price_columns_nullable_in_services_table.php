<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        Schema::table('services', function (Blueprint $table) {
            // Make price_per_piece nullable
            $table->decimal('price_per_piece', 10, 2)->nullable()->change();

            // Make price_per_load nullable (already is, but for consistency)
            $table->decimal('price_per_load', 10, 2)->nullable()->change();
        });

        // Update the CHECK constraint to include both pricing types
        if (DB::getDriverName() === 'mysql') {
            // First drop the existing constraint
            DB::statement('ALTER TABLE services DROP CONSTRAINT IF EXISTS pricing_type_check');

            // Add new constraint with both types
            DB::statement("ALTER TABLE services ADD CONSTRAINT pricing_type_check CHECK (pricing_type IN ('per_load', 'per_piece'))");
        }
    }

    public function down()
    {
        // Drop the constraint first (for MySQL)
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE services DROP CONSTRAINT IF EXISTS pricing_type_check');

            // Restore old constraint
            DB::statement("ALTER TABLE services ADD CONSTRAINT pricing_type_check CHECK (pricing_type IN ('per_load'))");
        }

        Schema::table('services', function (Blueprint $table) {
            // Change back to NOT NULL (but you need to ensure no null values exist)
            $table->decimal('price_per_piece', 10, 2)->nullable(false)->default(0)->change();
            $table->decimal('price_per_load', 10, 2)->nullable(false)->default(0)->change();
        });
    }
};
