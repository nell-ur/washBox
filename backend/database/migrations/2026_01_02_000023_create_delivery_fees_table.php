// database/migrations/2026_01_02_000023_create_delivery_fees_table.php
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('delivery_fees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete()->unique();
            $table->decimal('pickup_fee', 10, 2)->default(50.00);
            $table->decimal('delivery_fee', 10, 2)->default(50.00);
            $table->decimal('both_discount', 5, 2)->default(10.00);
            $table->decimal('minimum_laundry_for_free', 10, 2)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_fees');
    }
};
