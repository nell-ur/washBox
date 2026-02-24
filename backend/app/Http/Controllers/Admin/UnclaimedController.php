<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminNotification;
use App\Models\Branch;
use App\Models\Laundry;
use App\Models\Notification;
use App\Models\SystemSetting;
use App\Models\UnclaimedLaundry;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UnclaimedController extends Controller
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Display all unclaimed laundry across all branches
     */
    public function index(Request $request)
    {
        $disposalThreshold = SystemSetting::get('disposal_threshold_days', 30);

        // Build query - can use either UnclaimedLaundry or Laundry model
        // Using Laundry model directly for real-time accuracy
        $query = Laundry::with(['customer', 'service', 'branch', 'staff'])
            ->where('status', 'ready')
            ->whereNotNull('ready_at')
            ->orderBy('ready_at', 'asc'); // Oldest first (most critical)

        // Filter by branch
        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        // Filter by urgency level
        if ($request->filled('urgency')) {
            switch ($request->urgency) {
                case 'critical':
                    $query->where('ready_at', '<=', now()->subDays(14));
                    break;
                case 'urgent':
                    $query->where('ready_at', '<=', now()->subDays(7))
                          ->where('ready_at', '>', now()->subDays(14));
                    break;
                case 'warning':
                    $query->where('ready_at', '<=', now()->subDays(3))
                          ->where('ready_at', '>', now()->subDays(7));
                    break;
                case 'pending':
                    $query->where('ready_at', '<=', now()->subDays(1))
                          ->where('ready_at', '>', now()->subDays(3));
                    break;
            }
        }

        // Filter by minimum days
        if ($request->filled('min_days')) {
            $query->where('ready_at', '<=', now()->subDays((int) $request->min_days));
        }

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('tracking_number', 'like', "%{$search}%")
                  ->orWhereHas('customer', function ($q2) use ($search) {
                      $q2->where('name', 'like', "%{$search}%")
                         ->orWhere('phone', 'like', "%{$search}%");
                  });
            });
        }

        $laundries = $query->paginate(20)->withQueryString();

        // Also get UnclaimedLaundry records for backward compatibility
        $unclaimedLaundry = UnclaimedLaundry::with(['customer', 'branch', 'laundry'])
            ->where('status', 'unclaimed')
            ->orderBy('days_unclaimed', 'desc')
            ->paginate(10);

        // Get all stats
        $stats = $this->getGlobalStats();

        // Get branch stats for comparison
        $branchStats = $this->getBranchComparison();

        // Get branches for filter
        $branches = Branch::where('is_active', true)->orderBy('name')->get();

        return view('admin.unclaimed.index', compact(
            'laundries',
            'unclaimedLaundry',
            'stats',
            'branchStats',
            'branches',
            'disposalThreshold'
        ));
    }

    /**
     * Show single unclaimed laundry details
     */
    public function show($id)
    {
        $laundry = Laundry::with([
            'customer',
            'service',
            'branch',
            'staff',
            'statusHistories.changedBy',
        ])->where('status', 'ready')->findOrFail($id);

        // Get reminder history
        $reminderHistory = Notification::where('laundries_id', $laundry->id)
            ->whereIn('type', ['unclaimed_reminder', 'unclaimed_day1', 'unclaimed_day3', 'unclaimed_day7', 'unclaimed_day14'])
            ->orderBy('created_at', 'desc')
            ->get();

        return view('admin.unclaimed.show', compact('laundry', 'reminderHistory'));
    }

    /**
     * Send reminder to customer
     */
    public function sendReminder(Request $request, $id)
    {
        // Try to find Laundry first
        $laundry = Laundry::with(['customer', 'branch'])->find($id);

        // If not found, try UnclaimedLaundry
        if (!$laundry) {
            $unclaimed = UnclaimedLaundry::with(['laundry.customer', 'laundry.branch', 'customer'])->find($id);
            if ($unclaimed && $unclaimed->laundry) {
                $laundry = $unclaimed->laundry;
            }
        }

        if (!$laundry) {
            return back()->with('error', 'Laundry not found.');
        }

        // Determine urgency based on days
        $days = $this->getDaysUnclaimed($laundry);
        $urgency = $this->getUrgencyLevel($days);

        // Create and send notification using helper method
        if (method_exists(Notification::class, 'createUnclaimedReminder')) {
            Notification::createUnclaimedReminder($laundry, $days, $urgency);
        } else {
            // Fallback: create notification directly
            Notification::create([
                'customer_id' => $laundry->customer_id,
                'type' => 'unclaimed_reminder',
                'title' => $this->getReminderTitle($urgency),
                'body' => $this->getReminderBody($laundry, $days, $urgency),
                'laundries_id' => $laundry->id,
                'data' => json_encode([
                    'days' => $days,
                    'urgency' => $urgency
                ])
            ]);
        }

        // Record reminder sent if method exists
        if (method_exists($laundry, 'recordReminderSent')) {
            $laundry->recordReminderSent();
        } else {
            $laundry->update([
                'last_reminder_at' => now(),
                'reminder_count' => ($laundry->reminder_count ?? 0) + 1,
            ]);
        }

        // Log activity
        $laundry->statusHistories()->create([
            'status' => 'ready',
            'changed_by' => Auth::id(),
            'notes' => "Unclaimed reminder sent by admin (Day {$days}, {$urgency})",
        ]);

        // FCM push to customer (non-blocking) — reuse existing helper methods
        try {
            if ($laundry->customer && $laundry->customer->fcm_token) {
                // Use the static method correctly with proper parameter order
                NotificationService::sendToCustomer(
                    $laundry->customer->id,                    // 1st: customerId (int)
                    'unclaimed_reminder',                       // 2nd: type (string)
                    $this->getReminderTitle($urgency),          // 3rd: title (string)
                    $this->getReminderBody($laundry, $days, $urgency), // 4th: body (string)
                    $laundry->id,                               // 5th: laundryId (int)
                    null,                                        // 6th: pickupRequestId (null)
                    [                                            // 7th: data (array)
                        'laundries_id' => (string) $laundry->id,
                        'status' => 'ready',
                        'type' => 'unclaimed_reminder',
                        'days' => (string) $days
                    ]
                );
            }
        } catch (\Exception $e) {
            Log::error('Failed to send push notification: ' . $e->getMessage());
        }

        $customerName = $laundry->customer->name ?? 'Customer';
        return back()->with('success', "Reminder sent to {$customerName}!");
    }

    /**
     * Send reminders to all unclaimed laundries
     */
    public function remindAll(Request $request)
    {
        $query = Laundry::with(['customer', 'branch'])
            ->where('status', 'ready')
            ->whereNotNull('ready_at')
            ->where('ready_at', '<=', now()->subDays(3)); // At least 3 days old

        // Optional: filter by branch
        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        // Only send to laundries not reminded in last 24 hours
        $query->where(function ($q) {
            $q->whereNull('last_reminder_at')
              ->orWhere('last_reminder_at', '<=', now()->subHours(24));
        });

        $laundries = $query->get();
        $count = 0;

        foreach ($laundries as $laundry) {
            $days = $this->getDaysUnclaimed($laundry);
            $urgency = $this->getUrgencyLevel($days);

            // Create notification
            if (method_exists(Notification::class, 'createUnclaimedReminder')) {
                Notification::createUnclaimedReminder($laundry, $days, $urgency);
            } else {
                Notification::create([
                    'customer_id' => $laundry->customer_id,
                    'type' => 'unclaimed_reminder',
                    'title' => $this->getReminderTitle($urgency),
                    'body' => $this->getReminderBody($laundry, $days, $urgency),
                    'laundries_id' => $laundry->id,
                    'data' => json_encode([
                        'days' => $days,
                        'urgency' => $urgency
                    ])
                ]);
            }

            // Record reminder
            if (method_exists($laundry, 'recordReminderSent')) {
                $laundry->recordReminderSent();
            } else {
                $laundry->update([
                    'last_reminder_at' => now(),
                    'reminder_count' => ($laundry->reminder_count ?? 0) + 1,
                ]);
            }

            // FCM push (non-blocking per item)
            try {
                if ($laundry->customer && $laundry->customer->fcm_token) {
                    NotificationService::sendToCustomer(
                        $laundry->customer->id,
                        'unclaimed_reminder',
                        $this->getReminderTitle($urgency),
                        $this->getReminderBody($laundry, $days, $urgency),
                        $laundry->id,
                        null,
                        [
                            'laundries_id' => (string) $laundry->id,
                            'status' => 'ready',
                            'type' => 'unclaimed_reminder',
                            'days' => (string) $days
                        ]
                    );
                }
            } catch (\Exception $fcmEx) {
                Log::warning('FCM remindAll failed for laundry #' . $laundry->id . ': ' . $fcmEx->getMessage());
            }

            $count++;
        }

        if ($count > 0) {
            return back()->with('success', "Sent {$count} reminder(s) to customers with unclaimed laundry!");
        }

        return back()->with('warning', 'No laundries needed reminders (all were reminded within 24 hours).');
    }

    /**
     * Mark laundry as claimed/paid
     */
    public function markClaimed(Request $request, $id)
    {
        // Try Laundry first
        $laundry = Laundry::with(['customer', 'branch'])->find($id);
        $unclaimedRecord = null;

        // Try UnclaimedLaundry if Laundry not found
        if (!$laundry) {
            $unclaimedRecord = UnclaimedLaundry::with(['laundry.customer', 'laundry.branch'])->find($id);
            if ($unclaimedRecord && $unclaimedRecord->laundry) {
                $laundry = $unclaimedRecord->laundry;
            }
        } else {
            // Check if there's an associated UnclaimedLaundry record
            $unclaimedRecord = UnclaimedLaundry::where('laundries_id', $laundry->id)->first();
        }

        if (!$laundry) {
            return back()->with('error', 'Laundry not found.');
        }

        $daysUnclaimed = $this->getDaysUnclaimed($laundry);

        DB::transaction(function () use ($laundry, $unclaimedRecord, $daysUnclaimed) {
            // Update UnclaimedLaundry record if exists
            if ($unclaimedRecord) {
                $unclaimedRecord->update([
                    'status' => 'recovered',
                    'recovered_at' => now(),
                    'recovered_by' => Auth::id(),
                ]);
            }

            // Update laundry status
            if (method_exists($laundry, 'updateStatus')) {
                $laundry->updateStatus('paid', Auth::user(), 'Payment recorded - unclaimed laundry claimed after ' . $daysUnclaimed . ' days');
            } else {
                $laundry->update([
                    'status' => 'paid',
                    'payment_status' => 'paid',
                    'paid_at' => now(),
                ]);
            }

            // Clear unclaimed flag if exists
            if (isset($laundry->is_unclaimed)) {
                $laundry->update(['is_unclaimed' => false]);
            }
        });

        // Create success notification for admin
        AdminNotification::create([
            'type' => 'unclaimed_recovered',
            'title' => '💰 Revenue Recovered!',
            'message' => "Laundry #{$laundry->tracking_number} claimed after {$daysUnclaimed} days - ₱" . number_format($laundry->total_amount, 2),
            'icon' => 'currency-dollar',
            'color' => 'success',
            'link' => route('admin.laundries.show', $laundry->id),
            'branch_id' => $laundry->branch_id,
        ]);

        return redirect()->route('admin.laundries.show', $laundry)
            ->with('success', "Laundry marked as claimed! Revenue of ₱" . number_format($laundry->total_amount, 2) . " recovered.");
    }

    /**
     * Mark laundry as disposed
     */
    public function markDisposed(Request $request, $id)
    {
        // Try Laundry first
        $laundry = Laundry::with(['customer', 'branch'])->find($id);
        $unclaimedRecord = null;

        if (!$laundry) {
            $unclaimedRecord = UnclaimedLaundry::with(['laundry.customer', 'laundry.branch'])->find($id);
            if ($unclaimedRecord && $unclaimedRecord->laundry) {
                $laundry = $unclaimedRecord->laundry;
            }
        } else {
            $unclaimedRecord = UnclaimedLaundry::where('laundries_id', $laundry->id)->first();
        }

        if (!$laundry) {
            return back()->with('error', 'Laundry not found.');
        }

        // Check if eligible for disposal
        $disposalThreshold = SystemSetting::get('disposal_threshold_days', 30);
        $daysUnclaimed = $this->getDaysUnclaimed($laundry);

        if ($daysUnclaimed < $disposalThreshold) {
            return back()->with('error', "Laundry must be unclaimed for at least {$disposalThreshold} days before disposal.");
        }

        DB::transaction(function () use ($laundry, $unclaimedRecord, $daysUnclaimed) {
            // Create or update UnclaimedLaundry record
            if (!$unclaimedRecord) {
                $unclaimedRecord = UnclaimedLaundry::create([
                    'laundries_id' => $laundry->id,
                    'customer_id' => $laundry->customer_id,
                    'branch_id' => $laundry->branch_id,
                    'days_unclaimed' => $daysUnclaimed,
                    'status' => 'disposed',
                    'disposed_at' => now(),
                    'disposed_by' => Auth::id(),
                    'notes' => "Disposed after {$daysUnclaimed} days - exceeded storage policy",
                ]);
            } else {
                $unclaimedRecord->update([
                    'status' => 'disposed',
                    'disposed_at' => now(),
                    'disposed_by' => Auth::id(),
                    'days_unclaimed' => $daysUnclaimed,
                    'notes' => ($unclaimedRecord->notes ?? '') . " | Disposed after {$daysUnclaimed} days",
                ]);
            }

            // Update laundry status
            if (method_exists($laundry, 'updateStatus')) {
                $laundry->updateStatus('cancelled', Auth::user(), 'Disposed - exceeded storage policy after ' . $daysUnclaimed . ' days');
            } else {
                $laundry->update([
                    'status' => 'cancelled',
                    'payment_status' => 'unpaid',
                    'cancelled_at' => now(),
                ]);
            }

            $laundry->update([
                'cancellation_reason' => "Disposed after {$daysUnclaimed} days unclaimed",
            ]);
        });

        // DB notification record
        Notification::create([
            'customer_id' => $laundry->customer_id,
            'type' => 'laundry_disposed',
            'title' => 'Laundry Disposed',
            'body' => "Your laundry #{$laundry->tracking_number} has been disposed per our {$daysUnclaimed}-day policy. Please contact us for questions.",
            'laundries_id' => $laundry->id,
            'data' => json_encode([
                'days_unclaimed' => $daysUnclaimed,
                'disposed_at' => now()->toDateTimeString()
            ])
        ]);

        // FCM push — disposal is critical; customer must know (non-blocking)
        try {
            if ($laundry->customer && $laundry->customer->fcm_token) {
                NotificationService::sendToCustomer(
                    $laundry->customer->id,
                    'laundry_disposed',
                    '🗑️ Laundry Disposed',
                    "Your laundry #{$laundry->tracking_number} has been disposed after {$daysUnclaimed} days per our storage policy. Please contact us for any questions.",
                    $laundry->id,
                    null,
                    [
                        'laundries_id' => (string) $laundry->id,
                        'status' => 'cancelled',
                        'type' => 'laundry_disposed',
                        'days_unclaimed' => (string) $daysUnclaimed
                    ]
                );
            }
        } catch (\Exception $fcmEx) {
            Log::warning('FCM markDisposed failed for laundry #' . $laundry->id . ': ' . $fcmEx->getMessage());
        }

        // Admin notification
        AdminNotification::create([
            'type' => 'laundry_disposed',
            'title' => 'Laundry Disposed',
            'message' => "Laundry #{$laundry->tracking_number} disposed after {$daysUnclaimed} days - ₱" . number_format($laundry->total_amount, 2) . " lost",
            'icon' => 'trash',
            'color' => 'secondary',
            'link' => route('admin.unclaimed.history'),
            'branch_id' => $laundry->branch_id,
        ]);

        return back()->with('warning', "Laundry #{$laundry->tracking_number} has been marked as disposed. ₱" . number_format($laundry->total_amount, 2) . " recorded as loss.");
    }

    /**
     * View disposal history
     */
    public function disposalHistory(Request $request)
    {
        $query = UnclaimedLaundry::with(['laundry.customer', 'branch', 'disposedBy', 'customer'])
            ->where('status', 'disposed')
            ->orderBy('disposed_at', 'desc');

        // Filter by branch
        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        // Filter by date range
        if ($request->filled('from_date')) {
            $query->whereDate('disposed_at', '>=', $request->from_date);
        }

        if ($request->filled('to_date')) {
            $query->whereDate('disposed_at', '<=', $request->to_date);
        }

        $history = $query->paginate(15)->withQueryString();

        // Calculate totals
        $allDisposed = UnclaimedLaundry::where('status', 'disposed')->with('laundry')->get();

        $totalLoss = $allDisposed->sum(function($item) {
            return $item->laundry->total_amount ?? 0;
        });

        $totalDisposed = $allDisposed->count();

        // Loss by branch
        $lossByBranch = $allDisposed->groupBy('branch_id')->map(function ($items) {
            return [
                'count' => $items->count(),
                'value' => $items->sum(fn($item) => $item->laundry->total_amount ?? 0),
            ];
        });

        // This month's loss
        $thisMonthLoss = UnclaimedLaundry::where('status', 'disposed')
            ->whereMonth('disposed_at', now()->month)
            ->whereYear('disposed_at', now()->year)
            ->with('laundry')
            ->get()
            ->sum(fn($item) => $item->laundry->total_amount ?? 0);

        $branches = Branch::where('is_active', true)->orderBy('name')->get();

        return view('admin.unclaimed.history', compact(
            'history',
            'totalLoss',
            'totalDisposed',
            'lossByBranch',
            'thisMonthLoss',
            'branches'
        ));
    }

    /**
     * Send bulk reminders
     */
    public function sendBulkReminders(Request $request)
    {
        $request->validate([
            'laundries_ids' => 'required|array',
            'laundries_ids.*' => 'exists:laundries,id',
        ]);

        $count = 0;
        $laundries = Laundry::with(['customer', 'branch'])
            ->where('status', 'ready')
            ->whereIn('id', $request->laundries_ids)
            ->get();

        foreach ($laundries as $laundry) {
            $days = $this->getDaysUnclaimed($laundry);
            $urgency = $this->getUrgencyLevel($days);

            if (method_exists(Notification::class, 'createUnclaimedReminder')) {
                Notification::createUnclaimedReminder($laundry, $days, $urgency);
            } else {
                Notification::create([
                    'customer_id' => $laundry->customer_id,
                    'type' => 'unclaimed_reminder',
                    'title' => $this->getReminderTitle($urgency),
                    'body' => $this->getReminderBody($laundry, $days, $urgency),
                    'laundries_id' => $laundry->id,
                    'data' => json_encode([
                        'days' => $days,
                        'urgency' => $urgency
                    ])
                ]);
            }

            if (method_exists($laundry, 'recordReminderSent')) {
                $laundry->recordReminderSent();
            } else {
                $laundry->update([
                    'last_reminder_at' => now(),
                    'reminder_count' => ($laundry->reminder_count ?? 0) + 1,
                ]);
            }

            // FCM push (non-blocking per item)
            try {
                if ($laundry->customer && $laundry->customer->fcm_token) {
                    NotificationService::sendToCustomer(
                        $laundry->customer->id,
                        'unclaimed_reminder',
                        $this->getReminderTitle($urgency),
                        $this->getReminderBody($laundry, $days, $urgency),
                        $laundry->id,
                        null,
                        [
                            'laundries_id' => (string) $laundry->id,
                            'status' => 'ready',
                            'type' => 'unclaimed_reminder',
                            'days' => (string) $days
                        ]
                    );
                }
            } catch (\Exception $fcmEx) {
                Log::warning('FCM bulk reminder failed for laundry #' . $laundry->id . ': ' . $fcmEx->getMessage());
            }

            $count++;
        }

        return back()->with('success', "Sent {$count} reminder(s) successfully!");
    }

    /**
     * Export unclaimed list
     */
    public function export(Request $request)
    {
        $query = Laundry::with(['customer', 'service', 'branch'])
            ->where('status', 'ready')
            ->whereNotNull('ready_at')
            ->orderBy('ready_at', 'asc');

        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        $laundries = $query->get();

        $filename = 'unclaimed_laundry_' . date('Y-m-d_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($laundries) {
            $file = fopen('php://output', 'w');

            // Header row
            fputcsv($file, [
                'Tracking #',
                'Branch',
                'Customer Name',
                'Phone',
                'Email',
                'Service',
                'Weight (kg)',
                'Total Amount',
                'Ready Date',
                'Days Unclaimed',
                'Urgency',
                'Storage Fee',
                'Total with Fees',
                'Reminders Sent',
                'Last Reminder',
            ]);

            foreach ($laundries as $laundry) {
                $storageFee = $laundry->calculated_storage_fee ?? 0;
                $daysUnclaimed = $this->getDaysUnclaimed($laundry);
                $urgency = $this->getUrgencyLevel($daysUnclaimed);

                fputcsv($file, [
                    $laundry->tracking_number,
                    $laundry->branch->name ?? 'N/A',
                    $laundry->customer->name ?? 'N/A',
                    $laundry->customer->phone ?? 'N/A',
                    $laundry->customer->email ?? 'N/A',
                    $laundry->service->name ?? 'N/A',
                    $laundry->weight ?? 0,
                    number_format($laundry->total_amount, 2),
                    $laundry->ready_at?->format('Y-m-d') ?? 'N/A',
                    $daysUnclaimed,
                    ucfirst($urgency),
                    number_format($storageFee, 2),
                    number_format($laundry->total_amount + $storageFee, 2),
                    $laundry->reminder_count ?? 0,
                    $laundry->last_reminder_at?->format('Y-m-d H:i') ?? 'Never',
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Get statistics (AJAX)
     */
    public function stats(Request $request)
    {
        if ($request->filled('branch_id')) {
            $branchId = $request->branch_id;
            $baseQuery = Laundry::where('branch_id', $branchId)
                ->where('status', 'ready')
                ->whereNotNull('ready_at');

            return response()->json([
                'total' => (clone $baseQuery)->count(),
                'critical' => (clone $baseQuery)->where('ready_at', '<=', now()->subDays(14))->count(),
                'urgent' => (clone $baseQuery)->where('ready_at', '<=', now()->subDays(7))
                                              ->where('ready_at', '>', now()->subDays(14))->count(),
                'warning' => (clone $baseQuery)->where('ready_at', '<=', now()->subDays(3))
                                               ->where('ready_at', '>', now()->subDays(7))->count(),
                'total_value' => (clone $baseQuery)->sum('total_amount'),
            ]);
        }

        return response()->json($this->getGlobalStats());
    }

    /**
     * Get global statistics
     */
    private function getGlobalStats(): array
    {
        $baseQuery = Laundry::where('status', 'ready')->whereNotNull('ready_at');

        // Total unclaimed
        $total = (clone $baseQuery)->count();

        // By urgency
        $critical = (clone $baseQuery)->where('ready_at', '<=', now()->subDays(14))->count();
        $urgent = (clone $baseQuery)->where('ready_at', '<=', now()->subDays(7))
                                    ->where('ready_at', '>', now()->subDays(14))->count();
        $warning = (clone $baseQuery)->where('ready_at', '<=', now()->subDays(3))
                                     ->where('ready_at', '>', now()->subDays(7))->count();
        $pending = (clone $baseQuery)->where('ready_at', '<=', now()->subDays(1))
                                     ->where('ready_at', '>', now()->subDays(3))->count();

        // Values
        $totalValue = (clone $baseQuery)->sum('total_amount');
        $criticalValue = (clone $baseQuery)->where('ready_at', '<=', now()->subDays(14))->sum('total_amount');
        $urgentValue = (clone $baseQuery)->where('ready_at', '<=', now()->subDays(7))->sum('total_amount');

        // Storage fees calculation
        $storageFees = 0;
        $feePerDay = config('unclaimed.storage_fee_per_day', 10);
        $laundriesWithFees = (clone $baseQuery)->where('ready_at', '<=', now()->subDays(7))->get();
        foreach ($laundriesWithFees as $laundry) {
            $daysUnclaimed = $this->getDaysUnclaimed($laundry);
            $extraDays = max(0, $daysUnclaimed - 7);
            $storageFees += $extraDays * $feePerDay;
        }

        // Reminders sent today
        $remindersSentToday = Notification::whereIn('type', ['unclaimed_reminder', 'unclaimed_day1', 'unclaimed_day3', 'unclaimed_day7', 'unclaimed_day14'])
            ->whereDate('created_at', today())
            ->count();

        // Recovery this month (laundries that were unclaimed but got paid)
        $recoveredThisMonth = UnclaimedLaundry::where('status', 'recovered')
            ->whereMonth('recovered_at', now()->month)
            ->whereYear('recovered_at', now()->year)
            ->with('laundry')
            ->get()
            ->sum(fn($item) => $item->laundry->total_amount ?? 0);

        // Disposed this month
        $disposedThisMonth = UnclaimedLaundry::where('status', 'disposed')
            ->whereMonth('disposed_at', now()->month)
            ->count();

        // Loss this month
        $lossThisMonth = UnclaimedLaundry::where('status', 'disposed')
            ->whereMonth('disposed_at', now()->month)
            ->with('laundry')
            ->get()
            ->sum(fn($item) => $item->laundry->total_amount ?? 0);

        return [
            'total' => $total,
            'critical' => $critical,
            'urgent' => $urgent,
            'warning' => $warning,
            'pending' => $pending,
            'total_value' => $totalValue,
            'critical_value' => $criticalValue,
            'urgent_value' => $urgentValue,
            'storage_fees' => $storageFees,
            'potential_total' => $totalValue + $storageFees,
            'reminders_today' => $remindersSentToday,
            'recovered_this_month' => $recoveredThisMonth,
            'disposed_this_month' => $disposedThisMonth,
            'loss_this_month' => $lossThisMonth,
        ];
    }

    /**
     * Get branch comparison stats
     */
    private function getBranchComparison(): array
    {
        $branches = Branch::where('is_active', true)->get();
        $branchStats = [];

        foreach ($branches as $branch) {
            $baseQuery = Laundry::where('branch_id', $branch->id)
                ->where('status', 'ready')
                ->whereNotNull('ready_at');

            $total = (clone $baseQuery)->count();
            $critical = (clone $baseQuery)->where('ready_at', '<=', now()->subDays(14))->count();
            $value = (clone $baseQuery)->sum('total_amount');

            $branchStats[] = [
                'id' => $branch->id,
                'name' => $branch->name,
                'total' => $total,
                'critical' => $critical,
                'value' => $value,
            ];
        }

        // Sort by total descending
        usort($branchStats, fn($a, $b) => $b['total'] - $a['total']);

        return $branchStats;
    }

    /**
     * Helper: Get days unclaimed
     */
    private function getDaysUnclaimed($laundry): int
    {
        if (isset($laundry->days_unclaimed) && $laundry->days_unclaimed > 0) {
            return $laundry->days_unclaimed;
        }

        if ($laundry->ready_at) {
            return (int) now()->diffInDays($laundry->ready_at);
        }

        return 0;
    }

    /**
     * Helper: Get urgency level based on days
     */
    private function getUrgencyLevel(int $days): string
    {
        return match(true) {
            $days >= 14 => 'final',
            $days >= 7 => 'urgent',
            $days >= 3 => 'second',
            default => 'first',
        };
    }

    /**
     * Helper: Get reminder title based on urgency
     */
    private function getReminderTitle(string $urgency): string
    {
        return match($urgency) {
            'first' => 'Friendly Reminder 🧺',
            'second' => 'Your Laundry is Waiting 👕',
            'urgent' => '⚠️ Urgent: Laundry Unclaimed',
            'final' => '🚨 Final Notice: Action Required',
            default => 'Unclaimed Laundry Reminder',
        };
    }

    /**
     * Helper: Get reminder body based on urgency
     */
    private function getReminderBody(Laundry $laundry, int $days, string $urgency): string
    {
        $branchName = $laundry->branch->name ?? 'our branch';
        $trackingNumber = $laundry->tracking_number;

        return match($urgency) {
            'first' => "Hi! Your laundry (#{$trackingNumber}) is ready at {$branchName}. Please pick it up at your convenience.",
            'second' => "Your laundry has been ready for {$days} days. Please pick up laundry #{$trackingNumber} at {$branchName}.",
            'urgent' => "URGENT: Laundry #{$trackingNumber} has been unclaimed for {$days} days. Storage fees may apply after 7 days.",
            'final' => "FINAL NOTICE: Laundry #{$trackingNumber} unclaimed for {$days} days. Per policy, items may be disposed after 30 days. Please contact us immediately.",
            default => "Your laundry #{$trackingNumber} is ready for pickup at {$branchName}.",
        };
    }
}
