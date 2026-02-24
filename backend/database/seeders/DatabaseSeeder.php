<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->command->info('Starting database seeding...');

        // Seed in order
        $this->call(AdminUserSeeder::class);
        $this->call(StaffSeeder::class);
        $this->call(SystemSettingsSeeder::class);
        $this->call(UnclaimedLaundrySeeder::class);
        $this->call(ServiceTypeSeeder::class);
        $this->call(BranchSeeder::class);

        $this->command->info('Database seeding completed!');
        $this->command->info('');
        $this->command->info('Admin: admin@washbox.com / Admin@123');
        $this->command->info('Staff: staff@washbox.com / Staff@123');
    }
}
