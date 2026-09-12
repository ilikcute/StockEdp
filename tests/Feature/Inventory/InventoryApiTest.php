<?php

namespace Tests\Feature\Inventory;

use App\Features\Auth\Enums\RoleCode;
use App\Features\Auth\Models\Role;
use App\Features\Auth\Models\User;
use App\Features\Inventory\Models\InventoryBalance;
use App\Features\Inventory\Models\StockMovement;
use App\Features\Location\Models\Location;
use App\Features\Product\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class InventoryApiTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed'); // Memastikan Role/Permission ter-seed
        $this->admin = User::factory()->create();
        $this->admin->roles()->attach(Role::where('code', RoleCode::ADMIN->value)->first());
    }

    public function test_can_get_balances_with_pagination_and_filter()
    {
        $product = Product::factory()->create(['name' => 'Produk A']);
        $location = Location::factory()->create();

        $this->admin->locations()->attach($location);

        InventoryBalance::create([
            'product_id' => $product->id,
            'location_id' => $location->id,
            'quantity' => 50,
        ]);

        $response = $this->actingAs($this->admin)->getJson('/api/v1/inventory/balances?search=Produk A');

        $response->assertStatus(200)
            ->assertJsonPath('data.data.0.quantity', '50.0000');
    }

    public function test_can_get_movements_with_pagination_and_filter()
    {
        $product = Product::factory()->create();
        $location = Location::factory()->create();

        $this->admin->locations()->attach($location);

        StockMovement::create([
            'movement_id' => Str::uuid()->toString(),
            'product_id' => $product->id,
            'location_id' => $location->id,
            'movement_type' => 'RECEIPT',
            'quantity' => 10,
            'quantity_before' => 0,
            'quantity_after' => 10,
            'reference_type' => 'test',
            'reference_id' => 1,
            'occurred_at' => now(),
        ]);

        $response = $this->actingAs($this->admin)->getJson('/api/v1/inventory/movements?movement_type=RECEIPT');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data.data');
    }
}
