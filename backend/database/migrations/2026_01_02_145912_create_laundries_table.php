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
        Schema::create('laundries', function (Blueprint $table) {
            $table->id();

            // Tracking number (unique identifier)
            $table->string('tracking_number', 50)->unique();

            // Customer
            $table->foreignId('customer_id')->constrained()->onDelete('cascade');

            // Branch (where laundry was created)
            $table->foreignId('branch_id')->constrained()->onDelete('cascade');

            // Service (nullable for promo-only per-load laudries)
            $table->foreignId('service_id')->nullable()->constrained()->onDelete('restrict');

            // Created by staff
            $table->foreignId('created_by')->constrained('users')->onDelete('restrict');

            // Laudry details
            $table->decimal('weight', 10, 2); // in kg
            $table->decimal('price_per_piece', 10, 2);

            // Pricing
            $table->decimal('subtotal', 10, 2);
            $table->decimal('discount_amount', 10, 2)->default(0);
            $table->decimal('total_amount', 10, 2);

            // Promotion (if applied) - Foreign key added in separate migration
            $table->unsignedBigInteger('promotion_id')->nullable();

            // Status: received, ready, paid, completed, cancelled
            $table->enum('status', ['received', 'ready', 'paid', 'completed', 'cancelled'])->default('received');

            // Timestamps for each status
            $table->timestamp('received_at')->nullable();
            $table->timestamp('ready_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();

            // Notes
            $table->text('notes')->nullable();
            $table->text('cancellation_reason')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('tracking_number');
            $table->index('customer_id');
            $table->index('branch_id');
            $table->index('promotion_id');
            $table->index('status');
            $table->index('created_at');
            $table->index(['branch_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('laundries');
    }
};
