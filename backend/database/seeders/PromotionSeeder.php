<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Promotion;
use App\Models\Branch;
use Carbon\Carbon;

class PromotionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get branches
        $sibulan = Branch::where('name', 'Sibulan')->first();
        $dumaguete = Branch::where('name', 'Dumaguete')->first();
        $bais = Branch::where('name', 'Bais City')->first();

        // ====================================================================
        // POSTER PROMOTIONS (NEW!)
        // ====================================================================

        // 1. DROP OFF PROMO - Sibulan (Active)
        Promotion::create([
            'name' => 'Drop Off Promo - Sibulan',
            'description' => 'Special pricing for drop-off service at Sibulan branch',
            'type' => 'poster_promo',

            // Poster fields
            'poster_title' => 'DROP OFF PROMO!',
            'poster_subtitle' => null,
            'display_price' => 179,
            'price_unit' => 'PER 8KG LOAD',
            'poster_features' => [
                'FREE Laundring Detergent',
                'FREE Fabcon',
                'FREE Fold'
            ],
            'poster_notes' => 'Ariel or Breeze | Downy | Zonrox Colorsoft',
            'color_theme' => 'blue',

            // Standard fields
            'pricing_data' => [],
            'min_amount' => 0,
            'promo_code' => null,
            'applicable_services' => null,
            'applicable_days' => null,
            'start_date' => now()->subDays(7),
            'end_date' => now()->addDays(30),
            'branch_id' => $sibulan?->id,
            'is_active' => true,
            'banner_image' => null,
            'display_laundry' => 1,
            'featured' => true,
            'usage_count' => 45,
            'max_usage' => null,
        ]);

        // 2. COMFORTER SPECIAL - Dumaguete (Active)
        Promotion::create([
            'name' => 'Comforter Special - Dumaguete',
            'description' => 'Special rates for comforter cleaning',
            'type' => 'poster_promo',

            // Poster fields
            'poster_title' => 'WASHBOX DROP OFF PROMO!',
            'poster_subtitle' => 'COMFORTER',
            'display_price' => 149,
            'price_unit' => 'SMALL SIZES',
            'poster_features' => [
                'Professional Deep Clean',
                'Fabric Softener Included',
                'Same-Day Service Available'
            ],
            'poster_notes' => 'Large sizes: ₱179 | Extra large: ₱209',
            'color_theme' => 'blue',

            // Standard fields
            'pricing_data' => [],
            'min_amount' => 0,
            'promo_code' => 'COMFY2024',
            'applicable_services' => null,
            'applicable_days' => null,
            'start_date' => now()->subDays(3),
            'end_date' => now()->addDays(45),
            'branch_id' => $dumaguete?->id,
            'is_active' => true,
            'banner_image' => null,
            'display_laundry' => 2,
            'featured' => true,
            'usage_count' => 23,
            'max_usage' => 100,
        ]);

        // 3. PREMIUM LOAD - Bais City (Active)
        Promotion::create([
            'name' => 'Premium Load Special - Bais',
            'description' => 'Premium laundry service with premium products',
            'type' => 'poster_promo',

            // Poster fields
            'poster_title' => 'WASHBOX DROP OFF PROMO!',
            'poster_subtitle' => 'PREMIUM LOAD',
            'display_price' => 209,
            'price_unit' => 'PER LOAD',
            'poster_features' => [
                'Premium Detergent',
                'Premium Fabric Softener',
                'Premium Bleach'
            ],
            'poster_notes' => 'Ariel or Breeze | Downy | Zonrox Colorsoft',
            'color_theme' => 'purple',

            // Standard fields
            'pricing_data' => [],
            'min_amount' => 0,
            'promo_code' => null,
            'applicable_services' => null,
            'applicable_days' => null,
            'start_date' => now()->subDays(5),
            'end_date' => now()->addDays(60),
            'branch_id' => $bais?->id,
            'is_active' => true,
            'banner_image' => null,
            'display_laundry' => 3,
            'featured' => false,
            'usage_count' => 12,
            'max_usage' => 50,
        ]);

        // 4. STUDENT SPECIAL - Network Wide (Active)
        Promotion::create([
            'name' => 'Student Holiday Special',
            'description' => 'Special discount for students during holidays',
            'type' => 'poster_promo',

            // Poster fields
            'poster_title' => 'STUDENT SPECIAL!',
            'poster_subtitle' => 'VALID ID REQUIRED',
            'display_price' => 159,
            'price_unit' => 'PER 8KG LOAD',
            'poster_features' => [
                '20% OFF Regular Price',
                'FREE Detergent',
                'FREE Fabcon'
            ],
            'poster_notes' => 'Show valid student ID',
            'color_theme' => 'green',

            // Standard fields
            'pricing_data' => [],
            'min_amount' => 0,
            'promo_code' => 'STUDENT20',
            'applicable_services' => null,
            'applicable_days' => ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'],
            'start_date' => now()->subDays(10),
            'end_date' => now()->addDays(20),
            'branch_id' => null, // Network wide
            'is_active' => true,
            'banner_image' => null,
            'display_laundry' => 4,
            'featured' => true,
            'usage_count' => 67,
            'max_usage' => 200,
        ]);

        // 5. EXPRESS WASH - Sibulan (Scheduled)
        Promotion::create([
            'name' => 'Express Wash Launch',
            'description' => 'New express wash service launching next week',
            'type' => 'poster_promo',

            // Poster fields
            'poster_title' => 'EXPRESS WASH!',
            'poster_subtitle' => 'READY IN 3 HOURS',
            'display_price' => 199,
            'price_unit' => 'PER 5KG LOAD',
            'poster_features' => [
                'Super Fast Service',
                'Premium Products',
                'Same-Day Pickup'
            ],
            'poster_notes' => 'Available 8AM-5PM daily',
            'color_theme' => 'blue',

            // Standard fields
            'pricing_data' => [],
            'min_amount' => 0,
            'promo_code' => 'EXPRESS24',
            'applicable_services' => null,
            'applicable_days' => null,
            'start_date' => now()->addDays(7),
            'end_date' => now()->addDays(37),
            'branch_id' => $sibulan?->id,
            'is_active' => true,
            'banner_image' => null,
            'display_laundry' => 5,
            'featured' => false,
            'usage_count' => 0,
            'max_usage' => null,
        ]);

        // 6. WEEKEND SPECIAL - Network Wide (Active, Weekends Only)
        Promotion::create([
            'name' => 'Weekend Wash Special',
            'description' => 'Special rates every weekend',
            'type' => 'poster_promo',

            // Poster fields
            'poster_title' => 'WEEKEND SPECIAL!',
            'poster_subtitle' => 'SATURDAY & SUNDAY',
            'display_price' => 169,
            'price_unit' => 'PER 8KG LOAD',
            'poster_features' => [
                'Weekend Discount',
                'FREE Detergent',
                'FREE Fold'
            ],
            'poster_notes' => 'Valid Saturdays and Sundays only',
            'color_theme' => 'purple',

            // Standard fields
            'pricing_data' => [],
            'min_amount' => 0,
            'promo_code' => null,
            'applicable_services' => null,
            'applicable_days' => ['Saturday', 'Sunday'],
            'start_date' => now()->subDays(14),
            'end_date' => now()->addDays(90),
            'branch_id' => null,
            'is_active' => true,
            'banner_image' => null,
            'display_laundry' => 6,
            'featured' => false,
            'usage_count' => 89,
            'max_usage' => null,
        ]);

        // ====================================================================
        // SIMPLE PERCENTAGE DISCOUNT PROMOTIONS (EXISTING)
        // ====================================================================

        // 7. Grand Opening Sale - Bais (Active)
        Promotion::create([
            'name' => 'Grand Opening Sale - Bais City',
            'description' => 'Celebrate our grand opening with 25% off',
            'type' => 'percentage_discount',
            'pricing_data' => ['percentage' => 25],
            'min_amount' => 100,
            'promo_code' => 'GRAND25',
            'applicable_services' => null,
            'applicable_days' => null,
            'start_date' => now()->subDays(2),
            'end_date' => now()->addDays(14),
            'branch_id' => $bais?->id,
            'is_active' => true,
            'banner_image' => null,
            'display_laundry' => 7,
            'featured' => true,
            'usage_count' => 34,
            'max_usage' => 100,
        ]);

        // 8. First Timer Discount - Network Wide (Active)
        Promotion::create([
            'name' => 'First Timer Discount',
            'description' => '15% off for first-time customers',
            'type' => 'percentage_discount',
            'pricing_data' => ['percentage' => 15],
            'min_amount' => 0,
            'promo_code' => 'FIRST15',
            'applicable_services' => null,
            'applicable_days' => null,
            'start_date' => now()->subDays(30),
            'end_date' => now()->addDays(365),
            'branch_id' => null, // Network wide
            'is_active' => true,
            'banner_image' => null,
            'display_laundry' => 8,
            'featured' => false,
            'usage_count' => 156,
            'max_usage' => null,
        ]);

        // 9. Senior Citizen Discount - Network Wide (Active)
        Promotion::create([
            'name' => 'Senior Citizen Discount',
            'description' => '20% discount for senior citizens',
            'type' => 'percentage_discount',
            'pricing_data' => ['percentage' => 20],
            'min_amount' => 0,
            'promo_code' => null, // Auto-apply
            'applicable_services' => null,
            'applicable_days' => null,
            'start_date' => now()->subDays(90),
            'end_date' => now()->addDays(365),
            'branch_id' => null,
            'is_active' => true,
            'banner_image' => null,
            'display_laundry' => 9,
            'featured' => false,
            'usage_count' => 203,
            'max_usage' => null,
        ]);

        // 10. Mid-Year Sale - Dumaguete (Expired)
        Promotion::create([
            'name' => 'Mid-Year Sale - Dumaguete',
            'description' => 'Mid-year clearance sale',
            'type' => 'percentage_discount',
            'pricing_data' => ['percentage' => 30],
            'min_amount' => 150,
            'promo_code' => 'MIDYEAR30',
            'applicable_services' => null,
            'applicable_days' => null,
            'start_date' => now()->subDays(60),
            'end_date' => now()->subDays(15),
            'branch_id' => $dumaguete?->id,
            'is_active' => false,
            'banner_image' => null,
            'display_laundry' => 10,
            'featured' => false,
            'usage_count' => 89,
            'max_usage' => 100,
        ]);

        // 11. Birthday Month Special - Network Wide (Active)
        Promotion::create([
            'name' => 'Birthday Month Special',
            'description' => '10% off during your birthday month',
            'type' => 'percentage_discount',
            'pricing_data' => ['percentage' => 10],
            'min_amount' => 0,
            'promo_code' => null,
            'applicable_services' => null,
            'applicable_days' => null,
            'start_date' => now()->subDays(180),
            'end_date' => now()->addDays(180),
            'branch_id' => null,
            'is_active' => true,
            'banner_image' => null,
            'display_laundry' => 11,
            'featured' => false,
            'usage_count' => 45,
            'max_usage' => null,
        ]);

        // 12. Rainy Season Promo - Sibulan (Active)
        Promotion::create([
            'name' => 'Rainy Season Special - Sibulan',
            'description' => 'Beat the rainy weather with our special rates',
            'type' => 'percentage_discount',
            'pricing_data' => ['percentage' => 18],
            'min_amount' => 200,
            'promo_code' => 'RAINY18',
            'applicable_services' => null,
            'applicable_days' => null,
            'start_date' => now()->subDays(20),
            'end_date' => now()->addDays(40),
            'branch_id' => $sibulan?->id,
            'is_active' => true,
            'banner_image' => null,
            'display_laundry' => 12,
            'featured' => false,
            'usage_count' => 28,
            'max_usage' => 150,
        ]);

        $this->command->info('✅ Created 12 promotions (6 poster + 6 simple discount)');
    }
}
