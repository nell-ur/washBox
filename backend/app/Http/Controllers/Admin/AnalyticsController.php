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
    /**
     * Display the analytics dashboard.
     */
    public function index(Request $request)
    {
        $startDate = $request->input('start_date', now()->subDays(30));
        $endDate = $request->input('end_date', now());

        $startDate = Carbon::parse($startDate)->startOfDay();
        $endDate = Carbon::parse($endDate)->endOfDay();

        $revenueAnalytics = $this->getRevenueAnalytics($startDate, $endDate);
        $laundryAnalytics = $this->getLaundryAnalytics($startDate, $endDate);
        $branchPerformance = $this->getBranchPerformance($startDate, $endDate);
        $servicePopularity = $this->getServicePopularity($startDate, $endDate);
        $customerAnalytics = $this->getCustomerAnalytics($startDate, $endDate);
        $promotionEffectiveness = $this->getPromotionEffectiveness($startDate, $endDate);

        return view('admin.analytics.index', [
            'startDate' => $startDate->format('Y-m-d'),
            'endDate' => $endDate->format('Y-m-d'),
            'revenueAnalytics' => $revenueAnalytics,
            'laundryAnalytics' => $laundryAnalytics,
            'branchPerformance' => $branchPerformance,
            'servicePopularity' => $servicePopularity,
            'customerAnalytics' => $customerAnalytics,
            'promotionEffectiveness' => $promotionEffectiveness,
        ]);
    }

    /**
     * Get revenue analytics.
     */
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

        $revenueLabels = $revenueByDay->pluck('date')->map(function($date) {
            return Carbon::parse($date)->format('M d');
        })->toArray();

        $revenueData = $revenueByDay->pluck('revenue')->map(function($revenue) {
            return (float) $revenue;
        })->toArray();

        // Revenue Growth (compared to previous period)
        $periodDays = $startDate->diffInDays($endDate);
        $previousStartDate = $startDate->copy()->subDays($periodDays);
        $previousEndDate = $startDate->copy()->subDay();

        $previousRevenue = Laundry::whereBetween('created_at', [$previousStartDate, $previousEndDate])
            ->whereIn('status', ['paid', 'completed'])
            ->sum('total_amount');

        $revenueGrowth = $previousRevenue > 0
            ? (($totalRevenue - $previousRevenue) / $previousRevenue) * 100
            : 0;

        return [
            'total' => (float) $totalRevenue,
            'average_laundry_value' => (float) ($averageLaundryValue ?? 0),
            'growth_percentage' => round($revenueGrowth, 2),
            'labels' => $revenueLabels,
            'data' => $revenueData,
        ];
    }

    /**
     * Get laundry analytics.
     */
    protected function getLaundryAnalytics($startDate, $endDate)
    {
        $totalLaundry = Laundry::whereBetween('created_at', [$startDate, $endDate])->count();

        $orderByStatus = Laundry::whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->get();

        $statusLabels = $orderByStatus->pluck('status')->map(function($status) {
            return ucfirst($status);
        })->toArray();

        $statusData = $orderByStatus->pluck('count')->toArray();

        $completedLaundry = Laundry::whereBetween('created_at', [$startDate, $endDate])
            ->where('status', 'completed')
            ->count();

        $completionRate = $totalLaundry > 0 ? ($completedLaundry / $totalLaundry) * 100 : 0;

        $avgProcessingTime = Laundry::whereBetween('created_at', [$startDate, $endDate])
            ->whereNotNull('completed_at')
            ->selectRaw('AVG(TIMESTAMPDIFF(HOUR, created_at, completed_at)) as avg_hours')
            ->value('avg_hours');

        return [
            'total' => $totalLaundry,
            'completed' => $completedLaundry,
            'completion_rate' => round($completionRate, 2),
            'avg_processing_time_hours' => round($avgProcessingTime ?? 0, 2),
            'status_labels' => $statusLabels,
            'status_data' => $statusData,
        ];
    }

    /**
     * Get branch performance analytics.
     */
    protected function getBranchPerformance($startDate, $endDate)
    {
        $branches = Branch::withCount(['laundries' => function($query) use ($startDate, $endDate) {
            $query->whereBetween('created_at', [$startDate, $endDate]);
        }])
        ->with(['laundries' => function($query) use ($startDate, $endDate) {
            $query->whereBetween('created_at', [$startDate, $endDate])
                ->whereIn('status', ['paid', 'completed']);
        }])
        ->get();

        $branchData = [];

        foreach ($branches as $branch) {
            $revenue = $branch->laundries->sum('total_amount');

            $branchData[] = [
                'name' => $branch->name,
                'code' => $branch->code,
                'laundries' => $branch->laundries_count,
                'revenue' => (float) $revenue,
            ];
        }

        usort($branchData, function($a, $b) {
            return $b['revenue'] <=> $a['revenue'];
        });

        $branchLabels = array_column($branchData, 'code');
        $branchLaundryData = array_column($branchData, 'laundries');
        $branchRevenueData = array_column($branchData, 'revenue');

        return [
            'branches' => $branchData,
            'labels' => $branchLabels,
            'laundry_data' => $branchLaundryData,
            'revenue_data' => $branchRevenueData,
        ];
    }

    /**
     * Get service popularity analytics.
     */
    protected function getServicePopularity($startDate, $endDate)
    {
        $services = Service::withCount(['laundries' => function($query) use ($startDate, $endDate) {
            $query->whereBetween('created_at', [$startDate, $endDate]);
        }])
        ->with(['laundries' => function($query) use ($startDate, $endDate) {
            $query->whereBetween('created_at', [$startDate, $endDate])
                ->whereIn('status', ['paid', 'completed']);
        }])
        ->get();

        $serviceData = [];

        foreach ($services as $service) {
            $revenue = $service->laundries->sum('total_amount');

            $serviceData[] = [
                'name' => $service->name,
                'laundries' => $service->laundries_count,
                'revenue' => (float) $revenue,
            ];
        }

        usort($serviceData, function($a, $b) {
            return $b['laundries'] <=> $a['laundries'];
        });

        $serviceLabels = array_column($serviceData, 'name');
        $serviceLaundryData = array_column($serviceData, 'laundries');
        $serviceRevenueData = array_column($serviceData, 'revenue');

        return [
            'services' => $serviceData,
            'labels' => $serviceLabels,
            'laundry_data' => $serviceLaundryData,
            'revenue_data' => $serviceRevenueData,
        ];
    }

    /**
     * Get customer analytics.
     */
    protected function getCustomerAnalytics($startDate, $endDate)
    {
        $totalCustomers = Customer::count();
        $newCustomers = Customer::whereBetween('created_at', [$startDate, $endDate])->count();

        $customerGrowth = Customer::whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->get();

        $growthLabels = $customerGrowth->pluck('date')->map(function($date) {
            return Carbon::parse($date)->format('M d');
        })->toArray();

        $growthData = $customerGrowth->pluck('count')->toArray();

        $avgLaundriesPerCustomer = Laundry::whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw('customer_id, COUNT(*) as laundry_count')
            ->groupBy('customer_id')
            ->get()
            ->avg('laundry_count');

        // ═══ FIX 2: Added withCount so $customer->laundries_count works in the blade ═══
        $topCustomers = Customer::withCount(['laundries' => function($query) use ($startDate, $endDate) {
                $query->whereBetween('created_at', [$startDate, $endDate]);
            }])
            ->withSum(['laundries' => function($query) use ($startDate, $endDate) {
                $query->whereBetween('created_at', [$startDate, $endDate])
                    ->whereIn('status', ['paid', 'completed']);
            }], 'total_amount')
            ->having('laundries_sum_total_amount', '>', 0)
            ->orderByDesc('laundries_sum_total_amount')
            ->take(10)
            ->get();

        return [
            'total' => $totalCustomers,
            'new' => $newCustomers,
            'avg_laundries_per_customer' => round($avgLaundriesPerCustomer ?? 0, 2),
            'growth_labels' => $growthLabels,
            'growth_data' => $growthData,
            'top_customers' => $topCustomers,
        ];
    }

    /**
     * Get promotion effectiveness analytics.
     */
    protected function getPromotionEffectiveness($startDate, $endDate)
    {
        $promotions = Promotion::withCount(['laundries' => function($query) use ($startDate, $endDate) {
            $query->whereBetween('created_at', [$startDate, $endDate]);
        }])
        ->with(['laundries' => function($query) use ($startDate, $endDate) {
            $query->whereBetween('created_at', [$startDate, $endDate])
                ->whereIn('status', ['paid', 'completed']);
        }])
        ->where('start_date', '<=', $endDate)
        ->where('end_date', '>=', $startDate)
        ->get();

        $promotionData = [];

        foreach ($promotions as $promotion) {
            $revenue = $promotion->laundries->sum('total_amount');
            $discount = $promotion->laundries->sum('discount_amount');

            $promotionData[] = [
                'name' => $promotion->name,
                'type' => $promotion->type,
                'usage_count' => $promotion->laundries_count,
                'revenue' => (float) $revenue,
                'total_discount' => (float) $discount,
                'is_active' => $promotion->is_active,
            ];
        }

        usort($promotionData, function($a, $b) {
            return $b['usage_count'] <=> $a['usage_count'];
        });

        $promotionLabels = array_column($promotionData, 'name');
        $promotionUsageData = array_column($promotionData, 'usage_count');

        return [
            'promotions' => $promotionData,
            'labels' => $promotionLabels,
            'usage_data' => $promotionUsageData,
        ];
    }
}
