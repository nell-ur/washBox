// database/migrations/2026_01_02_000027_create_system_settings_table.php
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->enum('type', ['string', 'integer', 'boolean', 'json'])->default('string');
            $table->string('group')->default('general');
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index('key');
            $table->index('group');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_settings');
    }
};
