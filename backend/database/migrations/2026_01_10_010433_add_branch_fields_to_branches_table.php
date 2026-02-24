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
        Schema::table('branches', function (Blueprint $table) {
            // Add missing columns if they don't exist
            if (!Schema::hasColumn('branches', 'branch_code')) {
                $table->string('branch_code', 50)->nullable()->unique()->after('name');
            }

            if (!Schema::hasColumn('branches', 'address')) {
                $table->string('address', 500)->nullable()->after('location');
            }

            if (!Schema::hasColumn('branches', 'phone')) {
                $table->string('phone', 50)->nullable()->after('address');
            }

            if (!Schema::hasColumn('branches', 'email')) {
                $table->string('email')->nullable()->after('phone');
            }

            if (!Schema::hasColumn('branches', 'manager_name')) {
                $table->string('manager_name')->nullable()->after('email');
            }

            if (!Schema::hasColumn('branches', 'operating_hours')) {
                $table->string('operating_hours')->nullable()->after('manager_name');
            }

            if (!Schema::hasColumn('branches', 'photo_url')) {
                $table->string('photo_url')->nullable()->after('operating_hours');
            }

            if (!Schema::hasColumn('branches', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('photo_url');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            $columns = [
                'branch_code',
                'address',
                'phone',
                'email',
                'manager_name',
                'operating_hours',
                'photo_url',
                'is_active'
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('branches', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
