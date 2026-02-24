<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // ── 1. Add category column ──────────────────────────────────────────
        Schema::table('services', function (Blueprint $table) {
            if (!Schema::hasColumn('services', 'category')) {
                $table->string('category', 50)->default('drop_off')->after('service_type');
            }
        });

        // ── 2. Backfill category from existing service_type ────────────────
        DB::table('services')->whereIn('service_type', ['full_service', 'special_item', 'regular_clothes'])
            ->update(['category' => 'drop_off']);

        DB::table('services')->where('service_type', 'self_service')
            ->update(['category' => 'self_service']);

        DB::table('services')->where('service_type', 'addon')
            ->update(['category' => 'addon']);

        // ── 3. Move any per_piece records to per_load ──────────────────────
        // special_item comforters: price_per_piece → price_per_load
        DB::table('services')
            ->where('pricing_type', 'per_piece')
            ->whereNotNull('price_per_piece')
            ->update([
                'price_per_load' => DB::raw('price_per_piece'),
                'price_per_piece' => null,
                'pricing_type'   => 'per_load',
            ]);

        // Catch any remaining per_piece without a price
        DB::table('services')
            ->where('pricing_type', 'per_piece')
            ->update(['pricing_type' => 'per_load']);

        // ── 4. Update CHECK constraint to only allow per_load ──────────────
        if (DB::getDriverName() === 'mysql') {
            try {
                DB::statement('ALTER TABLE services DROP CONSTRAINT IF EXISTS pricing_type_check');
            } catch (\Exception $e) {
                // May not exist — safe to continue
            }

            DB::statement("
                ALTER TABLE services
                ADD CONSTRAINT pricing_type_check
                CHECK (pricing_type IN ('per_load'))
            ");
        }
    }

    public function down(): void
    {
        // Restore per_piece constraint
        if (DB::getDriverName() === 'mysql') {
            try {
                DB::statement('ALTER TABLE services DROP CONSTRAINT IF EXISTS pricing_type_check');
            } catch (\Exception $e) {}

            DB::statement("
                ALTER TABLE services
                ADD CONSTRAINT pricing_type_check
                CHECK (pricing_type IN ('per_load', 'per_piece'))
            ");
        }

        // Drop category column
        Schema::table('services', function (Blueprint $table) {
            if (Schema::hasColumn('services', 'category')) {
                $table->dropColumn('category');
            }
        });
    }
};
