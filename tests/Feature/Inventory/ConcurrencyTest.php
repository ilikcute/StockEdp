<?php

namespace Tests\Feature\Inventory;

use App\Features\Auth\Models\User;
use App\Features\Inventory\Enums\IssueStatus;
use App\Features\Inventory\Enums\ReceiptStatus;
use App\Features\Inventory\Models\InventoryBalance;
use App\Features\Inventory\Models\StockIssue;
use App\Features\Inventory\Models\StockReceipt;
use App\Features\Location\Models\Location;
use App\Features\Product\Models\Product;
use App\Features\Supplier\Models\Supplier;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class ConcurrencyTest extends TestCase
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

    public function test_concurrent_receipt_posting_creates_balance_without_race_condition()
    {
        $user = User::factory()->create();
        $supplier = Supplier::factory()->create(['is_active' => true]);
        $product = Product::factory()->create(['is_active' => true]);
        $location = Location::factory()->create(['is_active' => true]);

        $user->locations()->attach($location);

        $receipt1 = StockReceipt::create([
            'receipt_number' => 'REC-001',
            'supplier_id' => $supplier->id,
            'date' => now(),
            'status' => ReceiptStatus::DRAFT,
            'created_by' => $user->id,
        ]);
        $receipt1->items()->create(['product_id' => $product->id, 'location_id' => $location->id, 'quantity' => 10]);

        $receipt2 = StockReceipt::create([
            'receipt_number' => 'REC-002',
            'supplier_id' => $supplier->id,
            'date' => now(),
            'status' => ReceiptStatus::DRAFT,
            'created_by' => $user->id,
        ]);
        $receipt2->items()->create(['product_id' => $product->id, 'location_id' => $location->id, 'quantity' => 20]);

        $process1 = $this->runWorkerCommand('receipt', $receipt1->id, $user->id);
        $process2 = $this->runWorkerCommand('receipt', $receipt2->id, $user->id);

        $process1->wait();
        $process2->wait();

        $this->assertEquals(0, $process1->getExitCode(), $process1->getErrorOutput());
        $this->assertEquals(0, $process2->getExitCode(), $process2->getErrorOutput());

        $balance = InventoryBalance::where('product_id', $product->id)
            ->where('location_id', $location->id)
            ->first();

        $this->assertNotNull($balance);
        $this->assertEquals(30, $balance->quantity);
    }

    public function test_concurrent_issue_posting_prevents_negative_stock()
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['is_active' => true]);
        $location = Location::factory()->create(['is_active' => true]);

        $user->locations()->attach($location);

        InventoryBalance::create([
            'product_id' => $product->id,
            'location_id' => $location->id,
            'quantity' => 15,
        ]);

        $issue1 = StockIssue::create([
            'issue_number' => 'ISS-001',
            'purpose' => 'Test 1',
            'date' => now(),
            'status' => IssueStatus::DRAFT,
            'created_by' => $user->id,
        ]);
        $issue1->items()->create(['product_id' => $product->id, 'location_id' => $location->id, 'quantity' => 10]);

        $issue2 = StockIssue::create([
            'issue_number' => 'ISS-002',
            'purpose' => 'Test 2',
            'date' => now(),
            'status' => IssueStatus::DRAFT,
            'created_by' => $user->id,
        ]);
        $issue2->items()->create(['product_id' => $product->id, 'location_id' => $location->id, 'quantity' => 10]);

        $process1 = $this->runWorkerCommand('issue', $issue1->id, $user->id);
        $process2 = $this->runWorkerCommand('issue', $issue2->id, $user->id);

        $process1->wait();
        $process2->wait();

        $exitCode1 = $process1->getExitCode();
        $exitCode2 = $process2->getExitCode();

        // One should succeed, one should fail due to negative stock
        $this->assertTrue(($exitCode1 === 0 && $exitCode2 !== 0) || ($exitCode1 !== 0 && $exitCode2 === 0));

        $balance = InventoryBalance::where('product_id', $product->id)
            ->where('location_id', $location->id)
            ->first();

        $this->assertEquals(5, $balance->quantity);
    }

    public function test_concurrent_duplicate_posting_prevents_double_posting()
    {
        $user = User::factory()->create();
        $supplier = Supplier::factory()->create(['is_active' => true]);
        $product = Product::factory()->create(['is_active' => true]);
        $location = Location::factory()->create(['is_active' => true]);

        $user->locations()->attach($location);

        $receipt = StockReceipt::create([
            'receipt_number' => 'REC-001',
            'supplier_id' => $supplier->id,
            'date' => now(),
            'status' => ReceiptStatus::DRAFT,
            'created_by' => $user->id,
        ]);
        $receipt->items()->create(['product_id' => $product->id, 'location_id' => $location->id, 'quantity' => 10]);

        $process1 = $this->runWorkerCommand('receipt', $receipt->id, $user->id);
        $process2 = $this->runWorkerCommand('receipt', $receipt->id, $user->id);

        $process1->wait();
        $process2->wait();

        $exitCode1 = $process1->getExitCode();
        $exitCode2 = $process2->getExitCode();

        // One should succeed, one should fail due to already posted
        $this->assertTrue(($exitCode1 === 0 && $exitCode2 !== 0) || ($exitCode1 !== 0 && $exitCode2 === 0));

        $balance = InventoryBalance::where('product_id', $product->id)
            ->where('location_id', $location->id)
            ->first();

        $this->assertEquals(10, $balance->quantity);
    }
}
