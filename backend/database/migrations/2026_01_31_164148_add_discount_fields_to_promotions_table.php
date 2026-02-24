<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('promotions', function (Blueprint $table) {
            if (!Schema::hasColumn('promotions', 'discount_type')) {
                $table->enum('discount_type', ['percentage', 'fixed'])->nullable()->after('application_type');
            }
            if (!Schema::hasColumn('promotions', 'discount_value')) {
                $table->decimal('discount_value', 10, 2)->nullable()->after('discount_type');
            }
            if (!Schema::hasColumn('promotions', 'application_type')) {
                $table->enum('application_type', ['discount', 'per_load_override'])->default('discount')->after('type');
            }
        });
    }

    public function down(): void
    {
        // Don't drop columns as they might be needed
    }
};
