<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeliveryFee extends Model
{
    protected $fillable = [
        'branch_id',
        'pickup_fee',
        'delivery_fee',
        'both_discount',
        'minimum_laundry_for_free',
        'is_active',
    ];

    protected $casts = [
        'pickup_fee' => 'decimal:2',
        'delivery_fee' => 'decimal:2',
        'both_discount' => 'decimal:2',
        'minimum_laundry_for_free' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    /**
     * Get the branch that owns this fee structure
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Calculate total fee based on service type
     */
    public function calculateFee(string $serviceType, ?float $laundryAmount = null): array
    {
        // Check if laundry qualifies for free delivery
        if ($laundryAmount && $this->minimum_laundry_for_free && $laundryAmount >= $this->minimum_laundry_for_free) {
            return [
                'pickup_fee' => 0,
                'delivery_fee' => 0,
                'total_fee' => 0,
                'discount_applied' => 'Free delivery (minimum laundry reached)',
            ];
        }

        $pickupFee = 0;
        $deliveryFee = 0;
        $discount = 0;

        switch ($serviceType) {
            case 'pickup_only':
                $pickupFee = $this->pickup_fee;
                break;

            case 'delivery_only':
                $deliveryFee = $this->delivery_fee;
                break;

            case 'both':
                $pickupFee = $this->pickup_fee;
                $deliveryFee = $this->delivery_fee;

                // Apply discount for using both services
                if ($this->both_discount > 0) {
                    $totalBeforeDiscount = $pickupFee + $deliveryFee;
                    $discount = ($totalBeforeDiscount * $this->both_discount) / 100;
                    $pickupFee = ($pickupFee * (100 - $this->both_discount)) / 100;
                    $deliveryFee = ($deliveryFee * (100 - $this->both_discount)) / 100;
                }
                break;
        }

        return [
            'pickup_fee' => round($pickupFee, 2),
            'delivery_fee' => round($deliveryFee, 2),
            'total_fee' => round($pickupFee + $deliveryFee, 2),
            'discount_applied' => $discount > 0 ? number_format($discount, 2) : null,
        ];
    }

    /**
     * Get active fee structure for a branch
     */
    public static function getActiveFeeForBranch(int $branchId): ?self
    {
        return self::where('branch_id', $branchId)
            ->where('is_active', true)
            ->first();
    }

    /**
     * Get or create default fee structure for a branch
     */
    public static function getOrCreateForBranch(int $branchId): self
    {
        return self::firstOrCreate(
            ['branch_id' => $branchId],
            [
                'pickup_fee' => 50.00,
                'delivery_fee' => 50.00,
                'both_discount' => 10, // 10% discount when using both
                'is_active' => true,
            ]
        );
    }
}
