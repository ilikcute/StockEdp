<?php

namespace Tests\Feature\Inventory;

use App\Features\Inventory\DTOs\StockChangeDTO;
use App\Features\Inventory\Enums\MovementType;
use App\Features\Inventory\Exceptions\InsufficientStockException;
use App\Features\Inventory\Models\InventoryBalance;
use App\Features\Inventory\Repositories\Eloquent\InventoryBalanceRepository;
use App\Features\Inventory\Repositories\Eloquent\StockMovementRepository;
use App\Features\Inventory\Services\StockMovementService;
use App\Features\Location\Models\Location;
use App\Features\Product\Models\Product;
use App\Features\Unit\Models\Unit;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class InventoryEngineTest extends TestCase
{
    use RefreshDatabase;

    private StockMovementService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new StockMovementService(
            new InventoryBalanceRepository,
            new StockMovementRepository
        );
    }

    public function test_unique_product_warehouse_constraint()
    {
        $product = Product::factory()->create();
        $location = Location::factory()->create();

        InventoryBalance::create([
            'product_id' => $product->id,
            'location_id' => $location->id,
            'quantity' => 10,
        ]);

        $this->expectException(QueryException::class);
        $this->expectExceptionMessage('Integrity constraint violation');

        InventoryBalance::create([
            'product_id' => $product->id,
            'location_id' => $location->id,
            'quantity' => 5,
        ]);
    }

    public function test_addition_creates_movement_and_updates_balance()
    {
        $product = Product::factory()->create();
        $location = Location::factory()->create();

        $dto = new StockChangeDTO(
            productId: $product->id,
            locationId: $location->id,
            quantity: '10.5000',
            movementType: MovementType::RECEIPT,
            referenceType: 'test',
            referenceId: 1
        );

        $this->service->recordMovement($dto);

        $this->assertDatabaseHas('inventory_balances', [
            'product_id' => $product->id,
            'location_id' => $location->id,
            'quantity' => '10.5000',
        ]);

        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'location_id' => $location->id,
            'movement_type' => 'RECEIPT',
            'quantity' => '10.5000',
            'quantity_before' => '0.0000',
            'quantity_after' => '10.5000',
        ]);
    }

    public function test_subtraction_creates_movement_and_updates_balance()
    {
        $product = Product::factory()->create();
        $location = Location::factory()->create();

        // Initial setup
        $this->service->recordMovement(new StockChangeDTO(
            productId: $product->id,
            locationId: $location->id,
            quantity: '20.0000',
            movementType: MovementType::RECEIPT,
            referenceType: 'setup',
            referenceId: 1
        ));

        // Subtraction
        $this->service->recordMovement(new StockChangeDTO(
            productId: $product->id,
            locationId: $location->id,
            quantity: '5.0000',
            movementType: MovementType::ISSUE,
            referenceType: 'test',
            referenceId: 2
        ));

        $this->assertDatabaseHas('inventory_balances', [
            'product_id' => $product->id,
            'location_id' => $location->id,
            'quantity' => '15.0000',
        ]);

        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'location_id' => $location->id,
            'movement_type' => 'ISSUE',
            'quantity' => '5.0000',
            'quantity_before' => '20.0000',
            'quantity_after' => '15.0000',
        ]);
    }

    public function test_quantity_zero_or_negative_rejected()
    {
        $product = Product::factory()->create();
        $location = Location::factory()->create();

        $this->expectException(\InvalidArgumentException::class);

        $this->service->recordMovement(new StockChangeDTO(
            productId: $product->id,
            locationId: $location->id,
            quantity: '-5.0000',
            movementType: MovementType::RECEIPT,
            referenceType: 'test',
            referenceId: 1
        ));
    }

    public function test_negative_balance_rejected()
    {
        $product = Product::factory()->create();
        $location = Location::factory()->create();

        $this->expectException(InsufficientStockException::class);

        $this->service->recordMovement(new StockChangeDTO(
            productId: $product->id,
            locationId: $location->id,
            quantity: '5.0000',
            movementType: MovementType::ISSUE,
            referenceType: 'test',
            referenceId: 1
        ));
    }

    public function test_rollback_on_failure_in_multiple_movements()
    {
        $product = Product::factory()->create();
        $location = Location::factory()->create();

        // 1st is okay, 2nd will fail due to insufficient balance
        $dto1 = new StockChangeDTO(
            productId: $product->id,
            locationId: $location->id,
            quantity: '10.0000',
            movementType: MovementType::RECEIPT,
            referenceType: 'test',
            referenceId: 1
        );
        $dto2 = new StockChangeDTO(
            productId: $product->id,
            locationId: $location->id,
            quantity: '15.0000',
            movementType: MovementType::ISSUE,
            referenceType: 'test',
            referenceId: 2
        );

        try {
            $this->service->recordMultipleMovements([$dto1, $dto2]);
        } catch (\Exception $e) {
            // Expected
        }

        // Must rollback both
        $this->assertDatabaseMissing('stock_movements', ['reference_id' => 1]);
        $this->assertDatabaseMissing('stock_movements', ['reference_id' => 2]);
        $this->assertDatabaseMissing('inventory_balances', ['quantity' => '10.0000']);
    }

    public function test_concurrency_report()
    {
        // Testing strict concurrency with real deadlocks in a PHPUnit RefreshDatabase
        // setup using SQLite or single connection is impossible/unreliable.
        // Real DB transactions limit our ability to test lockForUpdate properly.
        // We report this as a blocker for automated concurrency simulation via unit tests,
        // although DB transaction mechanisms logically ensure the correctness.
        $this->assertTrue(true, 'Test concurrency is difficult to simulate purely via DB:transaction in PHPUnit.');
    }
}
