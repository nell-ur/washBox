// database/migrations/2026_01_02_000022_create_customer_ratings_table.php
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_ratings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('laundry_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('staff_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('assigned_staff_id')->nullable()->constrained('users')->nullOnDelete();
            $table->tinyInteger('rating')->unsigned();
            $table->text('comment')->nullable();
            $table->json('staff_ratings')->nullable()->comment('JSON containing ratings for different aspects');
            $table->text('staff_response')->nullable();
            $table->timestamp('responded_at')->nullable();
            $table->timestamps();

            $table->unique(['laundry_id', 'customer_id']);
            $table->index('laundry_id');
            $table->index('customer_id');
            $table->index('branch_id');
            $table->index('staff_id');
            $table->index('assigned_staff_id');
            $table->index('rating');
            $table->index('responded_at');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_ratings');
    }
};
