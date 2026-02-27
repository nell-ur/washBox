// database/migrations/2026_01_02_000026_create_reports_table.php
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['sales', 'laundries', 'unclaimed', 'analytics', 'custom']);
            $table->string('name');
            $table->foreignId('generated_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->cascadeOnDelete();
            $table->date('start_date');
            $table->date('end_date');
            $table->json('data');
            $table->string('file_path')->nullable();
            $table->enum('status', ['pending', 'completed', 'failed'])->default('pending');
            $table->timestamps();

            $table->index('type');
            $table->index('generated_by');
            $table->index('branch_id');
            $table->index(['start_date', 'end_date']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};
