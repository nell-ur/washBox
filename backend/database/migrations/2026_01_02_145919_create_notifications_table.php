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
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();

            // Recipient (customer)
            $table->foreignId('customer_id')->constrained()->onDelete('cascade');

            // Notification type
            $table->enum('type', [
                'laundry_received',
                'laundry_ready',
                'laundry_completed',
                'pickup_accepted',
                'pickup_en_route',
                'pickup_completed',
                'unclaimed_day3',
                'unclaimed_day5',
                'unclaimed_day7',
                'general'
            ]);

            // Content
            $table->string('title');
            $table->text('body');
            $table->json('data')->nullable(); // Additional data (order_id, tracking_number, etc)

            // Related entities
            $table->foreignId('laundries_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('pickup_request_id')->nullable()->constrained()->onDelete('cascade');

            // FCM response
            $table->text('fcm_message_id')->nullable();
            $table->enum('fcm_status', ['pending', 'sent', 'failed'])->default('pending');
            $table->text('fcm_error')->nullable();

            // Read status
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();

            $table->timestamps();

            // Indexes
            $table->index('customer_id');
            $table->index('type');
            $table->index('is_read');
            $table->index(['customer_id', 'is_read']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
