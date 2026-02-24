<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CustomerRating;
use App\Models\Laundry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class CustomerRatingController extends Controller
{
    /**
     * GET /v1/customer/ratings
     *
     * List all ratings by the authenticated customer,
     * along with summary stats (average, total, distribution).
     */
    public function index(Request $request)
    {
        try {
            $customer = $request->user();

            $ratings = CustomerRating::where('customer_id', $customer->id)
                ->with([
                    // FIXED: removed 'service_name' (not a column), added 'service_id' for nested eager load
                    'laundry:id,tracking_number,service_id,weight,total_amount,branch_id,created_at',
                    'laundry.service:id,name',
                    'branch:id,name',
                ])
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($rating) {
                    return [
                        'id'               => $rating->id,
                        'laundry_id'       => $rating->laundry_id,
                        'tracking_number'  => $rating->laundry?->tracking_number,
                        'service_name'     => $rating->laundry?->service?->name ?? 'Laundry Service',
                        'branch_name'      => $rating->branch?->name,
                        'weight'           => $rating->laundry?->weight ?? 0,
                        'total_amount'     => $rating->laundry?->total_amount ?? 0,
                        'rating'           => $rating->rating,
                        'comment'          => $rating->comment,
                        'created_at'       => $rating->created_at->toIso8601String(),
                    ];
                });

            // Build stats
            $totalRatings = $ratings->count();
            $averageRating = $totalRatings > 0 ? round($ratings->avg('rating'), 1) : 0;

            $distribution = [];
            for ($i = 1; $i <= 5; $i++) {
                $distribution[$i] = $ratings->where('rating', $i)->count();
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'ratings' => $ratings->values(),
                    'stats' => [
                        'averageRating'  => $averageRating,
                        'totalRatings'   => $totalRatings,
                        'distribution'   => $distribution,
                    ],
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching customer ratings: ' . $e->getMessage(), [
                'customer_id' => $request->user()?->id,
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch ratings.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * POST /v1/customer/ratings
     *
     * Submit a new rating for a completed laundry.
     */
    public function store(Request $request)
    {
        $customer = $request->user();

        $validator = Validator::make($request->all(), [
            'laundry_id' => 'required|exists:laundries,id',
            'rating'     => 'required|integer|min:1|max:5',
            'comment'    => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        // Verify the laundry belongs to the customer
        $laundry = Laundry::where('id', $request->laundry_id)
            ->where('customer_id', $customer->id)
            ->first();

        if (!$laundry) {
            return response()->json([
                'success' => false,
                'message' => 'Laundry not found or does not belong to you.',
            ], 404);
        }

        // Only allow rating completed laundries (case-insensitive)
        if (strtolower($laundry->status) !== 'completed') {
            return response()->json([
                'success' => false,
                'message' => 'You can only rate completed laundries.',
            ], 422);
        }

        // Check if already rated (unique constraint: laundry_id + customer_id)
        $existingRating = CustomerRating::where('laundry_id', $laundry->id)
            ->where('customer_id', $customer->id)
            ->first();

        if ($existingRating) {
            return response()->json([
                'success' => false,
                'message' => 'You have already rated this laundry.',
            ], 409);
        }

        // Create the rating
        $rating = CustomerRating::create([
            'laundry_id'  => $laundry->id,
            'customer_id' => $customer->id,
            'branch_id'   => $laundry->branch_id,
            'rating'      => $request->rating,
            'comment'     => $request->comment,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Rating submitted successfully.',
            'data'    => [
                'rating' => [
                    'id'        => $rating->id,
                    'rating'    => $rating->rating,
                    'comment'   => $rating->comment,
                    'created_at' => $rating->created_at->toIso8601String(),
                ],
            ],
        ], 201);
    }

    /**
     * GET /v1/customer/ratings/check/{laundryId}
     *
     * Check if the authenticated customer has already rated a specific laundry.
     */
    public function check(Request $request, $laundryId)
    {
        $customer = $request->user();

        $existing = CustomerRating::where('laundry_id', $laundryId)
            ->where('customer_id', $customer->id)
            ->first();

        $hasRated = $existing !== null;

        $rating = null;
        if ($hasRated) {
            $rating = [
                'id'      => $existing->id,
                'rating'  => $existing->rating,
                'comment' => $existing->comment,
            ];
        }

        return response()->json([
            'success' => true,
            'data' => [
                'has_rated' => $hasRated,
                'rating'    => $rating,
            ],
        ]);
    }

    /**
     * GET /v1/customer/unrated-laundries
     *
     * Get all completed laundries that the customer hasn't rated yet.
     */
    public function unratedLaundries(Request $request)
    {
        try {
            $customer = $request->user();

            // Get IDs of laundries already rated by this customer
            $ratedLaundryIds = CustomerRating::where('customer_id', $customer->id)
                ->pluck('laundry_id');

            // FIXED: case-insensitive status check using whereRaw
            $unrated = Laundry::where('customer_id', $customer->id)
                ->whereRaw('LOWER(status) = ?', ['completed'])
                ->whereNotIn('id', $ratedLaundryIds)
                ->with(['branch:id,name', 'service:id,name'])
                ->orderBy('updated_at', 'desc')
                ->limit(20)
                ->get()
                ->map(function ($laundry) {
                    return [
                        'id'               => $laundry->id,
                        'tracking_number'  => $laundry->tracking_number,
                        'service_name'     => $laundry->service?->name ?? 'Laundry Service',
                        'branch_name'      => $laundry->branch?->name ?? 'Branch',
                        'total_amount'     => $laundry->total_amount,
                        'completed_at'     => $laundry->updated_at->toIso8601String(),
                        'created_at'       => $laundry->created_at->toIso8601String(),
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => [
                    'laundries' => $unrated,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching unrated laundries: ' . $e->getMessage(), [
                'customer_id' => $request->user()?->id,
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch unrated laundries.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * PUT /v1/customer/ratings/{id}
     *
     * Update an existing rating (allows editing within 24 hours).
     */
    public function update(Request $request, $id)
    {
        $customer = $request->user();

        $rating = CustomerRating::where('id', $id)
            ->where('customer_id', $customer->id)
            ->first();

        if (!$rating) {
            return response()->json([
                'success' => false,
                'message' => 'Rating not found.',
            ], 404);
        }

        // Only allow edits within 24 hours
        if ($rating->created_at->diffInHours(now()) > 24) {
            return response()->json([
                'success' => false,
                'message' => 'Ratings can only be edited within 24 hours of submission.',
            ], 422);
        }

        $validator = Validator::make($request->all(), [
            'rating'  => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $rating->update([
            'rating'  => $request->rating,
            'comment' => $request->comment,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Rating updated successfully.',
            'data'    => [
                'rating' => [
                    'id'        => $rating->id,
                    'rating'    => $rating->rating,
                    'comment'   => $rating->comment,
                    'created_at' => $rating->created_at->toIso8601String(),
                    'updated_at' => $rating->updated_at->toIso8601String(),
                ],
            ],
        ]);
    }

    /**
     * DELETE /v1/customer/ratings/{id}
     *
     * Delete a rating (only within 24 hours).
     */
    public function destroy(Request $request, $id)
    {
        $customer = $request->user();

        $rating = CustomerRating::where('id', $id)
            ->where('customer_id', $customer->id)
            ->first();

        if (!$rating) {
            return response()->json([
                'success' => false,
                'message' => 'Rating not found.',
            ], 404);
        }

        // Only allow deletion within 24 hours
        if ($rating->created_at->diffInHours(now()) > 24) {
            return response()->json([
                'success' => false,
                'message' => 'Ratings can only be deleted within 24 hours of submission.',
            ], 422);
        }

        $rating->delete();

        return response()->json([
            'success' => true,
            'message' => 'Rating deleted successfully.',
        ]);
    }
}
