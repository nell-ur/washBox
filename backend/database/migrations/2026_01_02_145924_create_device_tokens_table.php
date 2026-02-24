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
        Schema::create('device_tokens', function (Blueprint $table) {
            $table->id();

            // Customer
            $table->foreignId('customer_id')->constrained()->onDelete('cascade');

            // FCM token
            $table->text('token');

            // Device info
            $table->string('device_type')->nullable(); // android, ios
            $table->string('device_name')->nullable();

            // Status
            $table->boolean('is_active')->default(true);

            // Last used
            $table->timestamp('last_used_at')->nullable();

            $table->timestamps();

            // Indexes
            $table->index('customer_id');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('device_tokens');
    }
};
