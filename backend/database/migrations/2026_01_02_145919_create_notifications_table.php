// database/migrations/2026_01_02_000017_create_notifications_table.php
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('type', 50);
            $table->string('title');
            $table->text('body');
            $table->json('data')->nullable();
            $table->foreignId('laundries_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('pickup_request_id')->nullable()->constrained()->cascadeOnDelete();
            $table->text('fcm_message_id')->nullable();
            $table->string('fcm_status', 20)->default('pending');
            $table->text('fcm_error')->nullable();
            $table->timestamp('fcm_sent_at')->nullable();
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index('customer_id');
            $table->index('type');
            $table->index('is_read');
            $table->index(['customer_id', 'is_read']);
            $table->index(['user_id', 'is_read']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
