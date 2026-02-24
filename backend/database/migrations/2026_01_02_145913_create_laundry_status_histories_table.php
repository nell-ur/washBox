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
        Schema::create('laundry_status_histories', function (Blueprint $table) {
            $table->id();

            // Order
            $table->foreignId('laundries_id')->constrained()->onDelete('cascade');

            // Status
            $table->enum('status', ['received', 'ready', 'paid', 'completed', 'cancelled']);

            // Changed by
            $table->foreignId('changed_by')->nullable()->constrained('users')->onDelete('set null');

            // Notes
            $table->text('notes')->nullable();

            $table->timestamp('created_at')->useCurrent();

            // Indexes
            $table->index('laundries_id');
            $table->index(['laundries_id', 'status']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('laundry_status_histories');
    }
};
