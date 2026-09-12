<?php

namespace Tests\Feature\Inventory;

use App\Features\Auth\Models\User;
use App\Features\Inventory\Enums\AdjustmentReason;
use App\Features\Inventory\Enums\AdjustmentStatus;
use App\Features\Inventory\Models\InventoryBalance;
use App\Features\Inventory\Models\StockAdjustment;
use App\Features\Inventory\Models\StockMovement;
use App\Features\Location\Models\Location;
use App\Features\Product\Models\Product;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class ConcurrencyStockAdjustmentTest extends TestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();

        if (config('database.default') === 'sqlite') {
            $this->markTestSkipped('Concurrency tests require MySQL/PostgreSQL to test row locking effectively.');
        }
    }

    private function runWorkerCommand(string $type, int $id, int $userId): Process
    {
        $process = new Process([
            PHP_BINARY,
            'artisan',
            'test:concurrency-worker',
            '--type='.$type,
            '--id='.$id,
            '--user='.$userId,
        ]);
        $process->start();

        return $process;
    }

    public function test_concurrent_post_same_adjustment_only_succeeds_once()
    {
        $creator = User::factory()->create();
        $supervisor1 = User::factory()->create();
        $supervisor2 = User::factory()->create();

        $location = Location::factory()->create(['is_active' => true]);
        $product = Product::factory()->create(['is_active' => true]);

        $creator->locations()->attach($location->id);
        $supervisor1->locations()->attach($location->id);
        $supervisor2->locations()->attach($location->id);

        $adjustment = StockAdjustment::create([
            'adjustment_number' => 'ADJ-CONC-001',
            'location_id' => $location->id,
            'adjustment_date' => now()->format('Y-m-d'),
            'direction' => 'INCREASE',
            'reason_code' => AdjustmentReason::FOUND->value,
            'status' => AdjustmentStatus::DRAFT,
            'created_by' => $creator->id,
        ]);
        $adjustment->items()->create(['product_id' => $product->id, 'quantity' => '20.0000']);

        $process1 = $this->runWorkerCommand('adjustment-post', $adjustment->id, $supervisor1->id);
        $process2 = $this->runWorkerCommand('adjustment-post', $adjustment->id, $supervisor2->id);

        $process1->wait();
        $process2->wait();

        $exit1 = $process1->getExitCode();
        $exit2 = $process2->getExitCode();

        // Exactly one worker must succeed, one must fail
        $this->assertTrue(($exit1 === 0 && $exit2 !== 0) || ($exit1 !== 0 && $exit2 === 0));

        $adjustment->refresh();
        $this->assertEquals(AdjustmentStatus::POSTED, $adjustment->status);

        $balance = InventoryBalance::where('location_id', $location->id)->where('product_id', $product->id)->first();
        $this->assertEquals('20.0000', $balance->quantity);

        $movementsCount = StockMovement::where('reference_type', StockAdjustment::class)
            ->where('reference_id', $adjustment->id)
            ->count();
        $this->assertEquals(1, $movementsCount);
    }

    public function test_concurrent_post_and_cancel_only_one_succeeds()
    {
        $creator = User::factory()->create();
        $supervisor = User::factory()->create();

        $location = Location::factory()->create(['is_active' => true]);
        $product = Product::factory()->create(['is_active' => true]);

        $creator->locations()->attach($location->id);
        $supervisor->locations()->attach($location->id);

        $adjustment = StockAdjustment::create([
            'adjustment_number' => 'ADJ-CONC-002',
            'location_id' => $location->id,
            'adjustment_date' => now()->format('Y-m-d'),
            'direction' => 'INCREASE',
            'reason_code' => AdjustmentReason::FOUND->value,
            'status' => AdjustmentStatus::DRAFT,
            'created_by' => $creator->id,
        ]);
        $adjustment->items()->create(['product_id' => $product->id, 'quantity' => '15.0000']);

        $process1 = $this->runWorkerCommand('adjustment-post', $adjustment->id, $supervisor->id);
        $process2 = $this->runWorkerCommand('adjustment-cancel', $adjustment->id, $creator->id);

        $process1->wait();
        $process2->wait();

        $exit1 = $process1->getExitCode();
        $exit2 = $process2->getExitCode();

        $this->assertTrue(($exit1 === 0 && $exit2 !== 0) || ($exit1 !== 0 && $exit2 === 0));

        $adjustment->refresh();
        $this->assertTrue(in_array($adjustment->status, [AdjustmentStatus::POSTED, AdjustmentStatus::CANCELED], true));
    }

    public function test_concurrent_two_adjustments_competing_for_same_balance()
    {
        $creator = User::factory()->create();
        $supervisor = User::factory()->create();

        $location = Location::factory()->create(['is_active' => true]);
        $product = Product::factory()->create(['is_active' => true]);

        $creator->locations()->attach($location->id);
        $supervisor->locations()->attach($location->id);

        InventoryBalance::create([
            'location_id' => $location->id,
            'product_id' => $product->id,
            'quantity' => '15.0000',
        ]);

        $adj1 = StockAdjustment::create([
            'adjustment_number' => 'ADJ-CONC-003',
            'location_id' => $location->id,
            'adjustment_date' => now()->format('Y-m-d'),
            'direction' => 'DECREASE',
            'reason_code' => AdjustmentReason::DAMAGED->value,
            'status' => AdjustmentStatus::DRAFT,
            'created_by' => $creator->id,
        ]);
        $adj1->items()->create(['product_id' => $product->id, 'quantity' => '10.0000']);

        $adj2 = StockAdjustment::create([
            'adjustment_number' => 'ADJ-CONC-004',
            'location_id' => $location->id,
            'adjustment_date' => now()->format('Y-m-d'),
            'direction' => 'DECREASE',
            'reason_code' => AdjustmentReason::LOST->value,
            'status' => AdjustmentStatus::DRAFT,
            'created_by' => $creator->id,
        ]);
        $adj2->items()->create(['product_id' => $product->id, 'quantity' => '10.0000']);

        // Balance is 15. Each adjustment requests 10. Only ONE can succeed without creating negative stock!
        $process1 = $this->runWorkerCommand('adjustment-post', $adj1->id, $supervisor->id);
        $process2 = $this->runWorkerCommand('adjustment-post', $adj2->id, $supervisor->id);

        $process1->wait();
        $process2->wait();

        $exit1 = $process1->getExitCode();
        $exit2 = $process2->getExitCode();

        $this->assertTrue(($exit1 === 0 && $exit2 !== 0) || ($exit1 !== 0 && $exit2 === 0));

        $balance = InventoryBalance::where('location_id', $location->id)->where('product_id', $product->id)->first();
        $this->assertEquals('5.0000', $balance->quantity);
    }
}
