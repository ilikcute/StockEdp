<?php

namespace Tests\Feature\Inventory;

use App\Features\Auth\Enums\PermissionCode;
use App\Features\Auth\Enums\RoleCode;
use App\Features\Auth\Models\Permission;
use App\Features\Auth\Models\Role;
use App\Features\Auth\Models\User;
use App\Features\Inventory\Models\InventoryBalance;
use App\Features\Inventory\Models\StockMovement;
use App\Features\Location\Models\Location;
use App\Features\Product\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class InventoryReconciliationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $staff;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed');

        $this->admin = User::factory()->create();
        $this->admin->roles()->attach(Role::where('code', RoleCode::ADMIN->value)->first());

        $this->staff = User::factory()->create();
        $this->staff->roles()->attach(Role::where('code', RoleCode::WAREHOUSE_OFFICER->value)->first());
    }

    public function test_unauthorized_user_cannot_access_reconciliation(): void
    {
        $responseScan = $this->actingAs($this->staff)->getJson('/api/v1/inventory/reconciliation/scan');
        $responseScan->assertStatus(403);

        $responseApply = $this->actingAs($this->staff)->postJson('/api/v1/inventory/reconciliation/apply');
        $responseApply->assertStatus(403);
    }

    public function test_admin_can_scan_and_detect_balance_discrepancies(): void
    {
        $product = Product::factory()->create(['name' => 'Barang Audit']);
        $location = Location::factory()->create(['name' => 'Gudang Audit']);

        $this->admin->locations()->attach($location);

        // Ledger has 10 units received
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
            'unit_cost' => 1000,
            'total_cost' => 10000,
            'condition' => 'GOOD',
            'occurred_at' => now(),
            'created_by' => $this->admin->id,
        ]);

        // Artificially simulate corrupted balance of 15 units
        InventoryBalance::create([
            'product_id' => $product->id,
            'location_id' => $location->id,
            'condition' => 'GOOD',
            'quantity' => 15,
        ]);

        $response = $this->actingAs($this->admin)->getJson('/api/v1/inventory/reconciliation/scan');

        $response->assertStatus(200)
            ->assertJsonPath('data.summary.discrepant_pairs', 1)
            ->assertJsonPath('data.discrepancies.0.product_id', $product->id)
            ->assertJsonPath('data.discrepancies.0.location_id', $location->id)
            ->assertJsonPath('data.discrepancies.0.current_quantity', '15.0000')
            ->assertJsonPath('data.discrepancies.0.expected_quantity', '10.0000')
            ->assertJsonPath('data.discrepancies.0.difference', '5.0000');
    }

    public function test_admin_can_apply_reconciliation_and_sync_balances(): void
    {
        $product = Product::factory()->create(['name' => 'Barang Audit']);
        $location = Location::factory()->create(['name' => 'Gudang Audit']);

        $this->admin->locations()->attach($location);

        // Ledger has 10 units received
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
            'unit_cost' => 1000,
            'total_cost' => 10000,
            'condition' => 'GOOD',
            'occurred_at' => now(),
            'created_by' => $this->admin->id,
        ]);

        // Artificially corrupted balance of 4 units
        $balance = InventoryBalance::create([
            'product_id' => $product->id,
            'location_id' => $location->id,
            'condition' => 'GOOD',
            'quantity' => 4,
        ]);

        $response = $this->actingAs($this->admin)->postJson('/api/v1/inventory/reconciliation/apply', [
            'keys' => [
                "{$location->id}_{$product->id}_GOOD",
            ],
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.reconciled_count', 1);

        $this->assertDatabaseHas('inventory_balances', [
            'id' => $balance->id,
            'quantity' => 10,
        ]);
    }

    public function test_artisan_command_reconciliation(): void
    {
        $product = Product::factory()->create();
        $location = Location::factory()->create();

        StockMovement::create([
            'movement_id' => Str::uuid()->toString(),
            'product_id' => $product->id,
            'location_id' => $location->id,
            'movement_type' => 'RECEIPT',
            'quantity' => 8,
            'quantity_before' => 0,
            'quantity_after' => 8,
            'reference_type' => 'test',
            'reference_id' => 1,
            'unit_cost' => 1000,
            'total_cost' => 8000,
            'condition' => 'GOOD',
            'occurred_at' => now(),
            'created_by' => $this->admin->id,
        ]);

        InventoryBalance::create([
            'product_id' => $product->id,
            'location_id' => $location->id,
            'condition' => 'GOOD',
            'quantity' => 3,
        ]);

        $this->artisan('inventory:reconcile-balances --dry-run')
            ->assertExitCode(0);

        // Still 3 because of --dry-run
        $this->assertDatabaseHas('inventory_balances', [
            'product_id' => $product->id,
            'location_id' => $location->id,
            'quantity' => 3,
        ]);

        $this->artisan('inventory:reconcile-balances --force')
            ->assertExitCode(0);

        // Now updated to 8
        $this->assertDatabaseHas('inventory_balances', [
            'product_id' => $product->id,
            'location_id' => $location->id,
            'quantity' => 8,
        ]);
    }
}
