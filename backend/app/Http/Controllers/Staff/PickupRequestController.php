<?php

namespace App\Http\Controllers\Staff;

use App\Models\Laundry;
use App\Models\Service;
use App\Models\Customer;
use App\Models\DeliveryFee;
use Illuminate\Http\Request;
use App\Models\PickupRequest;
use App\Services\RouteService;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class PickupRequestController extends Controller
{
    /**
     * Display pickup requests for staff's branch
     */
    public function index(Request $request)
    {
        $staff = Auth::user();

        if (!$staff || !$staff->branch_id) {
            return redirect()->route('staff.dashboard')
                ->with('error', 'Your account is not assigned to a branch.');
        }

        $query = PickupRequest::with(['customer', 'service', 'assignedStaff'])
            ->where('branch_id', $staff->branch_id)
            ->orderBy('id', 'asc');

        // Filter by status
        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        // Filter by date
        if ($request->has('date') && $request->date) {
            $query->whereDate('preferred_date', $request->date);
        }

        // Search
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('pickup_address', 'like', "%{$search}%")
                  ->orWhereHas('customer', function ($q2) use ($search) {
                      $q2->where('name', 'like', "%{$search}%");
                  });
            });
        }

        // Filter by "My Assigned Only"
        if ($request->has('my_assigned') && $request->my_assigned) {
            $query->where('assigned_to', $staff->id);
        }

        $pickups = $query->paginate(20);

        // Get stats
        $stats = $this->getBranchStats($staff);

        return view('staff.pickups.index', compact('pickups', 'stats'));
    }

    /**
     * Helper to keep stats consistent across methods
     */
    private function getBranchStats($staff)
    {
        $baseQuery = PickupRequest::where('branch_id', $staff->branch_id);

        return [
            'pending'     => (clone $baseQuery)->pending()->count(),
            'accepted'    => (clone $baseQuery)->accepted()->count(),
            'en_route'    => (clone $baseQuery)->enRoute()->count(),
            'picked_up'   => (clone $baseQuery)->pickedUp()->count(),
            'today'       => (clone $baseQuery)->whereDate('preferred_date', today())->count(),
            'my_assigned' => (clone $baseQuery)->where('assigned_to', $staff->id)->count(),
        ];
    }

    /**
     * Show create pickup request form
     */
    public function create()
    {
        $staff = Auth::user();

        if (!$staff || !$staff->branch_id) {
            return redirect()->route('staff.dashboard')
                ->with('error', 'Your account is not assigned to a branch.');
        }

        $customers = Customer::where('is_active', true)
            ->orderBy('name')
            ->get();

        $services = Service::where('is_active', true)
            ->orderBy('name')
            ->get();

        // Get delivery fee settings for this branch
        $deliveryFees = DeliveryFee::getOrCreateForBranch($staff->branch_id);

        return view('staff.pickups.create', compact('customers', 'services', 'deliveryFees'));
    }

    /**
     * Store new pickup request with fee calculation
     */
    public function store(Request $request)
{
    $validated = $request->validate([
        'customer_id' => 'required|exists:customers,id',
        'service_id' => 'required|exists:services,id',
        'weight' => 'required|numeric|min:0.1',
        'pickup_date' => 'nullable|date',
        'delivery_date' => 'nullable|date',
        'pickup_fee' => 'nullable|numeric|min:0',
        'delivery_fee' => 'nullable|numeric|min:0',
        'notes' => 'nullable|string|max:1000',
    ]);

    $staff = auth()->user();

    // Get service pricing
    $service = Service::findOrFail($validated['service_id']);
    $pricePerKg = $service->price_per_piece;
    $subtotal = $validated['weight'] * $pricePerKg;
    $discountAmount = 0;

    // Get fees from form (staff enters manually)
    $pickupFee = $validated['pickup_fee'] ?? 0;
    $deliveryFee = $validated['delivery_fee'] ?? 0;

    // Calculate total
    $totalAmount = $subtotal - $discountAmount + $pickupFee + $deliveryFee;

    // Generate tracking number
    $trackingNumber = $this->generateTrackingNumber();

    // Create laundry (NO pickup_request_id for staff walk-in pickups)
    $laundry = Laundry::create([
        'tracking_number' => $trackingNumber,
        'customer_id' => $validated['customer_id'],
        'branch_id' => $staff->branch_id,
        'service_id' => $validated['service_id'],
        'staff_id' => $staff->id,
        'created_by' => $staff->id,
        'weight' => $validated['weight'],
        'price_per_piece' => $pricePerPiece,
        'subtotal' => $subtotal,
        'discount_amount' => $discountAmount,
        'pickup_fee' => $pickupFee,
        'delivery_fee' => $deliveryFee,
        'total_amount' => $totalAmount,
        'status' => 'received',
        'received_at' => now(),
        'notes' => $validated['notes'] ?? null,
        // ❌ REMOVED: 'pickup_request_id' => null, // Staff pickups don't have pickup requests
    ]);

    // Create status history
    $laundry->statusHistories()->create([
        'status' => 'received',
        'changed_by' => $staff->id,
        'notes' => 'Pickup created by staff',
    ]);

    return redirect()->route('staff.pickups.show', $laundry)
        ->with('success', 'Pickup created successfully! Tracking: ' . $trackingNumber);
}

private function generateTrackingNumber(): string
{
    do {
        $tracking = 'WB-' . date('Ymd') . '-' . strtoupper(\Illuminate\Support\Str::random(4));
    } while (Laundry::where('tracking_number', $tracking)->exists());

    return $tracking;
}

    /**
     * Show single pickup request
     */
    public function show($id)
    {
        $staff = Auth::user();

        if (!$staff || !$staff->branch_id) {
            return redirect()->route('staff.dashboard')
                ->with('error', 'Your account is not assigned to a branch.');
        }

        // CRITICAL: Only fetch pickups from staff's branch
        $pickup = PickupRequest::with(['customer', 'branch', 'service', 'assignedStaff'])
            ->where('branch_id', $staff->branch_id)
            ->where('id', $id)
            ->firstOrFail();

        // Double-check branch ownership (extra security)
        if ($pickup->branch_id != $staff->branch_id) {
            abort(403, 'Unauthorized: This pickup belongs to a different branch.');
        }

        return view('staff.pickups.show', compact('pickup'));
    }

    /**
     * Accept pickup request (staff can self-assign)
     */
    public function accept($id)
    {
        $staff = Auth::user();
        $pickup = PickupRequest::findOrFail($id);

        // Ensure pickup belongs to staff's branch
        if ($pickup->branch_id != $staff->branch_id) {
            abort(403, 'Unauthorized: This pickup belongs to a different branch.');
        }

        if (!$pickup->canBeAccepted()) {
            return back()->with('error', 'Cannot accept pickup request in current status.');
        }

        $pickup->accept($staff->id);

        // TODO: Send notification to customer
        // $pickup->customer->notify(new PickupAccepted($pickup));

        return back()->with('success', 'Pickup request accepted and assigned to you!');
    }

    /**
     * Mark pickup as en route
     */
    public function markEnRoute($id)
    {
        $staff = Auth::user();
        $pickup = PickupRequest::findOrFail($id);

        // Ensure pickup belongs to staff's branch
        if ($pickup->branch_id != $staff->branch_id) {
            abort(403, 'Unauthorized: This pickup belongs to a different branch.');
        }

        // Ensure this staff is assigned to this pickup
        if ($pickup->assigned_to != $staff->id) {
            return back()->with('error', 'You are not assigned to this pickup request.');
        }

        if (!$pickup->canMarkEnRoute()) {
            return back()->with('error', 'Cannot mark as en route in current status.');
        }

        $pickup->markEnRoute();

        // TODO: Send notification to customer
        // $pickup->customer->notify(new PickupEnRoute($pickup));

        return back()->with('success', 'Status updated: On the way to customer!');
    }

    /**
     * Mark pickup as picked up and redirect to order creation
     */
    public function markPickedUp($id)
    {
        $staff = Auth::user();
        $pickup = PickupRequest::findOrFail($id);

        // Ensure pickup belongs to staff's branch
        if ($pickup->branch_id != $staff->branch_id) {
            abort(403, 'Unauthorized: This pickup belongs to a different branch.');
        }

        // Ensure this staff is assigned to this pickup
        if ($pickup->assigned_to != $staff->id) {
            return back()->with('error', 'You are not assigned to this pickup request.');
        }

        if (!$pickup->canMarkPickedUp()) {
            return back()->with('error', 'Cannot mark as picked up in current status.');
        }

        $pickup->markPickedUp();

        // TODO: Send notification to customer
        // $pickup->customer->notify(new PickupPickedUp($pickup));

        // Redirect to order creation with pickup data
        return redirect()->route('staff.laundries.create', ['pickup_id' => $pickup->id])
            ->with('success', 'Laundry picked up! Now create the laundry.');
    }

    /**
     * Cancel pickup request
     */
    public function cancel(Request $request, $id)
    {
        $staff = Auth::user();

        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $pickup = PickupRequest::findOrFail($id);

        // Ensure pickup belongs to staff's branch
        if ($pickup->branch_id != $staff->branch_id) {
            abort(403, 'Unauthorized: This pickup belongs to a different branch.');
        }

        if (!$pickup->canBeCancelled()) {
            return back()->with('error', 'Cannot cancel pickup in current status.');
        }

        $pickup->cancel($request->reason, $staff->id);

        // TODO: Send notification to customer
        // $pickup->customer->notify(new PickupCancelled($pickup));

        return back()->with('success', 'Pickup request cancelled. Customer has been notified.');
    }

    /**
     * Get pickup statistics for staff dashboard
     */
    public function stats()
    {
        $staff = Auth::user();

        if (!$staff || !$staff->branch_id) {
            return response()->json(['error' => 'Not assigned to branch'], 403);
        }

        return response()->json($this->getBranchStats($staff));
    }

    /**
     * Update GPS location during pickup
     */
    public function updateLocation(Request $request, $id)
    {
        $staff = Auth::user();

        $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
        ]);

        $pickup = PickupRequest::findOrFail($id);

        // Ensure pickup belongs to staff's branch and assigned to this staff
        if ($pickup->branch_id != $staff->branch_id || $pickup->assigned_to != $staff->id) {
            abort(403, 'Unauthorized');
        }

        $pickup->update([
            'current_latitude' => $request->latitude,
            'current_longitude' => $request->longitude,
            'location_updated_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Location updated successfully',
        ]);
    }

    /**
     * Get route from branch to pickup using RouteService (OSRM)
     */
    public function getRoute($id)
    {
        try {
            $staff = Auth::user();
            $pickup = PickupRequest::with('branch', 'customer')
                ->where('id', $id)
                ->where('branch_id', $staff->branch_id)
                ->firstOrFail();

            if (!$pickup->latitude || !$pickup->longitude) {
                return response()->json(['success' => false, 'error' => 'Invalid pickup coordinates'], 400);
            }

            $routeService = new RouteService();
            $route = $routeService->getRouteFromBranch($pickup, 'osrm');

            if (!$route['success']) {
                return response()->json(['success' => false, 'error' => $route['error'] ?? 'Route calculation failed'], 500);
            }

            return response()->json([
                'success' => true,
                'route' => $route['route'],
                'instructions' => $route['instructions'] ?? [],
                'provider' => $route['provider'],
                'estimated_arrival' => $route['estimated_arrival'] ?? null,
                'branch' => [
                    'name' => $pickup->branch->name ?? null,
                    'address' => $pickup->branch->address ?? null,
                    'latitude' => $pickup->branch->latitude ?? null,
                    'longitude' => $pickup->branch->longitude ?? null,
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Staff route calculation failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Start navigation for pickup (mark as en_route and assign to staff)
     */
    public function startNavigation(Request $request, $id)
    {
        try {
            $staff = Auth::user();
            $pickup = PickupRequest::where('id', $id)->where('branch_id', $staff->branch_id)->firstOrFail();

            $pickup->update([
                'status' => 'en_route',
                'en_route_at' => now(),
                'assigned_to' => $staff->id
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Navigation started for pickup #' . $pickup->id,
                'pickup' => $pickup->fresh()
            ]);

        } catch (\Exception $e) {
            Log::error('Staff navigation start failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Show customers list who have made pickup requests
     */
    public function customers()
    {
        $staff = Auth::user();

        if (!$staff || !$staff->branch_id) {
            return redirect()->route('staff.dashboard')
                ->with('error', 'Your account is not assigned to a branch.');
        }

        $pickupRequests = PickupRequest::with(['customer', 'service'])
            ->where('branch_id', $staff->branch_id)
            ->latest()
            ->get();

        // Group by customer
        $customers = $pickupRequests->groupBy('customer_id')->map(function ($requests) {
            $customer = $requests->first()->customer;
            return [
                'customer' => $customer,
                'total_requests' => $requests->count(),
                'pending' => $requests->where('status', 'pending')->count(),
                'completed' => $requests->where('status', 'picked_up')->count(),
                'latest_request' => $requests->first(),
            ];
        });

        $stats = [
            'total_customers' => $customers->count(),
            'total_requests' => $pickupRequests->count(),
            'pending' => $pickupRequests->where('status', 'pending')->count(),
            'completed' => $pickupRequests->where('status', 'picked_up')->count(),
        ];

        return view('staff.pickups.customers', compact('customers', 'stats'));
    }

    /**
     * Show quick confirmation page for pending pickups
     */
    public function confirm()
    {
        $staff = Auth::user();

        if (!$staff || !$staff->branch_id) {
            return redirect()->route('staff.dashboard')
                ->with('error', 'Your account is not assigned to a branch.');
        }

        // Get pending pickups
        $pendingPickups = PickupRequest::with(['customer', 'service'])
            ->where('branch_id', $staff->branch_id)
            ->where('status', 'pending')
            ->latest()
            ->get();

        // Get recently confirmed (assigned to this staff)
        $recentlyConfirmed = PickupRequest::with(['customer', 'service'])
            ->where('branch_id', $staff->branch_id)
            ->where('assigned_to', $staff->id)
            ->whereIn('status', ['accepted', 'en_route'])
            ->latest()
            ->take(5)
            ->get();

        return view('staff.pickups.confirm', compact('pendingPickups', 'recentlyConfirmed'));
    }



}
