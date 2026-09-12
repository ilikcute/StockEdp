<?php

namespace Tests\Feature\Reporting;

use App\Features\Auth\Enums\RoleCode;
use App\Features\Auth\Models\Role;
use App\Features\Auth\Models\User;
use App\Features\Inventory\Enums\MovementType;
use App\Features\Inventory\Models\StockMovement;
use App\Features\Location\Models\Location;
use App\Features\Product\Models\Product;
use Carbon\CarbonImmutable;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class FastMovingInventoryTest extends TestCase
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

    public function test_only_actual_issue_movements_count_towards_fast_moving_demand(): void
    {
        $productIssue = Product::factory()->create(['name' => 'Issue Product', 'is_active' => true]);
        $productReceipt = Product::factory()->create(['name' => 'Receipt Only Product', 'is_active' => true]);
        $productTransfer = Product::factory()->create(['name' => 'Transfer Only Product', 'is_active' => true]);
        $productAdjustment = Product::factory()->create(['name' => 'Adjustment Only Product', 'is_active' => true]);

        $now = CarbonImmutable::now('Asia/Jakarta');

        // 1. Genuine consumption / issue
        StockMovement::create([
            'movement_id' => (string) Str::uuid(),
            'product_id' => $productIssue->id,
            'location_id' => $this->location->id,
            'movement_type' => MovementType::ISSUE->value,
            'quantity' => '90.0000',
            'quantity_before' => '100.0000',
            'quantity_after' => '10.0000',
            'reference_type' => 'App\Features\Inventory\Models\StockIssue',
            'reference_id' => 1,
            'occurred_at' => $now->subDays(2),
            'created_by' => $this->user->id,
        ]);

        // 2. Receipt (Inbound)
        StockMovement::create([
            'movement_id' => (string) Str::uuid(),
            'product_id' => $productReceipt->id,
            'location_id' => $this->location->id,
            'movement_type' => MovementType::RECEIPT->value,
            'quantity' => '500.0000',
            'quantity_before' => '0.0000',
            'quantity_after' => '500.0000',
            'reference_type' => 'App\Features\Inventory\Models\StockReceipt',
            'reference_id' => 1,
            'occurred_at' => $now->subDays(2),
            'created_by' => $this->user->id,
        ]);

        // 3. Transfer Out & Transfer In (Internal Relocation)
        StockMovement::create([
            'movement_id' => (string) Str::uuid(),
            'product_id' => $productTransfer->id,
            'location_id' => $this->location->id,
            'movement_type' => MovementType::TRANSFER_OUT->value,
            'quantity' => '300.0000',
            'quantity_before' => '500.0000',
            'quantity_after' => '200.0000',
            'reference_type' => 'App\Features\Inventory\Models\StockTransfer',
            'reference_id' => 1,
            'occurred_at' => $now->subDays(2),
            'created_by' => $this->user->id,
        ]);

        // 4. Adjustment Out (Loss/Write-off)
        StockMovement::create([
            'movement_id' => (string) Str::uuid(),
            'product_id' => $productAdjustment->id,
            'location_id' => $this->location->id,
            'movement_type' => MovementType::ADJUSTMENT_OUT->value,
            'quantity' => '100.0000',
            'quantity_before' => '200.0000',
            'quantity_after' => '100.0000',
            'reference_type' => 'App\Features\Inventory\Models\StockAdjustment',
            'reference_id' => 1,
            'occurred_at' => $now->subDays(2),
            'created_by' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)->getJson('/api/v1/reports/inventory-movement?type=fast-moving&period=90');
        $response->assertStatus(200)
            ->assertJsonPath('meta.summary.fast_moving_count', 1)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.product_id', $productIssue->id)
            ->assertJsonPath('data.0.total_outbound_quantity', '90.0000')
            ->assertJsonPath('data.0.outbound_movement_count', 1)
            ->assertJsonPath('data.0.average_daily_outbound', '1.0000')
            ->assertJsonPath('data.0.movement_days', 1);
    }

    public function test_fast_moving_sorts_by_velocity_score_descending(): void
    {
        $highVelocityProduct = Product::factory()->create(['name' => 'High Velocity', 'is_active' => true]);
        $lowVelocityProduct = Product::factory()->create(['name' => 'Low Velocity', 'is_active' => true]);

        $now = CarbonImmutable::now('Asia/Jakarta');

        StockMovement::create([
            'movement_id' => (string) Str::uuid(),
            'product_id' => $highVelocityProduct->id,
            'location_id' => $this->location->id,
            'movement_type' => MovementType::ISSUE->value,
            'quantity' => '270.0000',
            'quantity_before' => '300.0000',
            'quantity_after' => '30.0000',
            'reference_type' => 'App\Features\Inventory\Models\StockIssue',
            'reference_id' => 1,
            'occurred_at' => $now->subDays(1),
            'created_by' => $this->user->id,
        ]);

        StockMovement::create([
            'movement_id' => (string) Str::uuid(),
            'product_id' => $lowVelocityProduct->id,
            'location_id' => $this->location->id,
            'movement_type' => MovementType::ISSUE->value,
            'quantity' => '45.0000',
            'quantity_before' => '50.0000',
            'quantity_after' => '5.0000',
            'reference_type' => 'App\Features\Inventory\Models\StockIssue',
            'reference_id' => 2,
            'occurred_at' => $now->subDays(1),
            'created_by' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)->getJson('/api/v1/reports/inventory-movement?type=fast-moving&period=90');
        $response->assertStatus(200)
            ->assertJsonPath('meta.summary.fast_moving_count', 2)
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.product_id', $highVelocityProduct->id)
            ->assertJsonPath('data.0.average_daily_outbound', '3.0000')
            ->assertJsonPath('data.1.product_id', $lowVelocityProduct->id)
            ->assertJsonPath('data.1.average_daily_outbound', '0.5000');
    }
}
