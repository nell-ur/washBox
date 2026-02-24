<?php

namespace App\Http\Controllers\Admin;

use App\Models\Laundry;
use App\Models\Branch;
use App\Models\Service;
use App\Models\Customer;
use App\Models\Promotion;
use App\Models\PickupRequest;
use App\Models\SystemSetting;
use App\Models\UnclaimedLaundry;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class DashboardController extends Controller
{
    // Cache duration in minutes
    protected $cacheDuration = 5;

    public function index()
    {
        // Use caching for dashboard data to improve performance
        $stats = Cache::remember('dashboard_stats_' . Auth::id(), $this->cacheDuration * 60, function () {
            return $this->getDashboardStats();
        });

        return view('admin.dashboard', compact('stats'));
    }

    private function getDashboardStats()
    {
        // Get dates for calculations
        $today = today();
        $yesterday = today()->subDay();
        $currentMonth = now()->startOfMonth();

        // 1. Laundry Stats & Trends
        $laundriesData = $this->getLaundryStats($today, $yesterday);
        $todayLaundries = $laundriesData['today'];
        $yesterdayLaundries = $laundriesData['yesterday'];
        $laundriesChange = $this->calculatePercentageChange($yesterdayLaundries, $todayLaundries);

        // 2. Revenue Stats
        $revenueData = $this->getRevenueStats($today, $yesterday);
        $todayRevenue = $revenueData['today'];
        $yesterdayRevenue = $revenueData['yesterday'];
        $revenueChange = $this->calculatePercentageChange($yesterdayRevenue, $todayRevenue);
        $thisMonthRevenue = $revenueData['month'];

        // 3. Laundry Pipeline Status
        $laundryPipeline = $this->getLaundryPipeline();

        // 4. Customer Management
        $customerData = $this->getCustomerStats($currentMonth);
        $activeCustomers = $customerData['active'];
        $newCustomersThisMonth = $customerData['new_this_month'];
        $customerRegistrationSource = $customerData['sources'];

        // 5. Unclaimed Laundry
        $unclaimedData = $this->getUnclaimedStats();
        $unclaimedLaundry = $unclaimedData['total'];
        $unclaimedBreakdown = $unclaimedData['breakdown'];
        $estimatedUnclaimedLoss = $unclaimedLaundry * 500;

        // 6. Pickup Requests
        $pickupStats = $this->getPickupStats();

        // 7. Notification Metrics
        $notificationStats = $this->getNotificationMetrics();

        // 8. Payment Collection
        $paymentCollection = $this->getPaymentCollection();

        // 9. Branch Performance
        $branchPerformance = $this->getBranchPerformance($currentMonth);

        // 10. Data Quality Metrics
        $dataQuality = $this->getDataQualityMetrics();

        // 11. 7-Day Revenue Data
        $last7DaysRevenue = $this->getLast7DaysRevenue();

        // 12. System Health Check
        $systemPulse = $this->getSystemHealth();

        return [
            // KPI Metrics
            'todayLaundries'      => $todayLaundries,
            'laundriesChange'     => $laundriesChange,
            'todayRevenue'     => $todayRevenue,
            'revenueChange'    => $revenueChange,
            'thisMonthRevenue' => $thisMonthRevenue,
            'activeCustomers'  => $activeCustomers,
            'newCustomersThisMonth' => $newCustomersThisMonth,
            'unclaimedLaundry' => $unclaimedLaundry,
            'estimatedUnclaimedLoss' => $estimatedUnclaimedLoss,
            'avgProcessingTime' => $this->calculateAverageProcessingTime(),

            // Laundry Management
            'laundryPipeline' => $laundryPipeline,
            'totalLaundries' => array_sum($laundryPipeline),

            // Unclaimed & Pickups
            'unclaimedBreakdown' => $unclaimedBreakdown,
            'pickupStats' => $pickupStats,
            'pendingPickups' => $this->getPendingPickups(), // Important for map

            // Customer Management
            'customerRegistrationSource' => $customerRegistrationSource,

            // Notifications
            'notificationStats' => $notificationStats,

            // Revenue & Payment
            'paymentCollection' => $paymentCollection,
            'revenueByService' => $this->getRevenueByService(),

            // Branch Performance
            'branchPerformance' => $branchPerformance,

            // Data Quality
            'dataQuality' => $dataQuality,

            // Charts Data
            'last7DaysRevenue' => $last7DaysRevenue['data'],
            'revenueLabels'    => $last7DaysRevenue['labels'],

            // Utilities
            'activePromotions' => Promotion::where('status', 'active')
                ->orderBy('created_at', 'desc')
                ->take(3)
                ->get(),
            'fcm_ready'        => \Illuminate\Support\Facades\File::exists(storage_path('app/firebase/service-account.json')),
            'system_pulse'     => $systemPulse,

            // Branch locations for map markers
            'branches' => Branch::select('id', 'name', 'address', 'phone', 'latitude', 'longitude')
                ->whereNotNull('latitude')
                ->whereNotNull('longitude')
                ->get()
                ->map(function ($branch) {
                    return [
                        'id'        => $branch->id,
                        'name'      => $branch->name,
                        'address'   => $branch->address,
                        'phone'     => $branch->phone,
                        'latitude'  => (float) $branch->latitude,
                        'longitude' => (float) $branch->longitude,
                    ];
                })
                ->values()
                ->toArray(),
        ];
    }

    private function getLaundryStats($today, $yesterday)
    {
        $laundries = Laundry::selectRaw("
            SUM(CASE WHEN DATE(created_at) = ? THEN 1 ELSE 0 END) as today_count,
            SUM(CASE WHEN DATE(created_at) = ? THEN 1 ELSE 0 END) as yesterday_count
        ", [$today->toDateString(), $yesterday->toDateString()])
        ->first();

        return [
            'today' => $laundries->today_count ?? 0,
            'yesterday' => $laundries->yesterday_count ?? 0
        ];
    }

    private function getRevenueStats($today, $yesterday)
    {
        $revenue = Laundry::where('status', '!=', 'cancelled')
            ->selectRaw("
                SUM(CASE WHEN DATE(created_at) = ? THEN total_amount ELSE 0 END) as today_revenue,
                SUM(CASE WHEN DATE(created_at) = ? THEN total_amount ELSE 0 END) as yesterday_revenue,
                SUM(CASE WHEN MONTH(created_at) = MONTH(CURDATE())
                         AND YEAR(created_at) = YEAR(CURDATE())
                         THEN total_amount ELSE 0 END) as month_revenue
            ", [$today->toDateString(), $yesterday->toDateString()])
            ->first();

        return [
            'today' => $revenue->today_revenue ?? 0,
            'yesterday' => $revenue->yesterday_revenue ?? 0,
            'month' => $revenue->month_revenue ?? 0
        ];
    }

    private function getLaundryPipeline()
    {
        return Laundry::select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();
    }

    private function getCustomerStats($currentMonth)
    {
        $activeCustomers = Customer::where('is_active', true)->count();

        $newCustomersThisMonth = Customer::where('created_at', '>=', $currentMonth)
            ->count();

        $rawSources = Customer::select('registration_type', DB::raw('count(*) as count'))
            ->groupBy('registration_type')
            ->pluck('count', 'registration_type')
            ->toArray();

        // Normalize DB values to consistent keys.
        // DB may store: 'mobile_app', 'Mobile App', 'app', 'walk_in', 'Walk-in', etc.
        $normalized = ['walk_in' => 0, 'app' => 0, 'referral' => 0, 'other' => 0];

        foreach ($rawSources as $type => $count) {
            $key = strtolower(trim((string) $type));
            $key = str_replace([' ', '-'], '_', $key); // "Mobile App" -> "mobile_app"

            if (in_array($key, ['app', 'mobile_app', 'mobile', 'online', 'self_registered'])) {
                $normalized['app'] += $count;
            } elseif (in_array($key, ['walk_in', 'walkin', 'counter'])) {
                $normalized['walk_in'] += $count;
            } elseif (in_array($key, ['referral', 'referred'])) {
                $normalized['referral'] += $count;
            } else {
                $normalized['other'] += $count;
            }
        }

        return [
            'active'         => $activeCustomers,
            'new_this_month' => $newCustomersThisMonth,
            'sources'        => $normalized,
        ];
    }

    private function getUnclaimedStats()
    {
        $unclaimed = UnclaimedLaundry::whereNull('recovered_at')
            ->whereNull('disposed_at');

        $breakdown = [
            'within_7_days' => (clone $unclaimed)->where('days_unclaimed', '<=', 7)->count(),
            '1_to_2_weeks' => (clone $unclaimed)->whereBetween('days_unclaimed', [8, 14])->count(),
            '2_to_4_weeks' => (clone $unclaimed)->whereBetween('days_unclaimed', [15, 28])->count(),
            'over_1_month' => (clone $unclaimed)->where('days_unclaimed', '>', 28)->count(),
        ];

        return [
            'total' => array_sum($breakdown),
            'breakdown' => $breakdown
        ];
    }

    private function getPickupStats()
    {
        return PickupRequest::select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();
    }

    private function getPendingPickups()
    {
        return PickupRequest::with(['customer', 'branch'])
            ->whereIn('status', ['pending', 'accepted', 'en_route'])
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->limit(20)
            ->get()
            ->map(function($pickup) {
                return [
                    'id' => $pickup->id,
                    'customer' => [
                        'name' => $pickup->customer->name ?? 'Unknown',
                        'phone' => $pickup->customer->phone ?? null
                    ],
                    'branch' => [
                        'name' => $pickup->branch->name ?? 'Main Branch',
                        'address' => $pickup->branch->address ?? null
                    ],
                    'pickup_address' => $pickup->pickup_address,
                    'latitude' => $pickup->latitude,
                    'longitude' => $pickup->longitude,
                    'status' => $pickup->status,
                    'preferred_date' => $pickup->preferred_date,
                    'preferred_time' => $pickup->preferred_time,
                    'notes' => $pickup->notes
                ];
            });
    }

    private function getNotificationMetrics()
    {
        return [
            'total_sent' => 0,
            'delivery_success' => 0,
            'delivery_failed' => 0,
            'engagement_rate' => $this->calculateEngagementRate(),
        ];
    }

    private function getPaymentCollection()
    {
        return [
            'paid_cash' => Laundry::where('status', 'paid')->count(),
            'pending_payment' => Laundry::where('status', 'ready')->count(),
            'disputes' => UnclaimedLaundry::where('status', 'disputed')->count(),
        ];
    }

    private function getBranchPerformance($currentMonth)
    {
        $branches = Branch::withCount([
            'laundries as monthly_laundries' => function ($query) use ($currentMonth) {
                $query->where('created_at', '>=', $currentMonth)
                      ->where('status', '!=', 'cancelled');
            },
            'laundries as monthly_revenue' => function ($query) use ($currentMonth) {
                $query->where('created_at', '>=', $currentMonth)
                      ->where('status', '!=', 'cancelled')
                      ->select(DB::raw('SUM(total_amount)'));
            }
        ])->get();

        $totalMonthlyRevenue = $branches->sum('monthly_revenue');

        return $branches->map(function ($branch) use ($totalMonthlyRevenue) {
            $percentage = $totalMonthlyRevenue > 0
                ? round(($branch->monthly_revenue / $totalMonthlyRevenue) * 100, 1)
                : 0;

            return (object) [
                'id' => $branch->id,
                'name' => $branch->name,
                'total_revenue' => $branch->monthly_revenue ?? 0,
                'total_laundries' => $branch->monthly_laundries ?? 0,
                'avg_laundry_value' => ($branch->monthly_laundries > 0)
                    ? round(($branch->monthly_revenue / $branch->monthly_laundries), 2)
                    : 0,
                'percentage' => $percentage
            ];
        })->sortByDesc('total_revenue')->values();
    }

    private function getDataQualityMetrics()
    {
        $totalRecords = Laundry::count();

        if ($totalRecords == 0) {
            return [
                'data_entry_errors' => 0,
                'billing_disputes' => 0,
                'info_accuracy' => 100
            ];
        }

        $accurateRecords = Laundry::whereNotNull('customer_id')
            ->whereNotNull('branch_id')
            ->where('total_amount', '>', 0)
            ->count();

        return [
            'data_entry_errors' => $totalRecords - $accurateRecords,
            'billing_disputes' => UnclaimedLaundry::where('status', 'disputed')->count(),
            'info_accuracy' => round(($accurateRecords / $totalRecords) * 100, 1)
        ];
    }

    private function getLast7DaysRevenue()
    {
        $data = [];
        $labels = [];

        for ($i = 6; $i >= 0; $i--) {
            $date = today()->subDays($i);
            $revenue = Laundry::whereDate('created_at', $date)
                ->where('status', '!=', 'cancelled')
                ->sum('total_amount');
            $data[] = (float)$revenue;
            $labels[] = $date->format('M d');
        }

        return ['data' => $data, 'labels' => $labels];
    }

    private function getSystemHealth()
    {
        $dbStatus = false;
        try {
            DB::connection()->getPdo();
            $dbStatus = true;
        } catch (\Exception $e) {
            $dbStatus = false;
        }

        return [
            'db_connected' => $dbStatus,
            'last_check' => now()->format('h:i:s A')
        ];
    }

    private function calculatePercentageChange($old, $new)
    {
        if ($old == 0) return $new > 0 ? 100 : 0;
        return round((($new - $old) / abs($old)) * 100, 1);
    }

    private function calculateAverageProcessingTime()
    {
        $avgDays = Laundry::whereNotNull('completed_at')
            ->whereNotNull('created_at')
            ->selectRaw('AVG(DATEDIFF(completed_at, created_at)) as avg_days')
            ->first()->avg_days ?? 0;

        return round($avgDays, 1) . ' days';
    }

    private function calculateEngagementRate()
    {
        return 0;
    }

    private function getRevenueByService()
    {
        return Service::where('is_active', true)
            ->withSum(['laundries' => function ($query) {
                $query->where('status', '!=', 'cancelled');
            }], 'total_amount')
            ->get()
            ->map(function ($service) {
                return [
                    'service' => $service->name,
                    'revenue' => $service->laundries_sum_total_amount ?? 0,
                ];
            })
            ->toArray();
    }

    /**
     * Get dashboard stats via API
     */
    public function getStats()
    {
        $stats = $this->getDashboardStats();
        return response()->json($stats);
    }

    /**
     * Clear dashboard cache manually
     */
    public function clearCache()
    {
        Cache::forget('dashboard_stats_' . Auth::id());
        return response()->json(['message' => 'Dashboard cache cleared successfully']);
    }
}
