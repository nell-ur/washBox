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
            $table->bigIncrements('id'); // bigint(20) UNIQUE AUTO_INCREMENT

            // Basic Information
            $table->string('name', 255); // varchar(255) NOT NULL
            $table->string('phone', 255); // varchar(255) NOT NULL
            $table->string('email', 255)->nullable(); // varchar(255) NULL
            $table->string('password', 255)->nullable(); // varchar(255) NULL
            $table->string('fcm_token', 255)->nullable(); // varchar(255) NULL
            $table->boolean('notification_enabled')->default(1); // tinyint(1) NOT NULL default 1

            // Registration Type
            $table->enum('registration_type', ['walk_in', 'self_registered'])->default('walk_in'); // enum NOT NULL default 'walk_in'

            // Address Information
            $table->text('address')->nullable(); // text NULL
            $table->decimal('latitude', 10, 8)->nullable(); // decimal(10,8) NULL
            $table->decimal('longitude', 10, 8)->nullable(); // decimal(10,8) NULL (with note: No data in this column)

            // Branch Association
            $table->unsignedBigInteger('preferred_branch_id')->nullable(); // bigint(20) NULL

            // Status
            $table->enum('status', ['active', 'inactive', 'suspended'])->default('active'); // enum NOT NULL default 'active' (with comment: Customer account status)

            // Registered by (for walk-in customers)
            $table->unsignedBigInteger('registered_by')->nullable(); // bigint(20) NULL

            // Profile
            $table->string('profile_photo', 255)->nullable(); // varchar(255) NULL

            // Active Flag
            $table->boolean('is_active')->default(1); // tinyint(1) NOT NULL default 1

            // Laravel Authentication
            $table->rememberToken(); // varchar(100) NULL

            // Timestamps
            $table->timestamp('created_at')->nullable(); // timestamp NULL
            $table->timestamp('updated_at')->nullable(); // timestamp NULL

            // Indexes (matching the database exactly)
            $table->primary('id'); // PRIMARY BTREE

            // Unique indexes
            $table->unique('phone', 'customers_phone_unique'); // Unique index on phone
            $table->unique('email', 'customers_email_unique'); // Unique index on email (allows NULL)

            // Regular indexes
            $table->index('registered_by', 'customers_registered_by_forlogin'); // Index on registered_by
            $table->index('phone', 'customers_phone_index'); // Index on phone
            $table->index('registration_type', 'customers_registering_type_index'); // Index on registration_type
            $table->index('preferred_branch_id', 'customers_preferred_branch_id_index'); // Index on preferred_branch_id
            $table->index('is_active', 'customers_is_active_index'); // Index on is_active

            
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
