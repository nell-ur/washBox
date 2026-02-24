<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('laundries', function (Blueprint $table) {
            // Check if column exists before adding
            if (!Schema::hasColumn('laundries', 'addons_total')) {
                $table->decimal('addons_total', 10, 2)->default(0)->after('subtotal');
            }

            // Also check for other missing columns from your fillable array
            $missingColumns = [
                'promotion_id' => 'unsignedBigInteger',
                'pickup_request_id' => 'unsignedBigInteger',
                'payment_status' => 'string',
                'payment_method' => 'string',
                'last_reminder_at' => 'datetime',
                'reminder_count' => 'integer',
                'is_unclaimed' => 'boolean',
                'unclaimed_at' => 'datetime',
                'storage_fee' => 'decimal:2',
            ];

            foreach ($missingColumns as $column => $type) {
                if (!Schema::hasColumn('laundries', $column)) {
                    switch ($type) {
                        case 'unsignedBigInteger':
                            $table->unsignedBigInteger($column)->nullable();
                            break;
                        case 'string':
                            $table->string($column)->nullable();
                            break;
                        case 'datetime':
                            $table->dateTime($column)->nullable();
                            break;
                        case 'integer':
                            $table->integer($column)->default(0);
                            break;
                        case 'boolean':
                            $table->boolean($column)->default(false);
                            break;
                        case 'decimal:2':
                            $table->decimal($column, 10, 2)->default(0);
                            break;
                    }
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('laundries', function (Blueprint $table) {
            // Only remove columns that were added by this migration
            if (Schema::hasColumn('laundries', 'addons_total')) {
                $table->dropColumn('addons_total');
            }
        });
    }
};
