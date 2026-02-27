<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('branches', function (Blueprint $table) {
            // Laravel's id() already handles bigIncrements, primary key, and unsigned
            $table->id();

            // Basic Information
            $table->string('name', 255); // NOT NULL per screenshot
            $table->string('branch_code', 50)->nullable();
            $table->string('code', 10); // NOT NULL per screenshot
            $table->text('address');
            $table->string('city', 255);
            $table->string('province', 255)->default('Negros Oriental');
            $table->string('phone', 255);
            $table->string('email', 255)->nullable();
            $table->string('manager_name', 255)->nullable();

            // Location coordinates
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable(); // Screenshot shows (11,8) for longitude

            // Additional Info
            $table->text('operating_hours')->nullable();
            $table->string('photo_url', 255)->nullable();

            // Status
            $table->tinyInteger('is_active')->default(1);

            // Timestamps
            $table->timestamps(); // Handles created_at and updated_at

            // Indexes (Strictly matching keynames in your screenshot)
            $table->unique('code', 'branches_code_unique');
            $table->unique('branch_code', 'branches_branch_code_unique');
            $table->index('code', 'branches_code_index'); // This is the index causing the error
            $table->index('is_active', 'branches_is_active_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('branches');
    }
};
