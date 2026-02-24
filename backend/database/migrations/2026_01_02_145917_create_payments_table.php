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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();

            // Order
            $table->foreignId('laundries_id')->constrained()->onDelete('cascade');

            // Payment method (only cash for now)
            $table->enum('method', ['cash'])->default('cash');

            // Amount
            $table->decimal('amount', 10, 2);

            // Physical receipt number
            $table->string('receipt_number', 50);

            // Received by (staff)
            $table->foreignId('received_by')->constrained('users')->onDelete('restrict');

            // Notes
            $table->text('notes')->nullable();

            $table->timestamp('created_at')->useCurrent();

            // Indexes
            $table->index('laundries_id');
            $table->index('receipt_number');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
