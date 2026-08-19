<?php

namespace Tests\Feature\Replenishment;

use App\Features\Auth\Models\User;
use App\Features\Category\Models\Category;
use App\Features\Inventory\Enums\TransferStatus;
use App\Features\Inventory\Models\InventoryBalance;
use App\Features\Inventory\Models\StockMovement;
use App\Features\Inventory\Models\StockTransfer;
use App\Features\Inventory\Models\StockTransferItem;
use App\Features\Location\Models\Location;
use App\Features\Product\Models\Product;
use App\Features\Unit\Models\Unit;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ReplenishmentActionValidationTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Location $targetLocation;

    protected Location $sourceLocation;

    protected Location $forbiddenLocation;

    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);

        $this->user = User::factory()->create(['is_active' => true]);
        $this->targetLocation = Location::factory()->create(['is_active' => true]);
        $this->sourceLocation = Location::factory()->create(['is_active' => true]);
        $this->forbiddenLocation = Location::factory()->create(['is_active' => true]);

        $this->user->locations()->attach([$this->targetLocation->id, $this->sourceLocation->id]);

        $adminRole = DB::table('roles')->where('code', 'ADMIN')->first();
        if ($adminRole) {
            $this->user->roles()->attach($adminRole->id);
        }

        $category = Category::factory()->create(['is_active' => true]);
        $unit = Unit::factory()->create(['is_active' => true]);

        $this->product = Product::factory()->create([
            'category_id' => $category->id,
            'unit_id' => $unit->id,
            'minimum_stock' => '50.0000',
            'is_active' => true,
        ]);

        // Target location on hand = 20.0000 (shortage = 30.0000)
        InventoryBalance::create([
            'location_id' => $this->targetLocation->id,
            'product_id' => $this->product->id,
            'quantity' => '20.0000',
        ]);

        // Source location on hand = 100.0000 (min stock 50.0000 -> surplus = 50.0000)
        InventoryBalance::create([
            'location_id' => $this->sourceLocation->id,
            'product_id' => $this->product->id,
            'quantity' => '100.0000',
        ]);
    }

    public function test_guest_cannot_validate_replenishment_action(): void
    {
        $response = $this->postJson('/api/v1/replenishment-recommendations/validate-action', [
            'target_location_id' => $this->targetLocation->id,
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'source_location_id' => $this->sourceLocation->id,
                    'requested_quantity' => '30.0000',
                ],
            ],
        ]);

        $response->assertStatus(401);
    }

    public function test_user_cannot_validate_action_for_unallowed_target_location(): void
    {
        $response = $this->actingAs($this->user)->postJson('/api/v1/replenishment-recommendations/validate-action', [
            'target_location_id' => $this->forbiddenLocation->id,
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'source_location_id' => $this->sourceLocation->id,
                    'requested_quantity' => '30.0000',
                ],
            ],
        ]);

        $response->assertStatus(403);
    }

    public function test_user_cannot_validate_action_for_unallowed_source_location(): void
    {
        $response = $this->actingAs($this->user)->postJson('/api/v1/replenishment-recommendations/validate-action', [
            'target_location_id' => $this->targetLocation->id,
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'source_location_id' => $this->forbiddenLocation->id,
                    'requested_quantity' => '30.0000',
                ],
            ],
        ]);

        $response->assertStatus(403);
    }

    public function test_valid_action_returns_200_and_validated_items(): void
    {
        $response = $this->actingAs($this->user)->postJson('/api/v1/replenishment-recommendations/validate-action', [
            'target_location_id' => $this->targetLocation->id,
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'source_location_id' => $this->sourceLocation->id,
                    'requested_quantity' => '30.0000',
                ],
            ],
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.valid', true)
            ->assertJsonPath('data.code', 'VALID')
            ->assertJsonPath('data.items.0.product_id', $this->product->id)
            ->assertJsonPath('data.items.0.requested_quantity', '30.0000')
            ->assertJsonPath('data.items.0.target_net_need', '30.0000')
            ->assertJsonPath('data.items.0.source_available_surplus', '50.0000');
    }

    public function test_stale_detection_when_target_need_is_covered_by_inbound(): void
    {
        // Add pending inbound transfer of 30.0000
        $transfer = StockTransfer::create([
            'transfer_number' => 'TRF-TEST-001',
            'transfer_date' => '2026-08-19',
            'origin_location_id' => $this->sourceLocation->id,
            'destination_location_id' => $this->targetLocation->id,
            'status' => TransferStatus::SENT->value,
            'created_by' => $this->user->id,
        ]);

        StockTransferItem::create([
            'stock_transfer_id' => $transfer->id,
            'product_id' => $this->product->id,
            'quantity' => '30.0000',
        ]);

        $response = $this->actingAs($this->user)->postJson('/api/v1/replenishment-recommendations/validate-action', [
            'target_location_id' => $this->targetLocation->id,
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'source_location_id' => $this->sourceLocation->id,
                    'requested_quantity' => '30.0000',
                ],
            ],
        ]);

        $response->assertStatus(409)
            ->assertJsonPath('success', false);
    }

    public function test_stale_detection_when_source_surplus_is_depleted(): void
    {
        // Reduce source balance to 50.0000 (surplus = 0.0000)
        DB::table('inventory_balances')
            ->where('location_id', $this->sourceLocation->id)
            ->where('product_id', $this->product->id)
            ->update(['quantity' => '50.0000']);

        $response = $this->actingAs($this->user)->postJson('/api/v1/replenishment-recommendations/validate-action', [
            'target_location_id' => $this->targetLocation->id,
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'source_location_id' => $this->sourceLocation->id,
                    'requested_quantity' => '30.0000',
                ],
            ],
        ]);

        $response->assertStatus(409)
            ->assertJsonPath('success', false);
    }

    public function test_frozen_target_location_is_rejected(): void
    {
        DB::table('inventory_location_locks')->updateOrInsert(
            ['location_id' => $this->targetLocation->id],
            ['is_frozen' => true, 'updated_at' => now()]
        );

        $response = $this->actingAs($this->user)->postJson('/api/v1/replenishment-recommendations/validate-action', [
            'target_location_id' => $this->targetLocation->id,
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'source_location_id' => $this->sourceLocation->id,
                    'requested_quantity' => '30.0000',
                ],
            ],
        ]);

        $response->assertStatus(409)
            ->assertJsonPath('success', false);
    }

    public function test_frozen_source_location_is_rejected(): void
    {
        DB::table('inventory_location_locks')->updateOrInsert(
            ['location_id' => $this->sourceLocation->id],
            ['is_frozen' => true, 'updated_at' => now()]
        );

        $response = $this->actingAs($this->user)->postJson('/api/v1/replenishment-recommendations/validate-action', [
            'target_location_id' => $this->targetLocation->id,
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'source_location_id' => $this->sourceLocation->id,
                    'requested_quantity' => '30.0000',
                ],
            ],
        ]);

        $response->assertStatus(409)
            ->assertJsonPath('success', false);
    }

    public function test_invalid_quantities_are_rejected(): void
    {
        $response = $this->actingAs($this->user)->postJson('/api/v1/replenishment-recommendations/validate-action', [
            'target_location_id' => $this->targetLocation->id,
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'source_location_id' => $this->sourceLocation->id,
                    'requested_quantity' => '1.23456', // > 4 decimals
                ],
            ],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['items.0.requested_quantity']);
    }

    public function test_read_only_integrity_validation_does_not_mutate_database(): void
    {
        $initialBalanceCount = InventoryBalance::count();
        $initialMovementCount = StockMovement::count();
        $initialTransferCount = StockTransfer::count();

        $response = $this->actingAs($this->user)->postJson('/api/v1/replenishment-recommendations/validate-action', [
            'target_location_id' => $this->targetLocation->id,
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'source_location_id' => $this->sourceLocation->id,
                    'requested_quantity' => '30.0000',
                ],
            ],
        ]);

        $response->assertStatus(200);

        $this->assertSame($initialBalanceCount, InventoryBalance::count());
        $this->assertSame($initialMovementCount, StockMovement::count());
        $this->assertSame($initialTransferCount, StockTransfer::count());
    }
}
