// database/migrations/2026_01_02_000002_create_users_table.php
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('employee_id', 50)->nullable()->unique();
            $table->string('position')->nullable();
            $table->date('hire_date')->nullable();
            $table->string('phone');
            $table->string('address', 500)->nullable();
            $table->string('emergency_contact')->nullable();
            $table->string('emergency_phone', 50)->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('fcm_token')->nullable();
            $table->string('profile_photo_path')->nullable();
            $table->enum('role', ['admin', 'staff'])->default('staff');
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->string('profile_photo')->nullable();
            $table->rememberToken();
            $table->timestamps();

            $table->index('role');
            $table->index('branch_id');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
