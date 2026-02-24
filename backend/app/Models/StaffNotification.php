<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class StaffNotification extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'type',
        'title',
        'message',
        'icon',
        'color',
        'link',
        'data',
        'branch_id',
        'read_at',
    ];

    protected $casts = [
        'data' => 'array',
        'read_at' => 'datetime',
    ];

    // ===========================
    // RELATIONSHIPS
    // ===========================

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    // ===========================
    // SCOPES
    // ===========================

    public function scopeUnread($query)
    {
        return $query->whereNull('read_at');
    }

    public function scopeRead($query)
    {
        return $query->whereNotNull('read_at');
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeForBranch($query, $branchId)
    {
        return $query->where('branch_id', $branchId);
    }

    // ===========================
    // ACCESSORS
    // ===========================

    public function getIsReadAttribute()
    {
        return $this->read_at !== null;
    }

    public function getTimeAgoAttribute()
    {
        return $this->created_at->diffForHumans();
    }

    public function getIconClassAttribute()
    {
        $icons = [
            'pickup_assigned' => 'bi-truck',
            'laundry_assigned' => 'bi-cart',
            'new_pickup' => 'bi-geo-alt',
            'urgent_pickup' => 'bi-exclamation-triangle',
            'customer_arriving' => 'bi-person-walking',
            'shift_reminder' => 'bi-clock',
            'message' => 'bi-chat',
            'system' => 'bi-gear',
        ];
        return $icons[$this->type] ?? 'bi-bell';
    }

    // ===========================
    // METHODS
    // ===========================

    public function markAsRead()
    {
        $this->update(['read_at' => now()]);
    }

    // ===========================
    // STATIC HELPERS
    // ===========================

    /**
     * Notify staff of assigned pickup
     */
    public static function notifyPickupAssigned($pickup, $staffId)
    {
        return self::create([
            'user_id' => $staffId,
            'type' => 'pickup_assigned',
            'title' => 'New Pickup Assigned',
            'message' => "You've been assigned pickup at {$pickup->pickup_address}",
            'icon' => 'truck',
            'color' => 'primary',
            'link' => route('staff.pickups.show', $pickup->id),
            'data' => [
                'pickup_id' => $pickup->id,
                'customer_name' => $pickup->customer->name,
                'address' => $pickup->pickup_address,
                'scheduled_date' => $pickup->preferred_date?->format('Y-m-d'),
            ],
            'branch_id' => $pickup->branch_id,
        ]);
    }

    /**
     * Notify staff of new or laundry assigned
     */
    public static function notifyLaundryAssigned($laundry, $staffId)
    {
        return self::create([
            'user_id' => $staffId,
            'type' => 'laundry_assigned',
            'title' => 'Laundry Assigned to You',
            'message' => "Laundry #{$laundry->tracking_number} has been assigned to you",
            'icon' => 'cart',
            'color' => 'info',
            'link' => route('staff.laundries.show', $laundry->id),
            'data' => [
                'laundries_id' => $laundry->id,
                'tracking_number' => $laundry->tracking_number,
            ],
            'branch_id' => $laundry->branch_id,
        ]);
    }

    /**
     * Notify all staff in branch of new pickup request
     */
    public static function notifyBranchNewPickup($pickup)
    {
        $staffUsers = User::where('branch_id', $pickup->branch_id)
            ->where('role', 'staff')
            ->where('is_active', true)
            ->get();

        foreach ($staffUsers as $staff) {
            self::create([
                'user_id' => $staff->id,
                'type' => 'new_pickup',
                'title' => 'New Pickup Request',
                'message' => "Customer {$pickup->customer->name} requested pickup",
                'icon' => 'geo-alt',
                'color' => 'info',
                'link' => route('staff.pickups.show', $pickup->id),
                'branch_id' => $pickup->branch_id,
            ]);
        }
    }

    /**
     * Notify staff of urgent unclaimed or laundry
     */
    public static function notifyUrgentUnclaimed($laundry, $staffId)
    {
        return self::create([
            'user_id' => $staffId,
            'type' => 'urgent_pickup',
            'title' => 'Urgent: Unclaimed Laundry',
            'message' => "Laundry #{$laundry->tracking_number} unclaimed for {$laundry->days_unclaimed} days",
            'icon' => 'exclamation-triangle',
            'color' => 'warning',
            'link' => route('staff.laundries.show', $laundry->id),
            'branch_id' => $laundry->branch_id,
        ]);
    }
}
