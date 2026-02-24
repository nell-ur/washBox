<?php

namespace App\Observers;

use App\Models\Laundry;
use App\Models\Notification;
use App\Models\AdminNotification;
use App\Models\StaffNotification;
use App\Models\User;
use Carbon\Carbon;

class LaundryObserver
{
    /**
     * Handle the Laundry "created" event.
     */
    public function created(Laundry $laundry): void
    {
        $laundry->loadMissing('customer', 'branch', 'service');

        // 🔔 NOTIFY ADMIN: New laundry received
        AdminNotification::create([
            'type' => 'new_laundry',
            'title' => 'New Laundry Received',
            'message' => "Laundry #{$laundry->tracking_number} from {$laundry->customer->name} - ₱" . number_format($laundry->total_amount, 2),
            'icon' => 'cart-plus',
            'color' => 'success',
            'link' => route('admin.laundries.show', $laundry->id),
            'data' => [
                'laundries_id' => $laundry->id,
                'tracking_number' => $laundry->tracking_number,
                'customer_name' => $laundry->customer->name,
                'total_amount' => $laundry->total_amount,
                'service' => $laundry->service?->name,
            ],
            'branch_id' => $laundry->branch_id,
            'user_id' => $laundry->created_by,
        ]);

        // 🔔 NOTIFY CUSTOMER: Laundry received
        Notification::create([
            'customer_id' => $laundry->customer_id,
            'type' => 'laudry_received',
            'title' => 'Lundry Received! 📦',
            'body' => "Your laundry laundry #{$laundry->tracking_number} has been received. We'll notify you when it's ready!",
            'laundries_id' => $laundry->id,
            'is_read' => false,
        ]);

        // 🔔 NOTIFY STAFF IN BRANCH: New laundry received
        if ($laundry->branch_id) {
            $staffUsers = User::where('branch_id', $laundry->branch_id)
                ->where('role', 'staff')
                ->where('is_active', true)
                ->get();

            foreach ($staffUsers as $staff) {
                StaffNotification::create([
                    'user_id' => $staff->id,
                    'type' => 'new_laundry',
                    'title' => 'New Laundry Received',
                    'message' => "Laundry #{$laundry->tracking_number} from {$laundry->customer->name} - ₱" . number_format($laundry->total_amount, 2),
                    'icon' => 'cart-plus',
                    'color' => 'info',
                    'link' => route('staff.laundries.show', $laundry->id),
                    'data' => [
                        'laundries_id' => $laundry->id,
                        'tracking_number' => $laundry->tracking_number,
                        'customer_name' => $laundry->customer->name,
                        'total_amount' => $laundry->total_amount,
                        'service' => $laundry->service?->name,
                    ],
                    'branch_id' => $laundry->branch_id,
                ]);
            }
        }
    }

    /**
     * Handle the Laundry "updated" event.
     */
    public function updated(Laundry $laundry): void
    {
        // Only process status changes
        if (!$laundry->isDirty('status')) {
            return;
        }

        $laundry->loadMissing('customer', 'branch', 'staff');
        $newStatus = $laundry->status;
        $oldStatus = $laundry->getOriginal('status');

        switch ($newStatus) {
            // ========================================
            // WASHING IN PROGRESS
            // ========================================
            case 'washing':
                // 🔔 NOTIFY CUSTOMER: Washing started
                Notification::create([
                    'customer_id' => $laundry->customer_id,
                    'type' => 'washing_started',
                    'title' => 'Washing Started! 🧼',
                    'body' => "Your laundry (Laundry #{$laundry->tracking_number}) is now being washed.",
                    'laundries_id' => $laundry->id,
                    'is_read' => false,
                ]);

                // 🔔 NOTIFY STAFF ASSIGNED: Laundry washing started
                if ($laundry->staff_id) {
                    StaffNotification::create([
                        'user_id' => $laundry->staff_id,
                        'type' => 'washing_started',
                        'title' => 'Laundry Washing Started',
                        'message' => "Laundry #{$laundry->tracking_number} washing in progress",
                        'icon' => 'droplet',
                        'color' => 'info',
                        'link' => route('staff.laundries.show', $laundry->id),
                        'data' => [
                            'laundries_id' => $laundry->id,
                            'tracking_number' => $laundry->tracking_number,
                            'customer_name' => $laundry->customer->name,
                        ],
                        'branch_id' => $laundry->branch_id,
                    ]);
                }
                break;

            // ========================================
            // LAUNDRY READY - Start unclaimed tracking
            // ========================================
            case 'ready':
                // 🔔 NOTIFY CUSTOMER: Laundry is ready!
                Notification::create([
                    'customer_id' => $laundry->customer_id,
                    'type' => 'laundry_ready',
                    'title' => 'Laundry Ready for Pickup! 👕',
                    'body' => "Great news! Your laundry (Laundry #{$laundry->tracking_number}) is ready. Please pick it up at {$laundry->branch->name}.",
                    'laundries_id' => $laundry->id,
                    'is_read' => false,
                ]);

                // 🔔 NOTIFY ADMIN: Laundry ready
                AdminNotification::create([
                    'type' => 'laundry_ready',
                    'title' => 'Laundry Ready for Pickup',
                    'message' => "Laundry #{$laundry->tracking_number} is ready for customer pickup",
                    'icon' => 'bag-check',
                    'color' => 'info',
                    'link' => route('admin.laundries.show', $laundry->id),
                    'branch_id' => $laundry->branch_id,
                ]);

                // 🔔 NOTIFY STAFF IN BRANCH: Laundry ready
                if ($laundry->branch_id) {
                    $staffUsers = User::where('branch_id', $laundry->branch_id)
                        ->where('role', 'staff')
                        ->where('is_active', true)
                        ->get();

                    foreach ($staffUsers as $staff) {
                        StaffNotification::create([
                            'user_id' => $staff->id,
                            'type' => 'laundry_ready',
                            'title' => 'Laundry Ready for Pickup',
                            'message' => "Laundry #{$laundry->tracking_number} is ready for customer pickup",
                            'icon' => 'bag-check',
                            'color' => 'info',
                            'link' => route('staff.laundries.show', $laundry->id),
                            'data' => [
                                'laundries_id' => $laundry->id,
                                'tracking_number' => $laundry->tracking_number,
                                'customer_name' => $laundry->customer->name,
                            ],
                            'branch_id' => $laundry->branch_id,
                        ]);
                    }
                }
                break;

            // ========================================
            // PAYMENT RECEIVED
            // ========================================
            case 'paid':
                // 🔔 NOTIFY CUSTOMER: Payment confirmed
                Notification::create([
                    'customer_id' => $laundry->customer_id,
                    'type' => 'payment_received',
                    'title' => 'Payment Confirmed! 💰',
                    'body' => "Payment of ₱" . number_format($laundry->total_amount, 2) . " received for laundry #{$laundry->tracking_number}. Thank you!",
                    'laundries_id' => $laundry->id,
                    'is_read' => false,
                ]);

                // 🔔 NOTIFY ADMIN: Payment received
                AdminNotification::create([
                    'type' => 'payment',
                    'title' => 'Payment Received',
                    'message' => "₱" . number_format($laundry->total_amount, 2) . " received for laundry #{$laundry->tracking_number}",
                    'icon' => 'currency-dollar',
                    'color' => 'success',
                    'link' => route('admin.laundries.show', $laundry->id),
                    'branch_id' => $laundry->branch_id,
                ]);

                // 🔔 NOTIFY STAFF ASSIGNED: Payment received
                if ($laundry->staff_id) {
                    StaffNotification::create([
                        'user_id' => $laundry->staff_id,
                        'type' => 'payment_received',
                        'title' => 'Payment Received',
                        'message' => "₱" . number_format($laundry->total_amount, 2) . " received for laundry #{$laundry->tracking_number}",
                        'icon' => 'currency-dollar',
                        'color' => 'success',
                        'link' => route('staff.laundries.show', $laundry->id),
                        'data' => [
                            'laundries_id' => $laundry->id,
                            'tracking_number' => $laundry->tracking_number,
                            'amount' => $laundry->total_amount,
                        ],
                        'branch_id' => $laundry->branch_id,
                    ]);
                }
                break;

            // ========================================
            // LAUNDRY COMPLETED
            // ========================================
            case 'completed':
                // 🔔 NOTIFY CUSTOMER: Laundry completed
                Notification::create([
                    'customer_id' => $laundry->customer_id,
                    'type' => 'laundry_completed',
                    'title' => 'Laundry Completed! ✅',
                    'body' => "Thank you for choosing WashBox! Your laundry #{$laundry->tracking_number} is complete. See you again!",
                    'laundries_id' => $laundry->id,
                    'is_read' => false,
                ]);

                // 🔔 NOTIFY ADMIN: Laundry completed
                AdminNotification::create([
                    'type' => 'laundry_completed',
                    'title' => 'Laundry Completed',
                    'message' => "Laundry #{$laundry->tracking_number} completed by customer",
                    'icon' => 'check-circle',
                    'color' => 'success',
                    'link' => route('admin.laundries.show', $laundry->id),
                    'branch_id' => $laundry->branch_id,
                ]);

                // 🔔 NOTIFY STAFF ASSIGNED: Laundry completed
                if ($laundry->staff_id) {
                    StaffNotification::create([
                        'user_id' => $laundry->staff_id,
                        'type' => 'laundry_completed',
                        'title' => 'Laundry Completed',
                        'message' => "Laundry #{$laundry->tracking_number} completed by customer",
                        'icon' => 'check-circle',
                        'color' => 'success',
                        'link' => route('staff.laundries.show', $laundry->id),
                        'data' => [
                            'laundries_id' => $laundry->id,
                            'tracking_number' => $laundry->tracking_number,
                            'customer_name' => $laundry->customer->name,
                        ],
                        'branch_id' => $laundry->branch_id,
                    ]);
                }
                break;

            // ========================================
            // LAUNDRY CANCELLED
            // ========================================
            case 'cancelled':
                $reason = $laundry->cancellation_reason ?? 'No reason provided';

                // 🔔 NOTIFY CUSTOMER: Laundry cancelled
                Notification::create([
                    'customer_id' => $laundry->customer_id,
                    'type' => 'laundry_cancelled',
                    'title' => 'Laundry Cancelled ❌',
                    'body' => "Your laundry #{$laundry->tracking_number} has been cancelled. Reason: {$reason}",
                    'laundries_id' => $laundry->id,
                    'is_read' => false,
                ]);

                // 🔔 NOTIFY ADMIN: Laundry cancelled
                AdminNotification::create([
                    'type' => 'laundry_cancelled',
                    'title' => 'Laundry Cancelled',
                    'message' => "Laundry #{$laundry->tracking_number} cancelled. Reason: {$reason}",
                    'icon' => 'x-circle',
                    'color' => 'danger',
                    'link' => route('admin.laundries.show', $laundry->id),
                    'branch_id' => $laundry->branch_id,
                ]);

                // 🔔 NOTIFY STAFF ASSIGNED: Laundry cancelled
                if ($laundry->staff_id) {
                    StaffNotification::create([
                        'user_id' => $laundry->staff_id,
                        'type' => 'laundry_cancelled',
                        'title' => 'Laundry Cancelled',
                        'message' => "Laundry #{$laundry->tracking_number} cancelled. Reason: {$reason}",
                        'icon' => 'x-circle',
                        'color' => 'danger',
                        'link' => route('staff.laundries.show', $laundry->id),
                        'data' => [
                            'laundries_id' => $laundry->id,
                            'tracking_number' => $laundry->tracking_number,
                            'reason' => $reason,
                        ],
                        'branch_id' => $laundry->branch_id,
                    ]);
                }
                break;

            // ========================================
            // PICKUP ASSIGNED
            // ========================================
            case 'pickup_assigned':
                // 🔔 NOTIFY STAFF ASSIGNED: Pickup assigned
                if ($laundry->staff_id) {
                    StaffNotification::create([
                        'user_id' => $laundry->staff_id,
                        'type' => 'pickup_assigned',
                        'title' => 'Pickup Assigned',
                        'message' => "You have been assigned a pickup for Laundry #{$laundry->tracking_number}",
                        'icon' => 'truck',
                        'color' => 'primary',
                        'link' => route('staff.laundries.show', $laundry->id),
                        'data' => [
                            'laundries_id' => $laundry->id,
                            'tracking_number' => $laundry->tracking_number,
                            'customer_name' => $laundry->customer->name,
                            'address' => $laundry->pickup_address,
                        ],
                        'branch_id' => $laundry->branch_id,
                    ]);
                }
                break;
        }

        // ========================================
        // UNCLAIMED LAUNDRY ALERTS
        // ========================================
                $this->checkUnclaimedLaundry($laundry);
    }

    /**
     * Check for unclaimed laundries and notify staff
     */
    private function checkUnclaimedLaundry(Laundry $laundry): void
    {
        // Only check for laundries that are ready but not claimed
        if ($laundry->status !== 'ready' || $laundry->claimed_at) {
            return;
        }

        $laundry->loadMissing('customer', 'branch');

        // Calculate days unclaimed
        $readyDate = $laundry->updated_at ?? $laundry->created_at;
        $daysUnclaimed = Carbon::now()->diffInDays($readyDate);

        // Send alerts based on days unclaimed
        if ($daysUnclaimed >= 1 && $daysUnclaimed < 3) {
            // 🔔 NOTIFY ALL STAFF IN BRANCH: Laundry unclaimed for 1 day
            $staffUsers = User::where('branch_id', $laundry->branch_id)
                ->where('role', 'staff')
                ->where('is_active', true)
                ->get();

            foreach ($staffUsers as $staff) {
                StaffNotification::create([
                    'user_id' => $staff->id,
                    'type' => 'unclaimed_laundry',
                    'title' => 'Laundry Unclaimed (1 Day)',
                    'message' => "Laundry #{$laundry->tracking_number} has been ready for 1 day",
                    'icon' => 'clock',
                    'color' => 'warning',
                    'link' => route('staff.laundries.show', $laundry->id),
                    'data' => [
                        'laundry_id' => $laundry->id,
                        'tracking_number' => $laundry->tracking_number,
                        'customer_name' => $laundry->customer->name,
                        'days_unclaimed' => 1,
                    ],
                    'branch_id' => $laundry->branch_id,
                ]);
            }
        } elseif ($daysUnclaimed >= 3) {
            // 🔔 NOTIFY ALL STAFF IN BRANCH: Urgent unclaimed laundry
            $staffUsers = User::where('branch_id', $laundry->branch_id)
                ->where('role', 'staff')
                ->where('is_active', true)
                ->get();

            foreach ($staffUsers as $staff) {
                StaffNotification::create([
                    'user_id' => $staff->id,
                    'type' => 'urgent_unclaimed',
                    'title' => 'URGENT: Laundry Unclaimed (3+ Days)',
                    'message' => "Laundry #{$laundry->tracking_number} has been unclaimed for {$daysUnclaimed} days!",
                    'icon' => 'exclamation-triangle',
                    'color' => 'danger',
                    'link' => route('staff.laundries.show', $laundry->id),
                    'data' => [
                        'laundry_id' => $laundry->id,
                        'tracking_number' => $laundry->tracking_number,
                        'customer_name' => $laundry->customer->name,
                        'days_unclaimed' => $daysUnclaimed,
                    ],
                    'branch_id' => $laundry->branch_id,
                ]);
            }

            // 🔔 ALSO NOTIFY ADMIN
            AdminNotification::create([
                'type' => 'unclaimed_urgent',
                'title' => 'URGENT: Unclaimed Laundry',
                'message' => "Laundry #{$laundry->tracking_number} unclaimed for {$daysUnclaimed} days",
                'icon' => 'exclamation-triangle',
                'color' => 'danger',
                'link' => route('admin.laundries.show', $laundry->id),
                'data' => [
                    'laundry_id' => $laundry->id,
                    'tracking_number' => $laundry->tracking_number,
                    'customer_name' => $laundry->customer->name,
                    'days_unclaimed' => $daysUnclaimed,
                ],
                'branch_id' => $laundry->branch_id,
            ]);
        }
    }

    /**
     * Handle the Laundry "assigned" event (when staff is assigned)
     */
    public function assigned(Laundry $laundry, $staffId): void
    {
        $laundry->loadMissing('customer', 'branch');

        // 🔔 NOTIFY STAFF: Laundry assigned to them
        StaffNotification::create([
            'user_id' => $staffId,
            'type' => 'laundry_assigned',
            'title' => 'Laundry Assigned to You',
            'message' => "Laundry #{$laundry->tracking_number} has been assigned to you",
            'icon' => 'person-badge',
            'color' => 'primary',
            'link' => route('staff.laundries.show', $laundry->id),
            'data' => [
                'laundry_id' => $laundry->id,
                'tracking_number' => $laundry->tracking_number,
                'customer_name' => $laundry->customer->name,
            ],
            'branch_id' => $laundry->branch_id,
        ]);

        // 🔔 NOTIFY CUSTOMER: Staff assigned
        Notification::create([
            'customer_id' => $laundry->customer_id,
            'type' => 'staff_assigned',
            'title' => 'Staff Assigned! 👨‍💼',
            'body' => "A staff member has been assigned to handle your laundry #{$laundry->tracking_number}",
            'laundry_id' => $laundry->id,
            'is_read' => false,
        ]);
    }

    /**
     * Handle the Laundry "staff changed" event
     */
    public function staffChanged(Laundry $laundry): void
    {
        $laundry->loadMissing('customer', 'branch');

        // 🔔 NOTIFY NEW STAFF: Laundry assigned
        if ($laundry->staff_id) {
            StaffNotification::create([
                'user_id' => $laundry->staff_id,
                'type' => 'laundry_assigned',
                'title' => 'Laundry Assigned to You',
                'message' => "Laundry #{$laundry->tracking_number} has been assigned to you",
                'icon' => 'person-badge',
                'color' => 'primary',
                'link' => route('staff.laundries.show', $laundry->id),
                'data' => [
                    'laundry_id' => $laundry->id,
                    'tracking_number' => $laundry->tracking_number,
                    'customer_name' => $laundry->customer->name,
                ],
                'branch_id' => $laundry->branch_id,
            ]);
        }
    }
}
