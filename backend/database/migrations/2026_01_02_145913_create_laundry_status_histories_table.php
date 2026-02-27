// database/migrations/2026_01_02_000012_create_laundry_status_histories_table.php
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('laundry_status_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('laundries_id')->constrained()->cascadeOnDelete();
            $table->string('status', 20)->comment('Status at time of history entry');
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index('laundries_id');
            $table->index(['laundries_id', 'status']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('laundry_status_histories');
    }
};
