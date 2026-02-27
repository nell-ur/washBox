// database/migrations/2026_01_02_000010_create_laundry_addon_table.php
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('laundry_addon', function (Blueprint $table) {
            $table->id();
            $table->foreignId('laundries_id')->constrained()->cascadeOnDelete();
            $table->foreignId('add_on_id')->constrained()->cascadeOnDelete();
            $table->decimal('price_at_purchase', 10, 2);
            $table->integer('quantity')->default(1);
            $table->timestamps();

            $table->unique(['laundries_id', 'add_on_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('laundry_addon');
    }
};
