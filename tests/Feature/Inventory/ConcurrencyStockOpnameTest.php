<?php

namespace Tests\Feature\Inventory;

use App\Features\Auth\Enums\RoleCode;
use App\Features\Auth\Models\Role;
use App\Features\Auth\Models\User;
use App\Features\Inventory\Actions\CompleteStockOpnameAction;
use App\Features\Inventory\Actions\InputCountAction;
use App\Features\Inventory\Actions\StartStockOpnameAction;
use App\Features\Inventory\Enums\OpnameStatus;
use App\Features\Inventory\Models\InventoryBalance;
use App\Features\Inventory\Models\StockOpname;
use App\Features\Location\Models\Location;
use App\Features\Product\Models\Product;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class ConcurrencyStockOpnameTest extends TestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();

        if (config('database.default') === 'sqlite') {
            $this->markTestSkipped('Concurrency tests require MySQL/PostgreSQL to test row locking effectively.');
        }

        $this->seed(RoleAndPermissionSeeder::class);
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

    public function test_concurrent_start_on_same_location_only_one_succeeds()
    {
        $user = User::factory()->create();
        $supervisorRole = Role::where('code', RoleCode::INVENTORY_SUPERVISOR->value)->first();
        $user->roles()->attach($supervisorRole);

        $location = Location::factory()->create(['is_active' => true]);
        $user->locations()->attach($location->id);

        $opname1 = StockOpname::create([
            'opname_number' => 'SOP-CONC-001',
            'location_id' => $location->id,
            'opname_date' => now()->format('Y-m-d'),
            'status' => OpnameStatus::DRAFT,
            'created_by' => $user->id,
        ]);

        $opname2 = StockOpname::create([
            'opname_number' => 'SOP-CONC-002',
            'location_id' => $location->id,
            'opname_date' => now()->format('Y-m-d'),
            'status' => OpnameStatus::DRAFT,
            'created_by' => $user->id,
        ]);

        $process1 = $this->runWorkerCommand('opname-start', $opname1->id, $user->id);
        $process2 = $this->runWorkerCommand('opname-start', $opname2->id, $user->id);

        $process1->wait();
        $process2->wait();

        $exit1 = $process1->getExitCode();
        $exit2 = $process2->getExitCode();

        // Exactly one process succeeds, the other gets 409
        $this->assertTrue(($exit1 === 0 && $exit2 !== 0) || ($exit1 !== 0 && $exit2 === 0));
    }

    public function test_concurrent_post_only_one_succeeds()
    {
        $creator = User::factory()->create();
        $poster1 = User::factory()->create();
        $poster2 = User::factory()->create();

        $supervisorRole = Role::where('code', RoleCode::INVENTORY_SUPERVISOR->value)->first();
        $poster1->roles()->attach($supervisorRole);
        $poster2->roles()->attach($supervisorRole);

        $location = Location::factory()->create(['is_active' => true]);
        $product = Product::factory()->create(['is_active' => true]);

        $poster1->locations()->attach($location->id);
        $poster2->locations()->attach($location->id);

        InventoryBalance::create(['location_id' => $location->id, 'product_id' => $product->id, 'quantity' => '100.0000']);

        $opname = StockOpname::create([
            'opname_number' => 'SOP-CONC-003',
            'location_id' => $location->id,
            'opname_date' => now()->format('Y-m-d'),
            'status' => OpnameStatus::DRAFT,
            'created_by' => $creator->id,
        ]);

        app(StartStockOpnameAction::class)->execute($opname, $creator->id);

        $item = $opname->items()->where('product_id', $product->id)->first();
        app(InputCountAction::class)->execute($opname, $item->id, ['counted_quantity' => '100.0000'], $creator->id);
        app(CompleteStockOpnameAction::class)->execute($opname, $creator->id);

        // Poster 1 and Poster 2 attempt concurrent post
        $process1 = $this->runWorkerCommand('opname-post', $opname->id, $poster1->id);
        $process2 = $this->runWorkerCommand('opname-post', $opname->id, $poster2->id);

        $process1->wait();
        $process2->wait();

        $exit1 = $process1->getExitCode();
        $exit2 = $process2->getExitCode();

        $this->assertTrue(($exit1 === 0 && $exit2 !== 0) || ($exit1 !== 0 && $exit2 === 0));

        $opname->refresh();
        $this->assertEquals(OpnameStatus::POSTED, $opname->status);
    }
}
