<?php
// database/migrations/2026_01_03_000002_resolve_circular_references.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add foreign key to pickup_requests (referencing laundries)
        Schema::table('pickup_requests', function (Blueprint $table) {
            $table->foreign('laundries_id')
                  ->references('id')
                  ->on('laundries')
                  ->onDelete('set null');
        });

        // Add foreign key to laundries (referencing pickup_requests)
        Schema::table('laundries', function (Blueprint $table) {
            $table->foreign('pickup_request_id')
                  ->references('id')
                  ->on('pickup_requests')
                  ->onDelete('set null');
        });
    }

    public function down(): void
    {
        // Drop foreign keys in reverse order
        Schema::table('laundries', function (Blueprint $table) {
            $table->dropForeign(['pickup_request_id']);
        });

        Schema::table('pickup_requests', function (Blueprint $table) {
            $table->dropForeign(['laundries_id']);
        });
    }
};
