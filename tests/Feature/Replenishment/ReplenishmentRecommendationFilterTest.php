<?php

namespace Tests\Feature\Replenishment;

use App\Features\Auth\Enums\RoleCode;
use App\Features\Auth\Models\Role;
use App\Features\Auth\Models\User;
use App\Features\Category\Models\Category;
use App\Features\Inventory\Enums\TransferStatus;
use App\Features\Inventory\Models\InventoryBalance;
use App\Features\Inventory\Models\StockTransfer;
use App\Features\Inventory\Models\StockTransferItem;
use App\Features\Location\Models\Location;
use App\Features\Product\Models\Product;
use App\Features\Unit\Models\Unit;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReplenishmentRecommendationFilterTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Location $targetLocation;

    private Location $sourceLocation;

    private Category $category;

    private Unit $unit;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);

        $this->targetLocation = Location::create(['code' => 'TGT-01', 'name' => 'Target Warehouse', 'is_active' => true]);
        $this->sourceLocation = Location::create(['code' => 'SRC-01', 'name' => 'Source Warehouse', 'is_active' => true]);

        $this->admin = User::factory()->create();
        $adminRole = Role::where('code', RoleCode::ADMIN->value)->first();
        $this->admin->roles()->attach($adminRole);
        $this->admin->locations()->attach([$this->targetLocation->id, $this->sourceLocation->id]);

        $this->category = Category::create(['code' => 'CAT-01', 'name' => 'Kategori 1', 'is_active' => true]);
        $this->unit = Unit::create(['code' => 'UNT-01', 'name' => 'PCS', 'symbol' => 'pcs', 'is_active' => true]);
    }

    public function test_recommendation_type_filter_with_matching_rows_outside_raw_page_1(): void
    {
        // Create 15 products with no sister surplus -> EXTERNAL_REORDER
        for ($i = 1; $i <= 15; $i++) {
            $num = str_pad((string) $i, 2, '0', STR_PAD_LEFT);
            $p = Product::create([
                'sku' => "PRD-A-{$num}",
                'name' => "Product Ext Reorder {$num}",
                'category_id' => $this->category->id,
                'unit_id' => $this->unit->id,
                'minimum_stock' => '20.0000',
                'is_active' => true,
            ]);
            InventoryBalance::create([
                'product_id' => $p->id,
                'location_id' => $this->targetLocation->id,
                'quantity' => '2.0000',
            ]);
            // Source has no surplus (balance = 0 or at min)
        }

        // Create 15 products with sister warehouse surplus -> INTERNAL_TRANSFER
        for ($i = 1; $i <= 15; $i++) {
            $num = str_pad((string) $i, 2, '0', STR_PAD_LEFT);
            $p = Product::create([
                'sku' => "PRD-B-{$num}",
                'name' => "Product Int Transfer {$num}",
                'category_id' => $this->category->id,
                'unit_id' => $this->unit->id,
                'minimum_stock' => '10.0000',
                'is_active' => true,
            ]);
            InventoryBalance::create([
                'product_id' => $p->id,
                'location_id' => $this->targetLocation->id,
                'quantity' => '1.0000',
            ]);
            InventoryBalance::create([
                'product_id' => $p->id,
                'location_id' => $this->sourceLocation->id,
                'quantity' => '50.0000', // Surplus 40.0000 > need 9.0000
            ]);
        }

        // Total low-stock products = 30
        // Query page 1 with per_page = 10, recommendation_type = INTERNAL_TRANSFER
        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson("/api/v1/replenishment-recommendations?location_id={$this->targetLocation->id}&per_page=10&recommendation_type=INTERNAL_TRANSFER")
            ->assertStatus(200);

        $items = $response->json('data.data');
        $meta = $response->json('data.meta');
        $summary = $response->json('data.summary');

        // Page 1 MUST contain 10 items, not be empty!
        $this->assertCount(10, $items);
        foreach ($items as $item) {
            $this->assertEquals('INTERNAL_TRANSFER', $item['recommendation_type']);
        }

        $this->assertEquals(15, $meta['total']);
        $this->assertEquals(1, $meta['current_page']);
        $this->assertEquals(2, $meta['last_page']);
        $this->assertEquals(10, $meta['per_page']);
        $this->assertEquals(1, $meta['from']);
        $this->assertEquals(10, $meta['to']);

        // Summary reflects base filter distribution across all 30 items
        $this->assertEquals(30, $summary['low_stock_product_count']);
        $this->assertEquals(15, $summary['internal_transfer_count']);
        $this->assertEquals(15, $summary['external_reorder_count']);

        // Query page 2
        $responsePage2 = $this->actingAs($this->admin, 'sanctum')
            ->getJson("/api/v1/replenishment-recommendations?location_id={$this->targetLocation->id}&per_page=10&page=2&recommendation_type=INTERNAL_TRANSFER")
            ->assertStatus(200);

        $items2 = $responsePage2->json('data.data');
        $meta2 = $responsePage2->json('data.meta');

        $this->assertCount(5, $items2);
        foreach ($items2 as $item) {
            $this->assertEquals('INTERNAL_TRANSFER', $item['recommendation_type']);
        }
        $this->assertEquals(15, $meta2['total']);
        $this->assertEquals(2, $meta2['current_page']);
        $this->assertEquals(11, $meta2['from']);
        $this->assertEquals(15, $meta2['to']);
    }

    public function test_all_recommendation_types_can_be_filtered_individually(): void
    {
        // 1. INBOUND_COVERED: Min 10, On-hand 0, SENT Inbound 10
        $pInbound = Product::create([
            'sku' => 'PRD-TYPE-INBOUND',
            'name' => 'Product Inbound Covered',
            'category_id' => $this->category->id,
            'unit_id' => $this->unit->id,
            'minimum_stock' => '10.0000',
            'is_active' => true,
        ]);
        InventoryBalance::create(['product_id' => $pInbound->id, 'location_id' => $this->targetLocation->id, 'quantity' => '0.0000']);
        $tr = StockTransfer::create([
            'transfer_number' => 'TR-TEST-001',
            'transfer_date' => now()->toDateString(),
            'origin_location_id' => $this->sourceLocation->id,
            'destination_location_id' => $this->targetLocation->id,
            'status' => TransferStatus::SENT->value,
            'created_by' => $this->admin->id,
        ]);
        StockTransferItem::create([
            'stock_transfer_id' => $tr->id,
            'product_id' => $pInbound->id,
            'quantity' => '10.0000',
        ]);

        // 2. INTERNAL_TRANSFER: Min 10, On-hand 0, Source has 30 (Surplus 20 > 10)
        $pInternal = Product::create([
            'sku' => 'PRD-TYPE-INTERNAL',
            'name' => 'Product Internal Transfer',
            'category_id' => $this->category->id,
            'unit_id' => $this->unit->id,
            'minimum_stock' => '10.0000',
            'is_active' => true,
        ]);
        InventoryBalance::create(['product_id' => $pInternal->id, 'location_id' => $this->targetLocation->id, 'quantity' => '0.0000']);
        InventoryBalance::create(['product_id' => $pInternal->id, 'location_id' => $this->sourceLocation->id, 'quantity' => '30.0000']);

        // 3. MIXED: Min 10, On-hand 0, Source has 15 (Surplus 5 < 10) -> Internal 5, External 5
        $pMixed = Product::create([
            'sku' => 'PRD-TYPE-MIXED',
            'name' => 'Product Mixed',
            'category_id' => $this->category->id,
            'unit_id' => $this->unit->id,
            'minimum_stock' => '10.0000',
            'is_active' => true,
        ]);
        InventoryBalance::create(['product_id' => $pMixed->id, 'location_id' => $this->targetLocation->id, 'quantity' => '0.0000']);
        InventoryBalance::create(['product_id' => $pMixed->id, 'location_id' => $this->sourceLocation->id, 'quantity' => '15.0000']);

        // 4. EXTERNAL_REORDER: Min 10, On-hand 0, Source has 10 (Surplus 0)
        $pExternal = Product::create([
            'sku' => 'PRD-TYPE-EXTERNAL',
            'name' => 'Product External Reorder',
            'category_id' => $this->category->id,
            'unit_id' => $this->unit->id,
            'minimum_stock' => '10.0000',
            'is_active' => true,
        ]);
        InventoryBalance::create(['product_id' => $pExternal->id, 'location_id' => $this->targetLocation->id, 'quantity' => '0.0000']);
        InventoryBalance::create(['product_id' => $pExternal->id, 'location_id' => $this->sourceLocation->id, 'quantity' => '10.0000']);

        $types = [
            'INBOUND_COVERED' => 'PRD-TYPE-INBOUND',
            'INTERNAL_TRANSFER' => 'PRD-TYPE-INTERNAL',
            'MIXED' => 'PRD-TYPE-MIXED',
            'EXTERNAL_REORDER' => 'PRD-TYPE-EXTERNAL',
        ];

        foreach ($types as $type => $expectedSku) {
            $res = $this->actingAs($this->admin, 'sanctum')
                ->getJson("/api/v1/replenishment-recommendations?location_id={$this->targetLocation->id}&recommendation_type={$type}")
                ->assertStatus(200);

            $data = $res->json('data.data');
            $meta = $res->json('data.meta');

            $this->assertCount(1, $data);
            $this->assertEquals($expectedSku, $data[0]['sku']);
            $this->assertEquals($type, $data[0]['recommendation_type']);
            $this->assertEquals(1, $meta['total']);
        }

        // Without filter: all 4 returned
        $resAll = $this->actingAs($this->admin, 'sanctum')
            ->getJson("/api/v1/replenishment-recommendations?location_id={$this->targetLocation->id}")
            ->assertStatus(200);

        $this->assertCount(4, $resAll->json('data.data'));
        $this->assertEquals(4, $resAll->json('data.meta.total'));
    }
}
