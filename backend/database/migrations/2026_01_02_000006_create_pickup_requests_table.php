<?php
// database/migrations/2026_01_02_000006_create_pickup_requests_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pickup_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->text('pickup_address');
            $table->decimal('latitude', 10, 8);
            $table->decimal('longitude', 11, 8);
            $table->date('preferred_date');
            $table->time('preferred_time')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('service_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('service_type', ['pickup_only', 'delivery_only', 'both'])->default('both');
            $table->decimal('pickup_fee', 10, 2)->default(0)->comment('Quoted pickup fee');
            $table->decimal('delivery_fee', 10, 2)->default(0)->comment('Quoted delivery fee');
            $table->enum('status', ['pending', 'accepted', 'en_route', 'picked_up', 'cancelled'])->default('pending');
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('distance_from_branch', 8, 2)->nullable()->comment('Distance in kilometers');
            $table->integer('estimated_travel_time')->nullable()->comment('Estimated travel time in minutes');
            $table->json('route_data')->nullable()->comment('Stores route polyline and waypoints');
            $table->timestamp('estimated_pickup_time')->nullable()->comment('ETA for pickup');
            $table->string('route_instructions')->nullable()->comment('Turn-by-turn instructions');
            $table->decimal('actual_distance', 8, 2)->nullable()->comment('Actual distance traveled');
            $table->integer('actual_travel_time')->nullable()->comment('Actual travel time in minutes');
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('en_route_at')->nullable();
            $table->timestamp('picked_up_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();

            // REMOVE THIS LINE - it creates circular dependency
            // $table->foreignId('laundries_id')->nullable()->constrained()->nullOnDelete();

            // Instead, just add the column without constraint
            $table->unsignedBigInteger('laundries_id')->nullable();

            $table->string('pin', 10)->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Add index but no foreign key
            $table->index('laundries_id');
            $table->index('customer_id');
            $table->index('branch_id');
            $table->index('status');
            $table->index('preferred_date');
            $table->index(['branch_id', 'status']);
            $table->index(['assigned_to', 'status']);
            $table->index('estimated_pickup_time');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pickup_requests');
    }
};
