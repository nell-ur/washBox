<?php

namespace App\Http\Controllers\Admin;

use App\Models\Branch;
use App\Models\Promotion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;

class PromotionController extends Controller
{
    /**
     * Display promotion listing
     */
    public function index()
    {
        $promotions = Promotion::with('branch')->latest()->paginate(12);

        $stats = [
            'total' => Promotion::count(),
            'active' => Promotion::where('is_active', true)
                ->where('start_date', '<=', now())
                ->where('end_date', '>=', now())
                ->count(),
            'scheduled' => Promotion::where('is_active', true)
                ->where('start_date', '>', now())
                ->count(),
            'expired' => Promotion::where('end_date', '<', now())->count(),
            'total_usage' => Promotion::sum('usage_count'),
        ];

        return view('admin.promotions.index', compact('promotions', 'stats'));
    }

    /**
     * Show form for creating promotion
     */
    public function create(Request $request)
    {
        $branches = Branch::where('is_active', true)->get();
        $mode = $request->query('mode', 'simple');

        // Route to different creation forms
        return match($mode) {
            'poster' => view('admin.promotions.create-poster', compact('branches')),
            'fixed-price' => view('admin.promotions.create-fixed-price', compact('branches')),
            default => view('admin.promotions.create', compact('branches')),
        };
    }

    /**
     * Store a new promotion
     */
    public function store(Request $request)
    {
        // Detect promotion type
        $applicationType = $request->input('application_type');
        $type = $request->input('type');

        // Fixed-price promotion
        if ($applicationType === 'per_load_override') {
            return $this->storeFixedPricePromotion($request);
        }

        // Poster promotion
        if ($type === 'poster_promo' || $request->has('poster_title')) {
            return $this->storePosterPromotion($request);
        }

        // Simple percentage discount
        return $this->storeSimplePromotion($request);
    }

    /**
     * Store FIXED-PRICE promotion (₱179/load)
     */
    private function storeFixedPricePromotion(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'display_price' => 'required|numeric|min:0',
            'price_unit' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'promo_code' => 'nullable|string|unique:promotions,promo_code',
            'description' => 'nullable|string',
            'banner_image' => 'nullable|image|max:2048',
            'branch_id' => 'nullable|exists:branches,id',
            'display_laundry' => 'nullable|integer|min:0',
            'max_usage' => 'nullable|integer|min:1',
        ]);

        $data = [
            'name' => $request->name,
            'type' => 'fixed_price',
            'application_type' => 'per_load_override',
            'display_price' => $request->display_price,
            'price_unit' => $request->price_unit,
            'description' => $request->description,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'promo_code' => $request->promo_code,
            'branch_id' => $request->branch_id,
            'display_laundry' => $request->display_laundry ?? 0,
            'max_usage' => $request->max_usage,
            'is_active' => $request->has('is_active'),
            'pricing_data' => [
                'fixed_price' => $request->display_price,
                'unit' => $request->price_unit,
            ],
        ];

        if ($request->hasFile('banner_image')) {
            $data['banner_image'] = $request->file('banner_image')->store('promotions', 'public');
        }

        Promotion::create($data);

        return redirect()->route('admin.promotions.index')
            ->with('success', 'Fixed-price promotion created successfully!');
    }

    /**
     * Store simple percentage discount promotion
     */
    private function storeSimplePromotion(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'discount_percent' => 'required|numeric|min:1|max:100',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'promo_code' => 'nullable|string|unique:promotions,promo_code',
            'banner_image' => 'nullable|image|max:2048',
            'branch_id' => 'nullable|exists:branches,id',
            'min_amount' => 'nullable|numeric|min:0',
            'max_usage' => 'nullable|integer|min:1',
            'display_laundry' => 'nullable|integer|min:0',
        ]);

        $data = [
            'name' => $request->name,
            'type' => 'percentage_discount',
            'application_type' => 'discount',
            'discount_type' => 'percentage',
            'discount_value' => $request->discount_percent,
            'pricing_data' => ['percentage' => $request->discount_percent],
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'promo_code' => $request->promo_code,
            'branch_id' => $request->branch_id,
            'is_active' => $request->input('is_active', 1) == 1,
            'min_amount' => $request->input('min_amount', 0),
            'max_usage' => $request->max_usage,
            'display_laundry' => $request->input('display_laundry', 0),
            'featured' => $request->has('featured'),
        ];

        if ($request->hasFile('banner_image')) {
            $data['banner_image'] = $request->file('banner_image')->store('promotions', 'public');
        }

        Promotion::create($data);

        return redirect()->route('admin.promotions.index')
            ->with('success', 'Promotion created successfully!');
    }

    /**
     * Store poster-style promotion
     */
    private function storePosterPromotion(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'poster_title' => 'required|string|max:255',
            'poster_subtitle' => 'nullable|string|max:255',
            'display_price' => 'required|numeric|min:0',
            'price_unit' => 'required|string|max:255',
            'poster_features' => 'nullable|array',
            'poster_features.*' => 'nullable|string',
            'poster_notes' => 'nullable|string',
            'color_theme' => 'required|string|in:blue,purple,green',
            'background_image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'branch_id' => 'nullable|exists:branches,id',
            'promo_code' => 'nullable|string|max:50',
            'description' => 'nullable|string',
        ]);

        $features = array_filter($request->input('poster_features', []), fn($f) => !empty($f));

        $data = [
            'name' => $request->name,
            'type' => 'poster_promo',
            'application_type' => 'per_load_override',
            'poster_title' => $request->poster_title,
            'poster_subtitle' => $request->poster_subtitle,
            'display_price' => $request->display_price,
            'price_unit' => $request->price_unit,
            'poster_features' => array_values($features),
            'poster_notes' => $request->poster_notes,
            'color_theme' => $request->color_theme,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'branch_id' => $request->branch_id,
            'promo_code' => $request->promo_code,
            'description' => $request->description,
            'is_active' => $request->has('is_active'),
            'pricing_data' => [],
        ];

        if ($request->hasFile('background_image')) {
            $data['banner_image'] = $request->file('background_image')->store('promotions/backgrounds', 'public');
        }

        Promotion::create($data);

        return redirect()->route('admin.promotions.index')
            ->with('success', 'Poster promotion created successfully!');
    }

    /**
     * Show the form for editing
     */
    public function edit($id)
    {
        $promotion = Promotion::findOrFail($id);
        $branches = Branch::where('is_active', true)->get();

        // Route to appropriate edit view
        if ($promotion->isPosterPromotion()) {
            return view('admin.promotions.edit-poster', compact('promotion', 'branches'));
        }

        if ($promotion->isFixedPricePromotion() && $promotion->type !== 'poster_promo') {
            return view('admin.promotions.edit-fixed-price', compact('promotion', 'branches'));
        }

        return view('admin.promotions.edit', compact('promotion', 'branches'));
    }

    /**
     * Update the specified promotion
     */
    public function update(Request $request, $id)
    {
        $promotion = Promotion::findOrFail($id);

        // Route to appropriate update method
        if ($promotion->isPosterPromotion()) {
            return $this->updatePosterPromotion($request, $promotion);
        }

        if ($promotion->isFixedPricePromotion() && $promotion->type !== 'poster_promo') {
            return $this->updateFixedPricePromotion($request, $promotion);
        }

        return $this->updateSimplePromotion($request, $promotion);
    }

    /**
     * Update fixed-price promotion
     */
    private function updateFixedPricePromotion(Request $request, Promotion $promotion)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'display_price' => 'required|numeric|min:0',
            'price_unit' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'promo_code' => 'nullable|string|unique:promotions,promo_code,' . $promotion->id,
            'description' => 'nullable|string',
            'banner_image' => 'nullable|image|max:2048',
            'branch_id' => 'nullable|exists:branches,id',
        ]);

        $data = [
            'name' => $request->name,
            'display_price' => $request->display_price,
            'price_unit' => $request->price_unit,
            'description' => $request->description,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'promo_code' => $request->promo_code,
            'branch_id' => $request->branch_id,
            'is_active' => $request->has('is_active'),
            'pricing_data' => [
                'fixed_price' => $request->display_price,
                'unit' => $request->price_unit,
            ],
        ];

        if ($request->hasFile('banner_image')) {
            if ($promotion->banner_image) {
                Storage::disk('public')->delete($promotion->banner_image);
            }
            $data['banner_image'] = $request->file('banner_image')->store('promotions', 'public');
        }

        $promotion->update($data);

        return redirect()->route('admin.promotions.index')
            ->with('success', 'Fixed-price promotion updated successfully!');
    }

    /**
     * Update simple promotion
     */
    private function updateSimplePromotion(Request $request, Promotion $promotion)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'discount_percent' => 'required|numeric|min:1|max:100',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'promo_code' => 'nullable|string|unique:promotions,promo_code,' . $promotion->id,
            'banner_image' => 'nullable|image|max:2048',
            'branch_id' => 'nullable|exists:branches,id',
        ]);

        $data = [
            'name' => $request->name,
            'discount_value' => $request->discount_percent,
            'pricing_data' => ['percentage' => $request->discount_percent],
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'promo_code' => $request->promo_code,
            'branch_id' => $request->branch_id,
        ];

        if ($request->hasFile('banner_image')) {
            if ($promotion->banner_image) {
                Storage::disk('public')->delete($promotion->banner_image);
            }
            $data['banner_image'] = $request->file('banner_image')->store('promotions', 'public');
        }

        $promotion->update($data);

        return redirect()->route('admin.promotions.index')
            ->with('success', 'Promotion updated successfully.');
    }

    /**
     * Update poster promotion
     */
    private function updatePosterPromotion(Request $request, Promotion $promotion)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'poster_title' => 'required|string|max:255',
            'poster_subtitle' => 'nullable|string|max:255',
            'display_price' => 'required|numeric|min:0',
            'price_unit' => 'required|string|max:255',
            'poster_features' => 'nullable|array',
            'poster_features.*' => 'nullable|string',
            'poster_notes' => 'nullable|string',
            'color_theme' => 'required|string|in:blue,purple,green',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'branch_id' => 'nullable|exists:branches,id',
            'background_image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'description' => 'nullable|string',
            'promo_code' => 'nullable|string|max:50',
        ]);

        $features = array_filter($request->input('poster_features', []), fn($f) => !empty($f));

        $data = [
            'name' => $request->name,
            'poster_title' => $request->poster_title,
            'poster_subtitle' => $request->poster_subtitle,
            'display_price' => $request->display_price,
            'price_unit' => $request->price_unit,
            'poster_features' => array_values($features),
            'poster_notes' => $request->poster_notes,
            'color_theme' => $request->color_theme,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'branch_id' => $request->branch_id,
            'is_active' => $request->has('is_active'),
            'description' => $request->description,
            'promo_code' => $request->promo_code,
        ];

        if ($request->hasFile('background_image')) {
            if ($promotion->banner_image) {
                Storage::disk('public')->delete($promotion->banner_image);
            }
            $data['banner_image'] = $request->file('background_image')->store('promotions/backgrounds', 'public');
        }

        $promotion->update($data);

        return redirect()->route('admin.promotions.index')
            ->with('success', 'Poster promotion updated successfully!');
    }

    /**
     * Display the specified promotion
     */
    public function show($id)
    {
        $promotion = Promotion::with(['branch', 'usages.customer', 'usages.laundry'])
            ->findOrFail($id);

        return view('admin.promotions.show', compact('promotion'));
    }

    /**
     * Delete promotion
     */
    public function destroy($id)
    {
        $promotion = Promotion::findOrFail($id);

        // Delete associated images
        if ($promotion->banner_image) {
            Storage::disk('public')->delete($promotion->banner_image);
        }
        if ($promotion->generated_poster_path) {
            Storage::disk('public')->delete($promotion->generated_poster_path);
        }

        $promotion->delete();

        return redirect()->route('admin.promotions.index')
            ->with('success', 'Promotion deleted successfully!');
    }

    /**
     * Toggle promotion active/inactive status
     */
    public function toggleStatus($id)
    {
        $promotion = Promotion::findOrFail($id);

        $promotion->update([
            'is_active' => !$promotion->is_active
        ]);

        $status = $promotion->is_active ? 'activated' : 'deactivated';

        return redirect()->route('admin.promotions.index')
            ->with('success', "Promotion {$status} successfully!");
    }
}
