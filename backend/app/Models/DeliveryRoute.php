<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class DeliveryRoute extends Model
{
    use HasFactory;

    protected $table = 'delivery_routes';

    protected $fillable = [
        'driver_id',
        'branch_id',
        'route_name',
        'pickup_ids',
        'route_data',
        'status',
        'total_distance',
        'total_duration',
        'estimated_fuel_cost',
        'scheduled_start',
        'estimated_completion',
        'actual_start',
        'actual_completion',
        'notes'
    ];

    protected $casts = [
        'pickup_ids' => 'array',
        'route_data' => 'array',
        'scheduled_start' => 'datetime',
        'estimated_completion' => 'datetime',
        'actual_start' => 'datetime',
        'actual_completion' => 'datetime',
        'total_distance' => 'decimal:2',
        'total_duration' => 'decimal:2',
        'estimated_fuel_cost' => 'decimal:2'
    ];

    /**
     * Get the driver assigned to this route
     */
    public function driver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'driver_id');
    }

    /**
     * Get the branch for this route
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    /**
     * Get the pickups for this route (using the pickup_ids array)
     */
    public function pickups()
    {
        if (!$this->pickup_ids || !is_array($this->pickup_ids)) {
            return collect();
        }

        return PickupRequest::whereIn('id', $this->pickup_ids)->get();
    }

    /**
     * Get formatted status
     */
    public function getStatusAttribute($value)
    {
        return ucfirst(str_replace('_', ' ', $value));
    }

    /**
     * Get formatted route name
     */
    public function getFormattedRouteNameAttribute()
    {
        return $this->route_name ?: 'Route #' . $this->id;
    }

    /**
     * Check if route is scheduled
     */
    public function isScheduled(): bool
    {
        return $this->status === 'scheduled';
    }

    /**
     * Check if route is in progress
     */
    public function isInProgress(): bool
    {
        return $this->status === 'in_progress';
    }

    /**
     * Check if route is completed
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Start the route
     */
    public function startRoute()
    {
        $this->update([
            'status' => 'in_progress',
            'actual_start' => now()
        ]);
    }

    /**
     * Complete the route
     */
    public function completeRoute()
    {
        $this->update([
            'status' => 'completed',
            'actual_completion' => now()
        ]);
    }

    /**
     * Calculate progress percentage
     */
    public function getProgressPercentageAttribute(): float
    {
        if ($this->isCompleted()) {
            return 100;
        }

        if ($this->isScheduled()) {
            return 0;
        }

        if ($this->isInProgress() && $this->pickup_ids) {
            $totalPickups = count($this->pickup_ids);
            $completedPickups = Pickup::whereIn('id', $this->pickup_ids)
                ->where('status', 'picked_up')
                ->count();

            return $totalPickups > 0 ? round(($completedPickups / $totalPickups) * 100, 2) : 0;
        }

        return 0;
    }

    /**
     * Get estimated time remaining
     */
    public function getEstimatedTimeRemainingAttribute(): ?string
    {
        if (!$this->isInProgress() || !$this->total_duration) {
            return null;
        }

        $elapsed = $this->actual_start ? now()->diffInMinutes($this->actual_start) : 0;
        $remaining = max(0, $this->total_duration - $elapsed);

        return $remaining > 60
            ? round($remaining / 60, 1) . ' hours'
            : $remaining . ' minutes';
    }
}
