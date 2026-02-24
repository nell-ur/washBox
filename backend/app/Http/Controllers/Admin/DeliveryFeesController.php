<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\DeliveryFee;
use Illuminate\Http\Request;

class DeliveryFeesController extends Controller
{
    /**
     * Display delivery fees management page
     */
    public function index()
    {
        $branches = Branch::with('deliveryFees')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('admin.delivery-fees.index', compact('branches'));
    }

    /**
     * Update delivery fees for a branch
     */
    public function update(Request $request, $branchId)
    {
        $request->validate([
            'pickup_fee' => 'required|numeric|min:0',
            'delivery_fee' => 'required|numeric|min:0',
            'both_discount' => 'required|numeric|min:0|max:100',
            'minimum_laundry_for_free' => 'nullable|numeric|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        $branch = Branch::findOrFail($branchId);

        // Update or create delivery fee settings
        DeliveryFee::updateOrCreate(
            ['branch_id' => $branchId],
            [
                'pickup_fee' => $request->pickup_fee,
                'delivery_fee' => $request->delivery_fee,
                'both_discount' => $request->both_discount,
                'minimum_laundry_for_free' => $request->minimum_laundry_for_free,
                'is_active' => $request->has('is_active'),
            ]
        );

        return redirect()->route('admin.delivery-fees.index')
            ->with('success', "Delivery fees updated for {$branch->name}!");
    }

    /**
     * Get delivery fees for a specific branch (AJAX)
     */
    public function show($branchId)
    {
        $deliveryFee = DeliveryFee::where('branch_id', $branchId)
            ->where('is_active', true)
            ->first();

        if (!$deliveryFee) {
            // Return default values
            return response()->json([
                'pickup_fee' => 50.00,
                'delivery_fee' => 50.00,
                'both_discount' => 10,
                'minimum_laundry_for_free' => null,
            ]);
        }

        return response()->json([
            'pickup_fee' => $deliveryFee->pickup_fee,
            'delivery_fee' => $deliveryFee->delivery_fee,
            'both_discount' => $deliveryFee->both_discount,
            'minimum_laundry_for_free' => $deliveryFee->minimum_laundry_for_free,
        ]);
    }

    /**
     * Calculate fee preview (AJAX)
     */
    public function calculatePreview(Request $request)
    {
        $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'service_type' => 'required|in:pickup_only,delivery_only,both',
            'laundry_amount' => 'nullable|numeric|min:0',
        ]);

        $deliveryFee = DeliveryFee::getOrCreateForBranch($request->branch_id);
        $fees = $deliveryFee->calculateFee($request->service_type, $request->laundry_amount);

        return response()->json($fees);
    }
}
