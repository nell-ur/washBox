<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Create add_ons table if it doesn't exist
        if (!Schema::hasTable('add_ons')) {
            Schema::create('add_ons', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('slug')->unique();
                $table->text('description')->nullable();
                $table->decimal('price', 10, 2);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->softDeletes();
            });

            echo "✓ Created 'add_ons' table\n";
        } else {
            echo "⏩ 'add_ons' table already exists - skipping\n";
        }

        // 2. Create order_addon pivot table if it doesn't exist
        if (!Schema::hasTable('laundry_addon')) {
            Schema::create('laundry_addon', function (Blueprint $table) {
                $table->id();
                $table->foreignId('laundries_id')->constrained()->onDelete('cascade');
                $table->foreignId('add_on_id')->constrained()->onDelete('cascade');
                $table->decimal('price_at_purchase', 10, 2);
                $table->integer('quantity')->default(1);
                $table->timestamps();

                $table->unique(['laundries_id', 'add_on_id']);
            });

            echo "✓ Created 'laundry_addon' pivot table\n";
        } else {
            echo "⏩ 'laundry_addon' pivot table already exists - skipping\n";
        }

        // 3. Verify foreign key constraints exist for add_on_id
        Schema::table('laundry_addon', function (Blueprint $table) {
            // Check if foreign key constraint exists
            $conn = Schema::getConnection();
            $dbName = $conn->getDatabaseName();

            $foreignKeyExists = $conn->selectOne("
                SELECT COUNT(*) as count
                FROM information_schema.table_constraints
                WHERE constraint_schema = ?
                AND table_name = 'laundry_addon'
                AND constraint_name = 'laundry_addon_add_on_id_foreign'
                AND constraint_type = 'FOREIGN KEY'
            ", [$dbName]);

            if ($foreignKeyExists->count == 0 && Schema::hasTable('add_ons')) {
                $table->foreign('add_on_id')->references('id')->on('add_ons')->onDelete('cascade');
                echo "✓ Added foreign key constraint for add_on_id\n";
            }
        });
    }

    public function down(): void
    {
        // Drop in reverse order
        Schema::dropIfExists('laundry_addon');
        Schema::dropIfExists('add_ons');
    }
};
