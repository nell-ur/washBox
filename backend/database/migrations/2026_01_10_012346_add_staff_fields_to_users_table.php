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
        Schema::table('users', function (Blueprint $table) {
            // Add staff-related columns if they don't exist

            if (!Schema::hasColumn('users', 'employee_id')) {
                $table->string('employee_id', 50)->nullable()->unique()->after('email');
            }

            if (!Schema::hasColumn('users', 'position')) {
                $table->string('position')->nullable()->after('employee_id');
            }

            if (!Schema::hasColumn('users', 'branch_id')) {
                $table->foreignId('branch_id')->nullable()->after('position')->constrained('branches')->onDelete('set null');
            }

            if (!Schema::hasColumn('users', 'phone')) {
                $table->string('phone', 50)->nullable()->after('email_verified_at');
            }

            if (!Schema::hasColumn('users', 'address')) {
                $table->string('address', 500)->nullable()->after('phone');
            }

            if (!Schema::hasColumn('users', 'hire_date')) {
                $table->date('hire_date')->nullable()->after('position');
            }

            if (!Schema::hasColumn('users', 'profile_photo_path')) {
                $table->string('profile_photo_path')->nullable()->after('password');
            }

            if (!Schema::hasColumn('users', 'emergency_contact')) {
                $table->string('emergency_contact')->nullable()->after('address');
            }

            if (!Schema::hasColumn('users', 'emergency_phone')) {
                $table->string('emergency_phone', 50)->nullable()->after('emergency_contact');
            }

            if (!Schema::hasColumn('users', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('remember_token');
            }

            if (!Schema::hasColumn('users', 'role')) {
                $table->enum('role', ['admin', 'staff', 'customer'])->default('customer')->after('password');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $columns = [
                'employee_id',
                'position',
                'branch_id',
                'phone',
                'address',
                'hire_date',
                'profile_photo_path',
                'emergency_contact',
                'emergency_phone',
                'is_active',
                'role'
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('users', $column)) {
                    if ($column === 'branch_id') {
                        $table->dropForeign(['branch_id']);
                    }
                    $table->dropColumn($column);
                }
            }
        });
    }
};
