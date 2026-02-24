<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PromotionUsage extends Model
{
    // Use default timestamps (migration uses timestamps())

    protected $fillable = [
        'promotion_id',
        'laundries_id',
        'user_id',
        'customer_id',
        'discount_amount',
        'original_amount',
        'final_amount',
        'code_used',
        'applied_at',
    ];

    protected $casts = [
        'discount_amount' => 'decimal:2',
        'original_amount' => 'decimal:2',
        'final_amount' => 'decimal:2',
        'applied_at' => 'datetime',
    ];

    public function promotion(): BelongsTo
    {
        return $this->belongsTo(Promotion::class);
    }

    public function laundry(): BelongsTo
    {
        return $this->belongsTo(Laundry::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function getFormattedDiscountAttribute(): string
    {
        return '₱' . number_format((float) $this->discount_amount, 2);
    }

    public function getFormattedOriginalAttribute(): string
    {
        return '₱' . number_format((float) $this->original_amount, 2);
    }

    public function getFormattedFinalAttribute(): string
    {
        return '₱' . number_format((float) $this->final_amount, 2);
    }

    public function getDiscountPercentageAttribute(): float
    {
        if ($this->original_amount == 0) {
            return 0;
        }

        return ($this->discount_amount / $this->original_amount) * 100;
    }


}
