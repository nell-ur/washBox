<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            if (!Schema::hasColumn('customers', 'fcm_token')) {
                $table->string('fcm_token')->nullable()->after('password');
            }
            if (!Schema::hasColumn('customers', 'notification_enabled')) {
                $table->boolean('notification_enabled')->default(true)->after('fcm_token');
            }
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn(['fcm_token', 'notification_enabled']);
        });
    }
};
