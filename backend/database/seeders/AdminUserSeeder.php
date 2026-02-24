<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * This seeder creates the initial admin user account for the WashBox system.
     *
     * Default Credentials:
     * - Email: admin@washbox.com
     * - Password: Admin@123
     *
     * ⚠️ IMPORTANT: Change these credentials after first login!
     */
    public function run(): void
    {
        // Check if admin already exists
        $existingAdmin = User::where('email', 'admin@washbox.com')->first();

        if ($existingAdmin) {
            $this->command->warn('Admin user already exists. Skipping...');
            return;
        }

        // Create admin user
        $admin = User::create([
            'name' => 'System Administrator',
            'email' => 'admin@washbox.com',
            'phone' => '09123456789',
            'password' => Hash::make('Admin@123'),
            'role' => 'admin',
            'branch_id' => null, // Admin has access to all branches
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $this->command->info('✅ Admin user created successfully!');
        $this->command->info('');
        $this->command->info('==============================================');
        $this->command->info('  ADMIN CREDENTIALS');
        $this->command->info('==============================================');
        $this->command->info('  Email:    admin@washbox.com');
        $this->command->info('  Password: Admin@123');
        $this->command->info('==============================================');
        $this->command->info('');
        $this->command->warn('⚠️  SECURITY WARNING: Please change these credentials after first login!');
        $this->command->info('');
    }
}
