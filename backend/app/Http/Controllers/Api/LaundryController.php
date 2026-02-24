<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Laundry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class LaundryController extends Controller
{
    /**
     * Get all laundries for authenticated customer
     *
     * GET /api/v1/laundries
     */
    public function index(Request $request)
    {
        try {
            $customer = $request->user();

            $laundries = Laundry::where('customer_id', $customer->id)
                ->with(['branch', 'service'])
                ->orderBy('created_at', 'desc')
                ->get();

            $formattedLaundries = $laundries->map(function ($laundry) {
                return [
                    'id' => $laundry->id,
                    'tracking_number' => $laundry->tracking_number,
                    'status' => $laundry->status,
                    'service_name' => $laundry->service->name ?? 'Laundry Service',
                    'branch_name' => $laundry->branch->name ?? 'Branch',
                    'weight' => (float) $laundry->weight,
                    'total_amount' => (float) $laundry->total_amount,
                    'estimated_completion' => $laundry->delivery_date
                        ? $laundry->delivery_date
                        : $laundry->created_at->addDays(2)->toIso8601String(),
                    'created_at' => $laundry->created_at->toIso8601String(),
                    'updated_at' => $laundry->updated_at->toIso8601String(),
                ];
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'laundries' => $formattedLaundries,
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching laundries: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch laundries',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get specific laundry details
     *
     * GET /api/v1/laundries/{id}
     */
    public function show(Request $request, $id)
    {
        try {
            $customer = $request->user();

            // Try to find by tracking number first, then by ID
            $laundry = Laundry::where('customer_id', $customer->id)
                ->where(function ($query) use ($id) {
                    $query->where('tracking_number', $id)
                          ->orWhere('id', $id);
                })
                ->with(['branch', 'service'])
                ->first();

            if (!$laundry) {
                return response()->json([
                    'success' => false,
                    'message' => 'Laundry not found',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'laundry' => [
                        'id' => $laundry->id,
                        'tracking_number' => $laundry->tracking_number,
                        'status' => $laundry->status,
                        'payment_status' => $laundry->payment_status ?? 'unpaid',
                        'service_name' => $laundry->service->name ?? 'Laundry Service',
                        'branch_name' => $laundry->branch->name ?? 'Branch',
                        'branch_address' => $laundry->branch->address ?? null,
                        'branch_phone' => $laundry->branch->phone ?? null,
                        'weight' => (float) $laundry->weight,
                        'price_per_piece' => (float) $laundry->price_per_piece,
                        'subtotal' => (float) $laundry->subtotal,
                        'pickup_fee' => (float) ($laundry->pickup_fee ?? 0),
                        'delivery_fee' => (float) ($laundry->delivery_fee ?? 0),
                        'discount_amount' => (float) ($laundry->discount_amount ?? 0),
                        'total_amount' => (float) $laundry->total_amount,
                        'notes' => $laundry->notes,
                        'estimated_completion' => $laundry->delivery_date
                            ? $laundry->delivery_date
                            : $laundry->created_at->addDays(2)->toIso8601String(),
                        'created_at' => $laundry->created_at->toIso8601String(),
                        'updated_at' => $laundry->updated_at->toIso8601String(),
                    ],
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching laundry: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch laundry',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Create new laundry (self-service)
     *
     * POST /api/v1/laundries
     */
    public function store(Request $request)
    {
        try {
            $customer = $request->user();

            $validated = $request->validate([
                'branch_id' => 'required|exists:branches,id',
                'service_id' => 'required|exists:services,id',
                'weight' => 'required|numeric|min:0.1|max:1000',
                'pickup_address' => 'nullable|string|max:500',
                'delivery_address' => 'nullable|string|max:500',
                'pickup_date' => 'nullable|date|after_or_equal:today',
                'delivery_date' => 'nullable|date|after_or_equal:today',
                'notes' => 'nullable|string|max:1000',
            ]);

            // Get service for pricing
            $service = \App\Models\Service::findOrFail($validated['service_id']);

            // Calculate pricing
            $pricePerPiece   = $service->price_per_piece;
            $weight = $validated['weight'];
            $subtotal = $pricePerPiece * $weight;
            $discountAmount = 0; // TODO: Apply promotions if any
            $totalAmount = $subtotal - $discountAmount;

            // Generate unique tracking number
            $trackingNumber = $this->generateTrackingNumber();

            // Create laundry
            $laundry = Laundry::create([
                'customer_id' => $customer->id,
                'branch_id' => $validated['branch_id'],
                'service_id' => $validated['service_id'],
                'tracking_number' => $trackingNumber,
                'weight' => $weight,
                'price_per_piece' => $pricePerPiece,
                'subtotal' => $subtotal,
                'discount_amount' => $discountAmount,
                'total_amount' => $totalAmount,
                'status' => 'received',
                'payment_status' => 'unpaid',
                'received_at' => now(),
                'pickup_address' => $validated['pickup_address'] ?? null,
                'delivery_address' => $validated['delivery_address'] ?? null,
                'pickup_date' => $validated['pickup_date'] ?? null,
                'delivery_date' => $validated['delivery_date'] ?? null,
                'notes' => $validated['notes'] ?? null,
            ]);

            // Load relationships
            $laundry->load(['service', 'branch']);

            // TODO: Send notification to customer
            // TODO: Send notification to branch staff

            return response()->json([
                'success' => true,
                'message' => 'Laundry created successfully',
                'data' => [
                    'laundry' => [
                        'id' => $laundry->id,
                        'tracking_number' => $laundry->tracking_number,
                        'status' => $laundry->status,
                        'service_name' => $laundry->service->name,
                        'branch_name' => $laundry->branch->name,
                        'total_amount' => (float) $laundry->total_amount,
                        'created_at' => $laundry->created_at->toIso8601String(),
                    ],
                ]
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error creating laundry: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to create laundry',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Cancel laundry
     *
     * PUT /api/v1/laundries/{id}/cancel
     */
    public function cancel(Request $request, $id)
    {
        try {
            $customer = $request->user();

            $laundry = Laundry::where('customer_id', $customer->id)
                ->where('id', $id)
                ->first();

            if (!$laundry) {
                return response()->json([
                    'success' => false,
                    'message' => 'Laundry not found',
                ], 404);
            }

            // Only allow cancellation of certain statuses
            if (!in_array($laundry->status, ['received', 'processing', 'washing'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot cancel laundry in current status. Laundry is already being processed.',
                ], 400);
            }

            $laundry->update([
                'status' => 'cancelled',
            ]);

            // TODO: Send cancellation notification to customer
            // TODO: Send cancellation notification to branch

            return response()->json([
                'success' => true,
                'message' => 'Laundry cancelled successfully',
            ]);
        } catch (\Exception $e) {
            Log::error('Error cancelling laundry: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to cancel laundry',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Generate unique tracking number
     */
    private function generateTrackingNumber(): string
    {
        do {
            $tracking = 'WB-' . date('Ymd') . '-' . strtoupper(Str::random(4));
        } while (Laundry::where('tracking_number', $tracking)->exists());

        return $tracking;
    }
}
