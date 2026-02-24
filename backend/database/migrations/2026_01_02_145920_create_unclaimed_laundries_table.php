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
        Schema::create('unclaimed_laundries', function (Blueprint $table) {
            $table->id();

            // LAUNDRY
            $table->foreignId('laundries_id')->constrained()->onDelete('cascade');

            // Customer
            $table->foreignId('customer_id')->constrained()->onDelete('cascade');

            // Branch
            $table->foreignId('branch_id')->constrained()->onDelete('cascade');

            // Days unclaimed (calculated)
            $table->integer('days_unclaimed')->default(0);

            // Status: unclaimed, recovered, disposed
            $table->enum('status', ['unclaimed', 'recovered', 'disposed'])->default('unclaimed');

            // Recovery/disposal info
            $table->timestamp('recovered_at')->nullable();
            $table->timestamp('disposed_at')->nullable();
            $table->foreignId('recovered_by')->nullable()->constrained('users')->onDelete('set null');
            $table->text('notes')->nullable();

            $table->timestamps();

            // Indexes
            $table->index('laundries_id');
            $table->index('customer_id');
            $table->index('branch_id');
            $table->index('status');
            $table->index('days_unclaimed');
            $table->index(['branch_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('unclaimed_laundries');
    }
};
