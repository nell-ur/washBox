<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('laundry_service_addon', function (Blueprint $table) {
            $table->id();
            $table->foreignId('laundries_id')->constrained()->onDelete('cascade');
            $table->foreignId('service_id')->constrained()->onDelete('cascade');
            $table->decimal('price_at_purchase', 10, 2);
            $table->integer('quantity')->default(1);
            $table->timestamps();

            $table->unique(['laundries_id', 'service_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('laundry_service_addon');
    }
};
