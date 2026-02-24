<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class Promotion extends Model
{
    use SoftDeletes;

    protected $fillable = [
        // Basic Info
        'name',
        'description',
        'type',
        'application_type',
        'discount_type',
        'discount_value',
        'pricing_data',
        'min_amount',
        'promo_code',

        // Fixed-Price Fields
        'display_price',
        'price_unit',

        // Poster Fields
        'poster_title',
        'poster_subtitle',
        'poster_features',
        'poster_notes',
        'color_theme',
        'generated_poster_path',

        // Applicability
        'applicable_services',
        'applicable_days',

        // Schedule
        'start_date',
        'end_date',

        // Targeting
        'branch_id',

        // Status
        'is_active',
        'banner_image',
        'display_laundry',
        'featured',

        // Usage
        'usage_count',
        'max_usage',
    ];

    protected $casts = [
        'pricing_data' => 'array',
        'applicable_services' => 'array',
        'applicable_days' => 'array',
        'poster_features' => 'array',
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'is_active' => 'boolean',
        'featured' => 'boolean',
        'min_amount' => 'decimal:2',
        'display_price' => 'decimal:2',
        'discount_value' => 'decimal:2',
        'usage_count' => 'integer',
        'max_usage' => 'integer',
    ];

    // ========================================================================
    // RELATIONSHIPS
    // ========================================================================

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function specialItems(): HasMany
    {
        return $this->hasMany(SpecialItemPricing::class);
    }

    public function usages(): HasMany
    {
        return $this->hasMany(PromotionUsage::class);
    }

    public function laundries(): HasMany
    {
        return $this->hasMany(Laundry::class);
    }

    // ========================================================================
    // SCOPES
    // ========================================================================

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeValid($query)
    {
        $now = now();
        return $query->where('start_date', '<=', $now)
                    ->where('end_date', '>=', $now);
    }

    public function scopeFeatured($query)
    {
        return $query->where('featured', true);
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeForBranch($query, ?int $branchId)
    {
        return $query->where(function($q) use ($branchId) {
            $q->whereNull('branch_id')
              ->orWhere('branch_id', $branchId);
        });
    }

    public function scopeApplicableToday($query)
    {
        $today = Carbon::now()->format('l');
        return $query->where(function($q) use ($today) {
            $q->whereNull('applicable_days')
              ->orWhereJsonContains('applicable_days', $today);
        });
    }

    public function scopeLaundryPromotions($query)
    {
        return $query->orderBy('display_laundry')->orderBy('created_at', 'desc');
    }

    public function scopePosterPromotions($query)
    {
        return $query->where('type', 'poster_promo')
                    ->whereNotNull('display_price');
    }

    public function scopeFixedPrice($query)
    {
        return $query->where('application_type', 'per_load_override');
    }

    public function scopeDiscountType($query)
    {
        return $query->where('application_type', 'discount');
    }

    // ========================================================================
    // ATTRIBUTES
    // ========================================================================

    public function getIsValidAttribute(): bool
    {
        $now = now();
        return $this->is_active
            && $this->start_date <= $now
            && $this->end_date >= $now
            && !$this->is_maxed_out;
    }

    public function getIsMaxedOutAttribute(): bool
    {
        if ($this->max_usage === null) {
            return false;
        }
        return $this->usage_count >= $this->max_usage;
    }

    public function getRemainingUsageAttribute(): ?int
    {
        if ($this->max_usage === null) {
            return null;
        }
        return max(0, $this->max_usage - $this->usage_count);
    }

    // ========================================================================
    // APPLICABILITY CHECKS
    // ========================================================================

    /**
     * Check if promotion is applicable to laundry data
     */
    public function isApplicableTo(array $laundryData): bool
    {
        if (!$this->is_valid) {
            return false;
        }

        // Check branch
        if ($this->branch_id && $this->branch_id != ($laundryData['branch_id'] ?? null)) {
            return false;
        }

        // Check minimum amount
        if ($this->min_amount > 0 && ($laundryData['subtotal'] ?? 0) < $this->min_amount) {
            return false;
        }

        // Check applicable services
        if ($this->applicable_services && isset($laundryData['service_id'])) {
            if (!in_array($laundryData['service_id'], $this->applicable_services)) {
                return false;
            }
        }

        // Check applicable days
        $today = Carbon::now()->format('l');
        if ($this->applicable_days && !in_array($today, $this->applicable_days)) {
            return false;
        }

        return true;
    }

    // ========================================================================
    // PRICE CALCULATION
    // ========================================================================

    /**
     * Calculate the effect of this promotion on a laundry
     *
     * Compatible with your existing LaundryController!
     */
    public function calculateEffect(float $serviceSubtotal, int $loads = 1): array
    {
        if ($this->application_type === 'per_load_override') {
            // FIXED PRICE PER LOAD (e.g., ₱179/load)
            $overrideTotal = $loads * $this->display_price;
            $discountAmount = max(0, $serviceSubtotal - $overrideTotal);

            return [
                'type' => 'per_load_override',
                'discount_amount' => $discountAmount,
                'override_total' => $overrideTotal,
                'price_per_load' => $this->display_price,
                'final_subtotal' => $overrideTotal,
                'display_text' => '₱' . number_format($this->display_price, 2) . '/load'
            ];
        } else {
            // REGULAR DISCOUNT (percentage or fixed amount)
            if ($this->discount_type === 'percentage') {
                $discountAmount = ($serviceSubtotal * $this->discount_value) / 100;
                $displayText = $this->discount_value . '% OFF';
            } else {
                $discountAmount = $this->discount_value;
                $displayText = '₱' . number_format($this->discount_value, 2) . ' OFF';
            }

            // Ensure discount doesn't exceed subtotal
            $discountAmount = min($discountAmount, $serviceSubtotal);
            $finalSubtotal = $serviceSubtotal - $discountAmount;

            return [
                'type' => 'discount',
                'discount_amount' => $discountAmount,
                'override_total' => null,
                'price_per_load' => null,
                'final_subtotal' => $finalSubtotal,
                'display_text' => $displayText
            ];
        }
    }

    // ========================================================================
    // HELPER METHODS
    // ========================================================================

    public function incrementUsage(): void
    {
        $this->increment('usage_count');
    }

    public function getStatusBadgeClass(): string
    {
        if (!$this->is_active) {
            return 'danger';
        }

        if ($this->is_maxed_out) {
            return 'warning';
        }

        $now = now();
        if ($now < $this->start_date) {
            return 'info';
        }

        if ($now > $this->end_date) {
            return 'secondary';
        }

        return 'success';
    }

    /**
     * Check if this is a poster promotion
     */
    public function isPosterPromotion(): bool
    {
        return $this->type === 'poster_promo' && $this->display_price !== null;
    }

    /**
     * Check if this is a fixed-price promotion
     */
    public function isFixedPricePromotion(): bool
    {
        return $this->application_type === 'per_load_override';
    }

    /**
     * Get CSS gradient for poster color theme
     */
    public function getColorGradient(): string
    {
        return match($this->color_theme) {
            'blue' => 'linear-gradient(135deg, #0EA5E9 0%, #0284C7 100%)',
            'purple' => 'linear-gradient(135deg, #8B5CF6 0%, #7C3AED 100%)',
            'green' => 'linear-gradient(135deg, #10B981 0%, #059669 100%)',
            default => 'linear-gradient(135deg, #0EA5E9 0%, #0284C7 100%)',
        };
    }

    /**
     * Get status for display
     */
    public function getStatus(): string
    {
        if (!$this->is_active) {
            return 'Inactive';
        }

        $now = now();

        if ($now < $this->start_date) {
            return 'Scheduled';
        }

        if ($now > $this->end_date) {
            return 'Expired';
        }

        if ($this->max_usage && $this->usage_count >= $this->max_usage) {
            return 'Maxed Out';
        }

        return 'Active';
    }

    /**
     * Get type label for display
     */
    public function getTypeLabel(): string
    {
        return match($this->type) {
            'percentage_discount' => 'Percentage Discount',
            'fixed_discount' => 'Fixed Discount',
            'fixed_price' => 'Fixed Price Per Load',
            'poster_promo' => 'Poster Promotion',
            default => ucwords(str_replace('_', ' ', $this->type)),
        };
    }

    /**
     * Get formatted price for display in dropdown
     */
    public function getFormattedPriceAttribute(): string
    {
        if ($this->application_type === 'per_load_override') {
            return '₱' . number_format($this->display_price, 0) . '/load';
        }

        if ($this->discount_type === 'percentage') {
            return $this->discount_value . '% OFF';
        }

        if ($this->discount_type === 'fixed') {
            return '₱' . number_format($this->discount_value, 0) . ' OFF';
        }

        return 'Special Offer';
    }
}
