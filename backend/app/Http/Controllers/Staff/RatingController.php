<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\CustomerRating;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RatingController extends Controller
{
    /**
     * Display staff ratings dashboard
     */
    public function index(Request $request)
    {
        $staff = Auth::user();
        
        // Date filtering
        $dateFrom = $request->get('date_from', now()->startOfMonth());
        $dateTo = $request->get('date_to', now()->endOfMonth());
        
        // Get ratings where this staff was rated
        $myRatings = CustomerRating::with(['customer', 'laundry', 'branch'])
            ->where('staff_id', $staff->id)
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->orderBy('created_at', 'desc')
            ->paginate(20);
        
        // Calculate statistics
        $stats = [
            'average_rating' => CustomerRating::where('staff_id', $staff->id)
                ->whereBetween('created_at', [$dateFrom, $dateTo])
                ->avg('rating') ?? 0,
            
            'total_ratings' => CustomerRating::where('staff_id', $staff->id)
                ->whereBetween('created_at', [$dateFrom, $dateTo])
                ->count(),
            
            'pending_responses' => CustomerRating::where('staff_id', $staff->id)
                ->whereNull('staff_response')
                ->count(),
            
            'high_ratings' => CustomerRating::where('staff_id', $staff->id)
                ->where('rating', '>=', 4)
                ->whereBetween('created_at', [$dateFrom, $dateTo])
                ->count(),
            
            'low_ratings' => CustomerRating::where('staff_id', $staff->id)
                ->where('rating', '<=', 2)
                ->whereBetween('created_at', [$dateFrom, $dateTo])
                ->count(),
        ];
        
        // Get category averages from JSON
        $categoryAverages = $this->getCategoryAverages($staff->id, $dateFrom, $dateTo);
        
        return view('staff.ratings.index', compact('myRatings', 'stats', 'categoryAverages', 'dateFrom', 'dateTo'));
    }
    
    /**
     * Show form to respond to a rating
     */
    public function showResponseForm(CustomerRating $rating)
    {
        // Ensure staff can only respond to their own ratings
        if ($rating->staff_id !== Auth::id()) {
            abort(403, 'You can only respond to your own ratings.');
        }
        
        return view('staff.ratings.respond', compact('rating'));
    }
    
    /**
     * Submit response to rating
     */
    public function submitResponse(Request $request, CustomerRating $rating)
    {
        if ($rating->staff_id !== Auth::id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        
        $request->validate([
            'response' => 'required|string|max:1000',
        ]);
        
        $rating->update([
            'staff_response' => $request->response,
            'responded_at' => now(),
        ]);
        
        return redirect()->route('staff.ratings.index')
            ->with('success', 'Response submitted successfully');
    }
    
    /**
     * Get average ratings by category from JSON field
     */
    private function getCategoryAverages($staffId, $dateFrom, $dateTo)
    {
        $ratings = CustomerRating::where('staff_id', $staffId)
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->whereNotNull('staff_ratings')
            ->get();
        
        $categories = [];
        $counts = [];
        
        foreach ($ratings as $rating) {
            if ($rating->staff_ratings) {
                foreach ($rating->staff_ratings as $category => $value) {
                    if (!isset($categories[$category])) {
                        $categories[$category] = 0;
                        $counts[$category] = 0;
                    }
                    $categories[$category] += $value;
                    $counts[$category]++;
                }
            }
        }
        
        $averages = [];
        foreach ($categories as $category => $total) {
            $averages[$category] = round($total / $counts[$category], 1);
        }
        
        return $averages;
    }
    
    /**
     * API endpoint for real-time notifications
     */
    public function getNewRatings(Request $request)
    {
        $lastChecked = $request->get('last_checked', now()->subMinutes(5));
        
        $newRatings = CustomerRating::where('staff_id', Auth::id())
            ->where('created_at', '>', $lastChecked)
            ->with('customer')
            ->get();
        
        return response()->json([
            'count' => $newRatings->count(),
            'ratings' => $newRatings
        ]);
    }
}