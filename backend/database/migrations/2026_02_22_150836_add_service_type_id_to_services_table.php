<?php
// database/migrations/xxxx_xx_xx_xxxxxx_add_service_type_id_to_services_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('services', function (Blueprint $table) {
            // Add service_type_id foreign key
            $table->foreignId('service_type_id')->nullable()
                  ->after('category')
                  ->constrained('service_types')
                  ->nullOnDelete();
            
            // Keep service_type as string for backward compatibility
            // but make it nullable since we'll use service_type_id
            $table->string('service_type')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->dropForeign(['service_type_id']);
            $table->dropColumn('service_type_id');
            $table->string('service_type')->nullable(false)->change();
        });
    }
};