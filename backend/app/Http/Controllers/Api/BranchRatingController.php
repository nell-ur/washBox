<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\CustomerRating;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class BranchRatingController extends Controller
{
    /**
     * GET /v1/customer/branches
     *
     * Get all active branches for rating
     */
    public function branches(Request $request)
    {
        try {
            $branches = Branch::where('is_active', true)
                ->select('id', 'name', 'code', 'address', 'phone')
                ->orderBy('name')
                ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'branches' => $branches,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching branches: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch branches.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * GET /v1/customer/branch-ratings
     *
     * List all branch ratings by the authenticated customer
     */
    public function index(Request $request)
    {
        try {
            $customer = $request->user();

            $ratings = CustomerRating::where('customer_id', $customer->id)
                ->whereNotNull('branch_id')
                ->with([
                    'branch:id,name,code,address',
                ])
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($rating) {
                    return [
                        'id'          => $rating->id,
                        'branch_id'   => $rating->branch_id,
                        'branch_name' => $rating->branch?->name,
                        'branch_code' => $rating->branch?->code,
                        'rating'      => $rating->rating,
                        'comment'     => $rating->comment,
                        'created_at'  => $rating->created_at->toIso8601String(),
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
                        'averageRating' => $averageRating,
                        'totalRatings'  => $totalRatings,
                        'distribution'  => $distribution,
                    ],
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching branch ratings: ' . $e->getMessage(), [
                'customer_id' => $request->user()?->id,
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch branch ratings.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * POST /v1/customer/branch-ratings
     *
     * Submit a new branch rating
     */
    public function store(Request $request)
    {
        $customer = $request->user();

        $validator = Validator::make($request->all(), [
            'branch_id' => 'required|exists:branches,id',
            'rating'    => 'required|integer|min:1|max:5',
            'comment'   => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        // Check if already rated this branch
        $existingRating = CustomerRating::where('branch_id', $request->branch_id)
            ->where('customer_id', $customer->id)
            ->whereNull('laundry_id') // Only branch ratings (no laundry_id)
            ->first();

        if ($existingRating) {
            return response()->json([
                'success' => false,
                'message' => 'You have already rated this branch.',
            ], 409);
        }

        // Create the branch rating (no laundry_id)
        $rating = CustomerRating::create([
            'branch_id'   => $request->branch_id,
            'customer_id' => $customer->id,
            'rating'      => $request->rating,
            'comment'     => $request->comment,
            // laundry_id is null for branch ratings
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Branch rating submitted successfully.',
            'data'    => [
                'rating' => [
                    'id'         => $rating->id,
                    'branch_id'  => $rating->branch_id,
                    'rating'     => $rating->rating,
                    'comment'    => $rating->comment,
                    'created_at' => $rating->created_at->toIso8601String(),
                ],
            ],
        ], 201);
    }

    /**
     * GET /v1/customer/branch-ratings/stats
     *
     * Get statistics about the customer's branch ratings
     */
    public function stats(Request $request)
    {
        try {
            $customer = $request->user();

            $branchRatings = CustomerRating::where('customer_id', $customer->id)
                ->whereNotNull('branch_id')
                ->whereNull('laundry_id') // Only branch ratings
                ->with('branch:id,name')
                ->get();

            $totalRated = $branchRatings->count();
            $averageRating = $totalRated > 0 ? round($branchRatings->avg('rating'), 1) : 0;

            // Get 5 most recent branch ratings
            $recentBranches = $branchRatings
                ->sortByDesc('created_at')
                ->take(5)
                ->values()
                ->map(function ($rating) {
                    return [
                        'id'          => $rating->id,
                        'branch_id'   => $rating->branch_id,
                        'branch_name' => $rating->branch?->name,
                        'rating'      => $rating->rating,
                        'comment'     => $rating->comment,
                        'created_at'  => $rating->created_at->toIso8601String(),
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => [
                    'total_branches_rated'  => $totalRated,
                    'average_branch_rating' => $averageRating,
                    'recent_branches'       => $recentBranches,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching branch stats: ' . $e->getMessage(), [
                'customer_id' => $request->user()?->id,
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch branch statistics.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * PUT /v1/customer/branch-ratings/{id}
     *
     * Update an existing branch rating (optional - if you want to allow edits)
     */
    public function update(Request $request, $id)
    {
        $customer = $request->user();

        $rating = CustomerRating::where('id', $id)
            ->where('customer_id', $customer->id)
            ->whereNull('laundry_id') // Only branch ratings
            ->first();

        if (!$rating) {
            return response()->json([
                'success' => false,
                'message' => 'Branch rating not found.',
            ], 404);
        }

        // Only allow edits within 24 hours
        if ($rating->created_at->diffInHours(now()) > 24) {
            return response()->json([
                'success' => false,
                'message' => 'Branch ratings can only be edited within 24 hours of submission.',
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
            'message' => 'Branch rating updated successfully.',
            'data'    => [
                'rating' => [
                    'id'         => $rating->id,
                    'branch_id'  => $rating->branch_id,
                    'rating'     => $rating->rating,
                    'comment'    => $rating->comment,
                    'created_at' => $rating->created_at->toIso8601String(),
                    'updated_at' => $rating->updated_at->toIso8601String(),
                ],
            ],
        ]);
    }

    /**
     * DELETE /v1/customer/branch-ratings/{id}
     *
     * Delete a branch rating (optional)
     */
    public function destroy(Request $request, $id)
    {
        $customer = $request->user();

        $rating = CustomerRating::where('id', $id)
            ->where('customer_id', $customer->id)
            ->whereNull('laundry_id') // Only branch ratings
            ->first();

        if (!$rating) {
            return response()->json([
                'success' => false,
                'message' => 'Branch rating not found.',
            ], 404);
        }

        // Only allow deletion within 24 hours
        if ($rating->created_at->diffInHours(now()) > 24) {
            return response()->json([
                'success' => false,
                'message' => 'Branch ratings can only be deleted within 24 hours of submission.',
            ], 422);
        }

        $rating->delete();

        return response()->json([
            'success' => true,
            'message' => 'Branch rating deleted successfully.',
        ]);
    }
}