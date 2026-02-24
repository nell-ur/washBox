<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pickup_requests', function (Blueprint $table) {
            $table->id();

            $table->foreignId('customer_id')->constrained()->onDelete('cascade');
            $table->foreignId('branch_id')->constrained()->onDelete('cascade');

            $table->text('pickup_address');
            $table->decimal('latitude', 10, 8);
            $table->decimal('longitude', 11, 8);

            $table->date('preferred_date');
            $table->time('preferred_time')->nullable();
            $table->text('notes')->nullable();

            $table->foreignId('service_id')->nullable()->constrained()->onDelete('set null');

            // Quoted fees for customer preview
           
            $table->decimal('pickup_fee', 10, 2)->default(0)->comment('Quoted pickup fee');
            $table->decimal('delivery_fee', 10, 2)->default(0)->comment('Quoted delivery fee');

            $table->enum('status', ['pending', 'accepted', 'en_route', 'picked_up', 'cancelled'])->default('pending');

            $table->foreignId('assigned_to')->nullable()->constrained('users')->onDelete('set null');

            // =========== ROUTING & DIRECTION COLUMNS ===========
            $table->decimal('distance_from_branch', 8, 2)->nullable()->comment('Distance in kilometers');
            $table->integer('estimated_travel_time')->nullable()->comment('Estimated travel time in minutes');
            $table->json('route_data')->nullable()->comment('Stores route polyline and waypoints');
            $table->timestamp('estimated_pickup_time')->nullable()->comment('ETA for pickup');
            $table->string('route_instructions')->nullable()->comment('Turn-by-turn instructions');
            $table->decimal('actual_distance', 8, 2)->nullable()->comment('Actual distance traveled');
            $table->integer('actual_travel_time')->nullable()->comment('Actual travel time in minutes');
            // ===================================================

            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('en_route_at')->nullable();
            $table->timestamp('picked_up_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();

            $table->text('cancellation_reason')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->onDelete('set null');

            // Links to order (one-way)
            $table->foreignId('laundries_id')->nullable()->constrained()->onDelete('set null');

            $table->timestamps();
            $table->softDeletes();

            $table->index('customer_id');
            $table->index('branch_id');
            $table->index('status');
            $table->index('preferred_date');
            $table->index(['branch_id', 'status']);
            $table->index(['assigned_to', 'status']);
            $table->index('estimated_pickup_time');
        });

        // Create delivery_routes table for optimized multi-pickup routing
        Schema::create('delivery_routes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('driver_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('branch_id')->constrained('branches')->onDelete('cascade');
            $table->string('route_name')->nullable();
            $table->json('pickup_ids'); // Array of pickup request IDs in optimal order
            $table->json('route_data'); // Complete route data including polylines
            $table->enum('status', ['planned', 'active', 'completed', 'cancelled'])->default('planned');
            $table->decimal('total_distance', 8, 2)->nullable(); // in kilometers
            $table->integer('total_duration')->nullable(); // in minutes
            $table->decimal('estimated_fuel_cost', 8, 2)->nullable();
            $table->timestamp('scheduled_start')->nullable();
            $table->timestamp('estimated_completion')->nullable();
            $table->timestamp('actual_start')->nullable();
            $table->timestamp('actual_completion')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_routes');
        Schema::dropIfExists('pickup_requests');
    }
};
