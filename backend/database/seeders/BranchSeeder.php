<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Branch;

class BranchSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * FIXED:
     * - Day names capitalized (Monday, Tuesday, etc.) to match PHP's date('l')
     * - Removed json_encode() - let the model cast handle it automatically
     */
    public function run(): void
    {
        $branches = [
            [
                'name' => 'WashBox Sibulan',
                'code' => 'SBL',
                'address' => 'National Highway, Poblacion',
                'city' => 'Sibulan',
                'province' => 'Negros Oriental',
                'phone' => '09171234567',
                'email' => 'sibulan@washbox.com',
                'latitude' => 9.3500,
                'longitude' => 123.2833,
                'operating_hours' => [  // ✅ No json_encode() - model cast handles it
                    'Monday' => ['open' => '08:00', 'close' => '18:00'],     // ✅ Capitalized
                    'Tuesday' => ['open' => '08:00', 'close' => '18:00'],
                    'Wednesday' => ['open' => '08:00', 'close' => '18:00'],
                    'Thursday' => ['open' => '08:00', 'close' => '18:00'],
                    'Friday' => ['open' => '08:00', 'close' => '18:00'],
                    'Saturday' => ['open' => '08:00', 'close' => '18:00'],
                    'Sunday' => ['open' => '09:00', 'close' => '17:00'],
                ],
                'is_active' => true,
            ],
            [
                'name' => 'WashBox Dumaguete',
                'code' => 'DGT',
                'address' => 'Rizal Boulevard',
                'city' => 'Dumaguete City',
                'province' => 'Negros Oriental',
                'phone' => '09187654321',
                'email' => 'dumaguete@washbox.com',
                'latitude' => 9.3068,
                'longitude' => 123.3054,
                'operating_hours' => [
                    'Monday' => ['open' => '07:00', 'close' => '19:00'],
                    'Tuesday' => ['open' => '07:00', 'close' => '19:00'],
                    'Wednesday' => ['open' => '07:00', 'close' => '19:00'],
                    'Thursday' => ['open' => '07:00', 'close' => '19:00'],
                    'Friday' => ['open' => '07:00', 'close' => '19:00'],
                    'Saturday' => ['open' => '07:00', 'close' => '19:00'],
                    'Sunday' => ['open' => '08:00', 'close' => '18:00'],
                ],
                'is_active' => true,
            ],
            [
                'name' => 'WashBox Bais City',
                'code' => 'BAI',
                'address' => 'National Road',
                'city' => 'Bais City',
                'province' => 'Negros Oriental',
                'phone' => '09198765432',
                'email' => 'bais@washbox.com',
                'latitude' => 9.5908,
                'longitude' => 123.1220,
                'operating_hours' => [
                    'Monday' => ['open' => '08:00', 'close' => '18:00'],
                    'Tuesday' => ['open' => '08:00', 'close' => '18:00'],
                    'Wednesday' => ['open' => '08:00', 'close' => '18:00'],
                    'Thursday' => ['open' => '08:00', 'close' => '18:00'],
                    'Friday' => ['open' => '08:00', 'close' => '18:00'],
                    'Saturday' => ['open' => '08:00', 'close' => '18:00'],
                    'Sunday' => 'closed',  // ✅ Use 'closed' string for closed days
                ],
                'is_active' => true,
            ],
        ];

        foreach ($branches as $branch) {
            Branch::create($branch);
        }

        $this->command->info('✅ Created ' . count($branches) . ' branches successfully!');
    }
}
