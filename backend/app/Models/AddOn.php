<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AddOn extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'add_ons';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'price',
        'is_active'
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'is_active' => 'boolean'
    ];

    /**
     * Laundries that have this add-on
     * Using laundries_id as the foreign key in the pivot table
     */
    public function laundries()
    {
        return $this->belongsToMany(Laundry::class, 'laundry_addon', 'add_on_id', 'laundries_id')
            ->withPivot('price_at_purchase', 'quantity')
            ->withTimestamps();
    }

    /**
     * Get formatted price attribute
     */
    public function getFormattedPriceAttribute()
    {
        return '₱' . number_format($this->price, 2);
    }

    /**
     * Get status label
     */
    public function getStatusLabelAttribute()
    {
        return $this->is_active ? 'Active' : 'Inactive';
    }

    /**
     * Get status color
     */
    public function getStatusColorAttribute()
    {
        return $this->is_active ? 'success' : 'secondary';
    }

    /**
     * Scope active add-ons
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope inactive add-ons
     */
    public function scopeInactive($query)
    {
        return $query->where('is_active', false);
    }
}