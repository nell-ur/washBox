<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Branch;
use Illuminate\Support\Facades\Hash;

class StaffSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Creates initial staff users for each branch
     */
    public function run(): void
    {
        echo "\n🔷 Seeding Staff Users...\n";

        // Get all branches
        $branches = Branch::all();

        if ($branches->isEmpty()) {
            echo "❌ No branches found. Please run BranchSeeder first.\n";
            return;
        }

        $staffData = [];

        // Create staff for each branch
        foreach ($branches as $branch) {
            $staffData[] = [
                'name' => $branch->name . ' Staff',
                'email' => strtolower($branch->code) . '.staff@washbox.com',
                'password' => Hash::make('Staff@123'),
                'role' => 'staff',
                'branch_id' => $branch->id,
                'phone' => '0917' . str_pad($branch->id, 7, '0', STR_PAD_LEFT),
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        // Insert staff users
        foreach ($staffData as $staff) {
            // Check if staff already exists
            $existing = User::where('email', $staff['email'])->first();

            if ($existing) {
                echo "⚠️  Staff already exists: {$staff['email']}\n";
                continue;
            }

            User::create($staff);
            echo "✅ Created staff: {$staff['email']} (Password: Staff@123)\n";
        }

        echo "\n📊 Staff Seeding Summary:\n";
        echo "   Total Staff Users: " . User::where('role', 'staff')->count() . "\n";

        echo "\n🔑 Default Staff Credentials:\n";
        foreach (User::where('role', 'staff')->get() as $staff) {
            echo "   📧 {$staff->email} / Staff@123 ({$staff->branch->name})\n";
        }

        echo "\n⚠️  IMPORTANT: Change passwords after first login!\n\n";
    }
}
