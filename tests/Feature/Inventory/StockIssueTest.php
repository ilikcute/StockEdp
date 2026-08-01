<?php

namespace Tests\Feature\Inventory;

use App\Features\Auth\Enums\RoleCode;
use App\Features\Auth\Models\User;
use App\Features\Inventory\Enums\IssueStatus;
use App\Features\Inventory\Models\InventoryBalance;
use App\Features\Inventory\Models\StockIssue;
use App\Features\Location\Models\Location;
use App\Features\Product\Models\Product;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockIssueTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Product $product;

    private Location $location;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);

        $this->admin = User::factory()->create();
        $this->admin->assignRole(RoleCode::ADMIN->value);

        $this->product = Product::factory()->create(['is_active' => true]);
        $this->location = Location::factory()->create(['is_active' => true]);

        $this->admin->locations()->attach($this->location);
    }

    public function test_can_create_draft_issue()
    {
        $payload = [
            'purpose' => 'Production Request',
            'date' => now()->format('Y-m-d'),
            'notes' => 'Test Issue',
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'location_id' => $this->location->id,
                    'quantity' => 5,
                ],
            ],
        ];

        $response = $this->actingAs($this->admin, 'sanctum')->postJson('/api/v1/stock-issues', $payload);

        $response->assertStatus(201);
        $this->assertDatabaseHas('stock_issues', [
            'purpose' => 'Production Request',
            'status' => IssueStatus::DRAFT->value,
        ]);

        $this->assertDatabaseHas('stock_issue_items', [
            'product_id' => $this->product->id,
            'quantity' => '5.0000',
        ]);
    }

    public function test_post_issue_deducts_stock()
    {
        // 1. Setup initial stock
        InventoryBalance::create([
            'product_id' => $this->product->id,
            'location_id' => $this->location->id,
            'quantity' => 10,
        ]);

        // 2. Create Draft Issue
        $issue = StockIssue::create([
            'issue_number' => 'ISS-202310-0001',
            'purpose' => 'Test',
            'status' => IssueStatus::DRAFT->value,
            'date' => now(),
        ]);
        $issue->items()->create([
            'product_id' => $this->product->id,
            'location_id' => $this->location->id,
            'quantity' => 4,
        ]);

        // 3. Post It
        $response = $this->actingAs($this->admin, 'sanctum')->postJson("/api/v1/stock-issues/{$issue->id}/post");
        $response->assertStatus(200);

        // 4. Verify Stock Update
        $this->assertDatabaseHas('inventory_balances', [
            'product_id' => $this->product->id,
            'quantity' => '6.0000',
        ]);

        // 5. Verify Movement
        $this->assertDatabaseHas('stock_movements', [
            'movement_type' => 'ISSUE',
            'quantity' => '4.0000',
            'quantity_before' => '10.0000',
            'quantity_after' => '6.0000',
        ]);
    }

    public function test_cannot_post_issue_with_insufficient_stock()
    {
        InventoryBalance::create([
            'product_id' => $this->product->id,
            'location_id' => $this->location->id,
            'quantity' => 3,
        ]);

        $issue = StockIssue::create([
            'issue_number' => 'ISS-202310-0002',
            'purpose' => 'Test',
            'status' => IssueStatus::DRAFT->value,
            'date' => now(),
        ]);
        $issue->items()->create([
            'product_id' => $this->product->id,
            'location_id' => $this->location->id,
            'quantity' => 4,
        ]);

        // Post should fail because 4 > 3
        $response = $this->actingAs($this->admin, 'sanctum')->postJson("/api/v1/stock-issues/{$issue->id}/post");

        $response->assertStatus(422);

        // Balance should remain 3
        $this->assertDatabaseHas('inventory_balances', [
            'product_id' => $this->product->id,
            'quantity' => '3.0000',
        ]);
    }

    public function test_multi_item_rollback_if_one_insufficient()
    {
        $product2 = Product::factory()->create();

        InventoryBalance::create([
            'product_id' => $this->product->id,
            'location_id' => $this->location->id,
            'quantity' => 10,
        ]);
        InventoryBalance::create([
            'product_id' => $product2->id,
            'location_id' => $this->location->id,
            'quantity' => 2,
        ]);

        $issue = StockIssue::create([
            'issue_number' => 'ISS-202310-0003',
            'purpose' => 'Test',
            'status' => IssueStatus::DRAFT->value,
            'date' => now(),
        ]);
        $issue->items()->create([
            'product_id' => $this->product->id, // Has 10, wants 4 (OK)
            'location_id' => $this->location->id,
            'quantity' => 4,
        ]);
        $issue->items()->create([
            'product_id' => $product2->id, // Has 2, wants 4 (FAIL)
            'location_id' => $this->location->id,
            'quantity' => 4,
        ]);

        $response = $this->actingAs($this->admin, 'sanctum')->postJson("/api/v1/stock-issues/{$issue->id}/post");
        $response->assertStatus(422);

        // Stock should rollback for BOTH
        $this->assertDatabaseHas('inventory_balances', [
            'product_id' => $this->product->id,
            'quantity' => '10.0000', // Didn't deduct 4
        ]);
        $this->assertDatabaseHas('inventory_balances', [
            'product_id' => $product2->id,
            'quantity' => '2.0000',
        ]);
    }
}
