<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // PART 1: Add to pickup_requests ONLY if missing
        Schema::table('pickup_requests', function (Blueprint $table) {
            if (!Schema::hasColumn('pickup_requests', 'service_type')) {
                $table->enum('service_type', ['pickup_only', 'delivery_only', 'both'])
                    ->default('both')
                    ->after('service_id');
            }

            if (!Schema::hasColumn('pickup_requests', 'pickup_fee')) {
                $table->decimal('pickup_fee', 10, 2)
                    ->default(0)
                    ->after('service_type')
                    ->comment('Quoted pickup fee');
            }

            if (!Schema::hasColumn('pickup_requests', 'delivery_fee')) {
                $table->decimal('delivery_fee', 10, 2)
                    ->default(0)
                    ->after('pickup_fee')
                    ->comment('Quoted delivery fee');
            }

            // Skip total_fee - redundant with orders.total_amount
        });

        // PART 2: Create delivery_fees (rules)
        if (!Schema::hasTable('delivery_fees')) {
            Schema::create('delivery_fees', function (Blueprint $table) {
                $table->id();
                $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
                $table->decimal('pickup_fee', 10, 2)->default(50.00);
                $table->decimal('delivery_fee', 10, 2)->default(50.00);
                $table->decimal('both_discount', 5, 2)->default(10);
                $table->decimal('minimum_laundry_for_free', 10, 2)->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->unique('branch_id');
            });
        }

        // PART 3: Add to orders ONLY if missing (no pickup_request_id to avoid circular FK)
        Schema::table('laundries', function (Blueprint $table) {
            if (!Schema::hasColumn('laundries', 'pickup_fee')) {
                $table->decimal('pickup_fee', 10, 2)
                    ->default(0)
                    ->after('discount_amount')
                    ->comment('Final billed pickup fee');
            }

            if (!Schema::hasColumn('laundries', 'delivery_fee')) {
                $table->decimal('delivery_fee', 10, 2)
                    ->default(0)
                    ->after('pickup_fee')
                    ->comment('Final billed delivery fee');
            }

            // NO pickup_request_id here - use pickup_requests.order_id instead
        });
    }

    public function down(): void
    {
        Schema::table('pickup_requests', function (Blueprint $table) {
            if (Schema::hasColumn('pickup_requests', 'service_type')) {
                $table->dropColumn('service_type');
            }
            if (Schema::hasColumn('pickup_requests', 'pickup_fee')) {
                $table->dropColumn('pickup_fee');
            }
            if (Schema::hasColumn('pickup_requests', 'delivery_fee')) {
                $table->dropColumn('delivery_fee');
            }
        });

        Schema::dropIfExists('delivery_fees');

        Schema::table('laundries', function (Blueprint $table) {
            if (Schema::hasColumn('laundries', 'pickup_fee')) {
                $table->dropColumn('pickup_fee');
            }
            if (Schema::hasColumn('laundries', 'delivery_fee')) {
                $table->dropColumn('delivery_fee');
            }
        });
    }
};
