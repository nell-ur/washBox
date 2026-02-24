<?php

namespace App\Console\Commands;

use App\Models\Order;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SyncOrderFees extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'washbox:sync-fees {--order= : Sync a specific order ID} {--force : Update even if fees are not zero}';

    /**
     * The console command description.
     */
    protected $description = 'Sync order fees from their parent pickup requests to fix data mismatches';

    /**
     * Execute the console command.
     */
   public function handle()
{
    $orderId = $this->option('order');
    $force = $this->option('force');

    // DEBUG: Check if the order even exists first
    if ($orderId && !Order::find($orderId)) {
        $this->error("Order ID {$orderId} does not exist in the database.");
        return 1;
    }

    $query = Order::query();

    if ($orderId) {
        $query->where('id', $orderId);
    }

    // Check if it has a pickup request
    $orders = $query->get();

    foreach ($orders as $order) {
        if (!$order->pickup_request_id) {
            $this->warn("Order #{$order->id} skipped: No linked pickup_request_id found.");
            continue;
        }

        // Proceed with sync logic...
        $this->info("Syncing Order #{$order->id}...");
        // (Insert the rest of the sync logic here)
    }
}
}
