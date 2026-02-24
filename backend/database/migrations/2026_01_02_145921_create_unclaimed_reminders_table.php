<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('unclaimed_reminders', function (Blueprint $table) {
            $table->id();

            // Unclaimed laundry
            $table->foreignId('unclaimed_laundry_id')->constrained()->onDelete('cascade');

            // Reminder day (3, 5, or 7)
            $table->integer('reminder_day');

            // Notification sent
            $table->foreignId('notification_id')->nullable()->constrained()->onDelete('set null');

            // Status
            $table->enum('status', ['pending', 'sent', 'failed'])->default('pending');

            // Sent timestamp
            $table->timestamp('sent_at')->nullable();

            $table->timestamp('created_at')->useCurrent();

            // Indexes
            $table->index('unclaimed_laundry_id');
            $table->index('reminder_day');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('unclaimed_reminders');
    }
};
