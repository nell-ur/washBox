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
        Schema::create('customer_ratings', function (Blueprint $table) {
            $table->id();

            // Foreign keys
            $table->foreignId('laundry_id')->constrained('laundries')->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();

            // Rating data
            $table->tinyInteger('rating')->unsigned()->between(1, 5);
            $table->text('comment')->nullable();

            // Timestamps
            $table->timestamps();

            // Indexes for queries
            $table->index('laundry_id');
            $table->index('customer_id');
            $table->index('branch_id');
            $table->index('rating');
            $table->index('created_at');

            // Ensure one rating per laundry per customer
            $table->unique(['laundry_id', 'customer_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_ratings');
    }
};
