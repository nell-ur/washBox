<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Service;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ServiceController extends Controller
{
    /**
     * Return active services grouped-ready for the mobile app.
     *
     * GET /api/v1/services
     *
     * Optional query params:
     *   ?category=drop_off|self_service|addon
     *   ?search=keyword
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Service::where('is_active', true);

            // Optional category filter
            if ($request->filled('category')) {
                $query->where('category', $request->category);
            }

            // Optional search
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%");
                });
            }

            $services = $query
                ->orderByRaw("FIELD(category, 'drop_off', 'self_service', 'addon')")
                ->orderBy('name')
                ->get([
                    'id',
                    'name',
                    'description',
                    'category',
                    'service_type',
                    'pricing_type',
                    'price_per_load',
                    'min_weight',
                    'max_weight',
                    'turnaround_time',
                    'is_active',
                ]);

            return response()->json([
                'success' => true,
                'data'    => [
                    'services' => $services,
                    'total'    => $services->count(),
                ],
            ]);

        } catch (\Exception $e) {
            // Always return JSON so the app can handle it gracefully
            return response()->json([
                'success' => false,
                'message' => 'Failed to load services.',
                'debug'   => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Return a single service.
     *
     * GET /api/v1/services/{id}
     */
    public function show(int $id): JsonResponse
    {
        try {
            $service = Service::where('is_active', true)->findOrFail($id);

            return response()->json([
                'success' => true,
                'data'    => ['service' => $service],
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Service not found.',
            ], 404);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load service.',
                'debug'   => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }
}
