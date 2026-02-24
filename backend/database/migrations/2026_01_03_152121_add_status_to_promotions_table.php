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
    Schema::table('promotions', function (Blueprint $table) {
        // Adding the status column as a string with a default value of 'active'
        $table->string('status')->default('active')->after('id');
    });
}

public function down(): void
{
    Schema::table('promotions', function (Blueprint $table) {
        $table->dropColumn('status');
    });
}
};
