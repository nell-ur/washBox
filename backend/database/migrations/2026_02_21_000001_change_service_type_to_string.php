<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // MySQL won't let you ALTER an enum column directly to string
        // so we use a raw statement to change it cleanly.
        DB::statement("ALTER TABLE services MODIFY COLUMN service_type VARCHAR(100) NULL");
    }

    public function down(): void
    {
        // Restore to the original enum if needed
        DB::statement("ALTER TABLE services MODIFY COLUMN service_type ENUM('regular_clothes','special_item','full_service','self_service','addon') NULL");
    }
};
