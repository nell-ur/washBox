<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CustomerRating;
use App\Models\Laundry;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class CustomerRatingController extends Controller
{
    /**
     * GET /v1/customer/ratings
     */
    public function index(Request $request)
    {
        try {
            $customer = $request->user();

            $ratings = CustomerRating::where('customer_id', $customer->id)
                ->with([
                    'laundry:id,tracking_number,service_id,weight,total_amount,branch_id,created_at',
                    'laundry.service:id,name',
                    'branch:id,name',
                ])
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($rating) {
                    $isBranch = is_null($rating->laundry_id) && ! is_null($rating->branch_id);

                    return [
                        'id'              => $rating->id,
                        'type'            => $isBranch ? 'branch' : 'laundry',
                        'laundry_id'      => $rating->laundry_id,
                        'tracking_number' => $rating->laundry?->tracking_number,
                        'service_name'    => $rating->laundry?->service?->name ?? 'Laundry Service',
                        'branch_id'       => $rating->branch_id,
                        'branch_name'     => $rating->branch?->name,
                        'weight'          => $rating->laundry?->weight ?? 0,
                        'total_amount'    => $rating->laundry?->total_amount ?? 0,
                        'rating'          => $rating->rating,
                        'comment'         => $rating->comment,
                        'created_at'      => $rating->created_at->toIso8601String(),
                    ];
                });

            $totalRatings   = $ratings->count();
            $averageRating  = $totalRatings > 0 ? round($ratings->avg('rating'), 1) : 0;
            $laundryRatings = $ratings->where('type', 'laundry')->count();
            $branchRatings  = $ratings->where('type', 'branch')->count();

            $distribution = [];
            for ($i = 1; $i <= 5; $i++) {
                $distribution[$i] = $ratings->where('rating', $i)->count();
            }

            return response()->json([
                'success' => true,
                'data'    => [
                    'ratings' => $ratings->values(),
                    'stats'   => [
                        'averageRating'  => $averageRating,
                        'totalRatings'   => $totalRatings,
                        'laundryRatings' => $laundryRatings,
                        'branchRatings'  => $branchRatings,
                        'distribution'   => $distribution,
                    ],
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching customer ratings: ' . $e->getMessage(), [
                'customer_id' => $request->user()?->id,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch ratings.',
            ], 500);
        }
    }

    /**
     * POST /v1/customer/ratings
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

        $laundry = Laundry::where('id', $request->laundry_id)
            ->where('customer_id', $customer->id)
            ->first();

        if (! $laundry) {
            return response()->json([
                'success' => false,
                'message' => 'Laundry not found or does not belong to you.',
            ], 404);
        }

        if (strtolower($laundry->status) !== 'completed') {
            return response()->json([
                'success' => false,
                'message' => 'You can only rate completed laundries.',
            ], 422);
        }

        $existingRating = CustomerRating::where('laundry_id', $laundry->id)
            ->where('customer_id', $customer->id)
            ->first();

        if ($existingRating) {
            return response()->json([
                'success' => false,
                'message' => 'You have already rated this laundry.',
            ], 409);
        }

        $rating = CustomerRating::create([
            'laundry_id'  => $laundry->id,
            'customer_id' => $customer->id,
            'branch_id'   => $laundry->branch_id,
            'rating'      => $request->rating,
            'comment'     => $request->comment,
        ]);

        // Notify admin + branch staff about the new laundry rating
        NotificationService::notifyLaundryRated($rating);

        return response()->json([
            'success' => true,
            'message' => 'Rating submitted successfully.',
            'data'    => [
                'rating' => [
                    'id'         => $rating->id,
                    'rating'     => $rating->rating,
                    'comment'    => $rating->comment,
                    'created_at' => $rating->created_at->toIso8601String(),
                ],
            ],
        ], 201);
    }

    /**
     * GET /v1/customer/ratings/check/{laundryId}
     */
    public function check(Request $request, $laundryId)
    {
        $customer = $request->user();

        $existing = CustomerRating::where('laundry_id', $laundryId)
            ->where('customer_id', $customer->id)
            ->first();

        return response()->json([
            'success' => true,
            'data'    => [
                'has_rated' => $existing !== null,
                'rating'    => $existing ? [
                    'id'      => $existing->id,
                    'rating'  => $existing->rating,
                    'comment' => $existing->comment,
                ] : null,
            ],
        ]);
    }

    /**
     * GET /v1/customer/unrated-laundries
     */
    public function unratedLaundries(Request $request)
    {
        try {
            $customer = $request->user();

            // Only pluck non-NULL laundry_ids — NULLs from branch ratings
            // would poison the NOT IN clause and hide all completed laundries.
            $ratedLaundryIds = CustomerRating::where('customer_id', $customer->id)
                ->whereNotNull('laundry_id')
                ->pluck('laundry_id');

            $unrated = Laundry::where('customer_id', $customer->id)
                ->whereRaw('LOWER(status) = ?', ['completed'])
                ->whereNotIn('id', $ratedLaundryIds)
                ->with(['branch:id,name', 'service:id,name'])
                ->orderBy('updated_at', 'desc')
                ->limit(20)
                ->get()
                ->map(fn($laundry) => [
                    'id'              => $laundry->id,
                    'tracking_number' => $laundry->tracking_number,
                    'service_name'    => $laundry->service?->name ?? 'Laundry Service',
                    'branch_name'     => $laundry->branch?->name  ?? 'Branch',
                    'total_amount'    => $laundry->total_amount,
                    'completed_at'    => $laundry->updated_at->toIso8601String(),
                    'created_at'      => $laundry->created_at->toIso8601String(),
                ]);

            return response()->json([
                'success' => true,
                'data'    => ['laundries' => $unrated],
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching unrated laundries: ' . $e->getMessage(), [
                'customer_id' => $request->user()?->id,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch unrated laundries.',
            ], 500);
        }
    }

    /**
     * PUT /v1/customer/ratings/{id}
     */
    public function update(Request $request, $id)
    {
        $customer = $request->user();

        $rating = CustomerRating::where('id', $id)
            ->where('customer_id', $customer->id)
            ->first();

        if (! $rating) {
            return response()->json(['success' => false, 'message' => 'Rating not found.'], 404);
        }

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

        $rating->update(['rating' => $request->rating, 'comment' => $request->comment]);

        return response()->json([
            'success' => true,
            'message' => 'Rating updated successfully.',
            'data'    => [
                'rating' => [
                    'id'         => $rating->id,
                    'rating'     => $rating->rating,
                    'comment'    => $rating->comment,
                    'created_at' => $rating->created_at->toIso8601String(),
                    'updated_at' => $rating->updated_at->toIso8601String(),
                ],
            ],
        ]);
    }

    /**
     * DELETE /v1/customer/ratings/{id}
     */
    public function destroy(Request $request, $id)
    {
        $customer = $request->user();

        $rating = CustomerRating::where('id', $id)
            ->where('customer_id', $customer->id)
            ->first();

        if (! $rating) {
            return response()->json(['success' => false, 'message' => 'Rating not found.'], 404);
        }

        if ($rating->created_at->diffInHours(now()) > 24) {
            return response()->json([
                'success' => false,
                'message' => 'Ratings can only be deleted within 24 hours of submission.',
            ], 422);
        }

        $rating->delete();

        return response()->json(['success' => true, 'message' => 'Rating deleted successfully.']);
    }
}
