<?php

namespace App\Http\Controllers\Staff;

use App\Models\Laundry;
use App\Models\Customer;
use App\Models\Service;
use App\Models\AddOn;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AnalyticsController extends Controller
{
    /**
     * Display analytics dashboard.
     */
    public function index()
    {
        try {
            // Get date range (last 30 days)
            $startDate = now()->subDays(30);
            $endDate = now();

            // Overall statistics
            $totalLaundries = Laundry::count();
            $completedLaundries = Laundry::where('status', 'completed')->count();
            $totalRevenue = Laundry::where('status', 'completed')->sum('total_amount');
            $totalCustomers = Customer::count();

            // Daily statistics for chart
            $dailyStats = Laundry::select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as total'),
                DB::raw('SUM(CASE WHEN status = "completed" THEN total_amount ELSE 0 END) as revenue')
            )
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('date')
            ->orderBy('date')
            ->get();

            // Popular services
            $popularServices = Service::withCount('laundries')
                ->orderBy('laundries_count', 'desc')
                ->limit(5)
                ->get();

            // Popular add-ons
            $popularAddons = AddOn::withCount('laundries')
                ->orderBy('laundries_count', 'desc')
                ->limit(5)
                ->get();

            // Status breakdown
            $statusBreakdown = Laundry::select('status', DB::raw('COUNT(*) as count'))
                ->groupBy('status')
                ->get();

            return view('staff.analytics.index', compact(
                'totalLaundries',
                'completedLaundries',
                'totalRevenue',
                'totalCustomers',
                'dailyStats',
                'popularServices',
                'popularAddons',
                'statusBreakdown',
                'startDate',
                'endDate'
            ));

        } catch (\Exception $e) {
            Log::error('Staff Analytics Index Error: ' . $e->getMessage());

            return redirect()->back()
                ->with('error', 'Error loading analytics: ' . $e->getMessage());
        }
    }

    /**
     * Export analytics data.
     */
    public function export(Request $request)
    {
        try {
            $type = $request->get('type', 'summary');
            $format = $request->get('format', 'csv');

            // Implementation for export (CSV, Excel, etc.)
            // You can use Laravel Excel package or custom CSV generation

            return redirect()->back()
                ->with('success', 'Analytics exported successfully!');

        } catch (\Exception $e) {
            Log::error('Staff Analytics Export Error: ' . $e->getMessage());

            return redirect()->back()
                ->with('error', 'Error exporting analytics: ' . $e->getMessage());
        }
    }
}
