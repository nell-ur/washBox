<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // PROMOTIONS TABLE - Enhanced with poster fields
        Schema::create('promotions', function (Blueprint $table) {
            $table->id();

            // Basic Information
            $table->string('name');
            $table->text('description')->nullable();

            // Type System - Enhanced
            $table->enum('type', [
                'per_load',
                'per_weight_tier',
                'special_item',
                'free_service',
                'percentage_discount',
                'fixed_discount',
                'poster_promo' // NEW: For visual poster promotions
            ]);

            // Pricing & Codes
            $table->json('pricing_data');
            $table->decimal('min_amount', 10, 2)->default(0);
            $table->string('promo_code', 50)->unique()->nullable();

            // ===== POSTER FIELDS (NEW) =====
            $table->string('poster_title')->nullable(); // "DROP OFF PROMO!"
            $table->string('poster_subtitle')->nullable(); // "COMFORTER" or "SMALL SIZES"
            $table->decimal('display_price', 10, 2)->nullable(); // 179.00
            $table->string('price_unit')->nullable(); // "PER 8KG LOAD"
            $table->json('poster_features')->nullable(); // ["FREE Detergent", "FREE Fabcon"]
            $table->text('poster_notes')->nullable(); // "Ariel or Breeze | Zonrox"
            $table->string('color_theme')->default('blue'); // blue, purple, green
            $table->string('generated_poster_path')->nullable(); // Generated image path
            // ================================

            // Applicability
            $table->json('applicable_services')->nullable();
            $table->json('applicable_days')->nullable();

            // Schedule
            $table->dateTime('start_date');
            $table->dateTime('end_date');

            // Branch
            $table->foreignId('branch_id')->nullable()->constrained()->onDelete('cascade');

            // Status & Display
            $table->boolean('is_active')->default(true);
            $table->string('banner_image')->nullable();
            $table->integer('display_laundry')->default(0);
            $table->boolean('featured')->default(false);

            // Usage Tracking
            $table->integer('usage_count')->default(0);
            $table->integer('max_usage')->nullable();

            $table->timestamps();
            $table->softDeletes(); // Soft delete for history

            // Indexes
            $table->index('type');
            $table->index('is_active');
            $table->index('featured');
            $table->index(['start_date', 'end_date']);
            $table->index('branch_id');
        });

        // SPECIAL ITEM PRICINGS (unchanged - perfect)
        Schema::create('special_item_pricings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('promotion_id')->constrained()->onDelete('cascade');
            $table->enum('item_type', [
                'comforter','blanket','curtain','carpet',
                'bedsheet','pillow','other'
            ]);
            $table->string('item_size', 50)->nullable();
            $table->decimal('price', 10, 2);
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['item_type', 'is_active']);
            $table->index('promotion_id');
        });

        // PROMOTION USAGES - Fixed (plural)
        Schema::create('promotion_usages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('promotion_id')->constrained()->onDelete('cascade');
            $table->foreignId('laundries_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');

            // Amounts
            $table->decimal('discount_amount', 10, 2)->default(0);
            $table->decimal('original_amount', 10, 2);
            $table->decimal('final_amount', 10, 2);

            // Code tracking
            $table->string('code_used')->nullable();
            $table->timestamp('applied_at')->useCurrent();

            $table->timestamps();

            // Constraints
            $table->unique(['promotion_id', 'laundries_id']);
            $table->index(['promotion_id', 'created_at']);
            $table->index('laundries_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promotion_usages');
        Schema::dropIfExists('special_item_pricings');
        Schema::dropIfExists('promotions');
    }
};
