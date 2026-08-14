<?php

namespace Tests\Feature\Replenishment;

use App\Features\Auth\Enums\RoleCode;
use App\Features\Auth\Models\Role;
use App\Features\Auth\Models\User;
use App\Features\Category\Models\Category;
use App\Features\Inventory\Models\InventoryBalance;
use App\Features\Location\Models\Location;
use App\Features\Product\Models\Product;
use App\Features\Unit\Models\Unit;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReplenishmentLocationScopeTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Location $allowedLocationA;

    private Location $allowedLocationB;

    private Location $forbiddenLocationSecret;

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);

        $this->allowedLocationA = Location::create([
            'code' => 'WH-A',
            'name' => 'Gudang A',
            'is_active' => true,
        ]);

        $this->allowedLocationB = Location::create([
            'code' => 'WH-B',
            'name' => 'Gudang B',
            'is_active' => true,
        ]);

        $this->forbiddenLocationSecret = Location::create([
            'code' => 'WH-SECRET',
            'name' => 'Gudang Rahasia',
            'is_active' => true,
        ]);

        $role = Role::where('code', RoleCode::WAREHOUSE_OFFICER->value)->first();
        $this->user = User::factory()->create();
        $this->user->roles()->attach($role);
        $this->user->locations()->attach([$this->allowedLocationA->id, $this->allowedLocationB->id]);

        $category = Category::create(['code' => 'CAT-01', 'name' => 'Kategori 1', 'is_active' => true]);
        $unit = Unit::create(['code' => 'UNT-01', 'name' => 'PCS', 'symbol' => 'pcs', 'is_active' => true]);

        $this->product = Product::create([
            'sku' => 'PRD-SCOPE-001',
            'name' => 'Produk Uji Scope',
            'category_id' => $category->id,
            'unit_id' => $unit->id,
            'minimum_stock' => '10.00',
            'is_active' => true,
        ]);
    }

    public function test_missing_location_id_returns_422(): void
    {
        $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/replenishment-recommendations')
            ->assertStatus(422)
            ->assertJsonValidationErrors(['location_id']);
    }

    public function test_requesting_forbidden_target_location_returns_403(): void
    {
        $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/replenishment-recommendations?location_id='.$this->forbiddenLocationSecret->id)
            ->assertStatus(403);
    }

    public function test_unauthorized_source_with_huge_surplus_is_never_leaked(): void
    {
        // Target Location A has 2 on hand (shortage = 8)
        InventoryBalance::create([
            'product_id' => $this->product->id,
            'location_id' => $this->allowedLocationA->id,
            'quantity' => '2.0000',
        ]);

        // Allowed Location B has 10 on hand (surplus = 0)
        InventoryBalance::create([
            'product_id' => $this->product->id,
            'location_id' => $this->allowedLocationB->id,
            'quantity' => '10.0000',
        ]);

        // Forbidden Location Secret has 100 on hand (huge surplus = 90)
        InventoryBalance::create([
            'product_id' => $this->product->id,
            'location_id' => $this->forbiddenLocationSecret->id,
            'quantity' => '100.0000',
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/replenishment-recommendations?location_id='.$this->allowedLocationA->id)
            ->assertStatus(200);

        $row = $response->json('data.data.0');
        $this->assertEquals('8.0000', $row['net_replenishment_need']);
        $this->assertEquals('EXTERNAL_REORDER', $row['recommendation_type']);
        $this->assertEmpty($row['source_allocations']);

        // Assert secret location is not leaked anywhere in response body
        $responseContent = $response->getContent();
        $this->assertStringNotContainsString('WH-SECRET', $responseContent);
        $this->assertStringNotContainsString('Gudang Rahasia', $responseContent);
    }

    public function test_filter_options_returns_only_allowed_active_locations(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/replenishment-recommendations/filter-options')
            ->assertStatus(200);

        $locations = collect($response->json('data.locations'));
        $locationIds = $locations->pluck('id')->all();

        $this->assertContains($this->allowedLocationA->id, $locationIds);
        $this->assertContains($this->allowedLocationB->id, $locationIds);
        $this->assertNotContains($this->forbiddenLocationSecret->id, $locationIds);
    }
}
