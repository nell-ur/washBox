<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\CustomerRating;
use App\Models\Laundry;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    /**
     * Display reports dashboard
     */
    public function index()
    {
        $stats = [
            'total_revenue' => Laundry::where('payment_status', 'paid')->sum('total_amount'),
            'total_laundries' => Laundry::count(),
            'total_customers' => Customer::count(),
            'active_branches' => Branch::where('is_active', true)->count(),
            'total_ratings' => CustomerRating::count(),
        ];

        // Revenue by month (last 12 months)
        $monthlyRevenue = Laundry::where('payment_status', 'paid')
            ->where('created_at', '>=', now()->subMonths(12))
            ->select(
                DB::raw('DATE_FORMAT(created_at, "%Y-%m") as month'),
                DB::raw('SUM(total_amount) as revenue'),
                DB::raw('COUNT(*) as laundries')
            )
            ->groupBy('month')
            ->orderBy('month', 'desc')
            ->get();

        // Top customers
        $topCustomers = Customer::withCount('laundries')
            ->with(['laundries' => function($query) {
                $query->where('payment_status', 'paid');
            }])
            ->having('laundries_count', '>', 0)
            ->orderBy('laundries_count', 'desc')
            ->limit(10)
            ->get();

        // Branch performance
        $branchStats = Branch::withCount('laundries')
            ->with(['laundries' => function($query) {
                $query->where('payment_status', 'paid');
            }])
            ->get()
            ->map(function($branch) {
                return [
                    'name' => $branch->name,
                    'laundries_count' => $branch->laundries_count,
                    'revenue' => $branch->laundries()->where('payment_status', 'paid')->sum('total_amount'),
                ];
            });

        return view('admin.reports.index', compact(
            'stats',
            'monthlyRevenue',
            'topCustomers',
            'branchStats'
        ));
    }

    /**
     * Revenue report
     */
    public function revenue(Request $request)
    {
        $startDate = $request->get('start_date', now()->startOfMonth());
        $endDate = $request->get('end_date', now()->endOfMonth());

        $data = Laundry::where('payment_status', 'paid')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('SUM(total_amount) as revenue'),
                DB::raw('COUNT(*) as laundries')
            )
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->get();

        return view('admin.reports.revenue', compact('data', 'startDate', 'endDate'));
    }

    /**
     * Laundries report
     */
    public function laundries(Request $request)
    {
        $startDate = $request->get('start_date', now()->startOfMonth());
        $endDate = $request->get('end_date', now()->endOfMonth());

        $laundries = Laundry::whereBetween('created_at', [$startDate, $endDate])
            ->with(['customer', 'branch'])
            ->orderBy('created_at', 'desc')
            ->paginate(50);

        $summary = [
            'total_laundries' => Laundry::whereBetween('created_at', [$startDate, $endDate])->count(),
            'completed_laundries' => Laundry::whereBetween('created_at', [$startDate, $endDate])
                ->where('status', 'completed')->count(),
            'pending_laundries' => Laundry::whereBetween('created_at', [$startDate, $endDate])
                ->whereIn('status', ['pending', 'processing'])->count(),
            'total_revenue' => Laundry::whereBetween('created_at', [$startDate, $endDate])
                ->where('payment_status', 'paid')->sum('total_amount'),
        ];

        return view('admin.reports.laundries', compact('laundries', 'summary', 'startDate', 'endDate'));
    }

    /**
     * Customers report with filtering
     */
    public function customers(Request $request)
    {
        $dateFrom = $this->getDateFromFilter($request);
        $dateTo = $this->getDateToFilter($request);

        // Build base query
        $query = Customer::withCount(['laundries' => function($q) use ($dateFrom, $dateTo) {
            $q->whereBetween('created_at', [$dateFrom, $dateTo]);
        }])
        ->with(['laundries' => function($q) use ($dateFrom, $dateTo) {
            $q->where('payment_status', 'paid')
              ->whereBetween('created_at', [$dateFrom, $dateTo]);
        }, 'ratings']);

        // Apply rating filter if provided
        if ($request->filled('rating')) {
            $query = $this->applyRatingFilter($query, $request->get('rating'));
        }

        $customers = $query->paginate(50);

        // Calculate metrics
        $metrics = $this->calculateCustomerMetrics($customers, $dateFrom, $dateTo);

        return view('admin.reports.customers', compact('customers', 'metrics'));
    }

    /**
     * Calculate customer metrics for display
     */
    private function calculateCustomerMetrics($customers, $dateFrom, $dateTo)
    {
        $totalCustomers = Customer::count();
        $newCustomersPeriod = Customer::whereBetween('created_at', [$dateFrom, $dateTo])->count();
        $activeCustomers = Customer::whereHas('laundries', function($q) {
            $q->where('created_at', '>=', now()->subDays(30));
        })->count();
        $returningCustomers = Customer::whereHas('laundries', function($q) {
            $q->where('created_at', '<', now()->subDays(30));
        })->count();
        $inactiveCustomers = $totalCustomers - $activeCustomers;

        // Ratings data
        $ratedCustomers = $customers->filter(fn($c) => $c->ratings && $c->ratings->count() > 0);
        $avgRating = $ratedCustomers->count() > 0
            ? round($ratedCustomers->avg(fn($c) => $c->ratings->avg('rating')), 1)
            : 0;

        // Calculate aggregate values
        $totalOrdersPeriod = Laundry::whereBetween('created_at', [$dateFrom, $dateTo])->count();
        $totalRevenuePeriod = Laundry::whereBetween('created_at', [$dateFrom, $dateTo])
            ->where('payment_status', 'paid')
            ->sum('total_amount');
        $avgOrdersPerCustomer = $totalCustomers > 0 ? round($totalOrdersPeriod / $totalCustomers, 2) : 0;
        $avgSpendPeriod = $totalOrdersPeriod > 0 ? round($totalRevenuePeriod / $totalOrdersPeriod, 2) : 0;
        $avgCustomerValue = $totalCustomers > 0
            ? round(Laundry::where('payment_status', 'paid')->sum('total_amount') / $totalCustomers, 2)
            : 0;

        return [
            'total_customers' => $totalCustomers,
            'new_customers_period' => $newCustomersPeriod,
            'active_customers' => $activeCustomers,
            'inactive_customers' => $inactiveCustomers,
            'returning_customers' => $returningCustomers,
            'avg_rating' => $avgRating,
            'rated_customers_count' => $ratedCustomers->count(),
            'avg_orders_per_customer' => $avgOrdersPerCustomer,
            'total_orders_period' => $totalOrdersPeriod,
            'total_revenue_period' => $totalRevenuePeriod,
            'avg_spend_period' => $avgSpendPeriod,
            'avg_customer_value' => $avgCustomerValue,
        ];
    }

    /**
     * Get date from filter or default
     */
    private function getDateFromFilter(Request $request)
    {
        $filter = $request->get('filter', 'this_month');

        return match($filter) {
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
     * Get date to filter or default
     */
    private function getDateToFilter(Request $request)
    {
        $filter = $request->get('filter', 'this_month');

        return match($filter) {
            'this_month' => now()->endOfMonth(),
            'last_month' => now()->subMonth()->endOfMonth(),
            'last_3_months' => now()->endOfDay(),
            'last_6_months' => now()->endOfDay(),
            'this_year' => now()->endOfYear(),
            'custom' => Carbon::parse($request->get('date_to', now()->endOfMonth())),
            default => now()->endOfMonth(),
        };
    }

    /**
     * Apply rating filter to query
     */
    private function applyRatingFilter($query, $ratingValue)
    {
        if ($ratingValue == 0) {
            // No ratings
            return $query->whereDoesntHave('ratings');
        }

        $rating = (int)$ratingValue;
        $minRating = $rating;
        $maxRating = $rating + 1;

        return $query->whereHas('ratings', function($q) use ($minRating, $maxRating) {
            $q->selectRaw('customer_id, AVG(rating) as avg_rating')
              ->groupByRaw('customer_id')
              ->havingRaw('AVG(rating) >= ? AND AVG(rating) < ?', [$minRating, $maxRating]);
        }, '>=', 1);
    }

    /**
     * Branches report
     */
    public function branches(Request $request)
    {
        $startDate = $request->get('start_date', now()->startOfMonth());
        $endDate = $request->get('end_date', now()->endOfMonth());

        $branches = Branch::with(['laundries' => function($query) use ($startDate, $endDate) {
            $query->whereBetween('created_at', [$startDate, $endDate]);
        }])
        ->withCount(['laundries' => function($query) use ($startDate, $endDate) {
            $query->whereBetween('created_at', [$startDate, $endDate]);
        }])
        ->get()
        ->map(function($branch) {
            return [
                'id' => $branch->id,
                'name' => $branch->name,
                'code' => $branch->code,
                'laundries_count' => $branch->laundries_count,
                'revenue' => $branch->laundries()->where('payment_status', 'paid')->sum('total_amount'),
                'avg_laundry_value' => $branch->laundries_count > 0
                    ? $branch->laundries()->where('payment_status', 'paid')->sum('total_amount') / $branch->laundries_count
                    : 0,
            ];
        });

        return view('admin.reports.branches', compact('branches', 'startDate', 'endDate'));
    }

    /**
     * Branch Ratings Report
     */
    public function branchRatings(Request $request)
    {
        $filter = $request->get('filter', 'this_month');
        $branchId = $request->get('branch_id');
        $rating = $request->get('rating');
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');

        // Set date range based on filter
        switch($filter) {
            case 'today':
                $startDate = Carbon::today();
                $endDate = Carbon::today()->endOfDay();
                break;
            case 'this_week':
                $startDate = Carbon::now()->startOfWeek();
                $endDate = Carbon::now()->endOfWeek();
                break;
            case 'this_month':
                $startDate = Carbon::now()->startOfMonth();
                $endDate = Carbon::now()->endOfMonth();
                break;
            case 'last_month':
                $startDate = Carbon::now()->subMonth()->startOfMonth();
                $endDate = Carbon::now()->subMonth()->endOfMonth();
                break;
            case 'last_3_months':
                $startDate = Carbon::now()->subMonths(3)->startOfDay();
                $endDate = Carbon::now()->endOfDay();
                break;
            case 'last_6_months':
                $startDate = Carbon::now()->subMonths(6)->startOfDay();
                $endDate = Carbon::now()->endOfDay();
                break;
            case 'this_year':
                $startDate = Carbon::now()->startOfYear();
                $endDate = Carbon::now()->endOfYear();
                break;
            case 'custom':
                $startDate = $dateFrom ? Carbon::parse($dateFrom) : Carbon::now()->startOfMonth();
                $endDate = $dateTo ? Carbon::parse($dateTo)->endOfDay() : Carbon::now()->endOfMonth();
                break;
            default:
                $startDate = Carbon::now()->startOfMonth();
                $endDate = Carbon::now()->endOfMonth();
        }

        // Get all active branches for filter dropdown
        $allBranches = Branch::where('is_active', true)
            ->select('id', 'name', 'code')
            ->get();

        // Get branches with their ratings
        $branches = Branch::where('is_active', true)
            ->with(['ratings' => function($query) use ($startDate, $endDate, $branchId, $rating) {
                $query->whereBetween('created_at', [$startDate, $endDate])
                    ->when($branchId, function($q) use ($branchId) {
                        return $q->where('branch_id', $branchId);
                    })
                    ->when($rating, function($q) use ($rating) {
                        return $q->where('rating', $rating);
                    })
                    ->with(['customer', 'laundry']);
            }])
            ->withAvg(['ratings' => function($query) use ($startDate, $endDate, $branchId, $rating) {
                $query->whereBetween('created_at', [$startDate, $endDate])
                    ->when($branchId, function($q) use ($branchId) {
                        return $q->where('branch_id', $branchId);
                    })
                    ->when($rating, function($q) use ($rating) {
                        return $q->where('rating', $rating);
                    });
            }], 'rating')
            ->withCount(['ratings as total_ratings' => function($query) use ($startDate, $endDate, $branchId, $rating) {
                $query->whereBetween('created_at', [$startDate, $endDate])
                    ->when($branchId, function($q) use ($branchId) {
                        return $q->where('branch_id', $branchId);
                    })
                    ->when($rating, function($q) use ($rating) {
                        return $q->where('rating', $rating);
                    });
            }])
            ->get()
            ->map(function($branch) use ($startDate, $endDate) {
                $ratings = $branch->ratings;
                
                // Calculate rating distribution
                $distribution = [
                    5 => $ratings->where('rating', 5)->count(),
                    4 => $ratings->where('rating', 4)->count(),
                    3 => $ratings->where('rating', 3)->count(),
                    2 => $ratings->where('rating', 2)->count(),
                    1 => $ratings->where('rating', 1)->count(),
                ];

                // Calculate monthly trend (last 3 months)
                $trend = [];
                for ($i = 2; $i >= 0; $i--) {
                    $month = Carbon::now()->subMonths($i);
                    $monthRatings = $ratings->filter(function($rating) use ($month) {
                        return $rating->created_at->month == $month->month && 
                               $rating->created_at->year == $month->year;
                    });
                    
                    $trend[] = [
                        'month' => $month->format('M'),
                        'rating' => $monthRatings->isNotEmpty() ? round($monthRatings->avg('rating'), 1) : 0,
                    ];
                }

                return [
                    'id' => $branch->id,
                    'name' => $branch->name,
                    'code' => $branch->code ?? substr($branch->name, 0, 3),
                    'total_ratings' => $branch->total_ratings,
                    'average_rating' => round($branch->ratings_avg_rating ?? 0, 2),
                    'distribution' => $distribution,
                    'trend' => $trend,
                ];
            })
            ->filter(function($branch) {
                return $branch['total_ratings'] > 0;
            })
            ->sortByDesc('average_rating')
            ->values();

        // Calculate overall statistics
        $totalRatings = DB::table('customer_ratings')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->when($branchId, function($query) use ($branchId) {
                return $query->where('branch_id', $branchId);
            })
            ->when($rating, function($query) use ($rating) {
                return $query->where('rating', $rating);
            })
            ->count();

        $totalComments = DB::table('customer_ratings')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->whereNotNull('comment')
            ->where('comment', '!=', '')
            ->when($branchId, function($query) use ($branchId) {
                return $query->where('branch_id', $branchId);
            })
            ->when($rating, function($query) use ($rating) {
                return $query->where('rating', $rating);
            })
            ->count();

        $avgRating = DB::table('customer_ratings')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->when($branchId, function($query) use ($branchId) {
                return $query->where('branch_id', $branchId);
            })
            ->when($rating, function($query) use ($rating) {
                return $query->where('rating', $rating);
            })
            ->avg('rating') ?? 0;

        // Rating distribution for all branches combined
        $ratingDistribution = [];
        for ($i = 5; $i >= 1; $i--) {
            $count = DB::table('customer_ratings')
                ->whereBetween('created_at', [$startDate, $endDate])
                ->where('rating', $i)
                ->when($branchId, function($query) use ($branchId) {
                    return $query->where('branch_id', $branchId);
                })
                ->when($rating, function($query) use ($rating) {
                    return $query->where('rating', $rating);
                })
                ->count();
            
            $percentage = $totalRatings > 0 ? round(($count / $totalRatings) * 100, 1) : 0;
            
            $ratingDistribution[$i] = [
                'count' => $count,
                'percentage' => $percentage,
            ];
        }

        $summary = [
            'total_ratings' => $totalRatings,
            'total_comments' => $totalComments,
            'avg_rating' => round($avgRating, 2),
            'branches_with_ratings' => $branches->count(),
            'total_branches' => Branch::where('is_active', true)->count(),
            'rating_distribution' => $ratingDistribution,
        ];

        // Get top rated branches (as objects for the view) - using average_rating property
        $topBranches = $branches->sortByDesc('average_rating')
            ->take(3)
            ->map(function($branch) {
                return (object) $branch;
            });

        // Get branches that need improvement (lowest rated)
        $needsImprovement = $branches->sortBy('average_rating')
            ->take(3)
            ->map(function($branch) {
                return (object) $branch;
            });

        // Get recent ratings for the table
        $recentRatingsQuery = CustomerRating::with(['branch', 'customer', 'laundry'])
            ->whereBetween('created_at', [$startDate, $endDate])
            ->when($branchId, function($query) use ($branchId) {
                return $query->where('branch_id', $branchId);
            })
            ->when($rating, function($query) use ($rating) {
                return $query->where('rating', $rating);
            })
            ->orderBy('created_at', 'desc');

        $recentRatings = $recentRatingsQuery->paginate(20)->withQueryString();

        return view('admin.reports.branch-ratings', compact(
            'branches',
            'allBranches',
            'summary',
            'startDate',
            'endDate',
            'filter',
            'branchId',
            'rating',
            'topBranches',
            'needsImprovement',
            'recentRatings',
            'ratingDistribution'
        ));
    }

    /**
     * Export Branch Ratings
     */
    public function exportBranchRatings(Request $request)
    {
        $filter = $request->get('filter', 'this_month');
        $branchId = $request->get('branch_id');
        $rating = $request->get('rating');
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');

        // Set date range based on filter
        switch($filter) {
            case 'today':
                $startDate = Carbon::today();
                $endDate = Carbon::today()->endOfDay();
                break;
            case 'this_week':
                $startDate = Carbon::now()->startOfWeek();
                $endDate = Carbon::now()->endOfWeek();
                break;
            case 'this_month':
                $startDate = Carbon::now()->startOfMonth();
                $endDate = Carbon::now()->endOfMonth();
                break;
            case 'last_month':
                $startDate = Carbon::now()->subMonth()->startOfMonth();
                $endDate = Carbon::now()->subMonth()->endOfMonth();
                break;
            case 'last_3_months':
                $startDate = Carbon::now()->subMonths(3)->startOfDay();
                $endDate = Carbon::now()->endOfDay();
                break;
            case 'last_6_months':
                $startDate = Carbon::now()->subMonths(6)->startOfDay();
                $endDate = Carbon::now()->endOfDay();
                break;
            case 'this_year':
                $startDate = Carbon::now()->startOfYear();
                $endDate = Carbon::now()->endOfYear();
                break;
            case 'custom':
                $startDate = $dateFrom ? Carbon::parse($dateFrom) : Carbon::now()->startOfMonth();
                $endDate = $dateTo ? Carbon::parse($dateTo)->endOfDay() : Carbon::now()->endOfMonth();
                break;
            default:
                $startDate = Carbon::now()->startOfMonth();
                $endDate = Carbon::now()->endOfMonth();
        }

        // Get data for export with filters
        $ratings = DB::table('customer_ratings')
            ->join('branches', 'customer_ratings.branch_id', '=', 'branches.id')
            ->join('customers', 'customer_ratings.customer_id', '=', 'customers.id')
            ->join('laundries', 'customer_ratings.laundry_id', '=', 'laundries.id')
            ->whereBetween('customer_ratings.created_at', [$startDate, $endDate])
            ->when($branchId, function($query) use ($branchId) {
                return $query->where('customer_ratings.branch_id', $branchId);
            })
            ->when($rating, function($query) use ($rating) {
                return $query->where('customer_ratings.rating', $rating);
            })
            ->select(
                'branches.name as branch_name',
                'branches.code as branch_code',
                'customers.name as customer_name',
                'customers.email as customer_email',
                'customers.phone as customer_phone',
                'customer_ratings.rating',
                'customer_ratings.comment',
                'customer_ratings.created_at',
                'laundries.tracking_number'
            )
            ->orderBy('customer_ratings.created_at', 'desc')
            ->get();

        $filename = 'branch-ratings-' . now()->format('Y-m-d') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        return response()->stream(function() use ($ratings) {
            $handle = fopen('php://output', 'w');
            
            // Add headers
            fputcsv($handle, [
                'Branch Name',
                'Branch Code',
                'Customer Name',
                'Customer Email',
                'Customer Phone',
                'Rating',
                'Comment',
                'Order #',
                'Date'
            ]);
            
            // Add data
            foreach ($ratings as $rating) {
                fputcsv($handle, [
                    $rating->branch_name,
                    $rating->branch_code,
                    $rating->customer_name,
                    $rating->customer_email,
                    $rating->customer_phone,
                    $rating->rating . ' ★',
                    $rating->comment,
                    $rating->tracking_number,
                    Carbon::parse($rating->created_at)->format('Y-m-d H:i:s'),
                ]);
            }
            
            fclose($handle);
        }, 200, $headers);
    }

    /**
     * Export report
     */
    public function export(Request $request)
    {
        $type = $request->get('type', 'laundries');
        $format = $request->get('format', 'csv');

        $filename = "{$type}_report_" . now()->format('Y-m-d') . ".{$format}";
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        switch ($type) {
            case 'laundries':
                return $this->exportLaundries($request, $headers);
            case 'revenue':
                return $this->exportRevenue($request, $headers);
            case 'customers':
                return $this->exportCustomers($request, $headers);
            case 'branch-ratings':
                return $this->exportBranchRatings($request);
            default:
                return redirect()->back()->with('error', 'Invalid export type');
        }
    }

    /**
     * Export laundries to CSV
     */
    private function exportLaundries(Request $request, array $headers)
    {
        $startDate = $request->get('start_date', now()->startOfMonth());
        $endDate = $request->get('end_date', now()->endOfMonth());

        $laundries = Laundry::whereBetween('created_at', [$startDate, $endDate])
            ->with(['customer', 'branch'])
            ->get();

        return response()->stream(function() use ($laundries) {
            $file = fopen('php://output', 'w');

            // Header row
            fputcsv($file, [
                'Laundry ID',
                'Tracking Number',
                'Customer',
                'Branch',
                'Status',
                'Payment Status',
                'Amount',
                'Date'
            ]);

            // Data rows
            foreach ($laundries as $laundry) {
                fputcsv($file, [
                    $laundry->id,
                    $laundry->tracking_number,
                    $laundry->customer->name ?? 'N/A',
                    $laundry->branch->name ?? 'N/A',
                    $laundry->status,
                    $laundry->payment_status,
                    $laundry->total_amount,
                    $laundry->created_at->format('Y-m-d H:i:s'),
                ]);
            }

            fclose($file);
        }, 200, $headers);
    }

    /**
     * Export revenue to CSV
     */
    private function exportRevenue(Request $request, array $headers)
    {
        $startDate = $request->get('start_date', now()->startOfMonth());
        $endDate = $request->get('end_date', now()->endOfMonth());

        $data = Laundry::where('payment_status', 'paid')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('SUM(total_amount) as revenue'),
                DB::raw('COUNT(*) as laundries')
            )
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->get();

        return response()->stream(function() use ($data) {
            $file = fopen('php://output', 'w');

            fputcsv($file, ['Date', 'Revenue', 'Laundries']);

            foreach ($data as $row) {
                fputcsv($file, [
                    $row->date,
                    $row->revenue,
                    $row->laundries,
                ]);
            }

            fclose($file);
        }, 200, $headers);
    }

    /**
     * Export customers to CSV
     */
    private function exportCustomers(Request $request, array $headers)
    {
        $dateFrom = $this->getDateFromFilter($request);
        $dateTo = $this->getDateToFilter($request);

        $customers = Customer::withCount(['laundries' => function($q) use ($dateFrom, $dateTo) {
            $q->whereBetween('created_at', [$dateFrom, $dateTo]);
        }])
        ->with(['laundries' => function($q) use ($dateFrom, $dateTo) {
            $q->where('payment_status', 'paid')->whereBetween('created_at', [$dateFrom, $dateTo]);
        }, 'ratings'])
        ->get();

        return response()->stream(function() use ($customers) {
            $file = fopen('php://output', 'w');

            fputcsv($file, [
                'Customer ID',
                'Name',
                'Email',
                'Phone',
                'Total Laundries',
                'Total Spent',
                'Avg Rating',
                'Rating Count',
                'Registration Date'
            ]);

            foreach ($customers as $customer) {
                $avgRating = $customer->ratings?->avg('rating') ?? 0;
                $ratingCount = $customer->ratings?->count() ?? 0;

                fputcsv($file, [
                    $customer->id,
                    $customer->name,
                    $customer->email,
                    $customer->phone,
                    $customer->laundries_count,
                    $customer->laundries()->where('payment_status', 'paid')->sum('total_amount'),
                    round($avgRating, 1),
                    $ratingCount,
                    $customer->created_at->format('Y-m-d'),
                ]);
            }

            fclose($file);
        }, 200, $headers);
    }
}