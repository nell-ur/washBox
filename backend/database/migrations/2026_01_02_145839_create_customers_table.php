<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('phone')->unique();
            $table->string('email')->nullable()->unique();
            $table->string('password')->nullable(); // Only for self-registered

            // Registration type
            $table->enum('registration_type', ['walk_in', 'self_registered'])->default('walk_in');

            // Address (required for self-registered, optional for walk-in)
            $table->text('address')->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();

            // Preferred branch (for self-registered)
            $table->foreignId('preferred_branch_id')->nullable()->constrained('branches')->onDelete('set null');

            // Registered by (for walk-in customers)
            $table->foreignId('registered_by')->nullable()->constrained('users')->onDelete('set null');

            // Profile
            $table->string('profile_photo')->nullable();

            // Status
            $table->boolean('is_active')->default(true);

            $table->rememberToken();
            $table->timestamps();

            // Indexes
            $table->index('phone');
            $table->index('email');
            $table->index('registration_type');
            $table->index('preferred_branch_id');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
