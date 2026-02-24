<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerRating extends Model
{
    use HasFactory;

    protected $fillable = [
        'laundry_id',
        'customer_id',
        'branch_id',
        'staff_id',
        'assigned_staff_id',
        'rating',
        'comment',
        'staff_ratings',  // JSON field
        'staff_response',
        'responded_at',
    ];

    protected $casts = [
        'rating' => 'integer',
        'staff_ratings' => 'array',  // Automatically cast JSON to array
        'responded_at' => 'datetime',
    ];

    // ========================================
    // RELATIONSHIPS
    // ========================================

    public function laundry()
    {
        return $this->belongsTo(Laundry::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function staff()
    {
        return $this->belongsTo(User::class, 'staff_id');
    }

    public function assignedStaff()
    {
        return $this->belongsTo(User::class, 'assigned_staff_id');
    }

    // ========================================
    // HELPER METHODS
    // ========================================

    /**
     * Get average of all staff rating categories
     */
    public function getAverageStaffRatingAttribute()
    {
        if (empty($this->staff_ratings)) {
            return null;
        }
        
        $ratings = array_filter($this->staff_ratings, function($value) {
            return is_numeric($value) && $value >= 1 && $value <= 5;
        });
        
        return count($ratings) > 0 ? round(array_sum($ratings) / count($ratings), 1) : null;
    }

    /**
     * Check if staff has responded
     */
    public function getHasRespondedAttribute()
    {
        return !is_null($this->staff_response);
    }

    /**
     * Get response status
     */
    public function getResponseStatusAttribute()
    {
        if ($this->staff_response) {
            return 'responded';
        }
        return 'pending';
    }

    // ========================================
    // SCOPES
    // ========================================

    public function scopeByStaff($query, $staffId)
    {
        return $query->where('staff_id', $staffId);
    }

    public function scopeByBranch($query, $branchId)
    {
        return $query->where('branch_id', $branchId);
    }

    public function scopePendingResponse($query)
    {
        return $query->whereNull('staff_response');
    }

    public function scopeWithResponse($query)
    {
        return $query->whereNotNull('staff_response');
    }

    public function scopeHighRating($query, $minRating = 4)
    {
        return $query->where('rating', '>=', $minRating);
    }

    public function scopeLowRating($query, $maxRating = 2)
    {
        return $query->where('rating', '<=', $maxRating);
    }
}