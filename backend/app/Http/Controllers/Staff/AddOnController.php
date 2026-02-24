<?php

namespace App\Http\Controllers\Staff;

use App\Models\AddOn;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;

class AddOnController extends Controller
{
    /**
     * Display a listing of add-ons for staff.
     */
    public function index()
    {
        try {
            $addons = AddOn::withCount('laundries as times_used')
                ->latest()
                ->get();

            return view('staff.addons.index', compact('addons'));

        } catch (\Exception $e) {
            Log::error('Staff AddOn Index Error: ' . $e->getMessage());

            return redirect()->back()
                ->with('error', 'Error loading add-ons: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified add-on.
     */
    public function show(AddOn $addon)
    {
        try {
            // Get usage statistics
            $times_used = $addon->laundries()->count();
            $total_revenue = $addon->laundries()
                ->where('status', 'completed')
                ->sum('price_at_purchase');

            // Get recent orders that used this add-on
            $recent_orders = $addon->laundries()
                ->with(['customer', 'branch'])
                ->latest()
                ->limit(5)
                ->get();

            return view('staff.addons.show', compact(
                'addon',
                'times_used',
                'total_revenue',
                'recent_orders'
            ));

        } catch (\Exception $e) {
            Log::error('Staff AddOn Show Error: ' . $e->getMessage());

            return redirect()->route('staff.addons.index')
                ->with('error', 'Error loading add-on details: ' . $e->getMessage());
        }
    }
}
