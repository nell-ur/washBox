<?php

namespace App\Http\Controllers\Admin;

use App\Models\Branch;
use App\Models\Service;
use App\Models\Customer;
use App\Models\DeliveryFee;
use Illuminate\Http\Request;
use App\Models\PickupRequest;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class PickupRequestController extends Controller
{
    protected $routingService;

    public function __construct()
    {
        // Try to resolve a RoutingService from the container if available
        if (app()->bound(\App\Services\RouteService::class)) {
            $this->routingService = app()->make(\App\Services\RouteService::class);
        } elseif (app()->bound('routeService')) {
            $this->routingService = app()->make('routeService');
        } else {
            $this->routingService = null;
        }
    }

    /**
     * Display list of pickup requests
     */
    public function index(Request $request)
    {
        $query = PickupRequest::with(['customer', 'branch', 'service', 'assignedStaff'])
            ->orderBy('created_at', 'desc');

        // Filter by status
        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        // Filter by branch
        if ($request->has('branch_id') && $request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }

        // Filter by date
        if ($request->has('date') && $request->date) {
            $query->whereDate('preferred_date', $request->date);
        }

        // Search by customer name or address
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('pickup_address', 'like', "%{$search}%")
                  ->orWhereHas('customer', function ($q2) use ($search) {
                      $q2->where('name', 'like', "%{$search}%");
                  });
            });
        }

        $pickups = $query->paginate(20);
        $branches = Branch::where('is_active', true)->get();

        return view('admin.pickups.index', compact('pickups', 'branches'));
    }

    /**
     * Show create pickup request form
     */
    public function create()
    {
        $customers = Customer::where('is_active', true)
            ->orderBy('name')
            ->get();

        $services = Service::where('is_active', true)
            ->orderBy('name')
            ->get();

        // Get all branches with their delivery fee settings
        $branches = Branch::with('deliveryFees')
            ->where('is_active', true)
            ->get();

        return view('admin.pickups.create', compact('customers', 'services', 'branches'));
    }

    /**
     * Store new pickup request with fee calculation
     */
    /**
 * Store new pickup request with fee calculation
 */
public function store(Request $request)
{
    $validated = $request->validate([
        'customer_id' => 'required|exists:customers,id',
        'branch_id' => 'required|exists:branches,id',
        'service_id' => 'required|exists:services,id',
        'service_type' => 'required|in:pickup_only,delivery_only,both',
        'preferred_date' => 'required|date|after_or_equal:today',
        'preferred_time' => 'required',
        'pickup_address' => 'required|string|max:500',
        'latitude' => 'nullable|numeric',
        'longitude' => 'nullable|numeric',
        'notes' => 'nullable|string|max:1000',
    ]);

    // GET FEES FROM DELIVERY_FEES TABLE
    $deliveryFee = DeliveryFee::getOrCreateForBranch($validated['branch_id']);
    $fees = $deliveryFee->calculateFee($validated['service_type']);

    // CREATE WITH CALCULATED FEES
    $pickup = PickupRequest::create([
        'customer_id' => $validated['customer_id'],
        'branch_id' => $validated['branch_id'],
        'service_id' => $validated['service_id'],
        'pickup_address' => $validated['pickup_address'],
        'latitude' => $validated['latitude'] ?? null,
        'longitude' => $validated['longitude'] ?? null,
        'preferred_date' => $validated['preferred_date'],
        'preferred_time' => $validated['preferred_time'],
        'notes' => $validated['notes'] ?? null,
        'service_type' => $validated['service_type'],
        'pickup_fee' => $fees['pickup_fee'],
        'delivery_fee' => $fees['delivery_fee'],
        'status' => 'pending',
    ]);

    return redirect()->route('admin.pickups.show', $pickup)
        ->with('success', 'Pickup created! Fee: ₱' . number_format($fees['total_fee'], 2));
}


    /**
     * Show single pickup request
     */
    public function show($id)
    {
        $pickup = PickupRequest::with(['customer', 'branch', 'service', 'assignedStaff', 'laundry'])
            ->findOrFail($id);

        return view('admin.pickups.show', compact('pickup'));
    }

    /**
     * Accept pickup request
     */
    public function accept(Request $request, $id)
    {
        $pickup = PickupRequest::findOrFail($id);

        if (!$pickup->canBeAccepted()) {
            return back()->with('error', 'Cannot accept pickup request in current status');
        }

        $pickup->accept(Auth::id());

        // TODO: Send notification to customer
        // $pickup->customer->notify(new PickupAccepted($pickup));

        return back()->with('success', 'Pickup request accepted successfully! Customer has been notified.');
    }

    /**
     * Mark pickup as en route
     */
    public function markEnRoute($id)
    {
        $pickup = PickupRequest::findOrFail($id);

        if (!$pickup->canMarkEnRoute()) {
            return back()->with('error', 'Cannot mark as en route in current status');
        }

        $pickup->markEnRoute();

        // TODO: Send notification to customer
        // $pickup->customer->notify(new PickupEnRoute($pickup));

        return back()->with('success', 'Pickup marked as en route! Customer has been notified.');
    }

    /**
     * Mark pickup as picked up and redirect to laundry creation
     */
    public function markPickedUp($id)
    {
        $pickup = PickupRequest::findOrFail($id);

        if (!$pickup->canMarkPickedUp()) {
            return back()->with('error', 'Cannot mark as picked up in current status');
        }

        $pickup->markPickedUp();

        // TODO: Send notification to customer
        // $pickup->customer->notify(new PickupPickedUp($pickup));

        // Redirect to laundry creation with pickup data
        return redirect()->route('admin.laundries.create', ['pickup_id' => $pickup->id])
            ->with('success', 'Laundry picked up! Now create the laundry.');
    }

    /**
     * Cancel pickup request
     */
    public function cancel(Request $request, $id)
    {
        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $pickup = PickupRequest::findOrFail($id);

        if (!$pickup->canBeCancelled()) {
            return back()->with('error', 'Cannot cancel pickup in current status');
        }

        $pickup->cancel($request->reason, Auth::id());

        // TODO: Send notification to customer
        // $pickup->customer->notify(new PickupCancelled($pickup));

        return back()->with('success', 'Pickup request cancelled. Customer has been notified.');
    }

    /**
     * Assign staff to pickup
     */
    public function assignStaff(Request $request, $id)
    {
        $request->validate([
            'staff_id' => 'required|exists:users,id',
        ]);

        $pickup = PickupRequest::findOrFail($id);

        $pickup->update([
            'assigned_to' => $request->staff_id,
        ]);

        return back()->with('success', 'Staff assigned successfully!');
    }

    /**
     * Get pickup statistics for dashboard
     */
    public function stats()
    {
       $stats['pickupStats'] = [
    'pending'  => PickupRequest::where('status', 'pending')->count(),
    'accepted' => PickupRequest::where('status', 'accepted')->count(),
    'en_route' => PickupRequest::where('status', 'en_route')->count(),
    'recieved' => PickupRequest::where('status', 'picked_up')->count(), // Mapping picked_up to your Blade key

            'today' => PickupRequest::whereDate('preferred_date', today())->count(),
            'tomorrow' => PickupRequest::whereDate('preferred_date', today()->addDay())->count(),
        ];

        return view('admin.dashboard', compact('stats'));
    }
    /**
     * Quick action: Accept multiple pickups
     */
    public function bulkAccept(Request $request)
    {
        $request->validate([
            'pickup_ids' => 'required|array',
            'pickup_ids.*' => 'exists:pickup_requests,id',
        ]);

        $count = 0;
        foreach ($request->pickup_ids as $id) {
            $pickup = PickupRequest::find($id);
            if ($pickup && $pickup->canBeAccepted()) {
                $pickup->accept(Auth::id());
                $count++;
            }
        }

        return back()->with('success', "{$count} pickup request(s) accepted successfully!");
    }

    // In app/Models/PickupRequest.php
protected $service_type; // Add this if you need a controller property
public function getServiceTypeLabelAttribute()
{
    $labels = [
        'pickup_only' => 'Pickup Only',
        'delivery_only' => 'Delivery Only',
        'both' => 'Pickup + Delivery'
    ];
    return $labels[$this->service_type] ?? 'Unknown';
}

public function getRoute($id)
{
    $pickup = PickupRequest::findOrFail($id);

    // Ensure routing service is available
    if (!$this->routingService) {
        return response()->json([
            'success' => false,
            'message' => 'Routing service not configured'
        ], 500);
    }

    // Calculate route using your routing service
    $route = $this->routingService->getRoute(
        from: [9.3068, 123.3033], // Your branch coordinates
        to: [$pickup->latitude, $pickup->longitude]
    );

    return response()->json([
        'success' => true,
        'route' => [
            'distance' => [
                'meters' => $route['distance_meters'],
                'kilometers' => $route['distance_km'],
                'text' => $route['distance_text']
            ],
            'duration' => [
                'seconds' => $route['duration_seconds'],
                'minutes' => $route['duration_minutes'],
                'text' => $route['duration_text']
            ],
            'geometry' => $route['polyline'], // Encoded polyline or coordinates array
            'waypoints' => [
                'start' => [
                    'lat' => 9.3068,
                    'lng' => 123.3033,
                    'name' => 'Main Branch - Sibulan'
                ],
                'end' => [
                    'lat' => $pickup->latitude,
                    'lng' => $pickup->longitude,
                    'name' => $pickup->customer->address
                ]
            ]
        ],
        'estimated_arrival' => now()->addSeconds($route['duration_seconds'])->format('h:i A')
    ]);
}

}
