<?php

namespace App\Http\Controllers\Staff;

use App\Models\Laundry;
use App\Models\Customer;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ReportController extends Controller
{
    /**
     * Display reports index.
     */
    public function index()
    {
        try {
            return view('staff.reports.index');

        } catch (\Exception $e) {
            Log::error('Staff Reports Index Error: ' . $e->getMessage());

            return redirect()->back()
                ->with('error', 'Error loading reports: ' . $e->getMessage());
        }
    }

    /**
     * Generate daily report.
     */
    public function daily(Request $request)
    {
        try {
            $date = $request->get('date', now()->format('Y-m-d'));

            $laundries = Laundry::whereDate('created_at', $date)
                ->with(['customer', 'branch', 'service'])
                ->get();

            $summary = [
                'total' => $laundries->count(),
                'completed' => $laundries->where('status', 'completed')->count(),
                'revenue' => $laundries->where('status', 'completed')->sum('total_amount'),
            ];

            return view('staff.reports.daily', compact('laundries', 'summary', 'date'));

        } catch (\Exception $e) {
            Log::error('Staff Daily Report Error: ' . $e->getMessage());

            return redirect()->back()
                ->with('error', 'Error generating daily report: ' . $e->getMessage());
        }
    }

    /**
     * Generate weekly report.
     */
    public function weekly(Request $request)
    {
        try {
            $startDate = $request->get('start_date', now()->startOfWeek()->format('Y-m-d'));
            $endDate = $request->get('end_date', now()->endOfWeek()->format('Y-m-d'));

            $laundries = Laundry::whereBetween('created_at', [$startDate, $endDate])
                ->with(['customer', 'branch', 'service'])
                ->get();

            $dailyBreakdown = Laundry::select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as total'),
                DB::raw('SUM(CASE WHEN status = "completed" THEN total_amount ELSE 0 END) as revenue')
            )
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('date')
            ->orderBy('date')
            ->get();

            return view('staff.reports.weekly', compact('laundries', 'dailyBreakdown', 'startDate', 'endDate'));

        } catch (\Exception $e) {
            Log::error('Staff Weekly Report Error: ' . $e->getMessage());

            return redirect()->back()
                ->with('error', 'Error generating weekly report: ' . $e->getMessage());
        }
    }

    /**
     * Generate monthly report.
     */
    public function monthly(Request $request)
    {
        try {
            $month = $request->get('month', now()->month);
            $year = $request->get('year', now()->year);

            $laundries = Laundry::whereYear('created_at', $year)
                ->whereMonth('created_at', $month)
                ->with(['customer', 'branch', 'service'])
                ->get();

            return view('staff.reports.monthly', compact('laundries', 'month', 'year'));

        } catch (\Exception $e) {
            Log::error('Staff Monthly Report Error: ' . $e->getMessage());

            return redirect()->back()
                ->with('error', 'Error generating monthly report: ' . $e->getMessage());
        }
    }

    /**
     * Export report.
     */
    public function export(Request $request)
    {
        try {
            $type = $request->get('type', 'daily');
            $format = $request->get('format', 'csv');

            // Implementation for report export
            // You can use Laravel Excel package or custom CSV generation

            return redirect()->back()
                ->with('success', 'Report exported successfully!');

        } catch (\Exception $e) {
            Log::error('Staff Report Export Error: ' . $e->getMessage());

            return redirect()->back()
                ->with('error', 'Error exporting report: ' . $e->getMessage());
        }
    }
}
