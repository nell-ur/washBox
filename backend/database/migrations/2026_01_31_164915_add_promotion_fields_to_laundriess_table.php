<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('laundries', function (Blueprint $table) {
            if (!Schema::hasColumn('laundries', 'promotion_override_total')) {
                $table->decimal('promotion_override_total', 10, 2)->nullable()->after('discount_amount');
            }
            if (!Schema::hasColumn('laundries', 'promotion_price_per_load')) {
                $table->decimal('promotion_price_per_load', 10, 2)->nullable()->after('promotion_override_total');
            }
        });
    }

    public function down(): void
    {
        Schema::table('laundries', function (Blueprint $table) {
            $table->dropColumn(['promotion_override_total', 'promotion_price_per_load']);
        });
    }
};
