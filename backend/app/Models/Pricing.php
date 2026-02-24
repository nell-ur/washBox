<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Pricing extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'description',
        'service_id',
        'branch_id',
        'pricing_type',
        'price_per_piece',
        'min_weight',
        'max_weight',
        'discount_percentage',
        'start_date',
        'end_date',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'price_per_piece' => 'decimal:2',
        'min_weight' => 'decimal:2',
        'max_weight' => 'decimal:2',
        'discount_percentage' => 'decimal:2',
        'start_date' => 'date',
        'end_date' => 'date',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the service this pricing applies to.
     */
    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    /**
     * Get the branch this pricing applies to.
     */
    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Scope a query to only include active pricing rules.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to only include inactive pricing rules.
     */
    public function scopeInactive($query)
    {
        return $query->where('is_active', false);
    }

    /**
     * Scope a query by pricing type.
     */
    public function scopeByType($query, $type)
    {
        return $query->where('pricing_type', $type);
    }

    /**
     * Scope a query to pricing valid for a specific date.
     */
    public function scopeValidOn($query, $date = null)
    {
        $date = $date ?? now();

        return $query->where(function($q) use ($date) {
            $q->where(function($q2) use ($date) {
                // Start date is null or before/equal to the date
                $q2->whereNull('start_date')
                   ->orWhere('start_date', '<=', $date);
            })->where(function($q2) use ($date) {
                // End date is null or after/equal to the date
                $q2->whereNull('end_date')
                   ->orWhere('end_date', '>=', $date);
            });
        });
    }

    /**
     * Scope for weight range.
     */
    public function scopeForWeight($query, $weight)
    {
        return $query->where(function($q) use ($weight) {
            $q->where(function($q2) use ($weight) {
                // Min weight is null or less than/equal to the weight
                $q2->whereNull('min_weight')
                   ->orWhere('min_weight', '<=', $weight);
            })->where(function($q2) use ($weight) {
                // Max weight is null or greater than/equal to the weight
                $q2->whereNull('max_weight')
                   ->orWhere('max_weight', '>=', $weight);
            });
        });
    }

    /**
     * Get the formatted price attribute.
     */
    public function getFormattedPriceAttribute()
    {
        return '₱' . number_format($this->price_per_piece, 2) . '/piece';
    }

    /**
     * Get the status label.
     */
    public function getStatusLabelAttribute()
    {
        return $this->is_active ? 'Active' : 'Inactive';
    }

    /**
     * Get the status color.
     */
    public function getStatusColorAttribute()
    {
        return $this->is_active ? 'success' : 'secondary';
    }

    /**
     * Get formatted pricing type.
     */
    public function getPricingTypeDisplayAttribute()
    {
        return ucfirst(str_replace('_', ' ', $this->pricing_type));
    }

    /**
     * Check if pricing is currently valid.
     */
    public function isCurrentlyValid()
    {
        if (!$this->is_active) {
            return false;
        }

        $now = now();

        if ($this->start_date && $this->start_date->gt($now)) {
            return false;
        }

        if ($this->end_date && $this->end_date->lt($now)) {
            return false;
        }

        return true;
    }

    /**
     * Check if weight is within range.
     */
    public function isWeightValid($weight)
    {
        if ($this->min_weight && $weight < $this->min_weight) {
            return false;
        }

        if ($this->max_weight && $weight > $this->max_weight) {
            return false;
        }

        return true;
    }

    /**
     * Calculate final price for given weight.
     */
    public function calculatePrice($weight)
    {
        $basePrice = $weight * $this->price_per_piece;

        if ($this->discount_percentage) {
            $discount = $basePrice * ($this->discount_percentage / 100);
            return $basePrice - $discount;
        }

        return $basePrice;
    }

    /**
     * Get discount amount.
     */
    public function getDiscountAmountAttribute()
    {
        if (!$this->discount_percentage) {
            return 0;
        }

        return $this->price_per_piece * ($this->discount_percentage / 100);
    }

    /**
     * Get final price after discount.
     */
    public function getFinalPriceAttribute()
    {
        return $this->price_per_piece - $this->discount_amount;
    }

    /**
     * Check if this is a bulk pricing rule.
     */
    public function isBulk()
    {
        return $this->pricing_type === 'bulk';
    }

    /**
     * Check if this is a member pricing rule.
     */
    public function isMember()
    {
        return $this->pricing_type === 'member';
    }

    /**
     * Check if this is a seasonal pricing rule.
     */
    public function isSeasonal()
    {
        return $this->pricing_type === 'seasonal';
    }

    /**
     * Check if this is a special pricing rule.
     */
    public function isSpecial()
    {
        return $this->pricing_type === 'special';
    }

    /**
     * Get applicable pricing rules for given parameters.
     */
    public static function getApplicable($serviceId = null, $branchId = null, $weight = null, $date = null)
    {
        $query = static::active();

        if ($serviceId) {
            $query->where(function($q) use ($serviceId) {
                $q->whereNull('service_id')
                  ->orWhere('service_id', $serviceId);
            });
        }

        if ($branchId) {
            $query->where(function($q) use ($branchId) {
                $q->whereNull('branch_id')
                  ->orWhere('branch_id', $branchId);
            });
        }

        if ($weight) {
            $query->forWeight($weight);
        }

        $query->validOn($date);

        return $query->orderBy('price_per_piece', 'asc')->get();
    }

    /**
     * Get the best pricing for given parameters.
     */
    public static function getBestPrice($serviceId = null, $branchId = null, $weight = null, $date = null)
    {
        $applicable = static::getApplicable($serviceId, $branchId, $weight, $date);

        if ($applicable->isEmpty()) {
            return null;
        }

        // Return the pricing with lowest final price
        return $applicable->sortBy(function($pricing) use ($weight) {
            return $pricing->calculatePrice($weight ?? 1);
        })->first();
    }
}
