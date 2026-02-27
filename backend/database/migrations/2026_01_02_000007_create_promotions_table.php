// database/migrations/2026_01_02_000007_create_promotions_table.php
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('promotions', function (Blueprint $table) {
            $table->id();
            $table->string('status')->default('active');
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('type', ['per_load', 'per_weight_tier', 'special_item', 'free_service', 'percentage_discount', 'fixed_discount', 'poster_promo']);
            $table->enum('application_type', ['discount', 'per_load_override'])->default('discount');
            $table->enum('discount_type', ['percentage', 'fixed'])->nullable();
            $table->decimal('discount_value', 10, 2)->nullable();
            $table->json('pricing_data');
            $table->decimal('min_amount', 10, 2)->default(0);
            $table->string('promo_code', 50)->nullable()->unique();
            $table->string('poster_title')->nullable();
            $table->string('poster_subtitle')->nullable();
            $table->decimal('display_price', 10, 2)->nullable();
            $table->string('price_unit')->nullable();
            $table->json('poster_features')->nullable();
            $table->text('poster_notes')->nullable();
            $table->string('color_theme')->default('blue');
            $table->string('generated_poster_path')->nullable();
            $table->json('applicable_services')->nullable();
            $table->json('applicable_days')->nullable();
            $table->dateTime('start_date');
            $table->dateTime('end_date');
            $table->foreignId('branch_id')->nullable()->constrained()->cascadeOnDelete();
            $table->boolean('is_active')->default(true);
            $table->string('banner_image')->nullable();
            $table->integer('display_laundry')->default(0);
            $table->boolean('featured')->default(false);
            $table->integer('usage_count')->default(0);
            $table->integer('max_usage')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('type');
            $table->index('is_active');
            $table->index('featured');
            $table->index(['start_date', 'end_date']);
            $table->index('branch_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promotions');
    }
};
