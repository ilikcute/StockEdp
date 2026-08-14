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

class ReplenishmentPendingInboundTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Location $targetLocation;

    private Location $originLocation;

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);

        $this->targetLocation = Location::create(['code' => 'WH-TGT', 'name' => 'Target Warehouse', 'is_active' => true]);
        $this->originLocation = Location::create(['code' => 'WH-ORG', 'name' => 'Origin Warehouse', 'is_active' => true]);

        $this->admin = User::factory()->create();
        $adminRole = Role::where('code', RoleCode::ADMIN->value)->first();
        $this->admin->roles()->attach($adminRole);
        $this->admin->locations()->attach([$this->targetLocation->id, $this->originLocation->id]);

        $cat = Category::create(['code' => 'CAT-01', 'name' => 'Kategori 1', 'is_active' => true]);
        $unit = Unit::create(['code' => 'UNT-01', 'name' => 'PCS', 'symbol' => 'pcs', 'is_active' => true]);

        // Product: min 10, target balance 2.0000 -> gross shortage 8.0000
        $this->product = Product::create([
            'sku' => 'PRD-INBOUND-001',
            'name' => 'Produk Inbound Test',
            'category_id' => $cat->id,
            'unit_id' => $unit->id,
            'minimum_stock' => '10.00',
            'is_active' => true,
        ]);
        InventoryBalance::create([
            'product_id' => $this->product->id,
            'location_id' => $this->targetLocation->id,
            'quantity' => '2.0000',
        ]);
    }

    public function test_only_sent_transfers_contribute_to_pending_inbound(): void
    {
        // 1. DRAFT transfer -> 5.0000 (must be ignored)
        $draftTransfer = StockTransfer::create([
            'transfer_number' => 'TRF-DRAFT',
            'transfer_date' => now()->toDateString(),
            'origin_location_id' => $this->originLocation->id,
            'destination_location_id' => $this->targetLocation->id,
            'status' => TransferStatus::DRAFT->value,
            'created_by' => $this->admin->id,
        ]);
        StockTransferItem::create([
            'stock_transfer_id' => $draftTransfer->id,
            'product_id' => $this->product->id,
            'quantity' => '5.0000',
        ]);

        // 2. RECEIVED transfer -> 7.0000 (must be ignored)
        $receivedTransfer = StockTransfer::create([
            'transfer_number' => 'TRF-RCVD',
            'transfer_date' => now()->toDateString(),
            'origin_location_id' => $this->originLocation->id,
            'destination_location_id' => $this->targetLocation->id,
            'status' => TransferStatus::RECEIVED->value,
            'created_by' => $this->admin->id,
        ]);
        StockTransferItem::create([
            'stock_transfer_id' => $receivedTransfer->id,
            'product_id' => $this->product->id,
            'quantity' => '7.0000',
        ]);

        // 3. CANCELED transfer -> 10.0000 (must be ignored)
        $canceledTransfer = StockTransfer::create([
            'transfer_number' => 'TRF-CNCL',
            'transfer_date' => now()->toDateString(),
            'origin_location_id' => $this->originLocation->id,
            'destination_location_id' => $this->targetLocation->id,
            'status' => TransferStatus::CANCELED->value,
            'created_by' => $this->admin->id,
        ]);
        StockTransferItem::create([
            'stock_transfer_id' => $canceledTransfer->id,
            'product_id' => $this->product->id,
            'quantity' => '10.0000',
        ]);

        // 4. Multiple SENT transfers: 1.2500 + 2.5000 + 0.2500 = 4.0000
        $sent1 = StockTransfer::create([
            'transfer_number' => 'TRF-SENT-1',
            'transfer_date' => now()->toDateString(),
            'origin_location_id' => $this->originLocation->id,
            'destination_location_id' => $this->targetLocation->id,
            'status' => TransferStatus::SENT->value,
            'created_by' => $this->admin->id,
        ]);
        StockTransferItem::create([
            'stock_transfer_id' => $sent1->id,
            'product_id' => $this->product->id,
            'quantity' => '1.2500',
        ]);

        $sent2 = StockTransfer::create([
            'transfer_number' => 'TRF-SENT-2',
            'transfer_date' => now()->toDateString(),
            'origin_location_id' => $this->originLocation->id,
            'destination_location_id' => $this->targetLocation->id,
            'status' => TransferStatus::SENT->value,
            'created_by' => $this->admin->id,
        ]);
        StockTransferItem::create([
            'stock_transfer_id' => $sent2->id,
            'product_id' => $this->product->id,
            'quantity' => '2.5000',
        ]);

        $sent3 = StockTransfer::create([
            'transfer_number' => 'TRF-SENT-3',
            'transfer_date' => now()->toDateString(),
            'origin_location_id' => $this->originLocation->id,
            'destination_location_id' => $this->targetLocation->id,
            'status' => TransferStatus::SENT->value,
            'created_by' => $this->admin->id,
        ]);
        StockTransferItem::create([
            'stock_transfer_id' => $sent3->id,
            'product_id' => $this->product->id,
            'quantity' => '0.2500',
        ]);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/v1/replenishment-recommendations?location_id='.$this->targetLocation->id)
            ->assertStatus(200);

        $row = $response->json('data.data.0');
        $this->assertEquals('8.0000', $row['gross_shortage_quantity']);
        $this->assertEquals('4.0000', $row['pending_inbound_quantity']);
        $this->assertEquals('4.0000', $row['net_replenishment_need']);
    }

    public function test_inbound_covering_entire_shortage_sets_inbound_covered_recommendation(): void
    {
        // Gross shortage is 8.0000; create SENT inbound of 10.0000
        $sentTransfer = StockTransfer::create([
            'transfer_number' => 'TRF-SENT-FULL',
            'transfer_date' => now()->toDateString(),
            'origin_location_id' => $this->originLocation->id,
            'destination_location_id' => $this->targetLocation->id,
            'status' => TransferStatus::SENT->value,
            'created_by' => $this->admin->id,
        ]);
        StockTransferItem::create([
            'stock_transfer_id' => $sentTransfer->id,
            'product_id' => $this->product->id,
            'quantity' => '10.0000',
        ]);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/v1/replenishment-recommendations?location_id='.$this->targetLocation->id)
            ->assertStatus(200);

        $row = $response->json('data.data.0');
        $this->assertEquals('8.0000', $row['gross_shortage_quantity']);
        $this->assertEquals('10.0000', $row['pending_inbound_quantity']);
        $this->assertEquals('0.0000', $row['net_replenishment_need']);
        $this->assertEquals('INBOUND_COVERED', $row['recommendation_type']);
        $this->assertEquals('0.0000', $row['internal_replenishment_quantity']);
        $this->assertEquals('0.0000', $row['external_reorder_quantity']);
        $this->assertEmpty($row['source_allocations']);
    }
}
