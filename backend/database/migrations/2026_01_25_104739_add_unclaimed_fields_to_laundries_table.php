<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('laundries', function (Blueprint $table) {
            // Check if columns don't exist before adding
            if (!Schema::hasColumn('laundries', 'processing_at')) {
                $table->timestamp('processing_at')->nullable()->after('received_at');
            }
            if (!Schema::hasColumn('laundries', 'payment_method')) {
                $table->string('payment_method')->nullable()->after('payment_status');
            }
            if (!Schema::hasColumn('luandries', 'last_reminder_at')) {
                $table->timestamp('last_reminder_at')->nullable()->after('completed_at');
            }
            if (!Schema::hasColumn('laundries', 'reminder_count')) {
                $table->integer('reminder_count')->default(0)->after('last_reminder_at');
            }
            if (!Schema::hasColumn('laundries', 'is_unclaimed')) {
                $table->boolean('is_unclaimed')->default(false)->after('reminder_count');
            }
            if (!Schema::hasColumn('laundries', 'unclaimed_at')) {
                $table->timestamp('unclaimed_at')->nullable()->after('is_unclaimed');
            }
            if (!Schema::hasColumn('laundries', 'storage_fee')) {
                $table->decimal('storage_fee', 10, 2)->default(0)->after('unclaimed_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('laundries', function (Blueprint $table) {
            $table->dropColumn([
                'processing_at',
                'payment_method',
                'last_reminder_at',
                'reminder_count',
                'is_unclaimed',
                'unclaimed_at',
                'storage_fee',
            ]);
        });
    }
};
