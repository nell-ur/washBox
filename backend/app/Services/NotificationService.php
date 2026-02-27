<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\User;
use App\Models\Laundry;
use App\Models\PickupRequest;
use App\Models\Customer;
use App\Models\CustomerRating;    // ← added for rating notifications
use App\Models\AdminNotification; // ← admin bell reads this table, NOT notifications

class NotificationService
{
    /**
     * Send notification to a specific user (staff/admin)
     */
    public static function sendToUser(
        int $userId,
        string $type,
        string $title,
        string $body,
        ?int $laundryId = null,
        ?int $pickupRequestId = null,
        ?int $customerId = null,
        array $data = []
    ): Notification {
        return Notification::create([
            'user_id' => $userId,
            'customer_id' => $customerId,
            'type' => $type,
            'title' => $title,
            'body' => $body,
            'laundries_id' => $laundryId,
            'pickup_request_id' => $pickupRequestId,
            'data' => $data,
            'is_read' => false,
        ]);
    }

    /**
     * Send notification to all staff in a branch
     */
    public static function sendToBranchStaff(
        int $branchId,
        string $type,
        string $title,
        string $body,
        ?int $laundryId = null,
        ?int $pickupRequestId = null,
        ?int $customerId = null,
        array $data = []
    ): int {
        $staffUsers = User::where('branch_id', $branchId)
            ->where('role', 'staff')
            ->where('is_active', true)
            ->get();

        $count = 0;
        foreach ($staffUsers as $staff) {
            self::sendToUser(
                $staff->id,
                $type,
                $title,
                $body,
                $laundryId,
                $pickupRequestId,
                $customerId,
                $data
            );
            $count++;
        }

        return $count;
    }

    /**
     * Send notification to all active staff
     */
    public static function sendToAllStaff(
        string $type,
        string $title,
        string $body,
        ?int $laundryId = null,
        ?int $pickupRequestId = null,
        ?int $customerId = null,
        array $data = []
    ): int {
        $staffUsers = User::where('role', 'staff')
            ->where('is_active', true)
            ->get();

        $count = 0;
        foreach ($staffUsers as $staff) {
            self::sendToUser(
                $staff->id,
                $type,
                $title,
                $body,
                $laundryId,
                $pickupRequestId,
                $customerId,
                $data
            );
            $count++;
        }

        return $count;
    }

    /**
     * Send notification to all admins
     */
    public static function sendToAllAdmins(
        string $type,
        string $title,
        string $body,
        ?int $laundryId = null,
        ?int $pickupRequestId = null,
        ?int $customerId = null,
        array $data = []
    ): int {
        $admins = User::where('role', 'admin')
            ->where('is_active', true)
            ->get();

        $count = 0;
        foreach ($admins as $admin) {
            self::sendToUser(
                $admin->id,
                $type,
                $title,
                $body,
                $laundryId,
                $pickupRequestId,
                $customerId,
                $data
            );
            $count++;
        }

        return $count;
    }

    /**
     * Send notification to customer
     */
    public static function sendToCustomer(
        int $customerId,
        string $type,
        string $title,
        string $body,
        ?int $laundryId = null,
        ?int $pickupRequestId = null,
        array $data = []
    ): Notification {
        return Notification::create([
            'customer_id' => $customerId,
            'user_id' => null,
            'type' => $type,
            'title' => $title,
            'body' => $body,
            'laundries_id' => $laundryId,
            'pickup_request_id' => $pickupRequestId,
            'data' => $data,
            'is_read' => false,
        ]);
    }

    // ========================================================================
    // PICKUP REQUEST NOTIFICATIONS
    // ========================================================================

    /**
     * Notify staff about new pickup request
     */
    public static function notifyNewPickupRequest(PickupRequest $pickup): int
    {
        $customer = $pickup->customer;
        $customerName = $customer ? $customer->name : 'A customer';

        $title = 'New Pickup Request';
        $body = "{$customerName} has requested a pickup at {$pickup->pickup_address}";

        // If pickup has a branch, notify only that branch's staff
        if ($pickup->branch_id) {
            return self::sendToBranchStaff(
                $pickup->branch_id,
                'pickup_request',
                $title,
                $body,
                null,
                $pickup->id,
                $pickup->customer_id,
                [
                    'pickup_id' => $pickup->id,
                    'customer_name' => $customerName,
                    'address' => $pickup->pickup_address,
                    'scheduled_at' => $pickup->scheduled_pickup_time?->format('M j, Y g:i A'),
                ]
            );
        }

        // Otherwise notify all staff
        return self::sendToAllStaff(
            'pickup_request',
            $title,
            $body,
            null,
            $pickup->id,
            $pickup->customer_id,
            [
                'pickup_id' => $pickup->id,
                'customer_name' => $customerName,
                'address' => $pickup->pickup_address,
                'scheduled_at' => $pickup->scheduled_pickup_time?->format('M j, Y g:i A'),
            ]
        );
    }

    /**
     * Notify customer that pickup was accepted
     */
    public static function notifyPickupAccepted(PickupRequest $pickup): Notification
    {
        return self::sendToCustomer(
            $pickup->customer_id,
            'pickup_accepted',
            'Pickup Request Accepted',
            'Your pickup request has been accepted. Our staff will arrive at the scheduled time.',
            null,
            $pickup->id,
            ['pickup_id' => $pickup->id]
        );
    }

    /**
     * Notify customer that staff is en route
     */
    public static function notifyPickupEnRoute(PickupRequest $pickup): Notification
    {
        return self::sendToCustomer(
            $pickup->customer_id,
            'pickup_en_route',
            'Staff En Route',
            'Our staff is on the way to pick up your laundry.',
            null,
            $pickup->id,
            ['pickup_id' => $pickup->id]
        );
    }

    /**
     * Notify customer that pickup is completed
     */
    public static function notifyPickupCompleted(PickupRequest $pickup, ?Laundry $laundry = null): Notification
    {
        $body = 'Your laundry has been picked up successfully.';
        if ($laundry) {
            $body .= " Laundry #{$laundry->tracking_number} has been created.";
        }

        return self::sendToCustomer(
            $pickup->customer_id,
            'pickup_completed',
            'Pickup Completed',
            $body,
            $laundry?->id,
            $pickup->id,
            [
                'pickup_id' => $pickup->id,
                'laundries_id' => $laundry?->id,
                'tracking_number' => $laundry?->tracking_number,
            ]
        );
    }

    // ========================================================================
    // LAUNDARY NOTIFICATIONS
    // ========================================================================

    /**
     * Notify staff about new laundry
     */
    public static function notifyNewLaundry(Laundry $laundry): int
    {
        $customer = $laundry->customer;
        $customerName = $customer ? $customer->name : 'Walk-in customer';

        $title = 'New Laundry Received';
        $body = "New laundry #{$laundry->tracking_number} from {$customerName}";

        if ($laundry->branch_id) {
            return self::sendToBranchStaff(
                $laundry->branch_id,
                'laundry_received',
                $title,
                $body,
                $laundry->id,
                null,
                $laundry->customer_id,
                [
                    'laundry_id' => $laundry->id,
                    'tracking_number' => $laundry->tracking_number,
                    'customer_name' => $customerName,
                ]
            );
        }

        return self::sendToAllStaff(
            'laundry_received',
            $title,
            $body,
            $laundry->id,
            null,
            $laundry->customer_id,
            [
                'laundry_id' => $laundry->id,
                'tracking_number' => $laundry->tracking_number,
                'customer_name' => $customerName,
            ]
        );
    }

    /**
     * Notify customer about laundry status change
     */
    public static function notifyLaundryStatusChanged(Laundry $laundry, string $oldStatus, string $newStatus): ?Notification
    {
        if (!$laundry->customer_id) {
            return null; // Walk-in customer, no notification
        }

        $statusMessages = [
            'processing' => [
                'title' => 'Laundry Being Processed',
                'body' => "Your laundry #{$laundry->tracking_number} is now being processed.",
            ],
            'washing' => [
                'title' => 'Washing In Progress',
                'body' => "Your laundry #{$laundry->tracking_number} is being washed.",
            ],
            'drying' => [
                'title' => 'Drying In Progress',
                'body' => "Your laundry #{$laundry->tracking_number} is being dried.",
            ],
            'folding' => [
                'title' => 'Folding In Progress',
                'body' => "Your laundry #{$laundry->tracking_number} is being folded.",
            ],
            'ready_for_pickup' => [
                'title' => 'Laundry Ready for Pickup',
                'body' => "Your laundry #{$laundry->tracking_number} is ready for pickup!",
            ],
            'ready_for_delivery' => [
                'title' => 'Laundry Ready for Delivery',
                'body' => "Your laundry #{$laundry->tracking_number} is ready and will be delivered soon.",
            ],
            'out_for_delivery' => [
                'title' => 'Out for Delivery',
                'body' => "Your laundry #{$laundry->tracking_number} is out for delivery!",
            ],
            // ── Updated: prompts customer to rate after completion ────────────
            'completed' => [
                'title' => '🎉 Laundry Completed!',
                'body' => "Your laundry #{$laundry->tracking_number} is done and ready for pickup. How was your experience? Tap to leave a rating!",
            ],
            // ─────────────────────────────────────────────────────────────────
            'cancelled' => [
                'title' => 'Laundry Cancelled',
                'body' => "Your laundry #{$laundry->tracking_number} has been cancelled.",
            ],
        ];

        if (!isset($statusMessages[$newStatus])) {
            return null;
        }

        $message = $statusMessages[$newStatus];

        return self::sendToCustomer(
            $laundry->customer_id,
            'laundry_' . $newStatus,
            $message['title'],
            $message['body'],
            $laundry->id,
            null,
            [
                'laundries_id' => $laundry->id,
                'tracking_number' => $laundry->tracking_number,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
            ]
        );
    }

    /**
     * Notify customer about payment received
     */
    public static function notifyPaymentReceived(Laundry $laundry, float $amount): ?Notification
    {
        if (!$laundry->customer_id) {
            return null;
        }

        return self::sendToCustomer(
            $laundry->customer_id,
            'payment_received',
            'Payment Received',
            "Payment of ₱" . number_format($amount, 2) . " received for laundry #{$laundry->tracking_number}.",
            $laundry->id,
            null,
            [
                'laundries_id' => $laundry->id,
                'tracking_number' => $laundry->tracking_number,
                'amount' => $amount,
            ]
        );
    }

    // ========================================================================
    // UNCLAIMED LAUNDY NOTIFICATIONS
    // ========================================================================

    /**
     * Notify staff about unclaimed laundry
     */
    public static function notifyUnclaimedLaundry(Laundry $laundry, int $daysUnclaimed): int
    {
        $customer = $laundry->customer;
        $customerName = $customer ? $customer->name : 'Unknown';

        $title = 'Unclaimed Laundry Reminder';
        $body = "Laundry #{$laundry->tracking_number} ({$customerName}) has been unclaimed for {$daysUnclaimed} days.";

        if ($laundry->branch_id) {
            return self::sendToBranchStaff(
                $laundry->branch_id,
                'unclaimed_reminder',
                $title,
                $body,
                $laundry->id,
                null,
                $laundry->customer_id,
                [
                    'laundries_id' => $laundry->id,
                    'tracking_number' => $laundry->tracking_number,
                    'days_unclaimed' => $daysUnclaimed,
                ]
            );
        }

        return self::sendToAllStaff(
            'unclaimed_reminder',
            $title,
            $body,
            $laundry->id,
            null,
            $laundry->customer_id,
            [
                'laundries_id' => $laundry->id,
                'tracking_number' => $laundry->tracking_number,
                'days_unclaimed' => $daysUnclaimed,
            ]
        );
    }

    // ========================================================================
    // RATING NOTIFICATIONS  ← NEW
    // ========================================================================

    /**
     * Notify admin + branch staff when a customer rates a completed LAUNDRY.
     *
     * Usage — call from CustomerRatingController::store() after rating is saved:
     *
     *   NotificationService::notifyLaundryRated($rating);
     */
    public static function notifyLaundryRated(CustomerRating $rating): void
    {
        $rating->loadMissing(['customer', 'branch']);

        $customerName = $rating->customer?->name ?? 'A customer';
        $branchName   = $rating->branch?->name   ?? 'the branch';
        $orderRef     = "Order #{$rating->laundry_id}";
        $stars        = str_repeat('★', $rating->rating) . str_repeat('☆', 5 - $rating->rating);
        $comment      = $rating->comment ? " \"{$rating->comment}\"" : '';

        $title   = 'New Laundry Rating';
        $message = "{$customerName} rated {$orderRef} {$stars} ({$rating->rating}/5) at {$branchName}.{$comment}";

        $iconColor = $rating->rating >= 4 ? 'success' : ($rating->rating >= 3 ? 'warning' : 'danger');

        // ── Admin: write to admin_notifications (what the admin bell reads) ──
        AdminNotification::create([
            'type'       => 'new_rating',
            'title'      => $title,
            'message'    => $message,
            'icon_class' => 'bi-star-fill',
            'color'      => $iconColor,
            'link'       => '/admin/reports/customers',
            'is_read'    => false,
        ]);

        // ── Branch staff: write to notifications (user_id based) ─────────────
        if ($rating->branch_id) {
            self::sendToBranchStaff(
                $rating->branch_id,
                'new_rating',
                $title,
                $message,
                $rating->laundry_id,
                null,
                $rating->customer_id,
                [
                    'rating_id'     => $rating->id,
                    'laundry_id'    => $rating->laundry_id,
                    'branch_id'     => $rating->branch_id,
                    'rating_value'  => $rating->rating,
                    'customer_name' => $customerName,
                ]
            );
        }
    }

    /**
     * Notify admin + branch staff when a customer rates a BRANCH directly.
     *
     * Usage — call from BranchRatingController::store() after rating is saved:
     *
     *   NotificationService::notifyBranchRated($rating);
     */
    public static function notifyBranchRated(CustomerRating $rating): void
    {
        $rating->loadMissing(['customer', 'branch']);

        $customerName = $rating->customer?->name ?? 'A customer';
        $branchName   = $rating->branch?->name   ?? 'a branch';
        $stars        = str_repeat('★', $rating->rating) . str_repeat('☆', 5 - $rating->rating);
        $comment      = $rating->comment ? " \"{$rating->comment}\"" : '';

        $title   = 'New Branch Rating';
        $message = "{$customerName} rated {$branchName} {$stars} ({$rating->rating}/5).{$comment}";

        $iconColor = $rating->rating >= 4 ? 'success' : ($rating->rating >= 3 ? 'warning' : 'danger');

        // ── Admin: write to admin_notifications (what the admin bell reads) ──
        AdminNotification::create([
            'type'       => 'new_branch_rating',
            'title'      => $title,
            'message'    => $message,
            'icon_class' => 'bi-building',
            'color'      => $iconColor,
            'link'       => '/admin/reports/customers',
            'is_read'    => false,
        ]);

        // ── Branch staff: write to notifications (user_id based) ─────────────
        if ($rating->branch_id) {
            self::sendToBranchStaff(
                $rating->branch_id,
                'new_branch_rating',
                $title,
                $message,
                null,
                null,
                $rating->customer_id,
                [
                    'rating_id'     => $rating->id,
                    'branch_id'     => $rating->branch_id,
                    'branch_name'   => $branchName,
                    'rating_value'  => $rating->rating,
                    'customer_name' => $customerName,
                ]
            );
        }
    }
}
