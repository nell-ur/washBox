<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PickupRequest;
use App\Models\Notification;  // ✅ ADDED
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PickupController extends Controller
{
    /**
     * Get all pickup requests for authenticated customer
     *
     * GET /api/v1/pickups
     */
    public function index(Request $request)
    {
        try {
            $customer = $request->user();

            $pickups = PickupRequest::where('customer_id', $customer->id)
                ->with(['branch', 'service', 'assignedStaff'])
                ->orderBy('created_at', 'desc')
                ->get();

            $formattedPickups = $pickups->map(function ($pickup) {
                return [
                    'id' => $pickup->id,
                    'status' => $pickup->status,
                    'pickup_address' => $pickup->pickup_address,
                    'preferred_date' => $pickup->preferred_date->format('Y-m-d'),
                    'preferred_time' => $pickup->preferred_time,
                    'notes' => $pickup->notes,
                    'service_name' => $pickup->service->name ?? null,
                    'branch' => [
                        'id' => $pickup->branch->id,
                        'name' => $pickup->branch->name,
                        'phone' => $pickup->branch->phone ?? null,
                    ],
                    'created_at' => $pickup->created_at->toIso8601String(),
                ];
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'pickups' => $formattedPickups,
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching pickups: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch pickup requests',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get specific pickup request details
     *
     * GET /api/v1/pickups/{id}
     */
    public function show(Request $request, $id)
    {
        try {
            $customer = $request->user();

            $pickup = PickupRequest::where('customer_id', $customer->id)
                ->where('id', $id)
                ->with(['branch', 'service', 'assignedStaff', 'laundry'])
                ->first();

            if (!$pickup) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pickup request not found',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'pickup' => [
                        'id' => $pickup->id,
                        'status' => $pickup->status,
                        'pickup_address' => $pickup->pickup_address,
                        'latitude' => $pickup->latitude,
                        'longitude' => $pickup->longitude,
                        'preferred_date' => $pickup->preferred_date->format('Y-m-d'),
                        'preferred_time' => $pickup->preferred_time,
                        'notes' => $pickup->notes,
                        'service' => $pickup->service ? [
                            'id' => $pickup->service->id,
                            'name' => $pickup->service->name,
                        ] : null,
                        'branch' => [
                            'id' => $pickup->branch->id,
                            'name' => $pickup->branch->name,
                            'address' => $pickup->branch->address ?? null,
                            'phone' => $pickup->branch->phone ?? null,
                        ],
                        'assigned_staff' => $pickup->assignedStaff ? [
                            'id' => $pickup->assignedStaff->id,
                            'name' => $pickup->assignedStaff->name,
                        ] : null,
                        'laundries_id' => $pickup->laundries_id,
                        'accepted_at' => $pickup->accepted_at ? $pickup->accepted_at->toIso8601String() : null,
                        'en_route_at' => $pickup->en_route_at ? $pickup->en_route_at->toIso8601String() : null,
                        'picked_up_at' => $pickup->picked_up_at ? $pickup->picked_up_at->toIso8601String() : null,
                        'created_at' => $pickup->created_at->toIso8601String(),
                    ],
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching pickup: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch pickup request',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
 * Create new pickup request
 *
 * POST /api/v1/pickups
 */
public function store(Request $request)
{
    try {
        $customer = $request->user();

        $validated = $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'pickup_address' => 'required|string|max:500',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'preferred_date' => 'required|date|after_or_equal:today',
            'preferred_time' => 'nullable|string',
            'notes' => 'nullable|string|max:500',
            'service_id' => 'nullable|exists:services,id',
        ]);

        $pickup = PickupRequest::create([
            'customer_id' => $customer->id,
            'branch_id' => $validated['branch_id'],
            'pickup_address' => $validated['pickup_address'],
            'latitude' => $validated['latitude'] ?? 0,
            'longitude' => $validated['longitude'] ?? 0,
            'preferred_date' => $validated['preferred_date'],
            'preferred_time' => $validated['preferred_time'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'service_id' => $validated['service_id'] ?? null,
            'status' => 'pending',
        ]);

        // 🔔 Observer will automatically trigger AdminNotification::notifyNewPickupRequest()

        return response()->json([
            'success' => true,
            'message' => 'Pickup request created successfully! We will contact you shortly.',
            'data' => [
                'pickup' => [
                    'id' => $pickup->id,
                    'status' => $pickup->status,
                    'preferred_date' => $pickup->preferred_date->format('Y-m-d'),
                    'preferred_time' => $pickup->preferred_time,
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
        Log::error('Error creating pickup request: ' . $e->getMessage());

        return response()->json([
            'success' => false,
            'message' => 'Failed to create pickup request',
            'error' => $e->getMessage(),
        ], 500);
    }
}
    /**
     * Cancel pickup request (Customer)
     *
     * PUT /api/v1/pickups/{id}/cancel
     */
    public function cancel(Request $request, $id)
    {
        try {
            $customer = $request->user();

            $pickup = PickupRequest::where('customer_id', $customer->id)
                ->where('id', $id)
                ->first();

            if (!$pickup) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pickup request not found',
                ], 404);
            }

            // Only allow cancellation of pending or accepted requests
            if (!in_array($pickup->status, ['pending', 'accepted'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot cancel pickup in current status',
                ], 400);
            }

            $pickup->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
                'cancellation_reason' => $request->reason ?? 'Cancelled by customer',
                'cancelled_by' => null, // Customer cancel, no user_id
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Pickup request cancelled successfully',
            ]);
        } catch (\Exception $e) {
            Log::error('Error cancelling pickup: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to cancel pickup request',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Accept pickup request (Staff/Owner)
     *
     * PUT /api/v1/staff/pickups/{id}/accept
     */
    public function accept(Request $request, $id)
    {
        try {
            $pickup = PickupRequest::findOrFail($id);

            if ($pickup->status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'Pickup request is not in pending status',
                ], 400);
            }

            $pickup->update([
                'status' => 'accepted',
                'accepted_at' => now(),
                'assigned_to' => $request->user()->id ?? null,
            ]);

            // ✅ CREATE NOTIFICATION
            Notification::createPickupAccepted($pickup);

            return response()->json([
                'success' => true,
                'message' => 'Pickup request accepted successfully',
                'data' => [
                    'pickup' => [
                        'id' => $pickup->id,
                        'status' => $pickup->status,
                        'accepted_at' => $pickup->accepted_at->toIso8601String(),
                    ],
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error accepting pickup: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to accept pickup request',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Mark pickup as en route (Rider is on the way)
     *
     * PUT /api/v1/staff/pickups/{id}/en-route
     */
    public function markEnRoute(Request $request, $id)
    {
        try {
            $pickup = PickupRequest::findOrFail($id);

            if ($pickup->status !== 'accepted') {
                return response()->json([
                    'success' => false,
                    'message' => 'Pickup must be accepted first',
                ], 400);
            }

            $pickup->update([
                'status' => 'en_route',
                'en_route_at' => now(),
            ]);

            // ✅ CREATE NOTIFICATION
            Notification::createPickupEnRoute($pickup);

            return response()->json([
                'success' => true,
                'message' => 'Pickup marked as en route',
            ]);
        } catch (\Exception $e) {
            Log::error('Error marking pickup en route: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to update pickup status',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Mark pickup as picked up (Laundry collected)
     *
     * PUT /api/v1/staff/pickups/{id}/picked-up
     */
    public function markPickedUp(Request $request, $id)
    {
        try {
            $pickup = PickupRequest::findOrFail($id);

            if (!in_array($pickup->status, ['accepted', 'en_route'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid status transition',
                ], 400);
            }

            $pickup->update([
                'status' => 'picked_up',
                'picked_up_at' => now(),
            ]);

            // ✅ CREATE NOTIFICATION
            Notification::createPickupCompleted($pickup);

            return response()->json([
                'success' => true,
                'message' => 'Pickup marked as picked up',
            ]);
        } catch (\Exception $e) {
            Log::error('Error marking pickup as picked up: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to mark pickup as picked up',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**laundry
     * Link pickup to laundry (after creating laundry from pickup)
     *
     * PUT /api/v1/staff/pickups/{id}/link-laundry
     */
    public function linkLaundry(Request $request, $id)
    {
        try {
            $request->validate([
                'laundries_id' => 'required|exists:laundries,id',
            ]);

            $pickup = PickupRequest::findOrFail($id);

            $pickup->update([
                'laundries_id' => $request->laundries_id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Laundry linked to pickup request successfully',
            ]);
        } catch (\Exception $e) {
            Log::error('Error linking laundry to pickup: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to link laundry',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
