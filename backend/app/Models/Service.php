<?php

namespace App\Models;

use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Service extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'slug',
        'description',
        'price_per_piece',
        'price_per_load',
        'pricing_type',
        'service_type_id',
        'service_type',
        'min_weight',
        'max_weight',
        'turnaround_time',
        'icon_path',
        'is_active',
        'category', // Make sure category is in fillable
        'display_laundry',
    ];

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        static::saving(function (Service $service) {
            // Only auto-set category if it's NOT already set AND service_type exists
            // This prevents overriding the manually selected category
            if (empty($service->category) && !empty($service->service_type)) {
                $service->category = match (true) {
                    in_array($service->service_type, ['regular_clothes', 'special_item', 'full_service']) => 'drop_off',
                    str_contains($service->service_type, 'self_service') => 'self_service',
                    str_contains($service->service_type, 'addon') => 'addon',
                    default => 'drop_off',
                };
            }

            // Generate slug if not set
            if (empty($service->slug) && !empty($service->name)) {
                $service->slug = Str::slug($service->name);
            }
        });
    }

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'price_per_piece' => 'decimal:2',
        'price_per_load' => 'decimal:2',
        'min_weight' => 'decimal:2',
        'max_weight' => 'decimal:2',
        'turnaround_time' => 'integer',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get all laundries using this service.
     */
    public function laundries()
    {
        return $this->hasMany(Laundry::class);
    }

    /**
     * Get branches that offer this service.
     */
    public function branches()
    {
        return $this->belongsToMany(Branch::class, 'branch_services')
            ->withPivot('is_available')
            ->withTimestamps();
    }

    /**
     * Get the service type relationship
     */
    public function serviceType()
    {
        return $this->belongsTo(ServiceType::class);
    }

    /**
     * Scope a query to only include active services.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to only include inactive services.
     */
    public function scopeInactive($query)
    {
        return $query->where('is_active', false);
    }

    /**
     * Scope a query by service type.
     */
    public function scopeByType($query, $type)
    {
        return $query->where('service_type', $type);
    }

    /**
     * Scope a query by pricing type.
     */
    public function scopeByPricingType($query, $type)
    {
        return $query->where('pricing_type', $type);
    }

    /**
     * Get the icon URL attribute.
     */
    public function getIconUrlAttribute()
    {
        if (!$this->icon_path) {
            return null;
        }

        // If it's already a full URL, return it
        if (filter_var($this->icon_path, FILTER_VALIDATE_URL)) {
            return $this->icon_path;
        }

        // Otherwise, return storage URL
        return asset('storage/' . $this->icon_path);
    }

    /**
     * Get the formatted price attribute based on pricing type.
     */
    public function getFormattedPriceAttribute()
    {
        if ($this->pricing_type === 'per_piece') {
            return '₱' . number_format($this->price_per_piece ?? 0, 2) . '/piece';
        }
        return '₱' . number_format($this->price_per_load ?? 0, 2) . '/load';
    }

    /**
     * Get the actual price value based on pricing type.
     */
    public function getPriceAttribute()
    {
        return $this->pricing_type === 'per_piece' ? $this->price_per_piece : $this->price_per_load;
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
     * Get formatted service type.
     */
    public function getServiceTypeDisplayAttribute()
    {
        if (!$this->service_type) {
            return 'General Service';
        }
        return ucfirst(str_replace('_', ' ', $this->service_type));
    }

    /**
     * Human-readable service type label.
     */
    public function getServiceTypeLabelAttribute()
    {
        return match ($this->service_type) {
            'full_service' => 'Full Service',
            'self_service' => 'Self Service',
            'special_item' => 'Special Item',
            'addon' => 'Add-on',
            default => 'General Service',
        };
    }

    /**
     * Human-readable pricing type label.
     */
    public function getPricingTypeLabelAttribute()
    {
        return match ($this->pricing_type) {
            'per_piece' => 'Per Piece',
            default => 'Per Load',
        };
    }

    /**
     * Get formatted pricing type.
     */
    public function getPricingTypeDisplayAttribute()
    {
        if (!$this->pricing_type) {
            return 'Not Set';
        }

        return ucfirst(str_replace('_', ' ', $this->pricing_type));
    }

    /**
     * Calculate total revenue from this service.
     */
    public function getTotalRevenueAttribute()
    {
        return $this->laundries()->where('status', 'completed')->sum('total_amount');
    }

    /**
     * Get count of completed laundries.
     */
    public function getCompletedLaundriesCountAttribute()
    {
        return $this->laundries()->where('status', 'completed')->count();
    }

    /**
     * Get count of pending laundries.
     */
    public function getPendingLaundriesCountAttribute()
    {
        return $this->laundries()->whereIn('status', ['pending', 'processing', 'ready'])->count();
    }

    /**
     * Get average laundry value.
     */
    public function getAvgLaundryValueAttribute()
    {
        return $this->laundries()->where('status', 'completed')->avg('total_amount') ?? 0;
    }

    /**
     * Get total weight processed.
     */
    public function getTotalWeightAttribute()
    {
        return $this->laundries()->sum('weight') ?? 0;
    }

    /**
     * Check if service has any laundries.
     */
    public function hasLaundries()
    {
        return $this->laundries()->exists();
    }

    /**
     * Calculate price based on quantity and pricing type.
     *
     * @param float|null $quantity
     * @return float
     */
    public function calculatePrice($quantity = null)
    {
        if ($this->pricing_type === 'per_piece') {
            return (float) ($this->price_per_piece ?? 0) * ($quantity ?? 1);
        }
        return (float) ($this->price_per_load ?? 0);
    }

    /**
     * Check if this service is priced per piece.
     */
    public function isPerPiece(): bool
    {
        return $this->pricing_type === 'per_piece';
    }

    /**
     * Check if this service is priced per load.
     */
    public function isPerLoad(): bool
    {
        return $this->pricing_type === 'per_load';
    }

    /**
     * Get popular services (most laundries).
     */
    public static function popular($limit = 5)
    {
        return static::withCount('laundries')
            ->orderBy('laundries_count', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get top revenue services.
     */
    public static function topRevenue($limit = 5)
    {
        return static::select('services.*')
            ->selectRaw('(SELECT SUM(total_amount) FROM laundries WHERE laundries.service_id = services.id AND laundries.status = "completed") as revenue')
            ->orderBy('revenue', 'desc')
            ->limit($limit)
            ->get();
    }
}
