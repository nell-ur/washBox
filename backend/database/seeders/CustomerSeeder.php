<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Customer;
use App\Models\Branch;
use Illuminate\Support\Facades\Hash;

class CustomerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Creates sample customer accounts for testing
     */
    public function run(): void
    {
        echo "\n👥 Seeding Customer Accounts...\n";

        // Get all branches
        $branches = Branch::all();

        if ($branches->isEmpty()) {
            echo "❌ No branches found. Please run BranchSeeder first.\n";
            return;
        }

        // Sample customer data
        $customers = [
            [
                'name' => 'Juan Dela Cruz',
                'email' => 'juan.delacruz@example.com',
                'password' => Hash::make('Customer@123'),
                'phone' => '09171234567',
                'address' => 'Rizal Boulevard, Dumaguete City',
                'preferred_branch_id' => $branches->where('code', 'DGT')->first()?->id ?? $branches->first()->id,
            ],
            [
                'name' => 'Maria Santos',
                'email' => 'maria.santos@example.com',
                'password' => Hash::make('Customer@123'),
                'phone' => '09181234567',
                'address' => 'Poblacion, Sibulan, Negros Oriental',
                'preferred_branch_id' => $branches->where('code', 'SBL')->first()?->id ?? $branches->first()->id,
            ],
            [
                'name' => 'Pedro Reyes',
                'email' => 'pedro.reyes@example.com',
                'password' => Hash::make('Customer@123'),
                'phone' => '09191234567',
                'address' => 'National Highway, Bais City',
                'preferred_branch_id' => $branches->where('code', 'BAS')->first()?->id ?? $branches->first()->id,
            ],
            [
                'name' => 'Ana Garcia',
                'email' => 'ana.garcia@example.com',
                'password' => Hash::make('Customer@123'),
                'phone' => '09201234567',
                'address' => 'Calindagan, Dumaguete City',
                'preferred_branch_id' => $branches->where('code', 'DGT')->first()?->id ?? $branches->first()->id,
            ],
            [
                'name' => 'Jose Fernandez',
                'email' => 'jose.fernandez@example.com',
                'password' => Hash::make('Customer@123'),
                'phone' => '09211234567',
                'address' => 'Bantayan, Dumaguete City',
                'preferred_branch_id' => $branches->where('code', 'DGT')->first()?->id ?? $branches->first()->id,
            ],
        ];

        foreach ($customers as $customerData) {
            // Check if customer already exists
            $existing = Customer::where('email', $customerData['email'])->first();

            if ($existing) {
                echo "⚠️  Customer already exists: {$customerData['email']}\n";
                continue;
            }

            Customer::create($customerData);
            echo "✅ Created customer: {$customerData['name']} ({$customerData['email']})\n";
        }

        echo "\n📊 Customer Seeding Summary:\n";
        echo "   Total Customers: " . Customer::count() . "\n";

        echo "\n🔑 Sample Customer Credentials:\n";
        foreach (Customer::take(5)->get() as $customer) {
            echo "   📧 {$customer->email} / Customer@123\n";
        }

        echo "\n💡 All customers use password: Customer@123\n\n";
    }
}
