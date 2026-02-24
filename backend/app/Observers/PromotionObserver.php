<?php

namespace App\Observers;

use App\Models\Promotion;
use App\Models\Notification;
use App\Models\Customer;
use Illuminate\Support\Facades\Log;

class PromotionObserver
{
    /**
     * Handle the Promotion "created" event.
     */
    // ✅ FIX: Change (Promotion promotion) to (Promotion $promotion)
    public function created(Promotion $promotion): void
    {
        // Only notify if the promotion is active
        if ($promotion->is_active) {
            $this->notifyAllCustomers($promotion);
        }
    }

    /**
     * Handle the Promotion "updated" event.
     */
    // ✅ FIX: Change (Promotion promotion) to (Promotion $promotion)
    public function updated(Promotion $promotion): void
    {
        // Notify if a promotion was previously hidden but is now set to active
        if ($promotion->isDirty('is_active') && $promotion->is_active) {
            $this->notifyAllCustomers($promotion);
        }
    }

    private function notifyAllCustomers(Promotion $promotion)
    {
        // Get all active customers
        $customers = Customer::all();

        foreach ($customers as $customer) {
            Notification::create([
                'customer_id' => $customer->id,
                'type'        => 'general',
                'title'       => 'New Promo: ' . ($promotion->poster_title ?? 'Special Offer!'),
                'body'        => $promotion->poster_subtitle ?? 'Check out our latest laundry deals.',
                'is_read'     => false,
            ]);
        }

        Log::info("Promotion notifications created for {$customers->count()} customers.");
    }
}
