<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('laundries', function (Blueprint $table) {
            if (!Schema::hasColumn('laundries', 'pickup_request_id')) {
                $table->unsignedBigInteger('pickup_request_id')->nullable()->after('id');
                $table->foreign('pickup_request_id')
                    ->references('id')
                    ->on('pickup_requests')
                    ->onDelete('set null');
            }
        });
    }

    public function down(): void
    {
        Schema::table('laundries', function (Blueprint $table) {
            $table->dropForeign(['pickup_request_id']);
            $table->dropColumn('pickup_request_id');
        });
    }
};
