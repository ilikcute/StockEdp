<?php

namespace Tests\Feature\Reporting;

use App\Features\Auth\Enums\RoleCode;
use App\Features\Auth\Models\Role;
use App\Features\Auth\Models\User;
use App\Features\Inventory\Enums\MovementType;
use App\Features\Inventory\Models\InventoryBalance;
use App\Features\Inventory\Models\StockMovement;
use App\Features\Location\Models\Location;
use App\Features\Product\Models\Product;
use Carbon\CarbonImmutable;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class SlowMovingInventoryTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Location $location;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);

        $this->location = Location::factory()->create(['name' => 'Main Warehouse', 'is_active' => true]);

        $this->user = User::factory()->create();
        $this->user->roles()->attach(Role::where('code', RoleCode::ADMIN->value)->first()->id);
        $this->user->locations()->attach($this->location->id);
    }

    public function test_product_with_zero_movements_in_period_is_classified_as_slow_moving(): void
    {
        $dormantProduct = Product::factory()->create(['name' => 'Dormant Product', 'is_active' => true]);
        InventoryBalance::create([
            'product_id' => $dormantProduct->id,
            'location_id' => $this->location->id,
            'quantity' => '100.0000',
        ]);

        $activeProduct = Product::factory()->create(['name' => 'Active Product', 'is_active' => true]);
        StockMovement::create([
            'movement_id' => (string) Str::uuid(),
            'product_id' => $activeProduct->id,
            'location_id' => $this->location->id,
            'movement_type' => MovementType::ISSUE->value,
            'quantity' => '10.0000',
            'quantity_before' => '50.0000',
            'quantity_after' => '40.0000',
            'reference_type' => 'App\Features\Inventory\Models\StockIssue',
            'reference_id' => 1,
            'occurred_at' => CarbonImmutable::now('Asia/Jakarta')->subDays(5),
            'created_by' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)->getJson('/api/v1/reports/inventory-movement?type=slow-moving&period=90');
        $response->assertStatus(200)
            ->assertJsonPath('meta.summary.slow_moving_count', 1)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.product_id', $dormantProduct->id)
            ->assertJsonPath('data.0.current_stock', '100.0000')
            ->assertJsonPath('data.0.days_since_last_movement', null)
            ->assertJsonPath('data.0.last_movement_at', null)
            ->assertJsonPath('data.0.movement_count', 0);
    }

    public function test_inactive_products_are_excluded_from_slow_moving(): void
    {
        $inactiveProduct = Product::factory()->create(['name' => 'Inactive Product', 'is_active' => false]);
        InventoryBalance::create([
            'product_id' => $inactiveProduct->id,
            'location_id' => $this->location->id,
            'quantity' => '50.0000',
        ]);

        $response = $this->actingAs($this->user)->getJson('/api/v1/reports/inventory-movement?type=slow-moving&period=90');
        $response->assertStatus(200)
            ->assertJsonPath('meta.summary.slow_moving_count', 0)
            ->assertJsonCount(0, 'data');
    }

    public function test_product_with_historical_movement_before_period_calculates_days_since_last_movement(): void
    {
        $product = Product::factory()->create(['name' => 'Moved Long Ago', 'is_active' => true]);
        $historicalDate = CarbonImmutable::now('Asia/Jakarta')->subDays(120);

        StockMovement::create([
            'movement_id' => (string) Str::uuid(),
            'product_id' => $product->id,
            'location_id' => $this->location->id,
            'movement_type' => MovementType::RECEIPT->value,
            'quantity' => '50.0000',
            'quantity_before' => '0.0000',
            'quantity_after' => '50.0000',
            'reference_type' => 'App\Features\Inventory\Models\StockReceipt',
            'reference_id' => 1,
            'occurred_at' => $historicalDate,
            'created_by' => $this->user->id,
        ]);

        // In a 90-day analysis, this product had 0 movements in the last 90 days -> Slow Moving
        $res90 = $this->actingAs($this->user)->getJson('/api/v1/reports/inventory-movement?type=slow-moving&period=90');
        $res90->assertStatus(200)
            ->assertJsonPath('meta.summary.slow_moving_count', 1)
            ->assertJsonPath('data.0.days_since_last_movement', 120);

        // In a 180-day analysis, the movement at day 120 falls inside the period -> Not Slow Moving
        $res180 = $this->actingAs($this->user)->getJson('/api/v1/reports/inventory-movement?type=slow-moving&period=180');
        $res180->assertStatus(200)
            ->assertJsonPath('meta.summary.slow_moving_count', 0)
            ->assertJsonCount(0, 'data');
    }

    public function test_missing_inventory_balance_row_defaults_to_zero_stock(): void
    {
        $productWithoutBalance = Product::factory()->create(['name' => 'Zero Stock Dormant', 'is_active' => true]);

        $response = $this->actingAs($this->user)->getJson('/api/v1/reports/inventory-movement?type=slow-moving&period=90');
        $response->assertStatus(200)
            ->assertJsonPath('meta.summary.slow_moving_count', 1)
            ->assertJsonPath('data.0.product_id', $productWithoutBalance->id)
            ->assertJsonPath('data.0.current_stock', '0.0000');
    }
}
