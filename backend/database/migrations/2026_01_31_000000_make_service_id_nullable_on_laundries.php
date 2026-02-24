<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * NOTE: This migration attempts to use the Schema::table()->change() method which
     * requires the doctrine/dbal package. If your environment does not have it installed
     * you should install it (`composer require doctrine/dbal`) before running migrations.
     *
     * The migration is reversible: the down() method will set any NULL `service_id` values
     * to an existing service id (first one found) before making the column non-nullable again.
     */
    public function up()
    {
        if (! Schema::hasTable('laundries')) {
            return;
        }

        // Make the service_id column nullable
        Schema::table('laundries', function (Blueprint $table) {
            // Requires doctrine/dbal for the ->change() call
            $table->unsignedBigInteger('service_id')->nullable()->change();
        });
    }

    public function down()
    {
        if (! Schema::hasTable('laundries')) {
            return;
        }

        // Ensure no nulls exist before making column non-nullable again
        $firstServiceId = DB::table('services')->value('id');
        if (! $firstServiceId) {
            // If there are no services, set to 1 as a best-effort; migration may still fail — handle accordingly in your environment
            $firstServiceId = 1;
        }

        DB::table('laundries')->whereNull('service_id')->update(['service_id' => $firstServiceId]);

        Schema::table('laundries', function (Blueprint $table) {
            $table->unsignedBigInteger('service_id')->nullable(false)->change();
        });
    }
};
