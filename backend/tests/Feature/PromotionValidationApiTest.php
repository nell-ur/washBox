<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Promotion;
use App\Models\Service;

class PromotionValidationApiTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function validate_code_returns_expected_discount()
    {
        $service = Service::create(['name' => 'Standard', 'slug' => 'standard', 'price_per_kg' => 100, 'is_active' => true]);

        $promo = Promotion::create([
            'name' => '10% OFF',
            'type' => 'percentage_discount',
            'pricing_data' => ['percentage' => 10],
            'promo_code' => 'TEST10',
            'is_active' => true,
            'start_date' => now()->subDay(),
            'end_date' => now()->addDay(),
            'min_amount' => 0,
        ]);

        $response = $this->getJson('/api/v1/promotions/validate-code?code=TEST10&subtotal=200&service_id=' . $service->id);

        $response->assertStatus(200)
            ->assertJson(["success" => true, "data" => ["is_applicable" => true, "discount_value" => 20.0]]);
    }

    /** @test */
    public function validate_code_returns_not_applicable_when_min_amount_not_met()
    {
        $service = Service::create(['name' => 'Standard', 'slug' => 'standard', 'price_per_kg' => 100, 'is_active' => true]);

        $promo = Promotion::create([
            'name' => 'Min 500 Promo',
            'type' => 'percentage_discount',
            'pricing_data' => ['percentage' => 20],
            'promo_code' => 'MIN500',
            'is_active' => true,
            'start_date' => now()->subDay(),
            'end_date' => now()->addDay(),
            'min_amount' => 500,
        ]);

        $response = $this->getJson('/api/v1/promotions/validate-code?code=MIN500&subtotal=200&service_id=' . $service->id);

        $response->assertStatus(200)
            ->assertJson(["success" => true, "data" => ["is_applicable" => false]]);
    }

    /** @test */
    public function validate_code_handles_per_load_override()
    {
        $service = Service::create(['name' => 'Standard', 'slug' => 'standard', 'price_per_kg' => 100, 'is_active' => true]);

        $promo = Promotion::create([
            'name' => 'Poster per-load',
            'type' => 'poster_promo',
            'pricing_data' => [],
            'display_price' => 120.0,
            'application_type' => 'per_load_override',
            'promo_code' => 'POSTER1',
            'is_active' => true,
            'start_date' => now()->subDay(),
            'end_date' => now()->addDay(),
            'min_amount' => 0,
        ]);

        // Suppose customer has 10kg @ 100/kg -> subtotal 1000. Loads = ceil(10/8)=2 -> override = 2*120 = 240
        $response = $this->getJson('/api/v1/promotions/validate-code?code=POSTER1&subtotal=1000&weight=10&service_id=' . $service->id);

        $response->assertStatus(200)
            ->assertJson(["success" => true, "data" => ["is_applicable" => true, "final_total" => 240.0]])
            ->assertJsonPath('data.extra.loads', 2);
    }
}
