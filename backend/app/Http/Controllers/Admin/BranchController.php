<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\laundry;
use App\Models\User;
use App\Services\GeocodingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class BranchController extends Controller
{
    /**
     * Display a listing of branches with statistics
     */
    public function index()
    {
        // Get all branches with stats for the current month
        $branches = Branch::withCount(['laundries as laundries_mtd' => function($query) {
                $query->whereMonth('created_at', Carbon::now()->month);
            }])
            ->withAvg('ratings', 'rating')      // avg rating per branch (single SQL aggregate)
            ->withCount('ratings')               // total review count per branch
            ->with(['ratings' => function($q) { // lightweight eager load for per-star breakdown
                $q->select('id', 'branch_id', 'rating');
            }])
            ->get()
            ->map(function($branch) {
                // Calculate MTD revenue
                $branch->revenue_mtd = laundry::where('branch_id', $branch->id)
                    ->whereMonth('created_at', Carbon::now()->month)
                    ->sum('total_amount');

                // Count active staff
                $branch->active_staff = User::where('branch_id', $branch->id)
                    ->where('role', 'staff')
                    ->where('is_active', true)
                    ->count();

                // Rating convenience fields for the blade
                $branch->avg_rating    = round((float) ($branch->ratings_avg_rating ?? 0), 1);
                $branch->total_ratings = (int) ($branch->ratings_count ?? 0);

                // Per-star breakdown [5 => n, 4 => n, 3 => n, 2 => n, 1 => n]
                $counts = $branch->ratings->groupBy('rating')->map->count();
                $branch->rating_distribution = collect(range(5, 1))
                    ->mapWithKeys(fn($star) => [$star => $counts->get($star, 0)])
                    ->toArray();

                return $branch;
            });

        // Calculate network-wide statistics
        $total_laundries = Laundry::count();
        $total_revenue = Laundry::sum('total_amount');

        return view('admin.branches.index', compact('branches', 'total_laundries', 'total_revenue'));
    }

    /**
     * Show the form for creating a new branch
     */
    public function create()
    {
        $defaultProvince = 'Negros Oriental';
        return view('admin.branches.create', compact('defaultProvince'));
    }

    /**
     * Store a newly created branch in storage
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:10|unique:branches,code',
            'address' => 'required|string',
            'city' => 'required|string|max:255',
            'province' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'email' => 'nullable|email|max:255',
            'manager' => 'nullable|string|max:255',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'operating_hours' => 'nullable|string|max:500',
            'is_active' => 'nullable|boolean',
        ]);

        // Set is_active (default to true if not provided)
        $validated['is_active'] = $request->has('is_active') ? true : false;

        // Auto-geocode if coordinates not provided by map picker
        if (empty($validated['latitude']) || empty($validated['longitude'])) {
            $validated = $this->autoGeocode($validated);
        }

        Branch::create($validated);

        $this->clearDashboardCache();

        return redirect()->route('admin.branches.index')
            ->with('success', 'Branch created successfully!');
    }

    /**
     * Display the specified branch with analytics
     */
    public function show(Branch $branch)
    {
        // Load relationships for detailed analytics
        $branch->load(['laundries', 'staff']);

        // Calculate branch statistics
        $stats = [
            'total_laundries' => $branch->laundries()->count(),
            'completed_laundries' => $branch->laundries()->where('status', 'completed')->count(),
            'total_revenue' => $branch->laundries()->sum('total_amount'),
            'avg_laundry_value' => $branch->laundries()->avg('total_amount') ?? 0,
            'staff_count' => $branch->staff()->count(),
            'active_staff' => $branch->staff()->where('is_active', true)->count(),
            'laundries_mtd' => $branch->laundries()->whereMonth('created_at', Carbon::now()->month)->count(),
            'revenue_mtd' => $branch->laundries()->whereMonth('created_at', Carbon::now()->month)->sum('total_amount'),
        ];

        // Recent laundries
        $recent_laundries = $branch->laundries()
            ->with(['customer', 'service'])
            ->latest()
            ->take(10)
            ->get();

        return view('admin.branches.show', compact('branch', 'stats', 'recent_laundries'));
    }

    /**
     * Show the form for editing the specified branch
     */
    public function edit(Branch $branch)
    {
        // Convert JSON operating_hours to simple text for display
        if ($branch->operating_hours && is_array($branch->operating_hours)) {
            $branch->operating_hours = $this->convertJsonToText($branch->operating_hours);
        }

        // Add MTD stats for display
        $branch->laundries_mtd = $branch->laundries()->whereMonth('created_at', Carbon::now()->month)->count();
        $branch->revenue_mtd = $branch->laundries()->whereMonth('created_at', Carbon::now()->month)->sum('total_amount');
        $branch->active_staff = User::where('branch_id', $branch->id)
            ->where('role', 'staff')
            ->where('is_active', true)
            ->count();

        return view('admin.branches.edit', compact('branch'));
    }

    /**
     * Update the specified branch in storage
     */
    public function update(Request $request, Branch $branch)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:10|unique:branches,code,' . $branch->id,
            'address' => 'required|string',
            'city' => 'required|string|max:255',
            'province' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'email' => 'nullable|email|max:255',
            'manager' => 'nullable|string|max:255',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'operating_hours' => 'nullable|string|max:500',
            'is_active' => 'nullable|boolean',
        ]);

        // Update is_active status
        $validated['is_active'] = $request->has('is_active') ? true : false;

        // Auto-geocode if coordinates were cleared or never set
        if (empty($validated['latitude']) || empty($validated['longitude'])) {
            $validated = $this->autoGeocode($validated);
        }

        $branch->update($validated);

        $this->clearDashboardCache();

        return redirect()->route('admin.branches.index')
            ->with('success', 'Branch updated successfully!');
    }

    /**
     * Remove the specified branch from storage
     */
    public function destroy(Branch $branch)
    {
        // Check if branch has any laundries
        if ($branch->laundries()->count() > 0) {
            return redirect()->route('admin.branches.index')
                ->with('error', 'Cannot delete branch with existing laundries. Please deactivate instead.');
        }

        // Check if branch has any staff
        if ($branch->staff()->count() > 0) {
            return redirect()->route('admin.branches.index')
                ->with('error', 'Cannot delete branch with assigned staff. Please reassign or deactivate staff first.');
        }

        $branch->delete();

        $this->clearDashboardCache();

        return redirect()->route('admin.branches.index')
            ->with('success', 'Branch deleted successfully!');
    }

    /**
     * Toggle branch active/inactive status
     */
    public function toggleStatus(Branch $branch)
    {
        $branch->update(['is_active' => !$branch->is_active]);

        $this->clearDashboardCache();

        $status = $branch->is_active ? 'activated' : 'deactivated';

        return redirect()->route('admin.branches.index')
            ->with('success', "Branch {$status} successfully!");
    }

    /**
     * Deactivate a branch
     */
    public function deactivate(Branch $branch)
    {
        $branch->update(['is_active' => false]);
        $this->clearDashboardCache();
        return redirect()->route('admin.branches.index')
            ->with('success', 'Branch deactivated successfully.');
    }

    /**
     * Activate a branch
     */
    public function activate(Branch $branch)
    {
        $branch->update(['is_active' => true]);
        $this->clearDashboardCache();
        return redirect()->route('admin.branches.index')
            ->with('success', 'Branch activated successfully.');
    }

    /**
     * Get branch staff
     */
    public function staff(Branch $branch)
    {
        $staff = User::where('branch_id', $branch->id)
            ->where('role', 'staff')
            ->with(['laundries' => function($query) {
                $query->whereMonth('created_at', Carbon::now()->month);
            }])
            ->get()
            ->map(function($user) {
                $user->laundries_mtd = $user->laundries->count();
                $user->revenue_mtd = $user->laundries->sum('total_amount');
                return $user;
            });

        return view('admin.branches.staff', compact('branch', 'staff'));
    }

    /**
     * Get branch analytics
     */
    public function analytics(Branch $branch)
    {
        // Get last 6 months of data
        $months = [];
        $laundriesData = [];
        $revenueData = [];

        for ($i = 5; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            $monthName = $month->format('M Y');

            $laundriesCount = $branch->laundries()
                ->whereMonth('created_at', $month->month)
                ->whereYear('created_at', $month->year)
                ->count();

            $revenue = $branch->laundries()
                ->whereMonth('created_at', $month->month)
                ->whereYear('created_at', $month->year)
                ->sum('total_amount');

            $months[] = $monthName;
            $laundriesData[] = $laundriesCount;
            $revenueData[] = $revenue;
        }

        // Get daily data for current month
        $days = [];
        $dailyLaundries = [];
        $dailyRevenue = [];

        for ($i = 6; $i >= 0; $i--) {
            $day = Carbon::now()->subDays($i);
            $dayName = $day->format('D');

            $laundriesCount = $branch->laundries()
                ->whereDate('created_at', $day->toDateString())
                ->count();

            $revenue = $branch->laundries()
                ->whereDate('created_at', $day->toDateString())
                ->sum('total_amount');

            $days[] = $dayName;
            $dailyLaundries[] = $laundriesCount;
            $dailyRevenue[] = $revenue;
        }

        // Top services
        $topServices = $branch->laundries()
            ->with('service')
            ->selectRaw('service_id, count(*) as laundry_count, sum(total_amount) as revenue')
            ->groupBy('service_id')
            ->orderByDesc('laundry_count')
            ->take(5)
            ->get();

        return view('admin.branches.analytics', compact(
            'branch',
            'months',
            'laundriesData',
            'revenueData',
            'days',
            'dailyLaundries',
            'dailyRevenue',
            'topServices'
        ));
    }

    /**
     * Auto-geocode branch address to coordinates using GeocodingService
     * Called as a server-side fallback when map picker wasn't used
     */
    private function autoGeocode(array $data): array
    {
        $addressParts = array_filter([
            $data['address'] ?? '',
            $data['city'] ?? '',
            $data['province'] ?? '',
            'Philippines'
        ]);

        if (count($addressParts) <= 1) {
            return $data;
        }

        try {
            $geocoder = app(GeocodingService::class);
            $result = $geocoder->geocodeAddress(implode(', ', $addressParts));

            if (!empty($result['success']) && $result['success'] === true) {
                $data['latitude'] = $result['latitude'];
                $data['longitude'] = $result['longitude'];
            }
        } catch (\Exception $e) {
            // Geocoding is best-effort; don't block branch creation
            \Illuminate\Support\Facades\Log::warning('Branch auto-geocode failed', [
                'address' => implode(', ', $addressParts),
                'error' => $e->getMessage(),
            ]);
        }

        return $data;
    }

    /**
     * Clear dashboard cache so map markers update immediately
     */
    private function clearDashboardCache()
    {
        Cache::forget('dashboard_stats_' . Auth::id());

        $adminIds = User::where('role', 'admin')->pluck('id');
        foreach ($adminIds as $id) {
            Cache::forget('dashboard_stats_' . $id);
        }
    }

    /**
     * Convert JSON operating hours to simple text
     */
    private function convertJsonToText($hours): string
    {
        if (empty($hours)) {
            return '';
        }

        // Check if all weekdays have same hours
        $weekdays = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
        $weekends = ['Saturday', 'Sunday'];

        $weekdayHours = '';
        $weekendHours = '';

        // Check weekdays
        $firstWeekday = $hours['Monday'] ?? null;
        if ($firstWeekday && !isset($firstWeekday['closed'])) {
            $allSame = true;
            foreach ($weekdays as $day) {
                if (!isset($hours[$day]) ||
                    ($hours[$day]['open'] ?? '') !== ($firstWeekday['open'] ?? '') ||
                    ($hours[$day]['close'] ?? '') !== ($firstWeekday['close'] ?? '')) {
                    $allSame = false;
                    break;
                }
            }

            if ($allSame) {
                $open = $this->formatTime($firstWeekday['open']);
                $close = $this->formatTime($firstWeekday['close']);
                $weekdayHours = "Monday-Friday: {$open} - {$close}";
            }
        }

        // Check weekends
        $firstWeekend = $hours['Saturday'] ?? null;
        if ($firstWeekend && !isset($firstWeekend['closed'])) {
            $bothSame = true;
            foreach ($weekends as $day) {
                if (!isset($hours[$day]) ||
                    ($hours[$day]['open'] ?? '') !== ($firstWeekend['open'] ?? '') ||
                    ($hours[$day]['close'] ?? '') !== ($firstWeekend['close'] ?? '')) {
                    $bothSame = false;
                    break;
                }
            }

            if ($bothSame) {
                $open = $this->formatTime($firstWeekend['open']);
                $close = $this->formatTime($firstWeekend['close']);
                $weekendHours = "Saturday-Sunday: {$open} - {$close}";
            }
        }

        // Combine
        if ($weekdayHours && $weekendHours) {
            return "$weekdayHours, $weekendHours";
        } elseif ($weekdayHours) {
            return $weekdayHours;
        } elseif ($weekendHours) {
            return $weekendHours;
        }

        // Fallback: list all days
        $result = [];
        foreach ($hours as $day => $time) {
            if (isset($time['closed']) && $time['closed']) {
                $result[] = ucfirst($day) . ": Closed";
            } elseif (isset($time['open']) && isset($time['close'])) {
                $open = $this->formatTime($time['open']);
                $close = $this->formatTime($time['close']);
                $result[] = ucfirst($day) . ": {$open} - {$close}";
            }
        }

        return implode(', ', $result);
    }

    /**
     * Format time to 12-hour format with AM/PM
     */
    private function formatTime($time): string
    {
        try {
            $dateTime = \DateTime::createFromFormat('H:i', $time);
            if ($dateTime) {
                return $dateTime->format('g:i A');
            }
        } catch (\Exception $e) {
            // Return as-is if conversion fails
        }
        return $time;
    }
}
