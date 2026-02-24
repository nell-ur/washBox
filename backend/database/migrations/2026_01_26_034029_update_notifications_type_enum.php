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
        // Change ENUM to VARCHAR to support all notification types
        // This is more flexible and avoids future migration issues

        // For MySQL, we need to use raw SQL to modify ENUM or convert to VARCHAR
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE `notifications` MODIFY `type` VARCHAR(50) NOT NULL");
        }
        // For other drivers (sqlite, pgsql) this migration is a no-op because ALTER TABLE MODIFY is driver-specific
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // If you need to revert, you can set it back to ENUM
        // But VARCHAR is more flexible, so we'll keep it
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE `notifications` MODIFY `type` VARCHAR(50) NOT NULL");
        }
    }
};
