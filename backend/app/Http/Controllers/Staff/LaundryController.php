<?php

namespace App\Http\Controllers\Staff;

use App\Models\Laundry;
use App\Models\Branch;
use App\Models\Service;
use App\Models\Customer;
use App\Models\PickupRequest;
use App\Models\Promotion;
use App\Models\PromotionUsage;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\AddOn;

class LaundryController extends Controller
{
    protected $notificationService;

    /**
     * Display laundry list with filters (branch-specific)
     */
    public function index(Request $request)
    {
        $staff = Auth::user();

        if (!$staff || !$staff->branch_id) {
            return redirect()->route('staff.dashboard')
                ->with('error', 'Your account is not assigned to a branch. Please contact administrator.');
        }

        $branchId = $staff->branch_id;

        $query = Laundry::with(['customer', 'service', 'branch'])
            ->where('branch_id', $branchId);

        if ($request->filled('search'))     $query->search($request->search);
        if ($request->filled('status'))     $query->byStatus($request->status);
        if ($request->filled('service_id')) $query->where('service_id', $request->service_id);
        if ($request->filled('date_from'))  $query->whereDate('created_at', '>=', $request->date_from);
        if ($request->filled('date_to'))    $query->whereDate('created_at', '<=', $request->date_to);

        $laundries = $query->latest()->paginate(20);

        $stats = [
            'total'           => Laundry::where('branch_id', $branchId)->count(),
            'received'        => Laundry::where('branch_id', $branchId)->where('status', 'received')->count(),
            'processing'      => Laundry::where('branch_id', $branchId)->where('status', 'processing')->count(),
            'ready'           => Laundry::where('branch_id', $branchId)->where('status', 'ready')->count(),
            'completed'       => Laundry::where('branch_id', $branchId)->where('status', 'completed')->count(),
            'total_revenue'   => Laundry::where('branch_id', $branchId)->where('status', 'completed')->sum('total_amount'),
            'today_laundries' => Laundry::where('branch_id', $branchId)->whereDate('created_at', today())->count(),
        ];

        $services = Service::where('is_active', true)
            ->orderBy('display_laundry')
            ->orderBy('name')
            ->get();

        return view('staff.laundries.index', compact('laundries', 'stats', 'services'));
    }

    /**
     * Show create laundry form (with optional pickup request)
     */
    public function create(Request $request)
    {
        $staff = Auth::user();

        if (!$staff || !$staff->branch_id) {
            return redirect()->route('staff.dashboard')
                ->with('error', 'Your account is not assigned to a branch. Please contact administrator.');
        }

        $pickup = null;
        if ($request->has('pickup_id')) {
            $pickup = PickupRequest::with(['customer', 'branch', 'service'])
                ->where('branch_id', $staff->branch_id)
                ->findOrFail($request->pickup_id);
        }

        $customers = Customer::where('is_active', true)
            ->where('preferred_branch_id', $staff->branch_id)
            ->orderBy('name')
            ->get();

        $services = Service::where('is_active', true)
            ->orderBy('display_laundry')
            ->orderBy('name')
            ->get();

        $promotions = Promotion::active()
            ->valid()
            ->where(function ($query) use ($staff, $pickup) {
                $query->whereNull('branch_id')
                      ->orWhere('branch_id', $pickup?->branch_id ?? $staff->branch_id);
            })
            ->orderBy('display_laundry')
            ->get();

        $addons        = AddOn::where('is_active', true)->orderBy('name')->get();
        $currentBranch = Branch::find($staff->branch_id);

        // Get all active branches for the branch dropdown
        $branches = Branch::where('is_active', true)->get();

        // Get staff members for the current branch
        $staffMembers = User::where('branch_id', $staff->branch_id)
            ->where('role', 'staff')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('staff.laundries.create', compact(
            'customers', 'services', 'currentBranch', 'pickup',
            'promotions', 'addons', 'branches',
        ) + ['staff' => $staffMembers,
             'routePrefix' => 'staff',
             'layout'      => 'staff.layouts.app',
        ]);
    }

    /**
     * Store new laundry
     */
   public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_id'       => 'required|exists:customers,id',
            'service_id'        => 'nullable|exists:services,id',
            'weight'            => 'nullable|numeric|min:0|max:1000',
            'number_of_loads'   => 'required|integer|min:1',
            'pickup_date'       => 'nullable|date|after_or_equal:today',
            'delivery_date'     => 'nullable|date|after_or_equal:today',
            'notes'             => 'nullable|string|max:1000',
            'pickup_request_id' => 'nullable|exists:pickup_requests,id',
            'pickup_fee'        => 'nullable|numeric|min:0',
            'delivery_fee'      => 'nullable|numeric|min:0',
            'addons'            => 'nullable|array',
            'addons.*.id'       => 'required_with:addons|integer|exists:add_ons,id',
            'addons.*.quantity' => 'nullable|integer|min:1|max:99',
            'promotion_id'      => 'nullable|integer|exists:promotions,id',
        ]);

        $staff = Auth::user();

        if (!$staff || !$staff->branch_id) {
            return back()->with('error', 'Your account is not assigned to a branch.');
        }

        if (empty($validated['service_id']) && empty($validated['promotion_id'])) {
            return back()->withErrors(['service_id' => 'Either a service or a promotion is required'])->withInput();
        }

        if (empty($validated['service_id']) && !empty($validated['promotion_id'])) {
            $promo = Promotion::find($validated['promotion_id']);
            if (!$promo || $promo->application_type !== 'per_load_override') {
                return back()->withErrors(['promotion_id' => 'Selected promotion cannot be applied without selecting a service.'])->withInput();
            }
        }

        DB::beginTransaction();

        try {
            $pickupRequest = null;
            if (!empty($validated['pickup_request_id'])) {
                $pickupRequest = PickupRequest::find($validated['pickup_request_id']);
            }

            $service               = null;
            $pricingType           = 'per_load';
            $unitPrice             = 0;
            $loads                 = (int) ($validated['number_of_loads'] ?? 1);
            $weight                = (float) ($validated['weight'] ?? 0);
            $snapshotPricePerPiece = 0;
            $snapshotPricePerLoad  = 0;
            $serviceSubtotal       = 0;

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

            // ── Promotion ──────────────────────────────────────────────────
            $promotion              = null;
            $promotionDiscount      = 0;
            $promotionOverrideTotal = null;
            $promotionPricePerLoad  = null;
            $finalServiceSubtotal   = $serviceSubtotal;

            if (!empty($validated['promotion_id'])) {
                $promotion = Promotion::find($validated['promotion_id']);

                if ($promotion && $promotion->isValid) {
                    $laundryData = [
                        'subtotal'   => $serviceSubtotal,
                        'service_id' => $service?->id,
                        'branch_id'  => $staff->branch_id,
                        'weight'     => $weight,
                        'loads'      => $loads,
                    ];

                    if ($promotion->isApplicableTo($laundryData)) {
                        if ($promotion->application_type === 'per_load_override') {
                            $promotionPricePerLoad  = $promotion->display_price;
                            $promotionOverrideTotal = $promotion->display_price * $loads;
                            $promotionDiscount      = 0;
                            $finalServiceSubtotal   = $promotionOverrideTotal;
                            if (empty($validated['service_id'])) {
                                $serviceSubtotal = 0;
                            }
                        } elseif ($promotion->discount_type === 'percentage' && !empty($validated['service_id'])) {
                            $effect               = $promotion->calculateEffect($serviceSubtotal, $loads);
                            $promotionDiscount    = $effect['discount_amount'];
                            $finalServiceSubtotal = $effect['final_subtotal'];
                        }
                    }
                }
            }

            // ── Add-ons: build pivot data with quantity and calculate total ──
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

            // ── Fees ───────────────────────────────────────────────────────
            $pickupFee = $request->filled('pickup_fee')
                ? (float) $validated['pickup_fee']
                : (float) ($pickupRequest->pickup_fee ?? 0);

            $deliveryFee = $request->filled('delivery_fee')
                ? (float) $validated['delivery_fee']
                : (float) ($pickupRequest->delivery_fee ?? 0);

            $totalAmount    = $finalServiceSubtotal + $addonsTotal + $pickupFee + $deliveryFee;
            $trackingNumber = $this->generateTrackingNumber();

            $laundry = Laundry::create([
                'tracking_number'          => $trackingNumber,
                'customer_id'              => $validated['customer_id'],
                'branch_id'                => $staff->branch_id,
                'service_id'               => $service?->id,
                'staff_id'                 => $staff->id,
                'created_by'               => $staff->id,
                'weight'                   => $weight,
                'number_of_loads'          => $loads,
                'price_per_piece'          => $snapshotPricePerPiece,
                'price_per_load'           => $snapshotPricePerLoad,
                'subtotal'                 => $serviceSubtotal,
                'addons_total'             => $addonsTotal,
                'discount_amount'          => $promotionDiscount,
                'promotion_id'             => $promotion?->id,
                'promotion_override_total' => $promotionOverrideTotal,
                'promotion_price_per_load' => $promotionPricePerLoad,
                'pickup_fee'               => $pickupFee,
                'delivery_fee'             => $deliveryFee,
                'total_amount'             => $totalAmount,
                'status'                   => 'received',
                'payment_status'           => 'pending',
                'received_at'              => now(),
                'notes'                    => $validated['notes'] ?? null,
                'pickup_request_id'        => $validated['pickup_request_id'] ?? null,
            ]);

            if (!empty($addonPivot)) {
                $laundry->addons()->attach($addonPivot);
            }

            $laundry->statusHistories()->create([
                'status'     => 'received',
                'changed_by' => $staff->id,
                'notes'      => 'Laundry created'
                    . ($service ? '' : ' with fixed price promotion')
                    . ($pickupRequest ? ' from pickup #' . $pickupRequest->id : '')
                    . ($addonsTotal > 0 ? ' with ' . count($addonPivot) . ' add-on(s)' : ''),
            ]);

            if ($promotion && $promotionDiscount > 0) {
                PromotionUsage::create([
                    'promotion_id'    => $promotion->id,
                    'laundries_id'    => $laundry->id,
                    'user_id'         => $staff->id,
                    'discount_amount' => $promotionDiscount,
                    'original_amount' => $serviceSubtotal,
                    'final_amount'    => $finalServiceSubtotal,
                    'code_used'       => $promotion->promo_code,
                    'applied_at'      => now(),
                ]);

                $promotion->incrementUsage();
            }

            if ($pickupRequest) {
                $pickupRequest->update([
                    'laundries_id' => $laundry->id,
                    'pickup_fee'   => $pickupFee,
                    'delivery_fee' => $deliveryFee,
                    'status'       => 'picked_up',
                    'picked_up_at' => now(),
                ]);
            }

            DB::commit();

            return redirect()
                ->route('staff.laundries.show', $laundry)
                ->with('success', 'Laundry created successfully! Tracking #: ' . $trackingNumber);

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error creating laundry: ' . $e->getMessage())->withInput();
        }
    }
    
    public function show(Laundry $laundry)
    {
        $staff = Auth::user();
        if (!$staff || !$staff->branch_id) {
            return redirect()->route('staff.dashboard')->with('error', 'Your account is not assigned to a branch.');
        }
        if ($laundry->branch_id != $staff->branch_id) {
            abort(403, 'Unauthorized: This laundry belongs to a different branch.');
        }
        $laundry->load(['customer', 'service', 'branch', 'staff', 'pickupRequest', 'addons', 'promotion']);
        return view('staff.laundries.show', compact('laundry'));
    }

    public function edit(Laundry $laundry)
    {
        $staff = Auth::user();
        if (!$staff || !$staff->branch_id) {
            return redirect()->route('staff.dashboard')->with('error', 'Your account is not assigned to a branch.');
        }
        if ($laundry->branch_id != $staff->branch_id) {
            abort(403, 'Unauthorized: This laundry belongs to a different branch.');
        }
        if (in_array($laundry->status, ['completed', 'cancelled'])) {
            return redirect()->route('staff.laundries.show', $laundry)
                ->with('error', 'Cannot edit completed or cancelled laundries.');
        }
        $customers = Customer::where('is_active', true)
            ->where('preferred_branch_id', $staff->branch_id)
            ->orderBy('name')->get();
        $services = Service::where('is_active', true)->orderBy('name')->get();
        return view('staff.laundries.edit', compact('laundry', 'customers', 'services'));
    }

    public function update(Request $request, Laundry $laundry)
    {
        $staff = Auth::user();
        if (!$staff || !$staff->branch_id) {
            return back()->with('error', 'Your account is not assigned to a branch.');
        }
        if ($laundry->branch_id != $staff->branch_id) {
            abort(403, 'Unauthorized: This laundry belongs to a different branch.');
        }
        if (in_array($laundry->status, ['completed', 'cancelled'])) {
            return redirect()->route('staff.laundries.show', $laundry)
                ->with('error', 'Cannot edit completed or cancelled laundries.');
        }

        $validated = $request->validate([
            'customer_id'     => 'required|exists:customers,id',
            'service_id'      => 'required_without:number_of_loads|exists:services,id',
            'number_of_loads' => 'nullable|integer|min:1',
            'weight'          => 'nullable|numeric|min:0|max:1000',
            'pickup_date'     => 'nullable|date',
            'delivery_date'   => 'nullable|date',
            'notes'           => 'nullable|string|max:1000',
        ]);

        $pricePerPiece  = $laundry->price_per_piece;
        $subtotal       = $laundry->subtotal;
        $discountAmount = $laundry->discount_amount ?? 0;

        if ($validated['number_of_loads'] ?? null) {
            $promo = $laundry->promotion;
            if ($promo && $promo->application_type === 'per_load_override') {
                $subtotal       = $promo->display_price * $validated['number_of_loads'];
                $discountAmount = 0;
            }
        } elseif ($laundry->service_id != ($validated['service_id'] ?? $laundry->service_id)) {
            $service       = Service::findOrFail($validated['service_id']);
            $pricingType   = $service->pricing_type ?? 'per_load';
            $unitPrice     = $pricingType === 'per_piece'
                ? (float) ($service->price_per_piece ?? 0)
                : (float) ($service->price_per_load  ?? 0);
            $pricePerPiece = $pricingType === 'per_piece' ? $unitPrice : null;
            $loads         = $validated['number_of_loads'] ?? $laundry->number_of_loads ?? 1;
            $subtotal      = $unitPrice * $loads;
        }

        $pickupFee   = $laundry->pickup_fee   ?? 0;
        $deliveryFee = $laundry->delivery_fee ?? 0;
        $addonsTotal = $laundry->addons_total ?? 0;
        $totalAmount = $subtotal - $discountAmount + $pickupFee + $deliveryFee + $addonsTotal;

        $laundry->update([
            'customer_id'     => $validated['customer_id'],
            'service_id'      => $validated['service_id'],
            'weight'          => (float) ($validated['weight'] ?? 0),
            'number_of_loads' => $validated['number_of_loads'] ?? null,
            'price_per_piece' => $pricePerPiece,
            'subtotal'        => $subtotal,
            'total_amount'    => $totalAmount,
            'pickup_date'     => $validated['pickup_date']   ?? null,
            'delivery_date'   => $validated['delivery_date'] ?? null,
            'notes'           => $validated['notes']         ?? null,
        ]);

        return redirect()->route('staff.laundries.show', $laundry)
            ->with('success', 'Laundry updated successfully!');
    }

    public function updateStatus(Request $request, Laundry $laundry)
    {
        $staff = Auth::user();
        if (!$staff || !$staff->branch_id) {
            return back()->with('error', 'Your account is not assigned to a branch.');
        }
        if ($laundry->branch_id != $staff->branch_id) {
            abort(403, 'Unauthorized: This laundry belongs to a different branch.');
        }

        $validated = $request->validate([
            'status' => 'required|in:received,processing,ready,completed',
            'notes'  => 'nullable|string|max:500',
        ]);

        $oldStatus = $laundry->status;
        $newStatus = $validated['status'];
        $laundry->update(['status' => $newStatus]);

        if ($newStatus === 'processing' && !$laundry->processing_at) $laundry->update(['processing_at' => now()]);
        elseif ($newStatus === 'ready'   && !$laundry->ready_at)      $laundry->update(['ready_at'      => now()]);
        elseif ($newStatus === 'completed' && !$laundry->completed_at) $laundry->update(['completed_at' => now()]);

        $laundry->statusHistories()->create([
            'status'     => $newStatus,
            'changed_by' => $staff->id,
            'notes'      => $validated['notes'] ?? "Status changed from {$oldStatus} to {$newStatus}",
        ]);

        return back()->with('success', 'Laundry status updated to: ' . ucfirst($newStatus));
    }

    public function receipt(Laundry $laundry)
    {
        $staff = Auth::user();
        if (!$staff || !$staff->branch_id) {
            return redirect()->route('staff.dashboard')->with('error', 'Your account is not assigned to a branch.');
        }
        if ($laundry->branch_id != $staff->branch_id) {
            abort(403, 'Unauthorized: This laundry belongs to a different branch.');
        }
        $laundry->load(['customer', 'service', 'branch', 'staff', 'pickupRequest', 'addons', 'promotion']);
        return view('staff.laundries.receipt', compact('laundry'));
    }

    public function recordPayment(Request $request, Laundry $laundry)
    {
        $staff = Auth::user();
        if (!$staff || !$staff->branch_id) {
            return redirect()->route('staff.laundries.show', $laundry)
                ->with('error', 'Your account is not assigned to a branch.');
        }
        if ($laundry->branch_id != $staff->branch_id) {
            abort(403, 'Unauthorized: This laundry belongs to a different branch.');
        }
        $laundry->updateStatus('paid', $staff, 'Payment recorded');
        return redirect()->route('staff.laundries.show', $laundry)
            ->with('success', 'Payment recorded successfully!');
    }

    private function generateTrackingNumber(): string
    {
        do {
            $tracking = 'WB-' . date('Ymd') . '-' . strtoupper(Str::random(4));
        } while (Laundry::where('tracking_number', $tracking)->exists());
        return $tracking;
    }
}
