<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('promotions', function (Blueprint $table) {
            // Add application_type field
            $table->enum('application_type', ['discount', 'per_load_override'])->default('discount')->after('type');

            // Add discount_type and discount_value for discount promotions
            $table->enum('discount_type', ['percentage', 'fixed'])->nullable()->after('application_type');
            $table->decimal('discount_value', 10, 2)->nullable()->after('discount_type');
        });
    }

    public function down(): void
    {
        Schema::table('promotions', function (Blueprint $table) {
            $table->dropColumn(['application_type', 'discount_type', 'discount_value']);
        });
    }
};
