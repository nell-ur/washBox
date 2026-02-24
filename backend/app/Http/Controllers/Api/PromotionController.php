<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Promotion;
use Illuminate\Http\Request;

class PromotionController extends Controller
{
    /**
     * Get all active promotions
     *
     * GET /api/v1/promotions
     */
    public function index()
    {
        $promotions = Promotion::where('is_active', true)
            ->where('start_date', '<=', now())
            ->where('end_date', '>=', now())
            ->with('branch')
            ->latest()
            ->get();

        $formattedPromotions = $promotions->map(function ($promo) {
            return [
                'id' => $promo->id,
                'name' => $promo->name,
                'type' => $promo->type,
                'poster_title' => $promo->poster_title,
                'poster_subtitle' => $promo->poster_subtitle,
                'display_price' => $promo->display_price,
                'price_unit' => $promo->price_unit,
                'poster_features' => $promo->poster_features,
                'poster_notes' => $promo->poster_notes,
                'promo_code' => $promo->promo_code,
                'color_theme' => $promo->color_theme,
                'banner_image' => $promo->banner_image
                    ? asset('storage/' . $promo->banner_image)
                    : null,
                'is_active' => $promo->is_active,
                'start_date' => $promo->start_date->format('Y-m-d'),
                'end_date' => $promo->end_date->format('Y-m-d'),
                'branch_name' => $promo->branch->name ?? 'All Branches',
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'promotions' => $formattedPromotions,
            ]
        ]);
    }

    /**
     * Get featured promotions for home screen
     *
     * GET /api/v1/promotions/featured
     */
    public function featured()
    {
        $promotions = Promotion::where('is_active', true)
            ->where('start_date', '<=', now())
            ->where('end_date', '>=', now())
            ->where('type', 'poster_promo') // Only show poster promos on home screen
            ->with('branch')
            ->latest()
            ->limit(5)
            ->get();

        $formattedPromotions = $promotions->map(function ($promo) {
            return [
                'id' => $promo->id,
                'name' => $promo->name,
                'type' => $promo->type,
                'poster_title' => $promo->poster_title,
                'poster_subtitle' => $promo->poster_subtitle,
                'display_price' => $promo->display_price,
                'price_unit' => $promo->price_unit,
                'poster_features' => $promo->poster_features ?? [],
                'poster_notes' => $promo->poster_notes,
                'promo_code' => $promo->promo_code,
                'color_theme' => $promo->color_theme ?? 'blue',
                'banner_image' => $promo->banner_image
                    ? asset('storage/' . $promo->banner_image)
                    : null,
                'is_active' => $promo->is_active,
                'branch_name' => $promo->branch->name ?? 'All Branches',
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'promotions' => $formattedPromotions,
            ]
        ]);
    }

    /**
     * Get specific promotion details
     *
     * GET /api/v1/promotions/{id}
     */
    public function show($id)
    {
        $promotion = Promotion::with('branch')->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => [
                'promotion' => [
                    'id' => $promotion->id,
                    'name' => $promotion->name,
                    'type' => $promotion->type,
                    'description' => $promotion->description,
                    'poster_title' => $promotion->poster_title,
                    'poster_subtitle' => $promotion->poster_subtitle,
                    'display_price' => $promotion->display_price,
                    'price_unit' => $promotion->price_unit,
                    'poster_features' => $promotion->poster_features,
                    'poster_notes' => $promotion->poster_notes,
                    'promo_code' => $promotion->promo_code,
                    'color_theme' => $promotion->color_theme,
                    'banner_image' => $promotion->banner_image
                        ? asset('storage/' . $promotion->banner_image)
                        : null,
                    'is_active' => $promotion->is_active,
                    'start_date' => $promotion->start_date->format('Y-m-d'),
                    'end_date' => $promotion->end_date->format('Y-m-d'),
                    'branch' => $promotion->branch ? [
                        'id' => $promotion->branch->id,
                        'name' => $promotion->branch->name,
                    ] : null,
                ],
            ]
        ]);
    }

    /**
     * Validate a promo code and compute discount preview
     *
     * GET /api/v1/promotions/validate-code?code=...&subtotal=...&service_id=...&branch_id=...
     */
    public function validateCode(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string',
            'subtotal' => 'nullable|numeric|min:0',
            'weight' => 'nullable|numeric|min:0',
            'service_id' => 'nullable|exists:services,id',
            'branch_id' => 'nullable|exists:branches,id',
        ]);

        $code = $validated['code'];
        $subtotal = (float) ($validated['subtotal'] ?? 0);
        $weight = isset($validated['weight']) ? (float) $validated['weight'] : null;

        $promotion = Promotion::where('promo_code', $code)->first();

        if (!$promotion) {
            return response()->json([
                'success' => true,
                'data' => [
                    'is_applicable' => false,
                    'message' => 'Promo code not found',
                    'discount_value' => 0,
                ],
            ]);
        }

        $service = null;
        if (!empty($validated['service_id'])) {
            $service = \App\Models\Service::find($validated['service_id']);
        }

        $laundryCandidate = (object) [
            'subtotal' => $subtotal,
            'service' => $service,
            'branch_id' => $validated['branch_id'] ?? null,
            'weight' => $weight,
        ];

        $isApplicable = $promotion->isApplicableTo($laundryCandidate);

        $discountValue = 0;
        $finalTotal = $subtotal;
        $extra = [];

        if ($isApplicable) {
            if ($promotion->application_type === 'per_load_override' && $weight !== null) {
                $info = $promotion->computeOverrideTotal($weight);
                $overrideTotal = $info['override_total'];
                $discountValue = max(0, $subtotal - $overrideTotal);
                $finalTotal = $overrideTotal;
                $extra = [
                    'loads' => $info['loads'],
                    'override_total' => round($overrideTotal, 2),
                ];
            } else {
                $discountValue = min($promotion->calculateDiscountValue($subtotal, $weight), $subtotal);
                $finalTotal = max(0, $subtotal - $discountValue);
            }
        }

        return response()->json([
            'success' => true,
            'data' => [
                'is_applicable' => $isApplicable,
                'message' => $isApplicable ? 'Promo is applicable' : 'Promo is not applicable to this laundry',
                'discount_value' => round($discountValue, 2),
                'final_total' => round($finalTotal, 2),
                'extra' => $extra,
                'promotion' => [
                    'id' => $promotion->id,
                    'name' => $promotion->name,
                    'promo_code' => $promotion->promo_code,
                    'type' => $promotion->type,
                    'application_type' => $promotion->application_type ?? 'discount',
                ],
            ],
        ]);
    }

    /**
     * Return all promotions applicable to a candidate laundry (subtotal, weight, service, branch)
     * GET /api/v1/promotions/applicable?subtotal=...&weight=...&service_id=...&branch_id=...
     */
    public function applicable(Request $request)
    {
        $validated = $request->validate([
            'subtotal' => 'nullable|numeric|min:0',
            'weight' => 'nullable|numeric|min:0',
            'service_id' => 'nullable|exists:services,id',
            'branch_id' => 'nullable|exists:branches,id',
        ]);

        $subtotal = (float) ($validated['subtotal'] ?? 0);
        $weight = isset($validated['weight']) ? (float) $validated['weight'] : null;

        $service = null;
        if (!empty($validated['service_id'])) {
            $service = \App\Models\Service::find($validated['service_id']);
        }

        $laundryCandidate = (object) [
            'subtotal' => $subtotal,
            'service' => $service,
            'branch_id' => $validated['branch_id'] ?? null,
            'weight' => $weight,
        ];

        $promotions = Promotion::where('is_active', true)
            ->where('start_date', '<=', now())
            ->where('end_date', '>=', now())
            ->with('branch')
            ->latest()
            ->get();

        $formatted = $promotions->map(function ($promo) use ($laundryCandidate) {
            $isApplicable = $promo->isApplicableTo($laundryCandidate);

            $discountValue = 0;
            $finalTotal = $laundryCandidate->subtotal;
            $extra = [];

            if ($isApplicable) {
                if ($promo->application_type === 'per_load_override' && isset($laundryCandidate->weight)) {
                    $info = $promo->computeOverrideTotal($laundryCandidate->weight);
                    $overrideTotal = $info['override_total'];
                    $discountValue = max(0, $laundryCandidate->subtotal - $overrideTotal);
                    $finalTotal = $overrideTotal;
                    $extra = [
                        'loads' => $info['loads'],
                        'override_total' => round($overrideTotal, 2),
                    ];
                } else {
                    $discountValue = min($promo->calculateDiscountValue($laundryCandidate->subtotal, $laundryCandidate->weight), $laundryCandidate->subtotal);
                    $finalTotal = max(0, $laundryCandidate->subtotal - $discountValue);
                }
            }

            return [
                'id' => $promo->id,
                'name' => $promo->name,
                'type' => $promo->type,
                'application_type' => $promo->application_type ?? 'discount',
                'promo_code' => $promo->promo_code,
                'is_applicable' => $isApplicable,
                'discount_value' => round($discountValue, 2),
                'final_total' => round($finalTotal, 2),
                'extra' => $extra,
                'branch_name' => $promo->branch->name ?? null,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'promotions' => $formatted,
            ]
        ]);
    }
}
