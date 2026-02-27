<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\CustomerRating;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class BranchRatingController extends Controller
{
    /**
     * GET /v1/customer/branches
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
                'data'    => ['branches' => $branches],
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching branches: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch branches.',
            ], 500);
        }
    }

    /**
     * GET /v1/customer/branch-ratings
     */
    public function index(Request $request)
    {
        try {
            $customer = $request->user();

            $ratings = CustomerRating::where('customer_id', $customer->id)
                ->whereNotNull('branch_id')
                ->whereNull('laundry_id')
                ->with(['branch:id,name,code,address'])
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(fn($rating) => [
                    'id'          => $rating->id,
                    'branch_id'   => $rating->branch_id,
                    'branch_name' => $rating->branch?->name,
                    'branch_code' => $rating->branch?->code,
                    'rating'      => $rating->rating,
                    'comment'     => $rating->comment,
                    'created_at'  => $rating->created_at->toIso8601String(),
                ]);

            $totalRatings  = $ratings->count();
            $averageRating = $totalRatings > 0 ? round($ratings->avg('rating'), 1) : 0;

            $distribution = [];
            for ($i = 1; $i <= 5; $i++) {
                $distribution[$i] = $ratings->where('rating', $i)->count();
            }

            return response()->json([
                'success' => true,
                'data'    => [
                    'ratings' => $ratings->values(),
                    'stats'   => [
                        'averageRating' => $averageRating,
                        'totalRatings'  => $totalRatings,
                        'distribution'  => $distribution,
                    ],
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching branch ratings: ' . $e->getMessage(), [
                'customer_id' => $request->user()?->id,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch branch ratings.',
            ], 500);
        }
    }

    /**
     * POST /v1/customer/branch-ratings
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

        // One branch rating per customer per branch
        $existingRating = CustomerRating::where('branch_id', $request->branch_id)
            ->where('customer_id', $customer->id)
            ->whereNull('laundry_id')
            ->first();

        if ($existingRating) {
            return response()->json([
                'success' => false,
                'message' => 'You have already rated this branch.',
            ], 409);
        }

        $rating = CustomerRating::create([
            'branch_id'   => $request->branch_id,
            'customer_id' => $customer->id,
            'rating'      => $request->rating,
            'comment'     => $request->comment,
            // laundry_id intentionally null — distinguishes branch from laundry ratings
        ]);

        // Notify admin + branch staff about the new branch rating
        NotificationService::notifyBranchRated($rating);

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
     */
    public function stats(Request $request)
    {
        try {
            $customer = $request->user();

            $branchRatings = CustomerRating::where('customer_id', $customer->id)
                ->whereNotNull('branch_id')
                ->whereNull('laundry_id')
                ->with('branch:id,name')
                ->get();

            $totalRated    = $branchRatings->count();
            $averageRating = $totalRated > 0 ? round($branchRatings->avg('rating'), 1) : 0;

            $recentBranches = $branchRatings
                ->sortByDesc('created_at')
                ->take(5)
                ->values()
                ->map(fn($rating) => [
                    'id'          => $rating->id,
                    'branch_id'   => $rating->branch_id,
                    'branch_name' => $rating->branch?->name,
                    'rating'      => $rating->rating,
                    'comment'     => $rating->comment,
                    'created_at'  => $rating->created_at->toIso8601String(),
                ]);

            return response()->json([
                'success' => true,
                'data'    => [
                    'total_branches_rated'  => $totalRated,
                    'average_branch_rating' => $averageRating,
                    'recent_branches'       => $recentBranches,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching branch stats: ' . $e->getMessage(), [
                'customer_id' => $request->user()?->id,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch branch statistics.',
            ], 500);
        }
    }

    /**
     * PUT /v1/customer/branch-ratings/{id}
     */
    public function update(Request $request, $id)
    {
        $customer = $request->user();

        $rating = CustomerRating::where('id', $id)
            ->where('customer_id', $customer->id)
            ->whereNull('laundry_id')
            ->first();

        if (! $rating) {
            return response()->json(['success' => false, 'message' => 'Branch rating not found.'], 404);
        }

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

        $rating->update(['rating' => $request->rating, 'comment' => $request->comment]);

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
     */
    public function destroy(Request $request, $id)
    {
        $customer = $request->user();

        $rating = CustomerRating::where('id', $id)
            ->where('customer_id', $customer->id)
            ->whereNull('laundry_id')
            ->first();

        if (! $rating) {
            return response()->json(['success' => false, 'message' => 'Branch rating not found.'], 404);
        }

        if ($rating->created_at->diffInHours(now()) > 24) {
            return response()->json([
                'success' => false,
                'message' => 'Branch ratings can only be deleted within 24 hours of submission.',
            ], 422);
        }

        $rating->delete();

        return response()->json(['success' => true, 'message' => 'Branch rating deleted successfully.']);
    }
}
