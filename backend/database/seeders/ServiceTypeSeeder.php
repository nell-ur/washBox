<?php
// database/seeders/ServiceTypeSeeder.php

namespace Database\Seeders;

use App\Models\ServiceType;
use Illuminate\Database\Seeder;

class ServiceTypeSeeder extends Seeder
{
    public function run(): void
    {
        $serviceTypes = [
            // Drop Off - Regular
            [
                'name' => 'Regular Clothes',
                'slug' => 'regular-clothes',
                'category' => 'drop_off',
                'description' => 'Wash, dry & fold — up to 8kg per load',
                'defaults' => [
                    'price' => 200,
                    'max_weight' => 8,
                    'turnaround' => 24,
                    'pricing_type' => 'per_load'
                ],
                'icon' => 'bi-bag',
                'display_order' => 10,
                'is_active' => true,
            ],
            [
                'name' => 'Premium Service',
                'slug' => 'premium-service',
                'category' => 'drop_off',
                'description' => 'Premium wash, dry & fold with extra care - includes 2 sachets detergent, 2 sachets fabcon, 60ml bleach',
                'defaults' => [
                    'price' => 220,
                    'max_weight' => 8,
                    'turnaround' => 24,
                    'pricing_type' => 'per_load'
                ],
                'icon' => 'bi-award',
                'display_order' => 20,
                'is_active' => true,
            ],
            
            // Drop Off - Comforters (per piece)
            [
                'name' => 'Comforter / Blanket - Small',
                'slug' => 'comforter-small',
                'category' => 'drop_off',
                'description' => 'Small comforter or blanket - all in including detergent & fabcon',
                'defaults' => [
                    'price' => 150,
                    'max_weight' => null,
                    'turnaround' => 24,
                    'pricing_type' => 'per_piece'
                ],
                'icon' => 'bi-stars',
                'display_order' => 30,
                'is_active' => true,
            ],
            [
                'name' => 'Comforter / Blanket - Medium',
                'slug' => 'comforter-medium',
                'category' => 'drop_off',
                'description' => 'Medium comforter or blanket - all in including detergent & fabcon',
                'defaults' => [
                    'price' => 180,
                    'max_weight' => null,
                    'turnaround' => 24,
                    'pricing_type' => 'per_piece'
                ],
                'icon' => 'bi-stars',
                'display_order' => 40,
                'is_active' => true,
            ],
            [
                'name' => 'Comforter / Blanket - Large',
                'slug' => 'comforter-large',
                'category' => 'drop_off',
                'description' => 'Large comforter or blanket - all in including detergent & fabcon',
                'defaults' => [
                    'price' => 200,
                    'max_weight' => null,
                    'turnaround' => 24,
                    'pricing_type' => 'per_piece'
                ],
                'icon' => 'bi-stars',
                'display_order' => 50,
                'is_active' => true,
            ],
            
            // Self Service
            [
                'name' => 'Wash Only',
                'slug' => 'wash-only',
                'category' => 'self_service',
                'description' => 'Customer-operated washing machine',
                'defaults' => [
                    'price' => 70,
                    'max_weight' => null,
                    'turnaround' => 1,
                    'pricing_type' => 'per_load'
                ],
                'icon' => 'bi-arrow-repeat',
                'display_order' => 10,
                'is_active' => true,
            ],
            [
                'name' => 'Dry Only',
                'slug' => 'dry-only',
                'category' => 'self_service',
                'description' => 'Customer-operated dryer',
                'defaults' => [
                    'price' => 70,
                    'max_weight' => null,
                    'turnaround' => 1,
                    'pricing_type' => 'per_load'
                ],
                'icon' => 'bi-thermometer-sun',
                'display_order' => 20,
                'is_active' => true,
            ],
            [
                'name' => 'Fold Only',
                'slug' => 'fold-only',
                'category' => 'self_service',
                'description' => 'Folding service per load',
                'defaults' => [
                    'price' => 30,
                    'max_weight' => null,
                    'turnaround' => 1,
                    'pricing_type' => 'per_load'
                ],
                'icon' => 'bi-layers',
                'display_order' => 30,
                'is_active' => true,
            ],
            [
                'name' => 'Wash & Dry',
                'slug' => 'wash-and-dry',
                'category' => 'self_service',
                'description' => 'Wash and dry combo',
                'defaults' => [
                    'price' => 130,
                    'max_weight' => null,
                    'turnaround' => 2,
                    'pricing_type' => 'per_load'
                ],
                'icon' => 'bi-arrow-repeat',
                'display_order' => 40,
                'is_active' => true,
            ],
            [
                'name' => 'Extra 10 Minutes Dry',
                'slug' => 'extra-dry',
                'category' => 'self_service',
                'description' => 'Extra 10 minutes of drying',
                'defaults' => [
                    'price' => 20,
                    'max_weight' => null,
                    'turnaround' => 0,
                    'pricing_type' => 'per_load'
                ],
                'icon' => 'bi-plus-slash-minus',
                'display_order' => 50,
                'is_active' => true,
            ],
            
            // Add-ons
            [
                'name' => 'Extra Wash',
                'slug' => 'extra-wash',
                'category' => 'addon',
                'description' => 'Additional wash cycle per load',
                'defaults' => [
                    'price' => 70,
                    'max_weight' => null,
                    'turnaround' => 0,
                    'pricing_type' => 'per_load'
                ],
                'icon' => 'bi-box-seam',
                'display_order' => 10,
                'is_active' => true,
            ],
            [
                'name' => 'Detergent (Ariel/Breeze)',
                'slug' => 'detergent',
                'category' => 'addon',
                'description' => '2 sachets of Ariel or Breeze laundry detergent',
                'defaults' => [
                    'price' => 20,
                    'max_weight' => null,
                    'turnaround' => 0,
                    'pricing_type' => 'per_load'
                ],
                'icon' => 'bi-droplet-half',
                'display_order' => 20,
                'is_active' => true,
            ],
            [
                'name' => 'Fabric Conditioner (Del Fabcon)',
                'slug' => 'fabric-conditioner',
                'category' => 'addon',
                'description' => 'Del Fabcon (50ml)',
                'defaults' => [
                    'price' => 16,
                    'max_weight' => null,
                    'turnaround' => 0,
                    'pricing_type' => 'per_load'
                ],
                'icon' => 'bi-moisture',
                'display_order' => 30,
                'is_active' => true,
            ],
            [
                'name' => 'Bleach (Zonrox)',
                'slug' => 'bleach',
                'category' => 'addon',
                'description' => 'Zonrox bleach colorsafe (60ml)',
                'defaults' => [
                    'price' => 15,
                    'max_weight' => null,
                    'turnaround' => 0,
                    'pricing_type' => 'per_load'
                ],
                'icon' => 'bi-droplet',
                'display_order' => 40,
                'is_active' => true,
            ],
        ];

        foreach ($serviceTypes as $type) {
            ServiceType::create($type);
        }
    }
}