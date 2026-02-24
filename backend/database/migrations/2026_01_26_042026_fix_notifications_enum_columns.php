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
        // Fix type column - change ENUM to VARCHAR
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE `notifications` MODIFY `type` VARCHAR(50) NOT NULL");
        }

        // Fix fcm_status column - change ENUM to VARCHAR
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE `notifications` MODIFY `fcm_status` VARCHAR(20) NULL DEFAULT 'pending'");
        }
        // Other drivers (sqlite, pgsql) no-op for raw ALTER MODIFY statements.
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Keep as VARCHAR (more flexible)
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE `notifications` MODIFY `type` VARCHAR(50) NOT NULL");
            DB::statement("ALTER TABLE `notifications` MODIFY `fcm_status` VARCHAR(20) NULL DEFAULT 'pending'");
        }
    }
};
