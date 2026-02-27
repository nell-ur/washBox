// database/migrations/2026_01_02_000024_create_delivery_routes_table.php
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('delivery_routes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('driver_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->string('route_name')->nullable();
            $table->json('pickup_ids');
            $table->json('route_data');
            $table->enum('status', ['planned', 'active', 'completed', 'cancelled'])->default('planned');
            $table->decimal('total_distance', 8, 2)->nullable();
            $table->integer('total_duration')->nullable();
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
    }
};
