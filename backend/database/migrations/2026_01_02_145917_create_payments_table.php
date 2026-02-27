// database/migrations/2026_01_02_000016_create_payments_table.php
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('laundries_id')->constrained()->cascadeOnDelete();
            $table->enum('method', ['cash'])->default('cash');
            $table->decimal('amount', 10, 2);
            $table->string('receipt_number', 50);
            $table->foreignId('received_by')->constrained('users');
            $table->text('notes')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index('laundries_id');
            $table->index('receipt_number');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
