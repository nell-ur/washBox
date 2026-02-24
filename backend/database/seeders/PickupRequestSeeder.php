<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class PickupRequestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Example: Seed 10 pickup requests
        for ($i = 1; $i <= 10; $i++) {
            DB::table('pickup_requests')->insert([
                'customer_id'      => rand(1, 5), // Adjust based on your customers
                'branch_id'        => rand(1, 3), // Adjust based on your branches
                'pickup_address'   => 'Sample Address ' . $i,
                'latitude'         => 9.3068 + (rand(-100, 100) / 10000),
                'longitude'        => 123.3054 + (rand(-100, 100) / 10000),
                'preferred_date'   => Carbon::now()->addDays(rand(0, 7))->toDateString(),
                'preferred_time'   => Carbon::now()->addHours(rand(8, 18))->format('H:i:s'),
                'notes'            => 'Pickup note ' . $i,
                'service_id'       => rand(1, 2), // Adjust based on your services
                'status'           => 'pending',
                'assigned_to'      => null,
                'accepted_at'      => null,
                'en_route_at'      => null,
                'picked_up_at'     => null,
                'cancelled_at'     => null,
                'cancellation_reason' => null,
                'cancelled_by'     => null,
                'laundries_id'         => null,
                'pin'              => str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT), // Random 4-digit PIN
                'created_at'       => now(),
                'updated_at'       => now(),
                'deleted_at'       => null,
            ]);
        }
    }
}
