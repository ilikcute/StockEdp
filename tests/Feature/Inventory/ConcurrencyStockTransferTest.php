<?php

namespace Tests\Feature\Inventory;

use App\Features\Auth\Models\User;
use App\Features\Inventory\Enums\TransferStatus;
use App\Features\Inventory\Models\InventoryBalance;
use App\Features\Inventory\Models\StockMovement;
use App\Features\Inventory\Models\StockTransfer;
use App\Features\Location\Models\Location;
use App\Features\Product\Models\Product;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class ConcurrencyStockTransferTest extends TestCase
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

    public function test_concurrent_send_same_transfer_only_succeeds_once()
    {
        $user = User::factory()->create();
        $origin = Location::factory()->create(['is_active' => true]);
        $destination = Location::factory()->create(['is_active' => true]);
        $product = Product::factory()->create(['is_active' => true]);

        $user->locations()->attach([$origin->id, $destination->id]);

        InventoryBalance::create([
            'location_id' => $origin->id,
            'product_id' => $product->id,
            'quantity' => 50,
        ]);

        $transfer = StockTransfer::create([
            'transfer_number' => 'TRF-CONC-001',
            'origin_location_id' => $origin->id,
            'destination_location_id' => $destination->id,
            'status' => TransferStatus::DRAFT,
            'transfer_date' => now(),
            'created_by' => $user->id,
        ]);
        $transfer->items()->create(['product_id' => $product->id, 'quantity' => 20]);

        $process1 = $this->runWorkerCommand('transfer-send', $transfer->id, $user->id);
        $process2 = $this->runWorkerCommand('transfer-send', $transfer->id, $user->id);

        $process1->wait();
        $process2->wait();

        $exit1 = $process1->getExitCode();
        $exit2 = $process2->getExitCode();

        // Exactly one worker must succeed, one must fail because status changed to SENT
        $this->assertTrue(($exit1 === 0 && $exit2 !== 0) || ($exit1 !== 0 && $exit2 === 0));

        $transfer->refresh();
        $this->assertEquals(TransferStatus::SENT, $transfer->status);

        $balanceOrigin = InventoryBalance::where('location_id', $origin->id)->where('product_id', $product->id)->first();
        $this->assertEquals(30, $balanceOrigin->quantity);

        $movements = StockMovement::where('reference_type', StockTransfer::class)
            ->where('reference_id', $transfer->id)
            ->count();
        $this->assertEquals(1, $movements);
    }

    public function test_concurrent_receive_same_transfer_only_succeeds_once()
    {
        $user = User::factory()->create();
        $origin = Location::factory()->create(['is_active' => true]);
        $destination = Location::factory()->create(['is_active' => true]);
        $product = Product::factory()->create(['is_active' => true]);

        $user->locations()->attach([$origin->id, $destination->id]);

        $transfer = StockTransfer::create([
            'transfer_number' => 'TRF-CONC-002',
            'origin_location_id' => $origin->id,
            'destination_location_id' => $destination->id,
            'status' => TransferStatus::SENT,
            'transfer_date' => now(),
            'created_by' => $user->id,
        ]);
        $transfer->items()->create(['product_id' => $product->id, 'quantity' => 20]);

        $process1 = $this->runWorkerCommand('transfer-receive', $transfer->id, $user->id);
        $process2 = $this->runWorkerCommand('transfer-receive', $transfer->id, $user->id);

        $process1->wait();
        $process2->wait();

        $exit1 = $process1->getExitCode();
        $exit2 = $process2->getExitCode();

        // Exactly one worker must succeed
        $this->assertTrue(($exit1 === 0 && $exit2 !== 0) || ($exit1 !== 0 && $exit2 === 0));

        $transfer->refresh();
        $this->assertEquals(TransferStatus::RECEIVED, $transfer->status);

        $balanceDest = InventoryBalance::where('location_id', $destination->id)->where('product_id', $product->id)->first();
        $this->assertNotNull($balanceDest);
        $this->assertEquals(20, $balanceDest->quantity);
    }

    public function test_concurrent_send_and_cancel_only_one_succeeds()
    {
        $user = User::factory()->create();
        $origin = Location::factory()->create(['is_active' => true]);
        $destination = Location::factory()->create(['is_active' => true]);
        $product = Product::factory()->create(['is_active' => true]);

        $user->locations()->attach([$origin->id, $destination->id]);

        InventoryBalance::create([
            'location_id' => $origin->id,
            'product_id' => $product->id,
            'quantity' => 50,
        ]);

        $transfer = StockTransfer::create([
            'transfer_number' => 'TRF-CONC-003',
            'origin_location_id' => $origin->id,
            'destination_location_id' => $destination->id,
            'status' => TransferStatus::DRAFT,
            'transfer_date' => now(),
            'created_by' => $user->id,
        ]);
        $transfer->items()->create(['product_id' => $product->id, 'quantity' => 20]);

        $process1 = $this->runWorkerCommand('transfer-send', $transfer->id, $user->id);
        $process2 = $this->runWorkerCommand('transfer-cancel', $transfer->id, $user->id);

        $process1->wait();
        $process2->wait();

        $exit1 = $process1->getExitCode();
        $exit2 = $process2->getExitCode();

        $this->assertTrue(($exit1 === 0 && $exit2 !== 0) || ($exit1 !== 0 && $exit2 === 0));

        $transfer->refresh();
        $this->assertTrue(in_array($transfer->status, [TransferStatus::SENT, TransferStatus::CANCELED]));
    }
}
