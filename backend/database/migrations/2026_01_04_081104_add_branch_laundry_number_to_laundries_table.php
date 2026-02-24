<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
{
    Schema::table('laundries', function (Blueprint $table) {
        $table->unsignedInteger('branch_laundry_number')->nullable()->after('id');
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
       Schema::table('laundries', function (Blueprint $table) {
        $table->dropColumn('branch_laundry_number');
        });
    }
};
