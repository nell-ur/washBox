<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\CustomerRating;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class BranchRatingController extends Controller
{
    /**
     * Display branch ratings report
     */
    public function index(Request $request)
    {
        $dateFrom = $this->getDateFromFilter($request);
        $dateTo = $this->getDateToFilter($request);

        // Get branch statistics with ratings
        $branches = Branch::withCount([
            'ratings' => function($query) use ($dateFrom, $dateTo) {
                $query->whereBetween('created_at', [$dateFrom, $dateTo]);
            }
        ])
        ->withAvg([
            'ratings' => function($query) use ($dateFrom, $dateTo) {
                $query->whereBetween('created_at', [$dateFrom, $dateTo]);
            }
        ], 'rating')
        ->get()
        ->map(function($branch) use ($dateFrom, $dateTo) {
            return [
                'id' => $branch->id,
                'name' => $branch->name,
                'code' => $branch->code,
                'total_ratings' => $branch->ratings_count,
                'average_rating' => round($branch->ratings_avg_rating ?? 0, 1),
                'distribution' => $this->getRatingDistribution($branch->id, $dateFrom, $dateTo),
                'trend' => $this->getMonthlyTrend($branch->id, $dateFrom, $dateTo),
            ];
        });

        // Get recent ratings with filters
        $query = CustomerRating::with(['customer', 'branch'])
            ->whereBetween('created_at', [$dateFrom, $dateTo]);

        // Apply branch filter
        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        // Apply rating filter
        if ($request->filled('rating')) {
            $query->where('rating', $request->rating);
        }

        $recentRatings = $query->latest()->paginate(20);

        // Calculate summary statistics
        $summary = [
            'total_ratings' => CustomerRating::whereBetween('created_at', [$dateFrom, $dateTo])->count(),
            'avg_rating' => round(CustomerRating::whereBetween('created_at', [$dateFrom, $dateTo])->avg('rating') ?? 0, 1),
            'branches_with_ratings' => CustomerRating::whereBetween('created_at', [$dateFrom, $dateTo])
                ->distinct('branch_id')
                ->count('branch_id'),
            'total_comments' => CustomerRating::whereBetween('created_at', [$dateFrom, $dateTo])
                ->whereNotNull('comment')
                ->count(),
            'rating_distribution' => $this->getOverallDistribution($dateFrom, $dateTo),
        ];

        // Get top rated branches
        $topBranches = Branch::withAvg(['ratings' => function($query) use ($dateFrom, $dateTo) {
                $query->whereBetween('created_at', [$dateFrom, $dateTo]);
            }], 'rating')
            ->having('ratings_avg_rating', '>', 0)
            ->orderBy('ratings_avg_rating', 'desc')
            ->limit(5)
            ->get(['id', 'name']);

        // Get branches needing improvement (lowest rated)
        $needsImprovement = Branch::withAvg(['ratings' => function($query) use ($dateFrom, $dateTo) {
                $query->whereBetween('created_at', [$dateFrom, $dateTo]);
            }], 'rating')
            ->having('ratings_avg_rating', '>', 0)
            ->orderBy('ratings_avg_rating', 'asc')
            ->limit(5)
            ->get(['id', 'name']);

        return view('admin.reports.branch-ratings', compact(
            'branches',
            'recentRatings',
            'summary',
            'topBranches',
            'needsImprovement',
            'dateFrom',
            'dateTo'
        ));
    }

    /**
     * Get rating distribution for a specific branch
     */
    private function getRatingDistribution($branchId, $dateFrom, $dateTo)
    {
        $ratings = CustomerRating::where('branch_id', $branchId)
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->select('rating', DB::raw('COUNT(*) as count'))
            ->groupBy('rating')
            ->pluck('count', 'rating')
            ->toArray();

        $distribution = [];
        for ($i = 1; $i <= 5; $i++) {
            $distribution[$i] = $ratings[$i] ?? 0;
        }

        return $distribution;
    }

    /**
     * Get overall rating distribution
     */
    private function getOverallDistribution($dateFrom, $dateTo)
    {
        $ratings = CustomerRating::whereBetween('created_at', [$dateFrom, $dateTo])
            ->select('rating', DB::raw('COUNT(*) as count'))
            ->groupBy('rating')
            ->pluck('count', 'rating')
            ->toArray();

        $distribution = [];
        $total = array_sum($ratings);
        
        for ($i = 1; $i <= 5; $i++) {
            $count = $ratings[$i] ?? 0;
            $distribution[$i] = [
                'count' => $count,
                'percentage' => $total > 0 ? round(($count / $total) * 100, 1) : 0
            ];
        }

        return $distribution;
    }

    /**
     * Get monthly trend for a branch
     */
    private function getMonthlyTrend($branchId, $dateFrom, $dateTo)
    {
        $trend = [];
        $currentDate = Carbon::parse($dateFrom);
        $endDate = Carbon::parse($dateTo);

        while ($currentDate <= $endDate) {
            $monthStart = $currentDate->copy()->startOfMonth();
            $monthEnd = $currentDate->copy()->endOfMonth();

            $avg = CustomerRating::where('branch_id', $branchId)
                ->whereBetween('created_at', [$monthStart, $monthEnd])
                ->avg('rating');

            $trend[] = [
                'month' => $currentDate->format('M Y'),
                'rating' => round($avg ?? 0, 1)
            ];

            $currentDate->addMonth();
        }

        return $trend;
    }

    /**
     * Get date from filter
     */
    private function getDateFromFilter(Request $request)
    {
        $filter = $request->get('filter', 'this_month');

        return match($filter) {
            'today' => now()->startOfDay(),
            'this_week' => now()->startOfWeek(),
            'this_month' => now()->startOfMonth(),
            'last_month' => now()->subMonth()->startOfMonth(),
            'last_3_months' => now()->subMonths(3)->startOfDay(),
            'last_6_months' => now()->subMonths(6)->startOfDay(),
            'this_year' => now()->startOfYear(),
            'custom' => Carbon::parse($request->get('date_from', now()->startOfMonth())),
            default => now()->startOfMonth(),
        };
    }

    /**
     * Get date to filter
     */
    private function getDateToFilter(Request $request)
    {
        $filter = $request->get('filter', 'this_month');

        return match($filter) {
            'today' => now()->endOfDay(),
            'this_week' => now()->endOfWeek(),
            'this_month' => now()->endOfMonth(),
            'last_month' => now()->subMonth()->endOfMonth(),
            'last_3_months' => now()->endOfDay(),
            'last_6_months' => now()->endOfDay(),
            'this_year' => now()->endOfYear(),
            'custom' => Carbon::parse($request->get('date_to', now()->endOfMonth())),
            default => now()->endOfMonth(),
        };
    }
}