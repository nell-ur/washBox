<?php

namespace App\Services;

use App\Models\Laundry;
use App\Models\SystemSetting;
use Illuminate\Support\Facades\Log;

class LaundryNotificationService
{
    protected FirebaseNotificationService $fcm;

    public function __construct(FirebaseNotificationService $fcm)
    {
        $this->fcm = $fcm;
    }

    /**
     * Call this whenever a laundry's status changes.
     *
     * Mapped to your actual statuses:
     *   received → processing → ready → paid → completed
     *                                        ↘ cancelled
     *
     * Usage:
     *   app(LaundryNotificationService::class)->onStatusChange($laundry, 'ready');
     */
    public function onStatusChange(Laundry $laundry, string $newStatus): void
    {
        if (!SystemSetting::get('enable_push_notifications', true)) return;

        // FCM token lives on the Customer model
        $token = optional($laundry->customer)->fcm_token;

        if (empty($token)) {
            Log::info("No FCM token for customer on Laundry #{$laundry->id} (Tracking: {$laundry->tracking_number})");
            return;
        }

        $tracking = $laundry->tracking_number ?? "#{$laundry->id}";
        $branch   = optional($laundry->branch)->name ?? 'our branch';

        match ($newStatus) {
            'received'   => $this->fcm->notifyOrderReceived($token, $tracking, $branch),
            'ready'      => $this->fcm->notifyLaundryReady($token, $tracking, $branch),
            'paid'       => $this->notifyPaymentReceived($token, $tracking),
            'completed'  => $this->fcm->notifyOrderCompleted($token, $tracking),
            'cancelled'  => $this->notifyCancelled($token, $tracking),
            default      => null, // 'processing' — no notification needed, just in-progress
        };
    }

    /**
     * Payment confirmed notification
     */
    private function notifyPaymentReceived(string $token, string $tracking): bool
    {
        return $this->fcm->sendToDevice(
            $token,
            '💳 Payment Confirmed!',
            "Payment for Laundry $tracking has been received. Thank you!",
            ['tracking' => $tracking, 'type' => 'payment_received']
        );
    }

    /**
     * Cancellation notification
     */
    private function notifyCancelled(string $token, string $tracking): bool
    {
        return $this->fcm->sendToDevice(
            $token,
            '❌ Order Cancelled',
            "Laundry $tracking has been cancelled. Please contact us if this was a mistake.",
            ['tracking' => $tracking, 'type' => 'cancelled']
        );
    }

    /**
     * Run daily via Laravel Scheduler for unclaimed reminders.
     * Fires at Day 3, 5, and 7 based on system settings toggles.
     * "Unclaimed" = status is 'ready' but customer hasn't picked up yet.
     */
    public function sendUnclaimedReminders(): void
    {
        if (!SystemSetting::get('enable_push_notifications', true)) return;

        $reminderDays = collect([3, 5, 7])->filter(
            fn($day) => SystemSetting::get("reminder_day_$day", false)
        );

        if ($reminderDays->isEmpty()) return;

        Laundry::where('status', 'ready')
            ->whereNotNull('ready_at')
            ->with('customer')
            ->get()
            ->each(function (Laundry $laundry) use ($reminderDays) {
                $daysUnclaimed = now()->diffInDays($laundry->ready_at);
                $token = optional($laundry->customer)->fcm_token;

                if (!$token) return;

                if ($reminderDays->contains($daysUnclaimed)) {
                    $this->fcm->notifyUnclaimedReminder(
                        $token,
                        $laundry->tracking_number ?? $laundry->id,
                        $daysUnclaimed
                    );
                }
            });
    }
}
