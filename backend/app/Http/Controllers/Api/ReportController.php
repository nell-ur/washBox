<?php

namespace App\Http\Controllers\Api;

use App\Models\Customer;
use App\Models\CustomerRating;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class ReportController extends Controller
{
    /**
     * Get customer reports with ratings
     */
    public function customerReports(Request $request)
    {
        $query = Customer::query()
            ->select([
                'id',
                'name',
                'email',
                'phone',
                'is_active',
                'created_at',
            ])
            ->selectRaw('(SELECT COUNT(*) FROM laundries WHERE customer_id = customers.id) as total_laundries')
            ->selectRaw('(SELECT COALESCE(SUM(total_amount), 0) FROM laundries WHERE customer_id = customers.id) as total_spent')
            ->selectRaw('(SELECT COALESCE(AVG(rating), 0) FROM customer_ratings WHERE customer_id = customers.id) as avg_rating')
            ->selectRaw('(SELECT COUNT(*) FROM customer_ratings WHERE customer_id = customers.id) as rating_count')
            ->selectRaw('(SELECT COUNT(*) FROM customer_ratings WHERE customer_id = customers.id AND rating = 5) as rating_5_count')
            ->selectRaw('(SELECT COUNT(*) FROM customer_ratings WHERE customer_id = customers.id AND rating = 4) as rating_4_count')
            ->selectRaw('(SELECT COUNT(*) FROM customer_ratings WHERE customer_id = customers.id AND rating = 3) as rating_3_count')
            ->selectRaw('(SELECT COUNT(*) FROM customer_ratings WHERE customer_id = customers.id AND rating = 2) as rating_2_count')
            ->selectRaw('(SELECT COUNT(*) FROM customer_ratings WHERE customer_id = customers.id AND rating = 1) as rating_1_count')
            ->selectRaw('(SELECT MAX(created_at) FROM laundries WHERE customer_id = customers.id) as last_laundry_date');

        // Search filter
        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('email', 'LIKE', "%{$search}%")
                  ->orWhere('phone', 'LIKE', "%{$search}%");
            });
        }

        // Rating filter
        if ($request->filled('rating')) {
            $rating = $request->get('rating');

            if ($rating == 0) {
                $query->whereRaw('(SELECT COUNT(*) FROM customer_ratings WHERE customer_id = customers.id) = 0');
            } else {
                $minRating = (int)$rating;
                $maxRating = $minRating + 1;
                $query->whereRaw("(SELECT COALESCE(AVG(rating), 0) FROM customer_ratings WHERE customer_id = customers.id) >= ?", [$minRating])
                      ->whereRaw("(SELECT COALESCE(AVG(rating), 0) FROM customer_ratings WHERE customer_id = customers.id) < ?", [$maxRating]);
            }
        }

        // Date range filter
        if ($request->filled('from')) {
            $query->whereDate('created_at', '>=', $request->get('from'));
        }
        if ($request->filled('to')) {
            $query->whereDate('created_at', '<=', $request->get('to'));
        }

        $customers = $query->orderByDesc('total_laundries')->get();

        return response()->json([
            'success' => true,
            'data' => $customers,
        ], 200);
    }

    /**
     * Get ratings for a specific customer
     */
    public function customerRatings($customerId)
    {
        $ratings = CustomerRating::where('customer_id', $customerId)
            ->select('id', 'rating', 'comment', 'laundry_number', 'created_at')
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $ratings,
        ], 200);
    }

}
