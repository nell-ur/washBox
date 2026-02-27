<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Branch extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'code',              // Changed from branch_code for consistency
        'branch_code',       // Keep for backward compatibility
        'location',          // Keep for backward compatibility
        'address',           // Detailed address
        'city',              // NEW: City
        'province',          // NEW: Province
        'phone',
        'email',
        'manager_name',
        'latitude',          // NEW: For map integration
        'longitude',         // NEW: For map integration
        'operating_hours',   // JSON field
        'photo_url',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
        'operating_hours' => 'array',      // ✅ Cast to array for JSON handling
        'latitude' => 'decimal:8',         // ✅ For precise coordinates
        'longitude' => 'decimal:8',        // ✅ For precise coordinates
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = [
        'full_name',
        'full_address',      // ✅ NEW: Needed by API
        'map_url',           // ✅ NEW: Needed by API
        'status_label',
        'status_color',
    ];

    // ========================================================================
    // RELATIONSHIPS
    // ========================================================================

    /**
     * Get all laundries for this branch.
     */
    public function laundries()
    {
        return $this->hasMany(Laundry::class);
    }

    /**
     * Get all staff members for this branch.
     */
    public function staff()
    {
        return $this->hasMany(User::class)->where('role', 'staff');
    }

    /**
     * Get all customers who have placed laundries at this branch.
     */
    public function customers()
    {
        return $this->hasManyThrough(Customer::class, Laundry::class);
    }

    /**
     * Get all services offered at this branch.
     */
    public function services()
    {
        return $this->belongsToMany(Service::class, 'branch_services')
            ->withPivot('is_available')
            ->withTimestamps();
    }

    /**
     * Get all promotions for this branch.
     */
    public function promotions()
    {
        return $this->hasMany(Promotion::class);
    }

    /**
     * Get all customer ratings for this branch.
     */
    public function ratings()
    {
        return $this->hasMany(CustomerRating::class);
    }

    /**
     * Get the average rating for this branch.
     */
    public function getAverageRating()
    {
        return round($this->ratings()->avg('rating') ?? 0, 1);
    }

    // ========================================================================
    // SCOPES
    // ========================================================================

    /**
     * Scope a query to only include active branches.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to only include inactive branches.
     */
    public function scopeInactive($query)
    {
        return $query->where('is_active', false);
    }

    // ========================================================================
    // ACCESSORS (For API Responses)
    // ========================================================================

    /**
     * Get the code attribute (handles both 'code' and 'branch_code')
     */
    public function getCodeAttribute($value)
    {
        return $value ?? $this->attributes['branch_code'] ?? null;
    }

    /**
     * Get the full address (address + city + province)
     * ✅ REQUIRED BY API
     */
    public function getFullAddressAttribute()
    {
        $parts = array_filter([
            $this->address,
            $this->city,
            $this->province,
        ]);

        return implode(', ', $parts) ?: $this->location;
    }

    /**
     * Get Google Maps URL based on coordinates
     * ✅ REQUIRED BY API
     */
    public function getMapUrlAttribute()
    {
        if ($this->latitude && $this->longitude) {
            return "https://www.google.com/maps?q={$this->latitude},{$this->longitude}";
        }
        return null;
    }

    /**
     * Get the photo URL attribute (handle both relative and full paths).
     */
    public function getPhotoUrlAttribute($value)
    {
        if (!$value) {
            return null;
        }

        // If it's already a full URL, return it
        if (filter_var($value, FILTER_VALIDATE_URL)) {
            return $value;
        }

        // Otherwise, return storage URL
        return asset('storage/' . $value);
    }

    /**
     * Get the display name with branch code.
     */
    public function getFullNameAttribute()
    {
        $code = $this->code ?? $this->branch_code;
        if ($code) {
            return "{$this->name} ({$code})";
        }
        return $this->name;
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
     * Calculate total revenue for this branch.
     */
    public function getTotalRevenueAttribute()
    {
        return $this->laundries()->sum('total_amount');
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
        return $this->laundries()->avg('total_amount') ?? 0;
    }

    // ========================================================================
    // OPERATING HOURS METHODS (For Mobile App)
    // ========================================================================

    /**
     * Check if branch is currently open
     * ✅ REQUIRED BY API
     *
     * @return bool
     */
    public function isOpen()
    {
        if (!$this->operating_hours || !is_array($this->operating_hours)) {
            return true; // Assume open if no hours set
        }

        $now = now();
        $dayOfWeek = $now->format('l'); // Monday, Tuesday, etc.

        if (!isset($this->operating_hours[$dayOfWeek])) {
            return false; // No hours defined for today
        }

        $hours = $this->operating_hours[$dayOfWeek];

        // Check if closed
        if ($hours === 'closed' || (is_array($hours) && isset($hours['status']) && $hours['status'] === 'closed')) {
            return false;
        }

        // Check if hours are set
        if (!is_array($hours) || !isset($hours['open']) || !isset($hours['close'])) {
            return false;
        }

        try {
            $openTime = Carbon::parse($hours['open']);
            $closeTime = Carbon::parse($hours['close']);

            return $now->between($openTime, $closeTime);
        } catch (\Exception $e) {
            return true; // If parsing fails, assume open
        }
    }

    /**
     * Get today's operating hours
     * ✅ REQUIRED BY API
     *
     * @return array|string|null
     */
    public function getTodayHours()
    {
        if (!$this->operating_hours || !is_array($this->operating_hours)) {
            return null;
        }

        $day = now()->format('l'); // Monday, Tuesday, etc.

        return $this->operating_hours[$day] ?? null;
    }

    /**
     * Get formatted operating hours for today
     *
     * @return string
     */
    public function getTodayHoursFormatted()
    {
        $hours = $this->getTodayHours();

        if (!$hours) {
            return 'Hours not available';
        }

        if ($hours === 'closed' || (is_array($hours) && isset($hours['status']) && $hours['status'] === 'closed')) {
            return 'Closed';
        }

        if (is_array($hours) && isset($hours['open']) && isset($hours['close'])) {
            return "{$hours['open']} - {$hours['close']}";
        }

        return 'Hours not available';
    }

    /**
     * Get all operating hours in a formatted way
     *
     * @return array
     */
    public function getFormattedOperatingHours()
    {
        if (!$this->operating_hours || !is_array($this->operating_hours)) {
            return [];
        }

        $formatted = [];

        foreach ($this->operating_hours as $day => $hours) {
            if ($hours === 'closed') {
                $formatted[$day] = 'Closed';
            } elseif (is_array($hours) && isset($hours['open']) && isset($hours['close'])) {
                $formatted[$day] = "{$hours['open']} - {$hours['close']}";
            } else {
                $formatted[$day] = 'Not set';
            }
        }

        return $formatted;
    }

    // ========================================================================
    // HELPER METHODS
    // ========================================================================

    /**
     * Check if branch has any laundries.
     */
    public function hasLaundries()
    {
        return $this->laundries()->exists();
    }

    /**
     * Check if branch has any active staff.
     */
    public function hasActiveStaff()
    {
        return $this->staff()->where('is_active', true)->exists();
    }

    /**
     * Get distance from given coordinates (in kilometers)
     * Uses Haversine formula
     *
     * @param float $latitude
     * @param float $longitude
     * @return float|null
     */
    public function getDistanceFrom($latitude, $longitude)
    {
        if (!$this->latitude || !$this->longitude) {
            return null;
        }

        $earthRadius = 6371; // km

        $latDiff = deg2rad($this->latitude - $latitude);
        $lngDiff = deg2rad($this->longitude - $longitude);

        $a = sin($latDiff / 2) * sin($latDiff / 2) +
             cos(deg2rad($latitude)) * cos(deg2rad($this->latitude)) *
             sin($lngDiff / 2) * sin($lngDiff / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        $distance = $earthRadius * $c;

        return round($distance, 2);
    }

    /**
     * Check if branch has coordinates set
     *
     * @return bool
     */
    public function hasCoordinates()
    {
        return !is_null($this->latitude) && !is_null($this->longitude);
    }
}
