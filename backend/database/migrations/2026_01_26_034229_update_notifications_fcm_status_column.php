<?php

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
        // Change fcm_status to VARCHAR for flexibility
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE `notifications` MODIFY `fcm_status` VARCHAR(20) NULL DEFAULT 'pending'");
        }
        // Other drivers (sqlite, pgsql) no-op because raw MODIFY is driver-specific
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE `notifications` MODIFY `fcm_status` VARCHAR(20) NULL DEFAULT 'pending'");
        }
    }
};
