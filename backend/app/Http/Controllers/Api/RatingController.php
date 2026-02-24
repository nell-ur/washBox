<?php
// app/Http/Controllers/Api/Customer/RatingController.php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Models\CustomerRating;
use App\Models\Laundry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RatingController extends Controller
{
    /**
     * Get customer's ratings (both laundry and branch)
     */
    public function index()
    {
        try {
            $customer = Auth::guard('sanctum')->user();
            
            $ratings = CustomerRating::where('customer_id', $customer->id)
                ->with(['branch', 'laundry'])
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function($rating) {
                    return [
                        'id' => $rating->id,
                        'laundry_id' => $rating->laundry_id,
                        'branch_id' => $rating->branch_id,
                        'branch_name' => $rating->branch?->name,
                        'tracking_number' => $rating->laundry?->tracking_number,
                        'service_name' => $rating->laundry?->service_name,
                        'rating' => $rating->rating,
                        'comment' => $rating->comment,
                        'created_at' => $rating->created_at,
                        'type' => $rating->laundry_id ? 'laundry' : 'branch',
                    ];
                });

            // Calculate stats
            $laundryRatings = $ratings->where('type', 'laundry');
            $branchRatings = $ratings->where('type', 'branch');

            $stats = [
                'averageRating' => $ratings->avg('rating') ?? 0,
                'totalRatings' => $ratings->count(),
                'laundryRatings' => $laundryRatings->count(),
                'branchRatings' => $branchRatings->count(),
                'distribution' => [
                    5 => $ratings->where('rating', 5)->count(),
                    4 => $ratings->where('rating', 4)->count(),
                    3 => $ratings->where('rating', 3)->count(),
                    2 => $ratings->where('rating', 2)->count(),
                    1 => $ratings->where('rating', 1)->count(),
                ],
            ];

            return response()->json([
                'success' => true,
                'data' => [
                    'ratings' => $ratings,
                    'stats' => $stats
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch ratings'
            ], 500);
        }
    }

    /**
     * Store a laundry rating
     */
    public function store(Request $request)
    {
        $request->validate([
            'laundry_id' => 'required|exists:laundries,id',
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:500',
        ]);

        try {
            $customer = Auth::guard('sanctum')->user();
            $laundry = Laundry::findOrFail($request->laundry_id);

            // Check if already rated
            $existing = CustomerRating::where('customer_id', $customer->id)
                ->where('laundry_id', $request->laundry_id)
                ->first();

            if ($existing) {
                return response()->json([
                    'success' => false,
                    'message' => 'You have already rated this laundry'
                ], 400);
            }

            $rating = CustomerRating::create([
                'customer_id' => $customer->id,
                'laundry_id' => $request->laundry_id,
                'branch_id' => $laundry->branch_id,
                'rating' => $request->rating,
                'comment' => $request->comment,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Rating submitted successfully',
                'data' => [
                    'rating' => $rating->load(['branch', 'laundry'])
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to submit rating'
            ], 500);
        }
    }

    /**
     * Get unrated laundries
     */
    public function unratedLaundries()
    {
        try {
            $customer = Auth::guard('sanctum')->user();

            $ratedLaundryIds = CustomerRating::where('customer_id', $customer->id)
                ->whereNotNull('laundry_id')
                ->pluck('laundry_id');

            $unratedLaundries = Laundry::where('customer_id', $customer->id)
                ->where('status', 'completed')
                ->whereNotIn('id', $ratedLaundryIds)
                ->with('branch')
                ->orderBy('completed_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'laundries' => $unratedLaundries
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch unrated laundries'
            ], 500);
        }
    }
}