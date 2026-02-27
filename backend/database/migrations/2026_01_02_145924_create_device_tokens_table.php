// database/migrations/2026_01_02_000025_create_device_tokens_table.php
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('device_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->text('token');
            $table->string('device_type')->nullable();
            $table->string('device_name')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();

            $table->index('customer_id');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('device_tokens');
    }
};
