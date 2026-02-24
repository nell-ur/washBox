<?php

namespace App\Http\Controllers\Staff;

use App\Models\Promotion;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;

class PromotionController extends Controller
{
    /**
     * Display a listing of promotions for staff.
     */
    public function index()
    {
        try {
            $promotions = Promotion::withCount('laundries as times_used')
                ->latest()
                ->get();

            return view('staff.promotions.index', compact('promotions'));

        } catch (\Exception $e) {
            Log::error('Staff Promotion Index Error: ' . $e->getMessage());

            return redirect()->back()
                ->with('error', 'Error loading promotions: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified promotion.
     */
    public function show(Promotion $promotion)
    {
        try {
            // Load promotion with related data
            $promotion->load(['services', 'addOns']);

            // Get usage statistics
            $times_used = $promotion->laundries()->count();
            $total_discount_given = $promotion->laundries()
                ->where('status', 'completed')
                ->sum('discount_amount');

            // Get recent orders that used this promotion
            $recent_orders = $promotion->laundries()
                ->with(['customer', 'branch'])
                ->latest()
                ->limit(5)
                ->get();

            return view('staff.promotions.show', compact(
                'promotion',
                'times_used',
                'total_discount_given',
                'recent_orders'
            ));

        } catch (\Exception $e) {
            Log::error('Staff Promotion Show Error: ' . $e->getMessage());

            return redirect()->route('staff.promotions.index')
                ->with('error', 'Error loading promotion details: ' . $e->getMessage());
        }
    }
}
