<?php

namespace Database\Seeders;

use App\Models\Laundry;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Service;
use App\Models\User;
use App\Models\UnclaimedLaundry;
use Illuminate\Database\Seeder;
use Carbon\Carbon;
use Illuminate\Support\Str;

class UnclaimedLaundrySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🧺 Seeding Unclaimed Laundry Data...');

        // Get existing data
        $branches = Branch::where('is_active', true)->get();
        $customers = Customer::all();
        $services = Service::where('is_active', true)->get();
        $admin = User::where('role', 'admin')->first();
        $staffUsers = User::where('role', 'staff')->get();

        if ($branches->isEmpty()) {
            $this->command->error('No branches found. Please run BranchSeeder first.');
            return;
        }

        if ($customers->isEmpty()) {
            $this->command->error('No customers found. Please run CustomerSeeder first.');
            return;
        }

        // =====================================================================
        // 1. CREATE UNCLAIMED LAUNDRIES (Various urgency levels)
        // =====================================================================
        $this->command->info('Creating unclaimed laundry across branches...');

        $unclaimedScenarios = [
            // Critical (14+ days) - Highest priority
            ['days' => 28, 'count' => 2, 'label' => 'Critical - 28 days'],
            ['days' => 21, 'count' => 2, 'label' => 'Critical - 21 days'],
            ['days' => 16, 'count' => 3, 'label' => 'Critical - 16 days'],
            ['days' => 14, 'count' => 2, 'label' => 'Critical - 14 days'],

            // Urgent (7-13 days)
            ['days' => 12, 'count' => 3, 'label' => 'Urgent - 12 days'],
            ['days' => 10, 'count' => 4, 'label' => 'Urgent - 10 days'],
            ['days' => 8, 'count' => 3, 'label' => 'Urgent - 8 days'],
            ['days' => 7, 'count' => 2, 'label' => 'Urgent - 7 days'],

            // Warning (3-6 days)
            ['days' => 6, 'count' => 4, 'label' => 'Warning - 6 days'],
            ['days' => 5, 'count' => 5, 'label' => 'Warning - 5 days'],
            ['days' => 4, 'count' => 4, 'label' => 'Warning - 4 days'],
            ['days' => 3, 'count' => 3, 'label' => 'Warning - 3 days'],

            // Pending (1-2 days)
            ['days' => 2, 'count' => 5, 'label' => 'Pending - 2 days'],
            ['days' => 1, 'count' => 4, 'label' => 'Pending - 1 day'],
        ];

        $totalCreated = 0;

        foreach ($unclaimedScenarios as $scenario) {
            for ($i = 0; $i < $scenario['count']; $i++) {
                $branch = $branches->random();
                $customer = $customers->random();
                $service = $services->isNotEmpty() ? $services->random() : null;
                $staff = $staffUsers->where('branch_id', $branch->id)->first() ?? $staffUsers->first();

                // Calculate dates
                $receivedAt = now()->subDays($scenario['days'] + rand(1, 3));
                $readyAt = now()->subDays($scenario['days']);

                // Create laundry first
                $laundry = Laundry::create([
                    'tracking_number' => 'WB-' . strtoupper(Str::random(8)),
                    'customer_id' => $customer->id,
                    'branch_id' => $branch->id,
                    'service_id' => $service?->id,
                    'created_by' => $staff?->id ?? $admin?->id,
                    'staff_id' => $staff?->id,
                    'weight' => rand(20, 100) / 10, // 2.0 - 10.0 kg
                    'price_per_piece' => $service?->price_per_piece ?? 35,
                    'subtotal' => 0, // Will calculate
                    'discount_amount' => 0,
                    'total_amount' => 0, // Will calculate
                    'payment_status' => 'unpaid',
                    'status' => 'ready',
                    'received_at' => $receivedAt,
                    'ready_at' => $readyAt,
                    'notes' => "Test unclaimed laundry - {$scenario['label']}",
                    'created_at' => $receivedAt,
                    'updated_at' => $readyAt,
                ]);

                // Calculate totals
                $subtotal = $laundry->weight * $laundry->price_per_piece;
                $laundry->update([
                    'subtotal' => $subtotal,
                    'total_amount' => $subtotal,
                ]);

                // Calculate reminder count based on days
                $reminderCount = 0;
                $lastReminderAt = null;

                if ($scenario['days'] >= 14) {
                    $reminderCount = rand(3, 5);
                    $lastReminderAt = now()->subDays(rand(1, 3));
                } elseif ($scenario['days'] >= 7) {
                    $reminderCount = rand(2, 3);
                    $lastReminderAt = now()->subDays(rand(1, 4));
                } elseif ($scenario['days'] >= 3) {
                    $reminderCount = rand(1, 2);
                    $lastReminderAt = now()->subDays(rand(1, 2));
                }

                // Update laundry with unclaimed tracking fields (if they exist)
                $laundry->update([
                    'reminder_count' => $reminderCount,
                    'last_reminder_at' => $lastReminderAt,
                    'is_unclaimed' => $scenario['days'] >= 7,
                    'unclaimed_at' => $scenario['days'] >= 7 ? $readyAt->addDays(7) : null,
                ]);

                // Create UnclaimedLaundry record
                UnclaimedLaundry::create([
                    'laundries_id' => $laundry->id,
                    'customer_id' => $customer->id,
                    'branch_id' => $branch->id,
                    'days_unclaimed' => $scenario['days'],
                    'status' => 'unclaimed',
                    'notes' => "Auto-tracked: {$scenario['label']} in {$branch->name}",
                    'created_at' => $readyAt,
                    'updated_at' => now(),
                ]);

                $totalCreated++;
            }
        }

        $this->command->info("✅ Created {$totalCreated} unclaimed laundry");

        // =====================================================================
        // 2. CREATE RECOVERED RECORDS (Success stories)
        // =====================================================================
        $this->command->info('Creating recovered (claimed) records...');

        $recoveredScenarios = [
            ['days_was' => 5, 'recovered_ago' => 1, 'count' => 3],
            ['days_was' => 8, 'recovered_ago' => 2, 'count' => 4],
            ['days_was' => 12, 'recovered_ago' => 3, 'count' => 2],
            ['days_was' => 15, 'recovered_ago' => 5, 'count' => 2],
            ['days_was' => 20, 'recovered_ago' => 7, 'count' => 1],
        ];

        $recoveredCount = 0;

        foreach ($recoveredScenarios as $scenario) {
            for ($i = 0; $i < $scenario['count']; $i++) {
                $branch = $branches->random();
                $customer = $customers->random();
                $service = $services->isNotEmpty() ? $services->random() : null;
                $staff = $staffUsers->where('branch_id', $branch->id)->first() ?? $staffUsers->first();
                $recoveredBy = $staffUsers->random() ?? $admin;

                $receivedAt = now()->subDays($scenario['days_was'] + $scenario['recovered_ago'] + rand(1, 3));
                $readyAt = now()->subDays($scenario['days_was'] + $scenario['recovered_ago']);
                $recoveredAt = now()->subDays($scenario['recovered_ago']);

                // Create completed laundry
                $laundry = Laundry::create([
                    'tracking_number' => 'WB-' . strtoupper(Str::random(8)),
                    'customer_id' => $customer->id,
                    'branch_id' => $branch->id,
                    'service_id' => $service?->id,
                    'created_by' => $staff?->id ?? $admin?->id,
                    'staff_id' => $staff?->id,
                    'weight' => rand(20, 80) / 10,
                    'price_per_piece' => $service?->price_per_piece ?? 35,
                    'subtotal' => 0,
                    'discount_amount' => 0,
                    'total_amount' => 0,
                    'payment_status' => 'paid',
                    'status' => 'completed',
                    'received_at' => $receivedAt,
                    'ready_at' => $readyAt,
                    'paid_at' => $recoveredAt,
                    'completed_at' => $recoveredAt,
                    'notes' => "Recovered after {$scenario['days_was']} days unclaimed",
                    'created_at' => $receivedAt,
                ]);

                $subtotal = $laundry->weight * $laundry->price_per_piece;
                $laundry->update([
                    'subtotal' => $subtotal,
                    'total_amount' => $subtotal,
                ]);

                // Create recovered UnclaimedLaundry record
                UnclaimedLaundry::create([
                    'laundries_id' => $laundry->id,
                    'customer_id' => $customer->id,
                    'branch_id' => $branch->id,
                    'days_unclaimed' => $scenario['days_was'],
                    'status' => 'recovered',
                    'recovered_at' => $recoveredAt,
                    'recovered_by' => $recoveredBy?->id,
                    'notes' => "Customer claimed after automated reminder system. Was unclaimed for {$scenario['days_was']} days.",
                    'created_at' => $readyAt,
                    'updated_at' => $recoveredAt,
                ]);

                $recoveredCount++;
            }
        }

        $this->command->info("✅ Created {$recoveredCount} recovered records");

        // =====================================================================
        // 3. CREATE DISPOSED RECORDS (Lost revenue)
        // =====================================================================
        $this->command->info('Creating disposed records...');

        $disposedScenarios = [
            ['days_was' => 35, 'disposed_ago' => 5, 'count' => 2],
            ['days_was' => 32, 'disposed_ago' => 10, 'count' => 1],
            ['days_was' => 45, 'disposed_ago' => 15, 'count' => 1],
            ['days_was' => 38, 'disposed_ago' => 20, 'count' => 2],
            ['days_was' => 60, 'disposed_ago' => 30, 'count' => 1],
        ];

        $disposedCount = 0;

        foreach ($disposedScenarios as $scenario) {
            for ($i = 0; $i < $scenario['count']; $i++) {
                $branch = $branches->random();
                $customer = $customers->random();
                $service = $services->isNotEmpty() ? $services->random() : null;
                $disposedBy = $admin ?? $staffUsers->first();

                $receivedAt = now()->subDays($scenario['days_was'] + $scenario['disposed_ago'] + rand(1, 3));
                $readyAt = now()->subDays($scenario['days_was'] + $scenario['disposed_ago']);
                $disposedAt = now()->subDays($scenario['disposed_ago']);

                // Create cancelled laundry
                $laundry = Laundry::create([
                    'tracking_number' => 'WB-' . strtoupper(Str::random(8)),
                    'customer_id' => $customer->id,
                    'branch_id' => $branch->id,
                    'service_id' => $service?->id,
                    'created_by' => $admin?->id,
                    'weight' => rand(30, 100) / 10,
                    'price_per_piece' => $service?->price_per_piece ?? 35,
                    'subtotal' => 0,
                    'discount_amount' => 0,
                    'total_amount' => 0,
                    'payment_status' => 'unpaid',
                    'status' => 'cancelled',
                    'received_at' => $receivedAt,
                    'ready_at' => $readyAt,
                    'cancelled_at' => $disposedAt,
                    'cancellation_reason' => "Disposed after {$scenario['days_was']} days unclaimed - exceeded 30-day storage policy",
                    'notes' => "Disposed per storage policy",
                    'created_at' => $receivedAt,
                ]);

                $subtotal = $laundry->weight * $laundry->price_per_piece;
                $laundry->update([
                    'subtotal' => $subtotal,
                    'total_amount' => $subtotal,
                ]);

                // Create disposed UnclaimedLaundry record
                UnclaimedLaundry::create([
                    'laundries_id' => $laundry->id,
                    'customer_id' => $customer->id,
                    'branch_id' => $branch->id,
                    'days_unclaimed' => $scenario['days_was'],
                    'status' => 'disposed',
                    'disposed_at' => $disposedAt,
                    'disposed_by' => $disposedBy?->id,
                    'disposal_reason' => 'Exceeded 30-day storage policy after multiple reminder attempts',
                    'notes' => "Disposed after {$scenario['days_was']} days. Multiple reminders sent with no response. Revenue loss: ₱" . number_format($subtotal, 2),
                    'created_at' => $readyAt,
                    'updated_at' => $disposedAt,
                ]);

                $disposedCount++;
            }
        }

        $this->command->info("✅ Created {$disposedCount} disposed records");

        // =====================================================================
        // 4. LINK EXISTING READY LAUNDRIES TO UNCLAIMED TRACKING
        // =====================================================================
        $this->command->info('Linking existing ready laundry to unclaimed tracking...');

        $existingReadyLaundries = Laundry::where('status', 'ready')
            ->whereNotNull('ready_at')
            ->whereDoesntHave('unclaimedLaundry')
            ->get();

        $linkedCount = 0;

        foreach ($existingReadyLaundries as $laundry) {
            $daysUnclaimed = $laundry->ready_at ? now()->diffInDays($laundry->ready_at) : 0;

            // Only track if 3+ days
            if ($daysUnclaimed >= 3) {
                UnclaimedLaundry::create([
                    'laundries_id' => $laundry->id,
                    'customer_id' => $laundry->customer_id,
                    'branch_id' => $laundry->branch_id,
                    'days_unclaimed' => $daysUnclaimed,
                    'status' => 'unclaimed',
                    'notes' => "Auto-linked from existing ready laundry",
                    'created_at' => $laundry->ready_at,
                ]);

                $linkedCount++;
            }
        }

        $this->command->info("✅ Linked {$linkedCount} existing ready laundry");

        // =====================================================================
        // 5. SUMMARY
        // =====================================================================
        $this->command->newLine();
        $this->command->info('📊 UNCLAIMED LAUNDRY SEEDING SUMMARY');
        $this->command->info('=====================================');

        // Calculate stats
        $stats = UnclaimedLaundry::getGlobalStats();

        $this->command->table(
            ['Metric', 'Value'],
            [
                ['Total Unclaimed', $stats['total']],
                ['Critical (14+ days)', $stats['critical']],
                ['Urgent (7-13 days)', $stats['urgent']],
                ['Warning (3-6 days)', $stats['warning']],
                ['Total Value at Risk', '₱' . number_format($stats['total_value'], 2)],
                ['Potential Storage Fees', '₱' . number_format($stats['storage_fees'], 2)],
                ['Recovered This Month', '₱' . number_format($stats['recovered_this_month'], 2)],
                ['Lost This Month', '₱' . number_format($stats['loss_this_month'], 2)],
                ['Disposed Count', $stats['disposed_this_month']],
            ]
        );

        // Branch breakdown
        $this->command->newLine();
        $this->command->info('📍 BY BRANCH:');

        foreach ($branches as $branch) {
            $branchStats = UnclaimedLaundry::getBranchStats($branch->id);
            $this->command->line("   {$branch->name}: {$branchStats['total']} unclaimed (₱" . number_format($branchStats['total_value'], 2) . ")");
        }

        $this->command->newLine();
        $this->command->info('✅ Unclaimed Laundry seeding completed!');
    }
}
