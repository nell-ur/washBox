<?php
// database/migrations/2026_01_02_000009_create_laundries_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('laundries', function (Blueprint $table) {
            $table->id();

            // REMOVE THIS LINE - it creates circular dependency
            // $table->foreignId('pickup_request_id')->nullable()->constrained()->nullOnDelete();

            // Instead, just add the column without constraint
            $table->unsignedBigInteger('pickup_request_id')->nullable();

            $table->unsignedInteger('branch_laundry_number')->nullable();
            $table->string('tracking_number', 50)->unique();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('staff_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('service_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->decimal('weight', 10, 2);
            $table->integer('number_of_loads')->nullable();
            $table->decimal('price_per_piece', 10, 2);
            $table->decimal('subtotal', 10, 2);
            $table->decimal('addons_total', 10, 2)->default(0);
            $table->decimal('discount_amount', 10, 2)->default(0);
            $table->decimal('promotion_override_total', 10, 2)->nullable();
            $table->decimal('promotion_price_per_load', 10, 2)->nullable();
            $table->decimal('pickup_fee', 10, 2)->default(0)->comment('Final billed pickup fee');
            $table->decimal('delivery_fee', 10, 2)->default(0)->comment('Final billed delivery fee');
            $table->decimal('total_amount', 10, 2);
            $table->foreignId('promotion_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status', 20)->default('received')->comment('Laundry status');
            $table->string('payment_status')->default('pending');
            $table->string('payment_method')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->timestamp('processing_at')->nullable();
            $table->timestamp('ready_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('last_reminder_at')->nullable();
            $table->integer('reminder_count')->default(0);
            $table->boolean('is_unclaimed')->default(false);
            $table->timestamp('unclaimed_at')->nullable();
            $table->decimal('storage_fee', 10, 2)->default(0);
            $table->timestamp('cancelled_at')->nullable();
            $table->text('notes')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Add index but no foreign key
            $table->index('pickup_request_id');
            $table->index('tracking_number');
            $table->index('customer_id');
            $table->index('branch_id');
            $table->index('promotion_id');
            $table->index('status');
            $table->index('created_at');
            $table->index(['branch_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('laundries');
    }
};
