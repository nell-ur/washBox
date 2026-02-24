<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

/**
 * HasBranch Trait
 *
 * CRITICAL: Automatically applies branch scoping to Eloquent models.
 *
 * Usage:
 * class Order extends Model {
 *     use HasBranch;
 * }
 *
 * Features:
 * - Automatically filters queries by current user's branch (for staff)
 * - Admins bypass branch scoping (access all branches)
 * - Prevents accidental cross-branch data access
 * - Applies to all query operations (find, where, get, etc.)
 */
trait HasBranch
{
    /**
     * Boot the HasBranch trait
     * Registers global scope for automatic branch filtering
     */
    protected static function bootHasBranch()
    {
        // Apply global scope only for staff users
        static::addGlobalScope('branch', function (Builder $builder) {
            $user = Auth::user();

            // Skip branch scoping for admin users
            if ($user && $user->role === 'admin') {
                return;
            }

            // Apply branch scoping for staff users
            if ($user && $user->role === 'staff') {
                $branchId = $user->staff->branch_id ?? config('app.current_branch_id');

                if ($branchId) {
                    $builder->where('branch_id', $branchId);
                }
            }
        });

        // Automatically set branch_id when creating new records (for staff)
        static::creating(function ($model) {
            $user = Auth::user();

            // Auto-assign branch_id for staff if not already set
            if ($user && $user->role === 'staff' && !$model->branch_id) {
                $model->branch_id = $user->staff->branch_id
                    ?? config('app.current_branch_id');
            }
        });
    }

    /**
     * Scope query to specific branch
     *
     * @param Builder $query
     * @param int $branchId
     * @return Builder
     */
    public function scopeForBranch(Builder $query, int $branchId): Builder
    {
        return $query->where('branch_id', $branchId);
    }

    /**
     * Scope query to exclude branch scoping (admin only)
     * Use with caution!
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeWithoutBranchScope(Builder $query): Builder
    {
        return $query->withoutGlobalScope('branch');
    }

    /**
     * Scope query to all branches (admin only)
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeAllBranches(Builder $query): Builder
    {
        $user = Auth::user();

        // Only allow admins to access all branches
        if ($user && $user->role === 'admin') {
            return $query->withoutGlobalScope('branch');
        }

        return $query;
    }

    /**
     * Check if model belongs to specific branch
     *
     * @param int $branchId
     * @return bool
     */
    public function belongsToBranch(int $branchId): bool
    {
        return $this->branch_id === $branchId;
    }

    /**
     * Check if model belongs to current user's branch
     *
     * @return bool
     */
    public function belongsToCurrentBranch(): bool
    {
        $user = Auth::user();

        if (!$user || !$user->role === 'staff') {
            return false;
        }

        $staffBranchId = $user->staff->branch_id ?? config('app.current_branch_id');

        return $this->branch_id === $staffBranchId;
    }

    /**
     * Relationship: Branch
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function branch()
    {
        return $this->belongsTo(\App\Models\Branch::class);
    }
}
