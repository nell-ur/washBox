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
        Schema::table('customer_ratings', function (Blueprint $table) {
            // Add staff_id to track which staff member was rated
            $table->foreignId('staff_id')->nullable()
                  ->after('branch_id')
                  ->constrained('users')
                  ->nullOnDelete();
            
            // Add assigned_staff_id to track which staff was assigned to the laundry
            $table->foreignId('assigned_staff_id')->nullable()
                  ->after('staff_id')
                  ->constrained('users')
                  ->nullOnDelete();
            
            // Add JSON field for detailed staff ratings
            $table->json('staff_ratings')->nullable()
                  ->after('comment')
                  ->comment('JSON containing ratings for different aspects');
            
            // Add staff response to customer feedback
            $table->text('staff_response')->nullable()
                  ->after('staff_ratings');
            
            // Track when staff responded
            $table->timestamp('responded_at')->nullable()
                  ->after('staff_response');
            
            // Add indexes for new columns
            $table->index('staff_id');
            $table->index('assigned_staff_id');
            $table->index('responded_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customer_ratings', function (Blueprint $table) {
            // Drop foreign keys first
            $table->dropForeign(['staff_id']);
            $table->dropForeign(['assigned_staff_id']);
            
            // Drop columns
            $table->dropColumn([
                'staff_id',
                'assigned_staff_id',
                'staff_ratings',
                'staff_response',
                'responded_at'
            ]);
        });
    }
};