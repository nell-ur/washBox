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
            // Add pricing_type as string with default value
            $table->string('pricing_type', 20)->default('per_load')->after('price_per_piece');

            // Add price_per_load column
            $table->decimal('price_per_load', 10, 2)->nullable()->after('pricing_type');
        });

        // Add CHECK constraint using raw SQL (for MySQL)
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE services ADD CONSTRAINT pricing_type_check CHECK (pricing_type IN ('per_load'))");
        }
    }

    public function down()
    {
        // Drop the constraint first (for MySQL)
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE services DROP CONSTRAINT IF EXISTS pricing_type_check');
        }

        Schema::table('services', function (Blueprint $table) {
            $table->dropColumn(['pricing_type', 'price_per_load']);
        });
    }
};
