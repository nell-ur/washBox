<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('unclaimed_laundries', function (Blueprint $table) {
            if (!Schema::hasColumn('unclaimed_laundries', 'disposed_by')) {
                $table->foreignId('disposed_by')->nullable()->after('disposed_at')->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('unclaimed_laundries', 'disposal_reason')) {
                $table->string('disposal_reason')->nullable()->after('disposed_by');
            }
        });
    }

    public function down(): void
    {
        Schema::table('unclaimed_laundries', function (Blueprint $table) {
            $table->dropForeign(['disposed_by']);
            $table->dropColumn(['disposed_by', 'disposal_reason']);
        });
    }
};
