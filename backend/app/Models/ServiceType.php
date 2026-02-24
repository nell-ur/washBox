<?php
// app/Models/ServiceType.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'category',
        'description',
        'defaults',
        'icon',
        'is_active',
        'display_order',
    ];

    protected $casts = [
        'defaults' => 'array',
        'is_active' => 'boolean',
    ];

    public function services()
    {
        return $this->hasMany(Service::class);
    }

    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function getDefaultPriceAttribute()
    {
        return $this->defaults['price'] ?? 0;
    }

    public function getDefaultMaxWeightAttribute()
    {
        return $this->defaults['max_weight'] ?? null;
    }

    public function getDefaultTurnaroundAttribute()
    {
        return $this->defaults['turnaround'] ?? 24;
    }

    public function getDefaultPricingTypeAttribute()
    {
        return $this->defaults['pricing_type'] ?? 'per_load';
    }
}