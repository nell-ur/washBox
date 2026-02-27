// database/migrations/2026_01_02_000013_create_pickup_status_histories_table.php
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pickup_status_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pickup_request_id')->constrained()->cascadeOnDelete();
            $table->enum('status', ['pending', 'accepted', 'en_route', 'picked_up', 'cancelled']);
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index('pickup_request_id');
            $table->index(['pickup_request_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pickup_status_histories');
    }
};
