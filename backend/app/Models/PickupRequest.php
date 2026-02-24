<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PickupRequest extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'customer_id',
        'branch_id',
        'pickup_address',
        'latitude',
        'longitude',
        'preferred_date',
        'preferred_time',
        'phone_number',        // ✅ ADDED
        'notes',
        'service_id',
        'assigned_to',
        'status',
        'accepted_at',
        'en_route_at',
        'picked_up_at',
        'cancelled_at',
        'cancellation_reason',
        'cancelled_by',
        'laundries_id',
    ];

    protected $casts = [
        'preferred_date' => 'date',
        'accepted_at' => 'datetime',
        'en_route_at' => 'datetime',
        'picked_up_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    /**
     * Relationships
     */

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    public function assignedStaff()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function cancelledByUser()
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function laundry()
    {
        return $this->belongsTo(Laundry::class);
    }

    /**
     * Scopes
     */

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeAccepted($query)
    {
        return $query->where('status', 'accepted');
    }

    public function scopeEnRoute($query)
    {
        return $query->where('status', 'en_route');
    }

    public function scopePickedUp($query)
    {
        return $query->where('status', 'picked_up');
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', ['pending', 'accepted', 'en_route']);
    }

    public function scopeForBranch($query, $branchId)
    {
        return $query->where('branch_id', $branchId);
    }

    public function scopeForDate($query, $date)
    {
        return $query->whereDate('preferred_date', $date);
    }

    /**
     * Helper Methods
     */

    public function isPending()
    {
        return $this->status === 'pending';
    }

    public function isAccepted()
    {
        return $this->status === 'accepted';
    }

    public function isEnRoute()
    {
        return $this->status === 'en_route';
    }

    public function isPickedUp()
    {
        return $this->status === 'picked_up';
    }

    public function isCancelled()
    {
        return $this->status === 'cancelled';
    }

    public function canBeCancelled()
    {
        return in_array($this->status, ['pending', 'accepted']);
    }

    public function canBeAccepted()
    {
        return $this->status === 'pending';
    }

    public function canMarkEnRoute()
    {
        return $this->status === 'accepted';
    }

    public function canMarkPickedUp()
    {
        return in_array($this->status, ['accepted', 'en_route']);
    }

    public function hasLaundry()
    {
        return !is_null($this->laundries_id);
    }

    /**
     * Status Transition Methods
     */

    public function accept($userId = null)
    {
        $this->update([
            'status' => 'accepted',
            'accepted_at' => now(),
            'assigned_to' => $userId,
        ]);
    }

    public function markEnRoute()
    {
        $this->update([
            'status' => 'en_route',
            'en_route_at' => now(),
        ]);
    }

    public function markPickedUp()
    {
        $this->update([
            'status' => 'picked_up',
            'picked_up_at' => now(),
        ]);
    }

    public function cancel($reason = null, $userId = null)
    {
        $this->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
            'cancellation_reason' => $reason,
            'cancelled_by' => $userId,
        ]);
    }

    public function linkToLaundry($laundryId)
    {
        $this->update([
            'laundries_id' => $laundryId,
        ]);
    }

    /**
     * Accessors
     */

    public function getStatusBadgeColorAttribute()
    {
        $colors = [
            'pending' => 'warning',
            'accepted' => 'info',
            'en_route' => 'primary',
            'picked_up' => 'success',
            'cancelled' => 'danger',
        ];

        return $colors[$this->status] ?? 'secondary';
    }

    public function getFormattedPreferredDateTimeAttribute()
    {
        $date = $this->preferred_date->format('M d, Y');

        if ($this->preferred_time) {
            return $date . ' at ' . date('g:i A', strtotime($this->preferred_time));
        }

        return $date;
    }

    public function getMapUrlAttribute()
    {
        if (!$this->latitude || !$this->longitude) {
            return null;
        }

        return "https://www.google.com/maps?q={$this->latitude},{$this->longitude}";
    }

    public function getDistanceFrom($latitude, $longitude)
    {
        if (!$this->latitude || !$this->longitude) {
            return null;
        }

        // Haversine formula
        $earthRadius = 6371; // km

        $latFrom = deg2rad($latitude);
        $lonFrom = deg2rad($longitude);
        $latTo = deg2rad($this->latitude);
        $lonTo = deg2rad($this->longitude);

        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;

        $angle = 2 * asin(sqrt(pow(sin($latDelta / 2), 2) +
            cos($latFrom) * cos($latTo) * pow(sin($lonDelta / 2), 2)));

        return $angle * $earthRadius;
    }


    // app/Models/PickupRequest.php
    public function getTotalFeeAttribute(): float
    {
        return (float) ($this->pickup_fee + $this->delivery_fee);
    }

}
