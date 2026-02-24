<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Pricing;
use App\Models\Service;
use App\Models\Branch;
use Illuminate\Http\Request;

class PricingController extends Controller
{
    /**
     * Display a listing of pricing rules
     */
    public function index(Request $request)
    {
        $query = Pricing::with(['service', 'branch']);

        // Filter by service
        if ($request->filled('service_id')) {
            $query->where('service_id', $request->service_id);
        }

        // Filter by branch
        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        // Filter by status
        if ($request->filled('status')) {
            if ($request->status === 'active') {
                $query->where('is_active', true);
            } elseif ($request->status === 'inactive') {
                $query->where('is_active', false);
            }
        }

        // Filter by type
        if ($request->filled('pricing_type')) {
            $query->where('pricing_type', $request->pricing_type);
        }

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Sort
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $pricings = $query->paginate(12);
        $services = Service::active()->get();
        $branches = Branch::active()->get();

        // Statistics
        $stats = [
            'total' => Pricing::count(),
            'active' => Pricing::where('is_active', true)->count(),
            'inactive' => Pricing::where('is_active', false)->count(),
            'special' => Pricing::where('pricing_type', 'special')->count(),
        ];

        return view('admin.pricing.index', compact('pricings', 'services', 'branches', 'stats'));
    }

    /**
     * Show the form for creating a new pricing rule
     */
    public function create()
    {
        $services = Service::active()->get();
        $branches = Branch::active()->get();
        return view('admin.pricing.create', compact('services', 'branches'));
    }

    /**
     * Store a newly created pricing rule in storage
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'service_id' => 'nullable|exists:services,id',
            'branch_id' => 'nullable|exists:branches,id',
            'pricing_type' => 'required|in:standard,bulk,member,seasonal,special',
            'price_per_piece' => 'required|numeric|min:0',
            'min_weight' => 'nullable|numeric|min:0',
            'max_weight' => 'nullable|numeric|min:0',
            'discount_percentage' => 'nullable|numeric|min:0|max:100',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'is_active' => 'nullable|boolean',
        ]);

        $validated['is_active'] = $request->has('is_active') ? true : false;

        Pricing::create($validated);

        return redirect()->route('admin.pricing.index')
            ->with('success', 'Pricing rule created successfully!');
    }

    /**
     * Display the specified pricing rule
     */
    public function show(Pricing $pricing)
    {
        $pricing->load(['service', 'branch']);

        // Calculate statistics if needed
        $stats = [
            'applicable_services' => $pricing->service ? 1 : Service::count(),
            'applicable_branches' => $pricing->branch ? 1 : Branch::count(),
        ];

        return view('admin.pricing.show', compact('pricing', 'stats'));
    }

    /**
     * Show the form for editing the specified pricing rule
     */
    public function edit(Pricing $pricing)
    {
        $services = Service::active()->get();
        $branches = Branch::active()->get();
        return view('admin.pricing.edit', compact('pricing', 'services', 'branches'));
    }

    /**
     * Update the specified pricing rule in storage
     */
    public function update(Request $request, Pricing $pricing)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'service_id' => 'nullable|exists:services,id',
            'branch_id' => 'nullable|exists:branches,id',
            'pricing_type' => 'required|in:standard,bulk,member,seasonal,special',
            'price_per_piece' => 'required|numeric|min:0',
            'min_weight' => 'nullable|numeric|min:0',
            'max_weight' => 'nullable|numeric|min:0',
            'discount_percentage' => 'nullable|numeric|min:0|max:100',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'is_active' => 'nullable|boolean',
        ]);

        $validated['is_active'] = $request->has('is_active') ? true : false;

        $pricing->update($validated);

        return redirect()->route('admin.pricing.index')
            ->with('success', 'Pricing rule updated successfully!');
    }

    /**
     * Remove the specified pricing rule from storage
     */
    public function destroy(Pricing $pricing)
    {
        $pricing->delete();

        return redirect()->route('admin.pricing.index')
            ->with('success', 'Pricing rule deleted successfully!');
    }

    /**
     * Toggle pricing rule active/inactive status
     */
    public function toggleStatus(Pricing $pricing)
    {
        $pricing->update([
            'is_active' => !$pricing->is_active
        ]);

        $status = $pricing->is_active ? 'activated' : 'deactivated';

        return redirect()->route('admin.pricing.index')
            ->with('success', "Pricing rule {$status} successfully!");
    }
}
