<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class AddOnsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $addons = [
            // =============================================
            // ADD-ONS FROM FULLSERVICE.PNG
            // =============================================
            [
                'id' => 1,
                'name' => 'Extra Wash',
                'slug' => 'extra-wash',
                'description' => 'Extra wash cycle per load',
                'price' => 70.00,
                'is_active' => true,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'id' => 2,
                'name' => 'Ariel/Breeze Detergent',
                'slug' => 'ariel-breeze-detergent',
                'description' => '2 sachets of Ariel or Breeze laundry detergent',
                'price' => 20.00,
                'is_active' => true,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'id' => 3,
                'name' => 'Del Fabric Conditioner',
                'slug' => 'del-fabric-conditioner',
                'description' => '50ml Del fabric conditioner',
                'price' => 16.00,
                'is_active' => true,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'id' => 4,
                'name' => 'Zonrox Colorsafe Bleach',
                'slug' => 'zonrox-colorsafe-bleach',
                'description' => '60ml Zonrox Colorsafe bleach',
                'price' => 15.00,
                'is_active' => true,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'id' => 5,
                'name' => 'Downy Fabric Conditioner',
                'slug' => 'downy-fabric-conditioner',
                'description' => 'Downy fabric conditioner sachet',
                'price' => 20.00,
                'is_active' => true,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],

            // =============================================
            // ADD-ONS FROM SELFSERVICE.PNG
            // =============================================
            [
                'id' => 6,
                'name' => 'Extra Dry Time',
                'slug' => 'extra-dry-time',
                'description' => 'Add 10 minutes to drying time',
                'price' => 20.00,
                'is_active' => true,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],

            // =============================================
            // ADDITIONAL COMMON ADD-ONS
            // =============================================
            [
                'id' => 7,
                'name' => 'Stain Treatment',
                'slug' => 'stain-treatment',
                'description' => 'Special stain treatment for tough stains',
                'price' => 30.00,
                'is_active' => true,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'id' => 8,
                'name' => 'Fabric Softener',
                'slug' => 'fabric-softener',
                'description' => 'Extra fabric softener',
                'price' => 15.00,
                'is_active' => true,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'id' => 9,
                'name' => 'Dryer Sheets',
                'slug' => 'dryer-sheets',
                'description' => 'Anti-static dryer sheets',
                'price' => 10.00,
                'is_active' => true,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'id' => 10,
                'name' => 'Laundry Bag',
                'slug' => 'laundry-bag',
                'description' => 'Disposable laundry bag',
                'price' => 5.00,
                'is_active' => true,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'id' => 11,
                'name' => 'Hanger Service',
                'slug' => 'hanger-service',
                'description' => 'Clothes returned on hangers',
                'price' => 25.00,
                'is_active' => true,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'id' => 12,
                'name' => 'Express Service',
                'slug' => 'express-service',
                'description' => 'Same-day or next-day service',
                'price' => 100.00,
                'is_active' => true,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'id' => 13,
                'name' => 'Ironing Service',
                'slug' => 'ironing-service',
                'description' => 'Professional ironing/pressing',
                'price' => 50.00,
                'is_active' => true,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'id' => 14,
                'name' => 'Fold Only Service',
                'slug' => 'fold-only-service',
                'description' => 'Folding service only',
                'price' => 30.00,
                'is_active' => true,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'id' => 15,
                'name' => 'Baby Detergent',
                'slug' => 'baby-detergent',
                'description' => 'Hypoallergenic detergent for baby clothes',
                'price' => 25.00,
                'is_active' => true,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
        ];

        // Insert or update addons
        foreach ($addons as $addon) {
            DB::table('add_ons')->updateOrInsert(
                ['id' => $addon['id']],
                $addon
            );
        }

        $this->command->info('✅ Add-ons seeded successfully!');
        $this->command->info('Total add-ons created: ' . count($addons));

        // Display table in console
        $this->command->table(
            ['ID', 'Name', 'Price', 'Description'],
            collect($addons)->map(function($addon) {
                return [
                    'id' => $addon['id'],
                    'name' => $addon['name'],
                    'price' => '₱' . number_format($addon['price'], 2),
                    'description' => Str::limit($addon['description'], 40)
                ];
            })->toArray()
        );
    }
}
