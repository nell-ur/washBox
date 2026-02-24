<?php

namespace App\Http\Controllers\Staff;

use App\Models\Laundry;
use App\Models\Notification;
use App\Models\AdminNotification;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Services\NotificationService;

class UnclaimedController extends Controller
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Display unclaimed laundry for staff's branch
     */
    public function index(Request $request)
    {
        $staff = Auth::user();

        if (!$staff || !$staff->branch_id) {
            return redirect()->route('staff.dashboard')
                ->with('error', 'Your account is not assigned to a branch.');
        }

        // Get the current branch
        $currentBranch = $staff->branch; // This loads the branch relationship

        $query = Laundry::with(['customer', 'service'])
            ->where('branch_id', $staff->branch_id)
            ->where('status', 'ready')
            ->whereNotNull('ready_at')
            ->orderBy('ready_at', 'asc'); // Oldest first

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

        // Filter by days range
        if ($request->filled('min_days')) {
            $query->where('ready_at', '<=', now()->subDays((int) $request->min_days));
        }

        if ($request->filled('max_days')) {
            $query->where('ready_at', '>=', now()->subDays((int) $request->max_days));
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

        $laundries = $query->paginate(15)->withQueryString();

        // Calculate stats for this branch
        $stats = $this->getBranchStats($staff->branch_id);

        // Pass both laundries, stats, and currentBranch to the view
        return view('staff.unclaimed.index', compact('laundries', 'stats', 'currentBranch'));
    }

    /**
     * Show single unclaimed laundry details
     */
    public function show($id)
    {
        $staff = Auth::user();

        if (!$staff || !$staff->branch_id) {
            return redirect()->route('staff.dashboard')
                ->with('error', 'Your account is not assigned to a branch.');
        }

        $laundry = Laundry::with(['customer', 'service', 'branch', 'statusHistories.changedBy'])
            ->where('branch_id', $staff->branch_id)
            ->where('status', 'ready')
            ->findOrFail($id);

        // Get reminder history for this laundry
        $reminderHistory = Notification::where('laundries_id', $laundry->id) // Fixed: changed from unclaimed_laundries_id
            ->where('type', 'unclaimed_reminder')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('staff.unclaimed.show', compact('laundry', 'reminderHistory'));
    }

    /**
     * Send reminder to customer
     */
    public function sendReminder(Request $request, $id)
    {
        $staff = Auth::user();

        if (!$staff || !$staff->branch_id) {
            return back()->with('error', 'Your account is not assigned to a branch.');
        }

        $laundry = Laundry::with(['customer', 'branch'])
            ->where('branch_id', $staff->branch_id)
            ->where('status', 'ready')
            ->findOrFail($id);

        // Determine urgency based on days
        $days = $this->getDaysUnclaimed($laundry);
        $urgency = $this->getUrgencyLevel($days);

        // Create DB notification record
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

        // Record reminder sent
        if (method_exists($laundry, 'recordReminderSent')) {
            $laundry->recordReminderSent();
        } else {
            $laundry->update([
                'last_reminder_at' => now(),
                'reminder_count' => ($laundry->reminder_count ?? 0) + 1,
            ]);
        }

        // FCM: push notification to customer (non-blocking)
        try {
            if ($laundry->customer && $laundry->customer->fcm_token) {
                $fcmMessages = [
                    'first'  => [
                        'title' => '📦 Reminder: Laundry Ready',
                        'body' => 'Your laundry at ' . ($laundry->branch->name ?? 'WashBox') . ' is ready for pickup. Tracking #: ' . $laundry->tracking_number
                    ],
                    'second' => [
                        'title' => '⏰ Friendly Reminder',
                        'body' => 'Day ' . $days . ': Your laundry is still waiting at ' . ($laundry->branch->name ?? 'WashBox') . '. Please pick it up soon.'
                    ],
                    'urgent' => [
                        'title' => '⚠️ Urgent: Unclaimed Laundry',
                        'body' => 'Day ' . $days . ': Your laundry at ' . ($laundry->branch->name ?? 'WashBox') . ' needs to be claimed. Storage fees may apply.'
                    ],
                    'final'  => [
                        'title' => '🚨 Final Notice',
                        'body' => 'Day ' . $days . ': FINAL REMINDER — Your laundry at ' . ($laundry->branch->name ?? 'WashBox') . ' will be disposed if not claimed soon.'
                    ],
                ];

                $msg = $fcmMessages[$urgency] ?? $fcmMessages['first'];

                // Use static method with correct parameter order
                NotificationService::sendToCustomer(
                    $laundry->customer->id,                    // 1st: customerId (int)
                    'unclaimed_reminder',                       // 2nd: type (string)
                    $msg['title'],                              // 3rd: title (string)
                    $msg['body'],                               // 4th: body (string)
                    $laundry->id,                               // 5th: laundryId (int)
                    null,                                        // 6th: pickupRequestId (null)
                    [                                            // 7th: data (array)
                        'laundries_id' => (string) $laundry->id,
                        'status' => 'ready',
                        'type' => 'unclaimed_reminder',
                        'days' => (string) $days,
                    ]
                );
            }
        } catch (\Exception $fcmEx) {
            Log::warning('FCM unclaimed reminder failed for laundry #' . $laundry->id . ': ' . $fcmEx->getMessage());
        }

        // Log activity
        $laundry->statusHistories()->create([
            'status' => 'ready',
            'changed_by' => $staff->id,
            'notes' => "Unclaimed reminder sent (Day {$days}, {$urgency})",
        ]);

        return back()->with('success', "Reminder sent to {$laundry->customer->name}!");
    }

    /**
     * Send bulk reminders
     */
    public function sendBulkReminders(Request $request)
    {
        $staff = Auth::user();

        if (!$staff || !$staff->branch_id) {
            return back()->with('error', 'Your account is not assigned to a branch.');
        }

        $request->validate([
            'laundry_ids' => 'required|array',
            'laundry_ids.*' => 'exists:laundries,id',
        ]);

        $count = 0;
        $laundries = Laundry::with(['customer', 'branch'])
            ->where('branch_id', $staff->branch_id)
            ->where('status', 'ready')
            ->whereIn('id', $request->laundry_ids)
            ->get();

        $fcmMessages = [
            'first'  => [
                'title' => '📦 Reminder: Laundry Ready',
                'body' => 'Your laundry at {branch} is ready for pickup. Tracking #: {tracking}'
            ],
            'second' => [
                'title' => '⏰ Friendly Reminder',
                'body' => 'Day {days}: Your laundry is still waiting at {branch}. Please pick it up soon.'
            ],
            'urgent' => [
                'title' => '⚠️ Urgent: Unclaimed Laundry',
                'body' => 'Day {days}: Your laundry at {branch} needs to be claimed. Storage fees may apply.'
            ],
            'final'  => [
                'title' => '🚨 Final Notice',
                'body' => 'Day {days}: FINAL REMINDER — your laundry at {branch} will be disposed if not claimed soon.'
            ],
        ];

        foreach ($laundries as $laundry) {
            $days = $this->getDaysUnclaimed($laundry);
            $urgency = $this->getUrgencyLevel($days);

            // DB record
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

            // Record reminder sent
            if (method_exists($laundry, 'recordReminderSent')) {
                $laundry->recordReminderSent();
            } else {
                $laundry->update([
                    'last_reminder_at' => now(),
                    'reminder_count' => ($laundry->reminder_count ?? 0) + 1,
                ]);
            }

            $count++;

            // FCM push (non-blocking per item)
            try {
                if ($laundry->customer && $laundry->customer->fcm_token) {
                    $template = $fcmMessages[$urgency] ?? $fcmMessages['first'];
                    $branchName = $laundry->branch->name ?? 'WashBox';

                    $body = str_replace(
                        ['{days}', '{branch}', '{tracking}'],
                        [$days, $branchName, $laundry->tracking_number],
                        $template['body']
                    );

                    // Use static method with correct parameter order
                    NotificationService::sendToCustomer(
                        $laundry->customer->id,
                        'unclaimed_reminder',
                        $template['title'],
                        $body,
                        $laundry->id,
                        null,
                        [
                            'laundries_id' => (string) $laundry->id,
                            'status' => 'ready',
                            'type' => 'unclaimed_reminder',
                            'days' => (string) $days,
                        ]
                    );
                }
            } catch (\Exception $fcmEx) {
                Log::warning('FCM bulk reminder failed for laundry #' . $laundry->id . ': ' . $fcmEx->getMessage());
            }
        }

        return back()->with('success', "Sent {$count} reminder(s) successfully!");
    }

    /**
     * Mark laundry as claimed/paid (quick action)
     */
    public function markClaimed(Request $request, $id)
    {
        $staff = Auth::user();

        if (!$staff || !$staff->branch_id) {
            return back()->with('error', 'Your account is not assigned to a branch.');
        }

        $laundry = Laundry::where('branch_id', $staff->branch_id)
            ->where('status', 'ready')
            ->findOrFail($id);

        $daysUnclaimed = $this->getDaysUnclaimed($laundry);

        // Update to paid status
        if (method_exists($laundry, 'updateStatus')) {
            $laundry->updateStatus('paid', $staff, 'Payment recorded - unclaimed laundry claimed after ' . $daysUnclaimed . ' days');
        } else {
            $laundry->update([
                'status' => 'paid',
                'payment_status' => 'paid',
                'paid_at' => now(),
            ]);
        }

        // Notify admin
        AdminNotification::create([
            'type' => 'unclaimed_recovered',
            'title' => 'Unclaimed Laundry Recovered! 💰',
            'message' => "Laundry #{$laundry->tracking_number} claimed after {$daysUnclaimed} days - ₱" . number_format($laundry->total_amount, 2),
            'icon' => 'currency-dollar',
            'color' => 'success',
            'link' => route('admin.laundries.show', $laundry->id),
            'branch_id' => $laundry->branch_id,
        ]);

        return redirect()->route('staff.laundries.show', $laundry)
            ->with('success', 'Laundry marked as claimed! Proceed to complete.');
    }

    /**
     * Call customer (log the attempt)
     */
    public function logCallAttempt(Request $request, $id)
    {
        $staff = Auth::user();

        if (!$staff || !$staff->branch_id) {
            return response()->json(['error' => 'Not authorized'], 403);
        }

        $laundry = Laundry::where('branch_id', $staff->branch_id)
            ->where('status', 'ready')
            ->findOrFail($id);

        $request->validate([
            'result' => 'required|in:answered,no_answer,busy,wrong_number,voicemail',
            'notes' => 'nullable|string|max:500',
        ]);

        // Log the call attempt
        $laundry->statusHistories()->create([
            'status' => 'ready',
            'changed_by' => $staff->id,
            'notes' => "Call attempt: {$request->result}" . ($request->notes ? " - {$request->notes}" : ""),
        ]);

        // Update last reminder if answered
        if ($request->result === 'answered') {
            $laundry->update(['last_reminder_at' => now()]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Call logged successfully',
        ]);
    }

    /**
     * Get branch statistics
     */
    private function getBranchStats(int $branchId): array
    {
        $baseQuery = Laundry::where('branch_id', $branchId)
            ->where('status', 'ready')
            ->whereNotNull('ready_at');

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

        // Total value at risk
        $totalValue = (clone $baseQuery)->sum('total_amount');

        // Critical value (14+ days)
        $criticalValue = (clone $baseQuery)->where('ready_at', '<=', now()->subDays(14))->sum('total_amount');

        // Potential storage fees
        $storageFees = 0;
        $feePerDay = config('unclaimed.storage_fee_per_day', 10);
        $laundriesWithFees = (clone $baseQuery)->where('ready_at', '<=', now()->subDays(7))->get();
        foreach ($laundriesWithFees as $laundry) {
            $daysUnclaimed = $this->getDaysUnclaimed($laundry);
            $extraDays = max(0, $daysUnclaimed - 7);
            $storageFees += $extraDays * $feePerDay;
        }

        // Reminders sent today
        $remindersSentToday = Notification::where('type', 'unclaimed_reminder')
            ->where('laundries_id', 'in', function($query) use ($branchId) {
                $query->select('id')->from('laundries')->where('branch_id', $branchId);
            })
            ->whereDate('created_at', today())
            ->count();

        return [
            'total' => $total,
            'critical' => $critical,
            'urgent' => $urgent,
            'warning' => $warning,
            'pending' => $pending,
            'total_value' => $totalValue,
            'critical_value' => $criticalValue,
            'storage_fees' => $storageFees,
            'reminders_today' => $remindersSentToday,
        ];
    }

    /**
     * Get statistics (AJAX)
     */
    public function stats()
    {
        $staff = Auth::user();

        if (!$staff || !$staff->branch_id) {
            return response()->json(['error' => 'Not authorized'], 403);
        }

        return response()->json($this->getBranchStats($staff->branch_id));
    }

    /**
     * Export unclaimed list
     */
    public function export(Request $request)
    {
        $staff = Auth::user();

        if (!$staff || !$staff->branch_id) {
            return back()->with('error', 'Your account is not assigned to a branch.');
        }

        $laundries = Laundry::with(['customer', 'service'])
            ->where('branch_id', $staff->branch_id)
            ->where('status', 'ready')
            ->whereNotNull('ready_at')
            ->orderBy('ready_at', 'asc')
            ->get();

        $filename = 'unclaimed_laundry_' . date('Y-m-d') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($laundries) {
            $file = fopen('php://output', 'w');

            // Header row
            fputcsv($file, [
                'Tracking #',
                'Customer Name',
                'Phone',
                'Service',
                'Total Amount',
                'Ready Date',
                'Days Unclaimed',
                'Urgency',
                'Reminders Sent',
                'Last Reminder',
            ]);

            foreach ($laundries as $laundry) {
                $daysUnclaimed = $this->getDaysUnclaimed($laundry);
                $urgency = $this->getUrgencyLevel($daysUnclaimed);

                fputcsv($file, [
                    $laundry->tracking_number,
                    $laundry->customer->name ?? 'N/A',
                    $laundry->customer->phone ?? 'N/A',
                    $laundry->service->name ?? 'N/A',
                    number_format($laundry->total_amount, 2),
                    $laundry->ready_at->format('Y-m-d'),
                    $daysUnclaimed,
                    ucfirst($urgency),
                    $laundry->reminder_count ?? 0,
                    $laundry->last_reminder_at?->format('Y-m-d H:i') ?? 'Never',
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
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
