<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UnclaimedLaundry extends Model
{
    use HasFactory;

    protected $fillable = [
        'laundries_id',
        'customer_id',
        'branch_id',
        'days_unclaimed',
        'status',           // unclaimed, recovered, disposed
        'recovered_at',
        'recovered_by',
        'disposed_at',
        'disposed_by',      // ← ADDED
        'disposal_reason',  // ← ADDED
        'notes',
    ];

    protected $casts = [
        'recovered_at' => 'datetime',
        'disposed_at' => 'datetime',
    ];

    // ========================================================================
    // RELATIONSHIPS
    // ========================================================================

   public function laundry(): BelongsTo
{
    return $this->belongsTo(Laundry::class, 'laundries_id');
}

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function recoveredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recovered_by');
    }

    /**
     * User who disposed this laundry
     */
    public function disposedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'disposed_by');
    }

    public function reminders(): HasMany
    {
        return $this->hasMany(UnclaimedReminder::class);
    }

    // ========================================================================
    // SCOPES
    // ========================================================================

    public function scopeUnclaimed($query)
    {
        return $query->where('status', 'unclaimed');
    }

    public function scopeRecovered($query)
    {
        return $query->where('status', 'recovered');
    }

    public function scopeDisposed($query)
    {
        return $query->where('status', 'disposed');
    }

    public function scopeByBranch($query, int $branchId)
    {
        return $query->where('branch_id', $branchId);
    }

    /**
     * Laundries unclaimed for X or more days
     */
    public function scopeUnclaimedForDays($query, int $days)
    {
        return $query->where('status', 'unclaimed')
            ->where('days_unclaimed', '>=', $days);
    }

    /**
     * Critical laundries (14+ days)
     */
    public function scopeCritical($query)
    {
        return $query->where('status', 'unclaimed')
            ->where('days_unclaimed', '>=', 14);
    }

    /**
     * Urgent laundries (7-13 days)
     */
    public function scopeUrgent($query)
    {
        return $query->where('status', 'unclaimed')
            ->where('days_unclaimed', '>=', 7)
            ->where('days_unclaimed', '<', 14);
    }

    /**
     * Warning laundries (3-6 days)
     */
    public function scopeWarning($query)
    {
        return $query->where('status', 'unclaimed')
            ->where('days_unclaimed', '>=', 3)
            ->where('days_unclaimed', '<', 7);
    }

    /**
     * Ready for disposal (30+ days)
     */
    public function scopeReadyForDisposal($query)
    {
        $threshold = config('unclaimed.disposal_after_days', 30);
        return $query->where('status', 'unclaimed')
            ->where('days_unclaimed', '>=', $threshold);
    }

    /**
     * Disposed this month
     */
    public function scopeDisposedThisMonth($query)
    {
        return $query->where('status', 'disposed')
            ->whereMonth('disposed_at', now()->month)
            ->whereYear('disposed_at', now()->year);
    }

    /**
     * Recovered this month
     */
    public function scopeRecoveredThisMonth($query)
    {
        return $query->where('status', 'recovered')
            ->whereMonth('recovered_at', now()->month)
            ->whereYear('recovered_at', now()->year);
    }

    // ========================================================================
    // ACCESSORS
    // ========================================================================

    /**
     * Get urgency status based on days unclaimed
     */
    public function getUrgencyStatusAttribute(): string
    {
        $days = $this->days_unclaimed;

        if ($days >= 14) return 'critical';
        if ($days >= 7) return 'urgent';
        if ($days >= 3) return 'warning';
        if ($days >= 1) return 'pending';
        return 'normal';
    }

    /**
     * Get urgency color for UI
     */
    public function getUrgencyColorAttribute(): string
    {
        $colors = [
            'critical' => 'danger',
            'urgent' => 'warning',
            'warning' => 'info',
            'pending' => 'secondary',
            'normal' => 'success',
        ];
        return $colors[$this->urgency_status] ?? 'secondary';
    }

    /**
     * Calculate storage fee
     */
    public function getStorageFeeAttribute(): float
    {
        if ($this->days_unclaimed <= 7) {
            return 0;
        }
        $extraDays = $this->days_unclaimed - 7;
        return $extraDays * config('unclaimed.storage_fee_per_day', 10);
    }

    /**
     * Get formatted storage fee
     */
    public function getFormattedStorageFeeAttribute(): string
    {
        return '₱' . number_format($this->storage_fee, 2);
    }

    /**
     * Get days until disposal
     */
    public function getDaysUntilDisposalAttribute(): int
    {
        $threshold = config('unclaimed.disposal_after_days', 30);
        return max(0, $threshold - $this->days_unclaimed);
    }

    /**
     * Check if ready for disposal
     */
    public function getCanBeDisposedAttribute(): bool
    {
        $threshold = config('unclaimed.disposal_after_days', 30);
        return $this->status === 'unclaimed' && $this->days_unclaimed >= $threshold;
    }

    /**
     * Get total amount including storage fee
     */
    public function getTotalWithFeeAttribute(): float
    {
        return ($this->laundry->total_amount ?? 0) + $this->storage_fee;
    }

    // ========================================================================
    // METHODS
    // ========================================================================

    /**
     * Update days unclaimed from laundry
     */
    public function updateDaysUnclaimed(): void
    {
        if ($this->laundry) {
            $this->days_unclaimed = $this->laundry->days_unclaimed ?? 0;
            $this->save();
        }
    }

    /**
     * Check if reminder is needed for specific day
     */
    public function needsReminder(int $day): bool
    {
        return $this->days_unclaimed >= $day &&
               !$this->reminders()->where('reminder_day', $day)->exists();
    }

    /**
     * Mark as recovered
     */
    public function markAsRecovered(?int $userId = null): void
    {
        $this->update([
            'status' => 'recovered',
            'recovered_at' => now(),
            'recovered_by' => $userId ?? auth()->id(),
        ]);
    }

    /**
     * Mark as disposed
     */
    public function markAsDisposed(?int $userId = null, ?string $reason = null): void
    {
        $this->update([
            'status' => 'disposed',
            'disposed_at' => now(),
            'disposed_by' => $userId ?? auth()->id(),
            'disposal_reason' => $reason ?? 'Exceeded storage policy',
            'notes' => ($this->notes ?? '') . ' | Disposed after ' . $this->days_unclaimed . ' days',
        ]);
    }

    /**
     * Record reminder sent
     */
    public function recordReminder(int $reminderDay, ?string $method = 'push'): void
    {
        $this->reminders()->create([
            'reminder_day' => $reminderDay,
            'sent_at' => now(),
            'method' => $method,
        ]);
    }

    /**
     * Get reminder history
     */
    public function getReminderHistory(): array
    {
        return $this->reminders()
            ->orderBy('reminder_day')
            ->get()
            ->map(fn($r) => [
                'day' => $r->reminder_day,
                'sent_at' => $r->sent_at,
                'method' => $r->method,
            ])
            ->toArray();
    }

    /**
     * Sync from laundry (create or update)
     */
    public static function syncFromLaundry(Laundry $laundry): ?self
    {
        // Only create for ready laundries
        if ($laundry->status !== 'ready' || !$laundry->ready_at) {
            return null;
        }

        $daysUnclaimed = $laundry->days_unclaimed ?? 0;

        // Only track if 3+ days unclaimed
        if ($daysUnclaimed < 3) {
            return null;
        }

        return self::updateOrCreate(
            ['laundries_id' => $laundry->id],
            [
                'customer_id' => $laundry->customer_id,
                'branch_id' => $laundry->branch_id,
                'days_unclaimed' => $daysUnclaimed,
                'status' => 'unclaimed',
            ]
        );
    }

    /**
     * Get statistics for a branch
     */
    public static function getBranchStats(int $branchId): array
    {
        $baseQuery = self::where('branch_id', $branchId)->where('status', 'unclaimed');

        return [
            'total' => (clone $baseQuery)->count(),
            'critical' => (clone $baseQuery)->where('days_unclaimed', '>=', 14)->count(),
            'urgent' => (clone $baseQuery)->where('days_unclaimed', '>=', 7)->where('days_unclaimed', '<', 14)->count(),
            'warning' => (clone $baseQuery)->where('days_unclaimed', '>=', 3)->where('days_unclaimed', '<', 7)->count(),
            'total_value' => (clone $baseQuery)->with('laundry')->get()->sum(fn($u) => $u->laundry->total_amount ?? 0),
        ];
    }

    /**
     * Get global statistics
     */
    public static function getGlobalStats(): array
    {
        $baseQuery = self::where('status', 'unclaimed');

        $totalValue = (clone $baseQuery)->with('laundry')->get()->sum(fn($u) => $u->laundry->total_amount ?? 0);
        $criticalValue = (clone $baseQuery)->where('days_unclaimed', '>=', 14)->with('laundry')->get()->sum(fn($u) => $u->laundry->total_amount ?? 0);

        // Storage fees
        $storageFees = (clone $baseQuery)->where('days_unclaimed', '>', 7)->get()->sum(fn($u) => $u->storage_fee);

        // Recovery this month
        $recoveredThisMonth = self::where('status', 'recovered')
            ->whereMonth('recovered_at', now()->month)
            ->with('laundry')
            ->get()
            ->sum(fn($u) => $u->laundry->total_amount ?? 0);

        // Loss this month
        $lossThisMonth = self::where('status', 'disposed')
            ->whereMonth('disposed_at', now()->month)
            ->with('laundry')
            ->get()
            ->sum(fn($u) => $u->laundry->total_amount ?? 0);

        return [
            'total' => (clone $baseQuery)->count(),
            'critical' => (clone $baseQuery)->where('days_unclaimed', '>=', 14)->count(),
            'urgent' => (clone $baseQuery)->where('days_unclaimed', '>=', 7)->where('days_unclaimed', '<', 14)->count(),
            'warning' => (clone $baseQuery)->where('days_unclaimed', '>=', 3)->where('days_unclaimed', '<', 7)->count(),
            'pending' => (clone $baseQuery)->where('days_unclaimed', '>=', 1)->where('days_unclaimed', '<', 3)->count(),
            'total_value' => $totalValue,
            'critical_value' => $criticalValue,
            'storage_fees' => $storageFees,
            'potential_total' => $totalValue + $storageFees,
            'recovered_this_month' => $recoveredThisMonth,
            'loss_this_month' => $lossThisMonth,
            'disposed_this_month' => self::disposedThisMonth()->count(),
        ];
    }
}
