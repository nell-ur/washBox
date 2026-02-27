<?php

namespace App\Http\Controllers\Admin;

use App\Models\Laundry;
use App\Models\Branch;
use App\Models\AddOn;
use App\Models\Service;
use App\Models\Customer;
use App\Models\CustomerRating;
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
        $branchPipeline  = $this->getLaundryPipelineByBranch();

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
            'laundryPipeline'  => $laundryPipeline,
            'branchPipeline'   => $branchPipeline,
            'totalLaundries'   => array_sum($laundryPipeline),

            // Unclaimed & Pickups
            'unclaimedBreakdown'    => $unclaimedBreakdown,
            'pickupStats'           => $pickupStats,
            'pendingPickups'        => $this->getPendingPickups(), // Important for map
            'pickupBranchPipeline'  => $this->getPickupPipelineByBranch(),

            // Customer Management
            'customerRegistrationSource' => $customerRegistrationSource,
            'customerBranchPipeline'    => $this->getCustomerPipelineByBranch(),
            'topCustomers'              => $this->getTopCustomers(),
            'topRatedCustomers'         => $this->getTopRatedCustomers(),

            // Notifications
            'notificationStats' => $notificationStats,

            // Revenue & Payment
            'paymentCollection'  => $paymentCollection,
            'revenueByService'   => $this->getRevenueByService(),
            'serviceChartData'   => $this->getServiceChartData(),

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

    private function getCustomerPipelineByBranch()
    {
        // Customers have preferred_branch_id directly on the customers table.
        // Registration types are: 'walk_in' and 'self_registered'.
        $raw = Customer::select(
                'preferred_branch_id',
                'registration_type',
                DB::raw('COUNT(*) as count')
            )
            ->whereNotNull('preferred_branch_id')
            ->groupBy('preferred_branch_id', 'registration_type')
            ->get()
            ->groupBy('preferred_branch_id');

        $branches = Branch::select('id', 'name')->orderBy('name')->get();

        return $branches->map(function ($branch) use ($raw) {
            $rows     = $raw->get($branch->id, collect());
            $walkIn   = 0;
            $mobile   = 0;

            foreach ($rows as $row) {
                $type = strtolower(trim((string) $row->registration_type));
                $cnt  = (int) $row->count;

                if ($type === 'self_registered') {
                    $mobile += $cnt;   // self-registered = mobile app customer
                } else {
                    $walkIn += $cnt;   // walk_in (default)
                }
            }

            $total = $walkIn + $mobile;
            if ($total === 0) return null;

            return [
                'id'      => $branch->id,
                'name'    => $branch->name,
                'walk_in' => $walkIn,
                'mobile'  => $mobile,
                'total'   => $total,
            ];
        })->filter()->values()->toArray();
    }

    private function getTopCustomers(int $limit = 5)
    {
        return Customer::withSum('laundries', 'total_amount')
            ->withCount('laundries')
            ->orderByDesc('laundries_sum_total_amount')
            ->limit($limit)
            ->get()
            ->map(fn($c) => [
                'id'             => $c->id,
                'name'           => $c->name,
                'laundries_count'=> (int) $c->laundries_count,
                'total_spent'    => (float) ($c->laundries_sum_total_amount ?? 0),
            ])->toArray();
    }

    private function getTopRatedCustomers(int $limit = 5)
    {
        // Use a subquery to get rating stats first, then join to customers
        // to avoid ONLY_FULL_GROUP_BY issues in strict MySQL mode.
        $ratingStats = CustomerRating::select('customer_id')
            ->selectRaw('ROUND(AVG(rating), 1) as avg_rating')
            ->selectRaw('COUNT(id) as ratings_count')
            ->groupBy('customer_id')
            ->having('ratings_count', '>=', 1)
            ->orderByDesc('avg_rating')
            ->orderByDesc('ratings_count')
            ->limit($limit)
            ->get();

        $customerIds = $ratingStats->pluck('customer_id');

        $customers = Customer::whereIn('id', $customerIds)->get()->keyBy('id');

        return $ratingStats->map(function ($stat) use ($customers) {
            $customer = $customers->get($stat->customer_id);
            if (!$customer) return null;

            return [
                'id'            => $customer->id,
                'name'          => $customer->name,
                'avg_rating'    => (float) $stat->avg_rating,
                'ratings_count' => (int)   $stat->ratings_count,
            ];
        })->filter()->values()->toArray();
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

    private function getPickupPipelineByBranch()
    {
        $statuses = ['pending', 'accepted', 'en_route', 'picked_up', 'cancelled'];

        // Single grouped query — no N+1
        $raw = PickupRequest::select('branch_id', 'status', DB::raw('count(*) as count'))
            ->whereIn('status', $statuses)
            ->groupBy('branch_id', 'status')
            ->get()
            ->groupBy('branch_id');

        $branches = Branch::select('id', 'name')->orderBy('name')->get();

        return $branches->map(function ($branch) use ($raw, $statuses) {
            $rows = $raw->get($branch->id, collect());

            $counts = collect($statuses)->mapWithKeys(function ($s) use ($rows) {
                $row = $rows->firstWhere('status', $s);
                return [$s => $row ? (int) $row->count : 0];
            })->toArray();

            return [
                'id'       => $branch->id,
                'name'     => $branch->name,
                'statuses' => $counts,
                'total'    => array_sum($counts),
                'active'   => ($counts['pending'] ?? 0) + ($counts['accepted'] ?? 0) + ($counts['en_route'] ?? 0),
            ];
        })->values()->toArray();
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

    private function getLaundryPipelineByBranch()
    {
        $statuses = ['received', 'processing', 'ready', 'completed', 'cancelled'];

        $raw = Laundry::select('branch_id', 'status', DB::raw('count(*) as count'))
            ->whereIn('status', $statuses)
            ->groupBy('branch_id', 'status')
            ->get()
            ->groupBy('branch_id');

        $branches = Branch::select('id', 'name')->orderBy('name')->get();

        return $branches->map(function ($branch) use ($raw, $statuses) {
            $rows = $raw->get($branch->id, collect());

            $counts = collect($statuses)->mapWithKeys(function ($s) use ($rows) {
                $row = $rows->firstWhere('status', $s);
                return [$s => $row ? (int) $row->count : 0];
            })->toArray();

            $total = array_sum($counts);
            if ($total === 0) return null;

            return [
                'id'       => $branch->id,
                'name'     => $branch->name,
                'statuses' => $counts,
                'total'    => $total,
            ];
        })->filter()->values()->toArray();
    }

    private function getServiceChartData()
    {
        // Services table covers drop_off and self_service categories
        $services = Service::where('is_active', true)
            ->withCount(['laundries as laundry_count'  => fn($q) => $q->where('status', '!=', 'cancelled')])
            ->withSum(['laundries as laundry_revenue'  => fn($q) => $q->where('status', '!=', 'cancelled')], 'total_amount')
            ->orderBy('name')
            ->get();

        // Add-ons are a separate model/table (add_ons) — query them independently
        // withSum can't reach pivot columns, so use a raw subquery for revenue
        $addons = AddOn::where('is_active', true)
            ->withCount('laundries as laundry_count')
            ->selectRaw('add_ons.*, (
                SELECT COALESCE(SUM(la.price_at_purchase * la.quantity), 0)
                FROM laundry_addon la
                WHERE la.add_on_id = add_ons.id
            ) as laundry_revenue')
            ->orderBy('name')
            ->get();

        $grandCount   = (int)   $services->sum('laundry_count') + (int) $addons->sum('laundry_count');
        $grandRevenue = (float) $services->sum('laundry_revenue') + (float) $addons->sum('laundry_revenue');

        $buckets = [
            'drop_off'     => ['labels' => [], 'counts' => [], 'revenues' => [], 'total' => 0],
            'self_service' => ['labels' => [], 'counts' => [], 'revenues' => [], 'total' => 0],
            'addon'        => ['labels' => [], 'counts' => [], 'revenues' => [], 'total' => 0],
        ];

        $allServices = [];

        // Bucket services by category
        foreach ($services as $svc) {
            $cat   = strtolower(trim((string) ($svc->category ?? 'drop_off')));
            if (!isset($buckets[$cat])) $cat = 'drop_off';

            $count = (int)   ($svc->laundry_count   ?? 0);
            $rev   = (float) ($svc->laundry_revenue  ?? 0);

            $buckets[$cat]['labels'][]   = $svc->name;
            $buckets[$cat]['counts'][]   = $count;
            $buckets[$cat]['revenues'][] = $rev;
            $buckets[$cat]['total']      += $count;

            $allServices[] = [
                'id'          => $svc->id,
                'name'        => $svc->name,
                'category'    => $cat,
                'count'       => $count,
                'revenue'     => $rev,
                'count_pct'   => $grandCount   > 0 ? round(($count / $grandCount)   * 100, 1) : 0,
                'revenue_pct' => $grandRevenue > 0 ? round(($rev   / $grandRevenue) * 100, 1) : 0,
            ];
        }

        // Bucket add-ons (always 'addon' category)
        foreach ($addons as $addon) {
            $count = (int)   ($addon->laundry_count   ?? 0);
            $rev   = (float) ($addon->laundry_revenue  ?? 0);

            $buckets['addon']['labels'][]   = $addon->name;
            $buckets['addon']['counts'][]   = $count;
            $buckets['addon']['revenues'][] = $rev;
            $buckets['addon']['total']      += $count;

            $allServices[] = [
                'id'          => $addon->id,
                'name'        => $addon->name . ' (Add-On)',
                'category'    => 'addon',
                'count'       => $count,
                'revenue'     => $rev,
                'count_pct'   => $grandCount   > 0 ? round(($count / $grandCount)   * 100, 1) : 0,
                'revenue_pct' => $grandRevenue > 0 ? round(($rev   / $grandRevenue) * 100, 1) : 0,
            ];
        }

        usort($allServices, fn($a, $b) => $b['count'] <=> $a['count']);

        return [
            'drop_off'     => $buckets['drop_off'],
            'self_service' => $buckets['self_service'],
            'addon'        => $buckets['addon'],
            'all_services' => $allServices,
            'grand_total'  => ['count' => $grandCount, 'revenue' => $grandRevenue],
        ];
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
