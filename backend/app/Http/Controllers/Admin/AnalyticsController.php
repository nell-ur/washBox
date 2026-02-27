<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Laundry;
use App\Models\Customer;
use App\Models\Branch;
use App\Models\Service;
use App\Models\Promotion;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AnalyticsController extends Controller
{
    public function index(Request $request)
    {
        $startDate = $request->input('start_date', now()->subDays(30));
        $endDate   = $request->input('end_date',   now());

        $startDate = Carbon::parse($startDate)->startOfDay();
        $endDate   = Carbon::parse($endDate)->endOfDay();

        return view('admin.analytics.index', [
            'startDate'              => $startDate->format('Y-m-d'),
            'endDate'                => $endDate->format('Y-m-d'),
            'revenueAnalytics'       => $this->getRevenueAnalytics($startDate, $endDate),
            'laundryAnalytics'       => $this->getLaundryAnalytics($startDate, $endDate),
            'branchPerformance'      => $this->getBranchPerformance($startDate, $endDate),
            'servicePopularity'      => $this->getServicePopularity($startDate, $endDate),
            'customerAnalytics'      => $this->getCustomerAnalytics($startDate, $endDate),
            'promotionEffectiveness' => $this->getPromotionEffectiveness($startDate, $endDate),
        ]);
    }

    /**
     * AJAX polling endpoint — returns fresh KPI data as JSON.
     * Route: GET /admin/analytics/refresh
     */
    public function refresh(Request $request)
    {
        $startDate = Carbon::parse($request->input('start_date', now()->subDays(30)))->startOfDay();
        $endDate   = Carbon::parse($request->input('end_date',   now()))->endOfDay();

        $revenue  = $this->getRevenueAnalytics($startDate, $endDate);
        $laundry  = $this->getLaundryAnalytics($startDate, $endDate);
        $customer = $this->getCustomerAnalytics($startDate, $endDate);

        return response()->json([
            'revenue' => [
                'total'                 => $revenue['total'],
                'average_laundry_value' => $revenue['average_laundry_value'],
                'growth_percentage'     => $revenue['growth_percentage'],
                'labels'                => $revenue['labels'],
                'data'                  => $revenue['data'],
            ],
            'laundry' => [
                'total'                     => $laundry['total'],
                'completed'                 => $laundry['completed'],
                'completion_rate'           => $laundry['completion_rate'],
                'avg_processing_time_hours' => $laundry['avg_processing_time_hours'],
                'status_labels'             => $laundry['status_labels'],
                'status_data'               => $laundry['status_data'],
            ],
            'customer' => [
                'total'                      => $customer['total'],
                'new'                        => $customer['new'],
                'avg_laundries_per_customer' => $customer['avg_laundries_per_customer'],
                'growth_labels'              => $customer['growth_labels'],
                'growth_data'                => $customer['growth_data'],
                'registration_source'        => $customer['registration_source'],
            ],
            'refreshed_at' => now()->toIso8601String(),
        ]);
    }

    protected function getRevenueAnalytics($startDate, $endDate)
    {
        $totalRevenue = Laundry::whereBetween('created_at', [$startDate, $endDate])
            ->whereIn('status', ['paid', 'completed'])
            ->sum('total_amount');

        $averageLaundryValue = Laundry::whereBetween('created_at', [$startDate, $endDate])
            ->whereIn('status', ['paid', 'completed'])
            ->avg('total_amount');

        $revenueByDay = Laundry::whereBetween('created_at', [$startDate, $endDate])
            ->whereIn('status', ['paid', 'completed'])
            ->selectRaw('DATE(created_at) as date, SUM(total_amount) as revenue')
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->get();

        $revenueLabels = $revenueByDay->pluck('date')->map(fn($d) => Carbon::parse($d)->format('M d'))->toArray();
        $revenueData   = $revenueByDay->pluck('revenue')->map(fn($v) => (float) $v)->toArray();

        $periodDays       = $startDate->diffInDays($endDate);
        $previousStart    = $startDate->copy()->subDays($periodDays);
        $previousEnd      = $startDate->copy()->subDay();
        $previousRevenue  = Laundry::whereBetween('created_at', [$previousStart, $previousEnd])
            ->whereIn('status', ['paid', 'completed'])
            ->sum('total_amount');

        $revenueGrowth = $previousRevenue > 0
            ? (($totalRevenue - $previousRevenue) / $previousRevenue) * 100
            : 0;

        return [
            'total'                => (float) $totalRevenue,
            'average_laundry_value'=> (float) ($averageLaundryValue ?? 0),
            'growth_percentage'    => round($revenueGrowth, 2),
            'labels'               => $revenueLabels,
            'data'                 => $revenueData,
        ];
    }

    protected function getLaundryAnalytics($startDate, $endDate)
    {
        $totalLaundry = Laundry::whereBetween('created_at', [$startDate, $endDate])->count();

        $orderByStatus = Laundry::whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->get();

        $statusLabels = $orderByStatus->pluck('status')->map(fn($s) => ucfirst($s))->toArray();
        $statusData   = $orderByStatus->pluck('count')->toArray();

        $completedLaundry = Laundry::whereBetween('created_at', [$startDate, $endDate])
            ->where('status', 'completed')
            ->count();

        $completionRate = $totalLaundry > 0 ? ($completedLaundry / $totalLaundry) * 100 : 0;

        $avgProcessingTime = Laundry::whereBetween('created_at', [$startDate, $endDate])
            ->whereNotNull('completed_at')
            ->selectRaw('AVG(TIMESTAMPDIFF(HOUR, created_at, completed_at)) as avg_hours')
            ->value('avg_hours');

        return [
            'total'                    => $totalLaundry,
            'completed'                => $completedLaundry,
            'completion_rate'          => round($completionRate, 2),
            'avg_processing_time_hours'=> round($avgProcessingTime ?? 0, 2),
            'status_labels'            => $statusLabels,
            'status_data'              => $statusData,
        ];
    }

    protected function getBranchPerformance($startDate, $endDate)
    {
        $branches = Branch::withCount(['laundries' => fn($q) => $q->whereBetween('created_at', [$startDate, $endDate])])
            ->with(['laundries' => fn($q) => $q->whereBetween('created_at', [$startDate, $endDate])->whereIn('status', ['paid', 'completed'])])
            ->get();

        $branchData = $branches->map(fn($b) => [
            'name'     => $b->name,
            'code'     => $b->code,
            'laundries'=> $b->laundries_count,
            'revenue'  => (float) $b->laundries->sum('total_amount'),
        ])->sortByDesc('revenue')->values()->toArray();

        return [
            'branches'     => $branchData,
            'labels'       => array_column($branchData, 'code'),
            'laundry_data' => array_column($branchData, 'laundries'),
            'revenue_data' => array_column($branchData, 'revenue'),
        ];
    }

    protected function getServicePopularity($startDate, $endDate)
    {
        $services = Service::withCount(['laundries' => fn($q) => $q->whereBetween('created_at', [$startDate, $endDate])])
            ->with(['laundries' => fn($q) => $q->whereBetween('created_at', [$startDate, $endDate])->whereIn('status', ['paid', 'completed'])])
            ->get();

        $serviceData = $services->map(fn($s) => [
            'name'     => $s->name,
            'laundries'=> $s->laundries_count,
            'revenue'  => (float) $s->laundries->sum('total_amount'),
        ])->sortByDesc('laundries')->values()->toArray();

        return [
            'services'     => $serviceData,
            'labels'       => array_column($serviceData, 'name'),
            'laundry_data' => array_column($serviceData, 'laundries'),
            'revenue_data' => array_column($serviceData, 'revenue'),
        ];
    }

    protected function getCustomerAnalytics($startDate, $endDate)
    {
        $totalCustomers = Customer::count();
        $newCustomers   = Customer::whereBetween('created_at', [$startDate, $endDate])->count();

        $customerGrowth = Customer::whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->get();

        $growthLabels = $customerGrowth->pluck('date')->map(fn($d) => Carbon::parse($d)->format('M d'))->toArray();
        $growthData   = $customerGrowth->pluck('count')->toArray();

        $avgLaundriesPerCustomer = Laundry::whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw('customer_id, COUNT(*) as laundry_count')
            ->groupBy('customer_id')
            ->get()
            ->avg('laundry_count');

        // ── Registration type breakdown (walk_in vs self_registered) ──
        $regCounts = Customer::select('registration_type', DB::raw('count(*) as count'))
            ->groupBy('registration_type')
            ->pluck('count', 'registration_type');

        $walkIn  = 0;
        $selfReg = 0;
        foreach ($regCounts as $type => $cnt) {
            $t = strtolower(trim((string) $type));
            if (in_array($t, ['walk_in', 'walkin', 'counter'])) {
                $walkIn += $cnt;
            } else {
                $selfReg += $cnt;
            }
        }

        $topCustomers = Customer::withCount(['laundries' => fn($q) => $q->whereBetween('created_at', [$startDate, $endDate])])
            ->withSum(['laundries' => fn($q) => $q->whereBetween('created_at', [$startDate, $endDate])->whereIn('status', ['paid', 'completed'])], 'total_amount')
            ->having('laundries_sum_total_amount', '>', 0)
            ->orderByDesc('laundries_sum_total_amount')
            ->take(10)
            ->get();

        return [
            'total'                       => $totalCustomers,
            'new'                         => $newCustomers,
            'avg_laundries_per_customer'  => round($avgLaundriesPerCustomer ?? 0, 2),
            'growth_labels'               => $growthLabels,
            'growth_data'                 => $growthData,
            'top_customers'               => $topCustomers,
            'registration_source'         => [
                'walk_in'        => $walkIn,
                'self_registered'=> $selfReg,
            ],
        ];
    }

    protected function getPromotionEffectiveness($startDate, $endDate)
    {
        $promotions = Promotion::withCount(['laundries' => fn($q) => $q->whereBetween('created_at', [$startDate, $endDate])])
            ->with(['laundries' => fn($q) => $q->whereBetween('created_at', [$startDate, $endDate])->whereIn('status', ['paid', 'completed'])])
            ->where('start_date', '<=', $endDate)
            ->where('end_date',   '>=', $startDate)
            ->get();

        $promotionData = $promotions->map(fn($p) => [
            'name'          => $p->name,
            'type'          => $p->type,
            'usage_count'   => $p->laundries_count,
            'revenue'       => (float) $p->laundries->sum('total_amount'),
            'total_discount'=> (float) $p->laundries->sum('discount_amount'),
            'is_active'     => $p->is_active,
        ])->sortByDesc('usage_count')->values()->toArray();

        return [
            'promotions' => $promotionData,
            'labels'     => array_column($promotionData, 'name'),
            'usage_data' => array_column($promotionData, 'usage_count'),
        ];
    }
}
