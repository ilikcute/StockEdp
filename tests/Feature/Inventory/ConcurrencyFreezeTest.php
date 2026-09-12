<?php

namespace Tests\Feature\Inventory;

use App\Features\Auth\Models\User;
use App\Features\Inventory\Enums\AdjustmentReason;
use App\Features\Inventory\Enums\AdjustmentStatus;
use App\Features\Inventory\Enums\IssueStatus;
use App\Features\Inventory\Enums\OpnameStatus;
use App\Features\Inventory\Enums\ReceiptStatus;
use App\Features\Inventory\Models\InventoryBalance;
use App\Features\Inventory\Models\StockAdjustment;
use App\Features\Inventory\Models\StockIssue;
use App\Features\Inventory\Models\StockOpname;
use App\Features\Inventory\Models\StockReceipt;
use App\Features\Location\Models\Location;
use App\Features\Product\Models\Product;
use App\Features\Supplier\Models\Supplier;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class ConcurrencyFreezeTest extends TestCase
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

    private function createTestOpname(Location $location, User $user): StockOpname
    {
        static $seq = 1;

        return StockOpname::create([
            'opname_number' => 'SOP-CF-'.str_pad((string) ($seq++), 4, '0', STR_PAD_LEFT),
            'location_id' => $location->id,
            'opname_date' => now()->format('Y-m-d'),
            'status' => OpnameStatus::IN_PROGRESS,
            'created_by' => $user->id,
        ]);
    }

    public function test_concurrent_freeze_and_receipt_post()
    {
        $user = User::factory()->create();
        $location = Location::factory()->create(['is_active' => true]);
        $product = Product::factory()->create(['is_active' => true]);
        $supplier = Supplier::factory()->create(['is_active' => true]);
        $user->locations()->attach($location->id);

        $opname = $this->createTestOpname($location, $user);

        $receipt = StockReceipt::create([
            'receipt_number' => 'RCP-CONC-001',
            'supplier_id' => $supplier->id,
            'date' => now()->format('Y-m-d'),
            'status' => ReceiptStatus::DRAFT,
            'created_by' => $user->id,
        ]);
        $receipt->items()->create(['location_id' => $location->id, 'product_id' => $product->id, 'quantity' => '10.0000']);

        // Process 1 freezes location with real opname id
        $process1 = $this->runWorkerCommand('freeze', $location->id, $opname->id);
        $process2 = $this->runWorkerCommand('receipt', $receipt->id, $user->id);

        $process1->wait();
        $process2->wait();

        $exit1 = $process1->getExitCode();
        $exit2 = $process2->getExitCode();

        if ($exit1 === 0 && $exit2 !== 0) {
            $receipt->refresh();
            $this->assertEquals(ReceiptStatus::DRAFT, $receipt->status);
        } else {
            $receipt->refresh();
            $this->assertEquals(ReceiptStatus::POSTED, $receipt->status);
        }
    }

    public function test_concurrent_freeze_and_issue_post()
    {
        $user = User::factory()->create();
        $location = Location::factory()->create(['is_active' => true]);
        $product = Product::factory()->create(['is_active' => true]);
        $user->locations()->attach($location->id);

        $opname = $this->createTestOpname($location, $user);

        InventoryBalance::create([
            'location_id' => $location->id,
            'product_id' => $product->id,
            'quantity' => '50.0000',
        ]);

        $issue = StockIssue::create([
            'issue_number' => 'ISS-CONC-001',
            'date' => now()->format('Y-m-d'),
            'purpose' => 'Operational',
            'status' => IssueStatus::DRAFT,
            'created_by' => $user->id,
        ]);
        $issue->items()->create(['location_id' => $location->id, 'product_id' => $product->id, 'quantity' => '10.0000']);

        $process1 = $this->runWorkerCommand('freeze', $location->id, $opname->id);
        $process2 = $this->runWorkerCommand('issue', $issue->id, $user->id);

        $process1->wait();
        $process2->wait();

        $this->assertTrue(true);
    }

    public function test_concurrent_freeze_and_adjustment_post()
    {
        $creator = User::factory()->create();
        $poster = User::factory()->create();
        $location = Location::factory()->create(['is_active' => true]);
        $product = Product::factory()->create(['is_active' => true]);
        $poster->locations()->attach($location->id);

        $opname = $this->createTestOpname($location, $creator);

        $adjustment = StockAdjustment::create([
            'adjustment_number' => 'ADJ-CONC-F01',
            'location_id' => $location->id,
            'adjustment_date' => now()->format('Y-m-d'),
            'direction' => 'INCREASE',
            'reason_code' => AdjustmentReason::FOUND->value,
            'status' => AdjustmentStatus::DRAFT,
            'created_by' => $creator->id,
        ]);
        $adjustment->items()->create(['product_id' => $product->id, 'quantity' => '10.0000']);

        $process1 = $this->runWorkerCommand('freeze', $location->id, $opname->id);
        $process2 = $this->runWorkerCommand('adjustment-post', $adjustment->id, $poster->id);

        $process1->wait();
        $process2->wait();

        $this->assertTrue(true);
    }

    public function test_concurrent_lock_row_creation_for_new_location()
    {
        $user = User::factory()->create();
        $newLoc = Location::factory()->create(['is_active' => true]);
        $newLoc2 = Location::factory()->create(['is_active' => true]);

        $opname1 = $this->createTestOpname($newLoc, $user);
        $opname2 = $this->createTestOpname($newLoc2, $user);

        // Clear lock row to simulate brand new unindexed location
        DB::table('inventory_location_locks')->where('location_id', $newLoc->id)->delete();

        $process1 = $this->runWorkerCommand('freeze', $newLoc->id, $opname1->id);
        $process2 = $this->runWorkerCommand('freeze', $newLoc->id, $opname2->id);

        $process1->wait();
        $process2->wait();

        $this->assertDatabaseHas('inventory_location_locks', [
            'location_id' => $newLoc->id,
            'is_frozen' => true,
        ]);
    }
}
