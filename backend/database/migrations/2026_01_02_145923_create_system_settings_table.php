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
        Schema::create('system_settings', function (Blueprint $table) {
            $table->id();

            // Setting key (unique)
            $table->string('key')->unique();

            // Setting value
            $table->text('value')->nullable();

            // Setting type (for casting)
            $table->enum('type', ['string', 'integer', 'boolean', 'json'])->default('string');

            // Group (for organization)
            $table->string('group')->default('general');

            // Description
            $table->text('description')->nullable();

            $table->timestamps();

            // Indexes
            $table->index('key');
            $table->index('group');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('system_settings');
    }
};
