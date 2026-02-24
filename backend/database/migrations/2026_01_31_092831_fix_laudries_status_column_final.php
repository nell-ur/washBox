<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Force the change with raw SQL
        DB::statement("ALTER TABLE laundries
            MODIFY COLUMN status VARCHAR(20)
            NOT NULL
            DEFAULT 'received'
            COMMENT 'Laundry status'");

        DB::statement("ALTER TABLE laundry_status_histories
            MODIFY COLUMN status VARCHAR(20)
            NOT NULL
            COMMENT 'Status at time of history entry'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE laundries
            MODIFY COLUMN status ENUM('received', 'ready', 'paid', 'completed', 'cancelled')
            NOT NULL
            DEFAULT 'received'");

        DB::statement("ALTER TABLE laudry_status_histories
            MODIFY COLUMN status ENUM('received', 'ready', 'paid', 'completed', 'cancelled')
            NOT NULL");
    }
};
