<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * This migration adds the foreign key constraint for promotion_id
     * AFTER both orders and promotions tables have been created.
     */
    public function up(): void
    {
        Schema::table('laundries', function (Blueprint $table) {
            // Add foreign key constraint to promotion_id
            $table->foreign('promotion_id')
                  ->references('id')
                  ->on('promotions')
                  ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('laundries', function (Blueprint $table) {
            // Drop the foreign key constraint
            $table->dropForeign(['promotion_id']);
        });
    }
};
