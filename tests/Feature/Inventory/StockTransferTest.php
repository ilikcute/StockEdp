<?php

namespace Tests\Feature\Inventory;

use App\Features\Auth\Enums\RoleCode;
use App\Features\Auth\Models\User;
use App\Features\Inventory\Enums\TransferStatus;
use App\Features\Inventory\Models\StockTransfer;
use App\Features\Inventory\Models\StockTransferItem;
use App\Features\Location\Models\Location;
use App\Features\Product\Models\Product;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class StockTransferTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Location $originLocation;

    private Location $destinationLocation;

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);

        $this->user = User::factory()->create();
        $this->user->assignRole(RoleCode::ADMIN->value);

        $this->originLocation = Location::factory()->create(['is_active' => true]);
        $this->destinationLocation = Location::factory()->create(['is_active' => true]);

        // Create category and unit via DB to avoid factory cascade issues
        $categoryId = DB::table('categories')->insertGetId([
            'name' => 'Test Category',
            'code' => 'CAT-TEST',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $unitId = DB::table('units')->insertGetId([
            'name' => 'Test Unit',
            'code' => 'UNT-TEST',
            'symbol' => 'U',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $productId = DB::table('products')->insertGetId([
            'name' => 'Test Product',
            'sku' => 'PRD-TEST',
            'category_id' => $categoryId,
            'unit_id' => $unitId,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->product = Product::find($productId);

        // Assign locations to user
        $this->user->locations()->attach([$this->originLocation->id, $this->destinationLocation->id]);
    }

    private function createTransferData(): array
    {
        return [
            'origin_location_id' => $this->originLocation->id,
            'destination_location_id' => $this->destinationLocation->id,
            'transfer_date' => now()->format('Y-m-d'),
            'notes' => 'Test transfer',
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 10,
                ],
            ],
        ];
    }

    public function test_user_can_create_stock_transfer()
    {
        $data = $this->createTransferData();

        $response = $this->actingAs($this->user)->postJson('/api/v1/stock-transfers', $data);

        $response->assertStatus(201)
            ->assertJsonPath('data.status', TransferStatus::DRAFT->value)
            ->assertJsonPath('data.items.0.quantity', '10.0000');

        $this->assertDatabaseHas('stock_transfers', [
            'origin_location_id' => $this->originLocation->id,
            'destination_location_id' => $this->destinationLocation->id,
            'status' => TransferStatus::DRAFT->value,
        ]);

        $this->assertDatabaseHas('stock_transfer_items', [
            'product_id' => $this->product->id,
            'quantity' => 10,
        ]);
    }

    public function test_cannot_create_transfer_if_locations_same()
    {
        $data = $this->createTransferData();
        $data['destination_location_id'] = $this->originLocation->id;

        $response = $this->actingAs($this->user)->postJson('/api/v1/stock-transfers', $data);
        $response->assertStatus(422);
    }

    public function test_cannot_create_transfer_with_inactive_product()
    {
        $inactiveProductId = DB::table('products')->insertGetId([
            'name' => 'Inactive Product',
            'sku' => 'PRD-INACT',
            'category_id' => $this->product->category_id,
            'unit_id' => $this->product->unit_id,
            'is_active' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $data = $this->createTransferData();
        $data['items'][0]['product_id'] = $inactiveProductId;

        $response = $this->actingAs($this->user)->postJson('/api/v1/stock-transfers', $data);
        $response->assertStatus(422);
    }

    public function test_user_can_send_draft_transfer()
    {
        $transfer = StockTransfer::create([
            'transfer_number' => 'TRF-TEST-1',
            'status' => TransferStatus::DRAFT,
            'origin_location_id' => $this->originLocation->id,
            'destination_location_id' => $this->destinationLocation->id,
            'transfer_date' => now()->format('Y-m-d'),
            'created_by' => $this->user->id,
        ]);
        StockTransferItem::create([
            'stock_transfer_id' => $transfer->id,
            'product_id' => $this->product->id,
            'quantity' => 10,
        ]);

        DB::table('inventory_balances')->insert([
            'location_id' => $this->originLocation->id,
            'product_id' => $this->product->id,
            'quantity' => 100,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($this->user)->postJson("/api/v1/stock-transfers/{$transfer->id}/send");

        $response->assertStatus(200);

        $transfer->refresh();
        $this->assertEquals(TransferStatus::SENT, $transfer->status);

        // check stock movement
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $this->product->id,
            'location_id' => $this->originLocation->id,
            'quantity' => '10.0000',
        ]);
    }

    public function test_user_can_receive_sent_transfer()
    {
        $transfer = StockTransfer::create([
            'transfer_number' => 'TRF-TEST-2',
            'status' => TransferStatus::SENT,
            'origin_location_id' => $this->originLocation->id,
            'destination_location_id' => $this->destinationLocation->id,
            'transfer_date' => now()->format('Y-m-d'),
            'created_by' => $this->user->id,
        ]);
        StockTransferItem::create([
            'stock_transfer_id' => $transfer->id,
            'product_id' => $this->product->id,
            'quantity' => 10,
        ]);

        $response = $this->actingAs($this->user)->postJson("/api/v1/stock-transfers/{$transfer->id}/receive");

        $response->assertStatus(200);

        $transfer->refresh();
        $this->assertEquals(TransferStatus::RECEIVED, $transfer->status);

        // check stock movement
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $this->product->id,
            'location_id' => $this->destinationLocation->id,
            'quantity' => '10.0000',
        ]);
    }
}
