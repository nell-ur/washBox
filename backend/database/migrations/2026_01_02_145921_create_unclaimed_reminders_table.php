// database/migrations/2026_01_02_000021_create_unclaimed_reminders_table.php
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('unclaimed_reminders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unclaimed_laundry_id')->constrained()->cascadeOnDelete();
            $table->integer('reminder_day');
            $table->foreignId('notification_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('status', ['pending', 'sent', 'failed'])->default('pending');
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index('unclaimed_laundry_id');
            $table->index('reminder_day');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('unclaimed_reminders');
    }
};
