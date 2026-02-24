<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Promotion;
use App\Models\Service;
use App\Models\Branch;
use App\Models\User;
use App\Models\Customer;
use App\Models\PromotionUsage;

class OrderPromotionApplicationTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function staff_can_create_order_with_promo_and_usage_is_recorded()
    {
        $branch = Branch::create([
            'name' => 'Test Branch',
            'code' => 'TST',
            'city' => 'City',
            'province' => 'Prov',
            'address' => '123 Test St',
            'phone' => '09170000000',
            'is_active' => true,
        ]);
        $staff = User::create(['name' => 'Staff', 'email' => 'staff@example.com', 'password' => 'password', 'role' => 'staff', 'branch_id' => $branch->id, 'is_active' => true, 'phone' => '09170000001']);
        $customer = Customer::create(['name' => 'Cust', 'phone' => '09171234567']);
        $service = Service::create(['name' => 'Standard', 'slug' => 'standard', 'price_per_kg' => 100, 'is_active' => true]);

        $promo = Promotion::create([
            'name' => '10% OFF',
            'type' => 'percentage_discount',
            'pricing_data' => ['percentage' => 10],
            'promo_code' => 'STAFF10',
            'is_active' => true,
            'start_date' => now()->subDay(),
            'end_date' => now()->addDay(),
            'min_amount' => 0,
            'usage_count' => 0,
            'max_usage' => null,
        ]);

        $this->actingAs($staff, 'sanctum');

        $payload = [
            'customer_id' => $customer->id,
            'service_id' => $service->id,
            'weight' => 5,
            'pickup_fee' => 0,
            'delivery_fee' => 0,
            'promo_code' => 'STAFF10',
        ];

        $response = $this->post(route('staff.orders.store'), $payload);
        $response->assertRedirect();

        // Assert PromotionUsage created
        $this->assertDatabaseHas('promotion_usages', [
            'code_used' => 'STAFF10',
        ]);

        // Assert order has discount amount
        $this->assertDatabaseHas('orders', [
            'discount_amount' => 50.0, // 10% of subtotal (5kg*100=500 => 50)
        ]);
    }

    /** @test */
    public function promo_respects_max_usage_and_is_not_applied_after_maxed_out()
    {
        $branch = Branch::create([
            'name' => 'Test Branch',
            'code' => 'TST',
            'city' => 'City',
            'province' => 'Prov',
            'address' => '123 Test St',
            'phone' => '09170000000',
            'is_active' => true,
        ]);
        $staff = User::create(['name' => 'Staff', 'email' => 'staff2@example.com', 'password' => 'password', 'role' => 'staff', 'branch_id' => $branch->id, 'is_active' => true, 'phone' => '09170000002']);
        $customer = Customer::create(['name' => 'Cust', 'phone' => '09171234568']);
        $service = Service::create(['name' => 'Standard', 'slug' => 'standard', 'price_per_kg' => 100, 'is_active' => true]);

        $promo = Promotion::create([
            'name' => 'OneTime 50',
            'type' => 'fixed_discount',
            'pricing_data' => ['discount_amount' => 50],
            'promo_code' => 'ONE50',
            'is_active' => true,
            'start_date' => now()->subDay(),
            'end_date' => now()->addDay(),
            'min_amount' => 0,
            'usage_count' => 0,
            'max_usage' => 1,
        ]);

        $this->actingAs($staff, 'sanctum');

        // First order should apply
        $payload1 = [
            'customer_id' => $customer->id,
            'service_id' => $service->id,
            'weight' => 2,
            'pickup_fee' => 0,
            'delivery_fee' => 0,
            'promo_code' => 'ONE50',
        ];

        $this->post(route('staff.orders.store'), $payload1);

        $this->assertDatabaseHas('promotion_usages', ['code_used' => 'ONE50']);

        // Second order should NOT apply because max_usage reached
        $payload2 = $payload1;
        $payload2['weight'] = 3;

        $this->post(route('staff.orders.store'), $payload2);

        // There should only be one promotion_usage for code ONE50
        $usages = PromotionUsage::where('code_used', 'ONE50')->count();
        $this->assertEquals(1, $usages);
    }

    /** @test */
    public function staff_can_create_order_with_per_load_override_promo()
    {
        $branch = Branch::create([
            'name' => 'Test Branch',
            'code' => 'TST',
            'city' => 'City',
            'province' => 'Prov',
            'address' => '123 Test St',
            'phone' => '09170000000',
            'is_active' => true,
        ]);
        $staff = User::create(['name' => 'Staff', 'email' => 'staff3@example.com', 'password' => 'password', 'role' => 'staff', 'branch_id' => $branch->id, 'is_active' => true, 'phone' => '09170000003']);
        $customer = Customer::create(['name' => 'Cust', 'phone' => '09171234569']);
        $service = Service::create(['name' => 'Standard', 'slug' => 'standard', 'price_per_kg' => 100, 'is_active' => true]);

        $promo = Promotion::create([
            'name' => 'Poster per-load',
            'type' => 'poster_promo',
            'pricing_data' => [],
            'display_price' => 120.0,
            'application_type' => 'per_load_override',
            'is_active' => true,
            'start_date' => now()->subDay(),
            'end_date' => now()->addDay(),
            'min_amount' => 0,
            'usage_count' => 0,
            'max_usage' => null,
        ]);

        $this->actingAs($staff, 'sanctum');

        // 10kg at 100/kg = subtotal 1000. loads = 2 -> override total = 240
        $payload = [
            'customer_id' => $customer->id,
            'service_id' => $service->id,
            'weight' => 10,
            'pickup_fee' => 0,
            'delivery_fee' => 0,
            'promotion_id' => $promo->id,
        ];

        $response = $this->post(route('staff.orders.store'), $payload);
        $response->assertRedirect();

        $this->assertDatabaseHas('promotion_usages', [
            'promotion_id' => $promo->id,
            'final_amount' => 240.0,
        ]);

        $this->assertDatabaseHas('orders', [
            'discount_amount' => 760.0, // 1000 - 240
            'total_amount' => 240.0,
        ]);
    }

    /** @test */
    public function staff_can_create_promo_only_order_with_per_load_override()
    {
        $branch = Branch::create([
            'name' => 'Test Branch',
            'code' => 'TST',
            'city' => 'City',
            'province' => 'Prov',
            'address' => '123 Test St',
            'phone' => '09170000000',
            'is_active' => true,
        ]);
        $staff = User::create(['name' => 'Staff', 'email' => 'staff4@example.com', 'password' => 'password', 'role' => 'staff', 'branch_id' => $branch->id, 'is_active' => true, 'phone' => '09170000004']);
        $customer = Customer::create(['name' => 'Cust', 'phone' => '09171234570']);

        $promo = Promotion::create([
            'name' => 'Poster per-load promo-only',
            'type' => 'poster_promo',
            'pricing_data' => [],
            'display_price' => 150.0,
            'application_type' => 'per_load_override',
            'is_active' => true,
            'start_date' => now()->subDay(),
            'end_date' => now()->addDay(),
            'min_amount' => 0,
            'usage_count' => 0,
            'max_usage' => null,
        ]);

        $this->actingAs($staff, 'sanctum');

        // 10kg -> loads=2 -> override total = 300
        $payload = [
            'customer_id' => $customer->id,
            'weight' => 10,
            'pickup_fee' => 0,
            'delivery_fee' => 0,
            'promotion_id' => $promo->id,
        ];

        $response = $this->post(route('staff.orders.store'), $payload);
        $response->assertRedirect();

        $this->assertDatabaseHas('promotion_usages', [
            'promotion_id' => $promo->id,
            'final_amount' => 300.0,
        ]);

        $this->assertDatabaseHas('orders', [
            'service_id' => null,
            'price_per_kg' => 0.0,
            'subtotal' => 300.0,
            'discount_amount' => 0.0,
            'total_amount' => 300.0,
        ]);
    }

    /** @test */
    public function admin_can_create_promo_only_order_with_per_load_override()
    {
        $branch = Branch::create([
            'name' => 'Test Branch',
            'code' => 'TST',
            'city' => 'City',
            'province' => 'Prov',
            'address' => '123 Test St',
            'phone' => '09170000000',
            'is_active' => true,
        ]);
        $admin = User::create(['name' => 'Admin', 'email' => 'admin@example.com', 'password' => 'password', 'role' => 'admin', 'is_active' => true, 'phone' => '09170000005']);
        $customer = Customer::create(['name' => 'Cust', 'phone' => '09171234571']);

        $promo = Promotion::create([
            'name' => 'Admin Poster per-load',
            'type' => 'poster_promo',
            'pricing_data' => [],
            'display_price' => 200.0,
            'application_type' => 'per_load_override',
            'is_active' => true,
            'start_date' => now()->subDay(),
            'end_date' => now()->addDay(),
            'min_amount' => 0,
            'usage_count' => 0,
            'max_usage' => null,
        ]);

        $this->actingAs($admin, 'sanctum');

        // 9kg -> loads=2 -> override total = 400
        $payload = [
            'customer_id' => $customer->id,
            'branch_id' => $branch->id,
            'weight' => 9,
            'pickup_fee' => 0,
            'delivery_fee' => 0,
            'promotion_id' => $promo->id,
        ];

        $response = $this->post(route('admin.orders.store'), $payload);
        $response->assertRedirect();

        $this->assertDatabaseHas('promotion_usages', [
            'promotion_id' => $promo->id,
            'final_amount' => 400.0,
        ]);

        $this->assertDatabaseHas('orders', [
            'service_id' => null,
            'price_per_kg' => 0.0,
            'subtotal' => 400.0,
            'discount_amount' => 0.0,
            'total_amount' => 400.0,
        ]);
    }
}
