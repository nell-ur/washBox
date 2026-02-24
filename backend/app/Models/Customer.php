<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'phone',
        'email',
        'password',
        'registration_type',
        'address',
        'latitude',
         'branch_id',
        'longitude',
        'preferred_branch_id',
        'registered_by',
        'profile_photo',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'is_active' => 'boolean',
        'password' => 'hashed',
        'is_active' => 'boolean',
    ];

    // ========================================================================
    // RELATIONSHIPS
    // ========================================================================

    /**
     * Preferred branch for this customer
     */
    public function preferredBranch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'preferred_branch_id');
    }

    /**
     * Staff who registered this customer (for walk-in)
     */
    public function registeredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registered_by');
    }

    /**
     * Laundries placed by this customer
     */
    public function laundries(): HasMany
    {
        return $this->hasMany(Laundry::class);
    }

    /**
     * Pickup requests by this customer
     */
    public function pickupRequests(): HasMany
    {
        return $this->hasMany(PickupRequest::class);
    }

    /**
     * Notifications sent to this customer
     */
    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }

    /**
     * Device tokens for push notifications
     */
    public function deviceTokens(): HasMany
    {
        return $this->hasMany(DeviceToken::class);
    }



    /**
     * Unclaimed laundries for this customer
     */
    public function unclaimedLaundries(): HasMany
    {
        return $this->hasMany(UnclaimedLaundry::class);
    }

    /**
     * Promotion usage by this customer
     */
    public function promotionUsage(): HasMany
    {
        return $this->hasMany(PromotionUsage::class);
    }

    // ========================================================================
    // SCOPES
    // ========================================================================

    /**
     * Get only active customers
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Get walk-in customers only
     */
    public function scopeWalkIn($query)
    {
        return $query->where('registration_type', 'walk_in');
    }

    /**
     * Get self-registered customers only
     */
    public function scopeSelfRegistered($query)
    {
        return $query->where('registration_type', 'self_registered');
    }

    /**
     * Get customers by preferred branch
     */
    public function scopeByBranch($query, $branchId)
    {
        return $query->where('preferred_branch_id', $branchId);
    }

    /**
     * Search customers by name, phone, or email
     */
    public function scopeSearch($query, string $search)
    {
        return $query->where(function($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
              ->orWhere('phone', 'like', "%{$search}%")
              ->orWhere('email', 'like', "%{$search}%");
        });
    }

    // ========================================================================
    // ACCESSORS
    // ========================================================================

    /**
     * Get profile photo URL
     */
    public function getProfilePhotoUrlAttribute(): ?string
    {
        if (!$this->profile_photo) {
            return null;
        }

        return asset('storage/' . $this->profile_photo);
    }

    /**
     * Get registration type label
     */
    public function getRegistrationTypeLabelAttribute(): string
    {
        return ucfirst(str_replace('_', '-', $this->registration_type));
    }

    /**
     * Get location coordinates
     */
    public function getCoordinatesAttribute(): ?array
    {
        if (!$this->latitude || !$this->longitude) {
            return null;
        }

        return [
            'lat' => (float) $this->latitude,
            'lng' => (float) $this->longitude,
        ];
    }

    /**
     * Get Google Maps URL
     */
    public function getMapUrlAttribute(): ?string
    {
        if (!$this->latitude || !$this->longitude) {
            return null;
        }

        return "https://www.google.com/maps?q={$this->latitude},{$this->longitude}";
    }

    // ========================================================================
    // METHODS
    // ========================================================================

    /**
     * Check if customer is walk-in
     */
    public function isWalkIn(): bool
    {
        return $this->registration_type === 'walk_in';
    }

    /**
     * Check if customer is self-registered
     */
    public function isSelfRegistered(): bool
    {
        return $this->registration_type === 'self_registered';
    }

    /**
     * Check if customer can request pickup
     * Only self-registered customers can request pickup
     */
    public function canRequestPickup(): bool
    {
        return $this->isSelfRegistered() && $this->is_active;
    }

    /**
     * Get total laundries count
     */
    public function getTotalLaundriesCount(): int
    {
        return $this->laundries()->count();
    }

    /**
     * Get total amount spent
     */
    public function getTotalSpent(): float
    {
        return $this->laundries()
                    ->where('status', 'completed')
                    ->sum('total_amount');
    }

    /**
     * Get average rating given by customer
     */
    public function ratings()
{
    return $this->hasMany(CustomerRating::class);
}

public function getAverageRating()
{
    return round($this->ratings()->avg('rating') ?? 0, 1);
}

    /**
     * Get active FCM token
     */
    public function getActiveFcmToken(): ?string
    {
        return $this->deviceTokens()
                    ->where('is_active', true)
                    ->latest('last_used_at')
                    ->value('token');
    }
}
