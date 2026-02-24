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
        Schema::create('pickup_status_histories', function (Blueprint $table) {
            $table->id();

            // Pickup request
            $table->foreignId('pickup_request_id')->constrained()->onDelete('cascade');

            // Status
            $table->enum('status', ['pending', 'accepted', 'en_route', 'picked_up', 'cancelled']);

            // Changed by
            $table->foreignId('changed_by')->nullable()->constrained('users')->onDelete('set null');

            // Notes
            $table->text('notes')->nullable();

            $table->timestamp('created_at')->useCurrent();

            // Indexes
            $table->index('pickup_request_id');
            $table->index(['pickup_request_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pickup_status_histories');
    }
};
