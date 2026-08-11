<?php

namespace Tests\Feature\Dashboard;

use App\Features\Auth\Enums\RoleCode;
use App\Features\Auth\Models\Role;
use App\Features\Auth\Models\User;
use App\Features\Category\Models\Category;
use App\Features\Inventory\Enums\MovementType;
use App\Features\Inventory\Models\StockMovement;
use App\Features\Location\Models\Location;
use App\Features\Product\Models\Product;
use App\Features\Unit\Models\Unit;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardRecentActivityTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Location $location;

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);

        $adminRole = Role::where('code', RoleCode::ADMIN->value)->first();
        $this->admin = User::factory()->create();
        $this->admin->roles()->attach($adminRole);

        $this->location = Location::create(['code' => 'LOC-ACT', 'name' => 'Loc Act', 'is_active' => true]);
        $this->admin->locations()->attach($this->location);

        $cat = Category::create(['code' => 'CAT-ACT', 'name' => 'Cat Act', 'is_active' => true]);
        $unit = Unit::create(['code' => 'UNT-ACT', 'name' => 'Unit Act', 'symbol' => 'ua', 'is_active' => true]);
        $this->product = Product::create([
            'sku' => 'PRD-ACT-001',
            'name' => 'Product Act',
            'category_id' => $cat->id,
            'unit_id' => $unit->id,
            'is_active' => true,
        ]);
    }

    public function test_recent_activity_returns_max_10_items_ordered_by_newest_first_with_decimal_quantity(): void
    {
        // Seed 15 movements with distinct created_at timestamps
        for ($i = 1; $i <= 15; $i++) {
            StockMovement::create([
                'movement_id' => "MOV-ACT-00{$i}",
                'reference_type' => 'App\Features\Inventory\Models\StockReceipt',
                'reference_id' => $i,
                'product_id' => $this->product->id,
                'location_id' => $this->location->id,
                'movement_type' => MovementType::RECEIPT->value,
                'quantity' => sprintf('%.4f', $i * 10),
                'quantity_before' => '0.0000',
                'quantity_after' => sprintf('%.4f', $i * 10),
                'reference_number' => "REF-ACT-00{$i}",
                'created_by' => $this->admin->id,
                'occurred_at' => now()->subMinutes(20 - $i),
                'created_at' => now()->subMinutes(20 - $i),
            ]);
        }

        $response = $this->actingAs($this->admin)->getJson('/api/v1/dashboard');
        $response->assertOk();

        $recentActivity = $response->json('data.recent_activity');

        // Max 10 items
        $this->assertCount(10, $recentActivity);

        // Newest first (movement #15 has quantity 150.0000)
        $this->assertSame('150.0000', $recentActivity[0]['quantity']);
        $this->assertSame('60.0000', $recentActivity[9]['quantity']);

        // Check string representation (no float casting)
        $this->assertIsString($recentActivity[0]['quantity']);
    }

    public function test_recent_activity_orders_by_created_at_descending_even_when_occurred_at_differs(): void
    {
        // Movement A: occurred_at newer, created_at older
        StockMovement::create([
            'movement_id' => 'MOV-ORD-A',
            'reference_type' => 'App\Features\Inventory\Models\StockReceipt',
            'reference_id' => 101,
            'product_id' => $this->product->id,
            'location_id' => $this->location->id,
            'movement_type' => MovementType::RECEIPT->value,
            'quantity' => '10.0000',
            'quantity_before' => '0.0000',
            'quantity_after' => '10.0000',
            'reference_number' => 'REF-A-OCCURRED-NEWER',
            'created_by' => $this->admin->id,
            'occurred_at' => '2026-08-11 15:00:00', // Newer occurred_at
            'created_at' => '2026-08-10 10:00:00',  // Older created_at
        ]);

        // Movement B: occurred_at older, created_at newer
        StockMovement::create([
            'movement_id' => 'MOV-ORD-B',
            'reference_type' => 'App\Features\Inventory\Models\StockReceipt',
            'reference_id' => 102,
            'product_id' => $this->product->id,
            'location_id' => $this->location->id,
            'movement_type' => MovementType::RECEIPT->value,
            'quantity' => '20.0000',
            'quantity_before' => '10.0000',
            'quantity_after' => '30.0000',
            'reference_number' => 'REF-B-CREATED-NEWER',
            'created_by' => $this->admin->id,
            'occurred_at' => '2026-08-01 10:00:00', // Older occurred_at
            'created_at' => '2026-08-11 10:00:00',  // Newer created_at
        ]);

        $response = $this->actingAs($this->admin)->getJson('/api/v1/dashboard');
        $response->assertOk();

        $recentActivity = $response->json('data.recent_activity');

        // Movement B (created_at 2026-08-11) MUST appear before Movement A (created_at 2026-08-10)
        $this->assertCount(2, $recentActivity);
        $this->assertSame('REF-B-CREATED-NEWER', $recentActivity[0]['reference_number']);
        $this->assertSame('REF-A-OCCURRED-NEWER', $recentActivity[1]['reference_number']);
    }
}
