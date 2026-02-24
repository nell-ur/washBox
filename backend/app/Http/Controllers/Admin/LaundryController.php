<?php

namespace App\Http\Controllers\Admin;

use App\Models\User;
use App\Models\Laundry;
use App\Models\AddOn;
use App\Models\Branch;
use App\Models\Service;
use App\Models\Customer;
use App\Models\Promotion;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\PickupRequest;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Services\LaundryNotificationService;

class LaundryController extends Controller
{
    public function index(Request $request)
    {
        $query = Laundry::with(['customer', 'service', 'branch', 'staff']);

        if ($request->filled('status'))    $query->byStatus($request->status);
        if ($request->filled('branch_id')) $query->byBranch($request->branch_id);
        if ($request->filled('service_id'))$query->where('service_id', $request->service_id);
        if ($request->filled('staff_id'))  $query->byStaff($request->staff_id);
        if ($request->filled('date_from')) $query->whereDate('created_at', '>=', $request->date_from);
        if ($request->filled('date_to'))   $query->whereDate('created_at', '<=', $request->date_to);
        if ($request->filled('search'))    $query->search($request->search);

        $sortBy      = $request->get('sort_by', 'created_at');
        $sortLaundry = $request->get('sort_laundry', 'desc');
        $query->orderBy($sortBy, $sortLaundry);

        $laundries = $query->paginate(15);
        $services  = Service::active()->get();
        $branches  = Branch::active()->get();
        $staff     = User::staff()->active()->get();

        $stats = [
            'total'          => Laundry::count(),
            'pending'        => Laundry::where('status', 'received')->count(),
            'processing'     => Laundry::where('status', 'processing')->count(),
            'ready'          => Laundry::where('status', 'ready')->count(),
            'completed'      => Laundry::where('status', 'completed')->count(),
            'total_revenue'  => Laundry::where('status', 'completed')->sum('total_amount'),
            'today_laundries'=> Laundry::whereDate('created_at', today())->count(),
        ];

        return view('admin.laundries.index', compact('laundries', 'services', 'branches', 'staff', 'stats'));
    }

    public function create(Request $request)
    {
        $pickup = null;
        if ($request->has('pickup_id')) {
            $pickup = PickupRequest::with(['customer', 'branch'])->find($request->pickup_id);
        }

        $promotions = Promotion::active()
            ->valid()
            ->where(function($query) use ($pickup) {
                $query->whereNull('branch_id')
                      ->orWhere('branch_id', $pickup?->branch_id);
            })
            ->orderBy('display_laundry')
            ->get();

        return view('admin.laundries.create', [
            'pickup'     => $pickup,
            'customers'  => Customer::active()->get(),
            'branches'   => Branch::active()->get(),
            'services'   => Service::active()->get(),
            'addons'     => AddOn::active()->get(),
            'staff'      => User::staff()->active()->get(),
            'promotions' => $promotions,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_id'           => 'required|exists:customers,id',
            'branch_id'             => 'required|exists:branches,id',
            'service_id'            => 'nullable|exists:services,id',
            'weight'                => 'nullable|numeric|min:0',
            'number_of_loads'       => 'required|integer|min:1',
            'pickup_fee'            => 'nullable|numeric|min:0',
            'delivery_fee'          => 'nullable|numeric|min:0',
            'staff_id'              => 'nullable|exists:users,id',
            'promotion_id'          => 'nullable|exists:promotions,id',
            'notes'                 => 'nullable|string|max:1000',
            'addons'                => 'nullable|array',
            'addons.*.id'           => 'required_with:addons|integer|exists:add_ons,id',
            'addons.*.quantity'     => 'nullable|integer|min:1|max:99',
            'pickup_request_id'     => 'nullable|exists:pickup_requests,id',
        ]);

        // Validate that either service_id is present OR it's a per_load_override promotion
        if (empty($validated['service_id']) && empty($validated['promotion_id'])) {
            return back()->withErrors(['service_id' => 'Either a service or a promotion is required'])->withInput();
        }

        if (empty($validated['service_id']) && !empty($validated['promotion_id'])) {
            $promotion = Promotion::find($validated['promotion_id']);
            if (!$promotion || $promotion->application_type !== 'per_load_override') {
                return back()->withErrors(['promotion_id' => 'Selected promotion cannot be applied without selecting a service.'])->withInput();
            }
        }

        $service               = null;
        $serviceSubtotal       = 0;
        $snapshotPricePerPiece = 0;
        $snapshotPricePerLoad  = 0;

        $loads  = (int) $validated['number_of_loads'];
        $weight = (isset($validated['weight']) && $validated['weight'] !== null && $validated['weight'] !== '')
            ? (float) $validated['weight']
            : 0;

        if (!empty($validated['service_id'])) {
            $service     = Service::findOrFail($validated['service_id']);
            $pricingType = $service->pricing_type ?? 'per_load';

            $unitPrice = $pricingType === 'per_piece'
                ? (float) ($service->price_per_piece ?? 0)
                : (float) ($service->price_per_load  ?? 0);

            $snapshotPricePerPiece = $pricingType === 'per_piece' ? $unitPrice : 0;
            $snapshotPricePerLoad  = $pricingType === 'per_piece' ? 0 : $unitPrice;
            $serviceSubtotal       = $unitPrice * $loads;
        }

        // ── Promotion ────────────────────────────────────────────────────────
        $discountAmount         = 0;
        $promotionOverrideTotal = null;
        $promotionPricePerLoad  = null;
        $finalSubtotal          = $serviceSubtotal;

        if (!empty($validated['promotion_id'])) {
            $promotion = Promotion::find($validated['promotion_id']);
            if ($promotion) {
                if ($promotion->application_type === 'per_load_override') {
                    $promotionPricePerLoad  = $promotion->display_price;
                    $promotionOverrideTotal = $promotion->display_price * $loads;
                    $discountAmount         = 0;
                    $finalSubtotal          = $promotionOverrideTotal;
                    if (empty($validated['service_id'])) {
                        $serviceSubtotal = 0;
                    }
                } elseif ($promotion->discount_type === 'percentage' && !empty($validated['service_id'])) {
                    $discountAmount = round(($serviceSubtotal * $promotion->discount_value) / 100, 2);
                    $finalSubtotal  = $serviceSubtotal - $discountAmount;
                }
            }
        }

        $pickupFee   = (float) ($validated['pickup_fee']   ?? 0);
        $deliveryFee = (float) ($validated['delivery_fee'] ?? 0);

        // ── Add-ons: build pivot data with quantity and calculate total ──────
        $addonsTotal = 0;
        $addonPivot  = [];  // [ addonId => ['price_at_purchase' => x, 'quantity' => y] ]

        if (!empty($validated['addons'])) {
            foreach ($validated['addons'] as $addonEntry) {
                $addonId  = (int) $addonEntry['id'];
                $quantity = (int) ($addonEntry['quantity'] ?? 1);
                if ($quantity < 1) $quantity = 1;

                $addon = AddOn::find($addonId);
                if (!$addon) continue;

                $lineTotal    = (float) $addon->price * $quantity;
                $addonsTotal += $lineTotal;

                $addonPivot[$addonId] = [
                    'price_at_purchase' => (float) $addon->price,
                    'quantity'          => $quantity,
                ];
            }
        }

        $totalAmount = $finalSubtotal + $pickupFee + $deliveryFee + $addonsTotal;

        $laundry = DB::transaction(function () use (
            $validated, $service, $loads, $weight,
            $snapshotPricePerLoad, $snapshotPricePerPiece,
            $serviceSubtotal, $discountAmount, $promotionOverrideTotal,
            $promotionPricePerLoad, $pickupFee, $deliveryFee,
            $addonsTotal, $totalAmount, $finalSubtotal, $addonPivot
        ) {
            $laundry = Laundry::create([
                'tracking_number'          => $this->generateTrackingNumber(),
                'customer_id'              => $validated['customer_id'],
                'branch_id'                => $validated['branch_id'],
                'service_id'               => $validated['service_id'] ?? null,
                'created_by'               => Auth::id(),
                'staff_id'                 => $validated['staff_id'] ?? null,
                'weight'                   => $weight,
                'number_of_loads'          => $loads,
                'price_per_piece'          => $snapshotPricePerPiece,
                'price_per_load'           => $snapshotPricePerLoad,
                'subtotal'                 => $serviceSubtotal,
                'addons_total'             => $addonsTotal,
                'discount_amount'          => $discountAmount,
                'total_amount'             => $totalAmount,
                'promotion_id'             => $validated['promotion_id'] ?? null,
                'promotion_override_total' => $promotionOverrideTotal,
                'promotion_price_per_load' => $promotionPricePerLoad,
                'pickup_request_id'        => $validated['pickup_request_id'] ?? null,
                'pickup_fee'               => $pickupFee,
                'delivery_fee'             => $deliveryFee,
                'notes'                    => $validated['notes'] ?? null,
                'status'                   => 'received',
                'payment_status'           => 'pending',
                'received_at'              => now(),
            ]);

            if (!empty($addonPivot)) {
                $laundry->addons()->attach($addonPivot);
            }

            $laundry->statusHistories()->create([
                'status'     => 'received',
                'changed_by' => Auth::id(),
                'notes'      => 'Laundry created' . ($service ? '' : ' with fixed price promotion'),
            ]);

            return $laundry;
        });

        return redirect()->route('admin.laundries.show', $laundry)
            ->with('success', 'Laundry created! Tracking #: ' . $laundry->tracking_number);
    }


    public function show(Laundry $laundry)
    {
        $laundry->load([
            'customer',
            'service',
            'branch',
            'staff',
            'createdBy',
            'statusHistories.changedBy',
            'payment',
            'addons',
            'promotion',
            'pickupRequest',
        ]);

        return view('admin.laundries.show', compact('laundry'));
    }

    public function edit(Laundry $laundry)
    {
        $customers = Customer::all();
        $services  = Service::active()->get();
        $branches  = Branch::active()->get();
        $staff     = User::staff()->active()->get();

        return view('admin.laundries.edit', compact('laundry', 'customers', 'services', 'branches', 'staff'));
    }

    public function update(Request $request, Laundry $laundry)
    {
        $validated = $request->validate([
            'customer_id'    => 'required|exists:customers,id',
            'branch_id'      => 'required|exists:branches,id',
            'service_id'     => 'required_without:number_of_loads|exists:services,id',
            'number_of_loads'=> 'nullable|integer|min:1',
            'staff_id'       => 'nullable|exists:users,id',
            'weight'         => 'nullable|numeric|min:0',
            'notes'          => 'nullable|string|max:1000',
        ]);

        $service     = Service::findOrFail($validated['service_id']);
        $loads       = $validated['number_of_loads'] ?? $laundry->number_of_loads ?? 1;
        $pricingType = $service->pricing_type ?? 'per_load';

        $unitPrice = $pricingType === 'per_piece'
            ? (float) ($service->price_per_piece ?? 0)
            : (float) ($service->price_per_load  ?? 0);

        $subtotal       = $unitPrice * $loads;
        $discountAmount = $laundry->discount_amount ?? 0;

        // Re-apply promotion if set
        $promo = $laundry->promotion;
        if ($promo && $promo->application_type === 'per_load_override') {
            $subtotal       = $promo->display_price * $loads;
            $discountAmount = 0;
        } elseif ($promo && $promo->discount_type === 'percentage') {
            $discountAmount = round(($subtotal * $promo->discount_value) / 100, 2);
        }

        $pickupFee   = $laundry->pickup_fee   ?? 0;
        $deliveryFee = $laundry->delivery_fee ?? 0;
        $totalAmount = $subtotal - $discountAmount + $pickupFee + $deliveryFee + ($laundry->addons_total ?? 0);

        $validated['weight']          = (isset($validated['weight']) && $validated['weight'] !== null) ? (float) $validated['weight'] : 0;
        $validated['price_per_piece'] = $pricingType === 'per_piece' ? $unitPrice : null;
        $validated['price_per_load']  = $pricingType === 'per_piece' ? null       : $unitPrice;
        $validated['number_of_loads'] = $loads;
        $validated['subtotal']        = $subtotal;
        $validated['total_amount']    = $totalAmount;

        $laundry->update($validated);

        return redirect()->route('admin.laundries.show', $laundry)
            ->with('success', 'Laundry updated successfully!');
    }

    public function updateStatus(Request $request, Laundry $laundry)
    {
        $validated = $request->validate([
            'status' => 'required|in:received,processing,ready,paid,completed,cancelled',
            'notes'  => 'nullable|string|max:500',
        ]);

        $laundry->updateStatus($validated['status'], Auth::user(), $validated['notes'] ?? null);

        app(LaundryNotificationService::class)->onStatusChange($laundry, $validated['status']);

        return redirect()->route('admin.laundries.show', $laundry)
            ->with('success', 'Laundry status updated to: ' . ucfirst($validated['status']));
    }

    public function assignStaff(Request $request, Laundry $laundry)
    {
        $validated = $request->validate(['staff_id' => 'required|exists:users,id']);

        $laundry->assignToStaff($validated['staff_id']);
        $staff = User::find($validated['staff_id']);

        return redirect()->route('admin.laundries.show', $laundry)
            ->with('success', 'Laundry assigned to: ' . $staff->name);
    }

    public function destroy(Laundry $laundry)
    {
        if ($laundry->status !== 'cancelled') {
            return redirect()->route('admin.laundries.index')
                ->with('error', 'Only cancelled laundries can be deleted. Please cancel the laundry first.');
        }

        $trackingNumber = $laundry->tracking_number;
        $laundry->delete();

        return redirect()->route('admin.laundries.index')
            ->with('success', "Laundry {$trackingNumber} deleted successfully!");
    }

    private function generateTrackingNumber(): string
    {
        do {
            $tracking = 'WB-' . date('Ymd') . '-' . strtoupper(Str::random(4));
        } while (Laundry::where('tracking_number', $tracking)->exists());

        return $tracking;
    }

    public function receipt(Laundry $laundry)
    {
        $laundry->load(['customer', 'service', 'branch', 'staff']);
        return view('admin.laundries.receipt', compact('laundry'));
    }

    public function recordPayment(Request $request, Laundry $laundry)
    {
        DB::transaction(function () use ($request, $laundry) {
            \App\Models\Payment::create([
                'laundries_id'   => $laundry->id,
                'method'         => 'cash',
                'amount'         => $laundry->total_amount,
                'receipt_number' => 'REC-' . now()->format('Ymd') . '-' . $laundry->id,
                'received_by'    => Auth::id(),
                'notes'          => $request->notes ?? 'Paid at counter',
            ]);

            $laundry->update([
                'payment_status' => 'paid',
                'payment_method' => 'cash',
                'paid_at'        => now(),
                'status'         => 'paid',
            ]);

            $laundry->statusHistories()->create([
                'status'     => 'paid',
                'changed_by' => Auth::id(),
                'notes'      => 'Payment recorded successfully',
            ]);

            app(LaundryNotificationService::class)->onStatusChange($laundry, 'paid');
        });

        return redirect()->route('admin.laundries.show', $laundry)
            ->with('success', 'Payment recorded and Laundry updated!');
    }
}
