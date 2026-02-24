<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Fix the broken CHECK constraint that only allowed 'per_load'.
     * Correct it to allow both 'per_load' and 'per_piece'.
     */
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            // Drop the old broken constraint (only allowed per_load)
            try {
                DB::statement('ALTER TABLE services DROP CONSTRAINT IF EXISTS pricing_type_check');
            } catch (\Exception $e) {
                // Constraint may not exist — safe to continue
            }

            // Add correct constraint
            DB::statement("
                ALTER TABLE services
                ADD CONSTRAINT pricing_type_check
                CHECK (pricing_type IN ('per_load', 'per_piece'))
            ");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            try {
                DB::statement('ALTER TABLE services DROP CONSTRAINT IF EXISTS pricing_type_check');
            } catch (\Exception $e) {}

            // Restore original (broken) constraint
            DB::statement("
                ALTER TABLE services
                ADD CONSTRAINT pricing_type_check
                CHECK (pricing_type IN ('per_load'))
            ");
        }
    }
};
