<?php

namespace Tests\Feature\Integrity;

use App\Features\Auth\Enums\RoleCode;
use App\Features\Auth\Models\Role;
use App\Features\Auth\Models\User;
use App\Features\Category\Models\Category;
use App\Features\Inventory\Actions\CreateStockIssueAction;
use App\Features\Inventory\Actions\CreateStockReceiptAction;
use App\Features\Inventory\Actions\PostStockIssueAction;
use App\Features\Inventory\Actions\PostStockReceiptAction;
use App\Features\Inventory\Enums\IssueStatus;
use App\Features\Inventory\Exceptions\InsufficientStockException;
use App\Features\Inventory\Models\InventoryBalance;
use App\Features\Inventory\Models\StockIssue;
use App\Features\Inventory\Models\StockMovement;
use App\Features\Location\Models\Location;
use App\Features\Product\Models\Product;
use App\Features\Supplier\Models\Supplier;
use App\Features\Unit\Models\Unit;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionRollbackTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Location $loc;

    private Product $prod1;

    private Product $prod2;

    private Supplier $supplier;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);

        $adminRole = Role::where('code', RoleCode::ADMIN->value)->first();

        $this->user = User::factory()->create(['is_active' => true]);
        $this->user->roles()->attach($adminRole->id);

        $this->loc = Location::create([
            'name' => 'Gudang Tes Rollback',
            'code' => 'LOC-RB',
            'is_active' => true,
        ]);
        $this->user->locations()->attach($this->loc->id);

        $cat = Category::create(['name' => 'General', 'code' => 'GEN', 'is_active' => true]);
        $unit = Unit::create(['name' => 'Pcs', 'code' => 'PCS', 'symbol' => 'pcs', 'is_active' => true]);

        $this->supplier = Supplier::create([
            'name' => 'Supplier Tes',
            'code' => 'SUP-RB',
            'is_active' => true,
        ]);

        $this->prod1 = Product::create([
            'name' => 'Produk RB 1',
            'sku' => 'SKU-RB-01',
            'category_id' => $cat->id,
            'unit_id' => $unit->id,
            'is_active' => true,
            'minimum_stock' => '5.0000',
        ]);

        $this->prod2 = Product::create([
            'name' => 'Produk RB 2',
            'sku' => 'SKU-RB-02',
            'category_id' => $cat->id,
            'unit_id' => $unit->id,
            'is_active' => true,
            'minimum_stock' => '5.0000',
        ]);
    }

    public function test_stock_issue_multi_item_transaction_rolls_back_on_insufficient_stock(): void
    {
        // Isi stok awal hanya untuk Produk 1 (50.0000), Produk 2 kosong (0.0000)
        $receipt = app(CreateStockReceiptAction::class)->execute([
            'supplier_id' => $this->supplier->id,
            'date' => now()->toDateString(),
            'notes' => 'Penerimaan Awal',
            'items' => [
                ['product_id' => $this->prod1->id, 'location_id' => $this->loc->id, 'quantity' => '50.0000'],
            ],
        ], $this->user->id);
        app(PostStockReceiptAction::class)->execute($receipt, $this->user->id);

        $initialMovementCount = StockMovement::count();

        // Buat Stock Issue DRAFT dengan 2 item: Item 1 cukup (10.0000), Item 2 kurang (10.0000 vs 0.0000)
        $issue = app(CreateStockIssueAction::class)->execute([
            'purpose' => 'Pengeluaran Gagal Partial',
            'date' => now()->toDateString(),
            'notes' => 'Pengeluaran Gagal Partial',
            'items' => [
                ['product_id' => $this->prod1->id, 'location_id' => $this->loc->id, 'quantity' => '10.0000'],
                ['product_id' => $this->prod2->id, 'location_id' => $this->loc->id, 'quantity' => '10.0000'],
            ],
        ], $this->user->id);

        // Eksekusi POST yang harus melempar InsufficientStockException karena stok Prod 2 tidak cukup
        $caught = null;
        try {
            app(PostStockIssueAction::class)->execute($issue, $this->user->id);
        } catch (\Throwable $e) {
            $caught = $e;
        }

        $this->assertInstanceOf(InsufficientStockException::class, $caught);
        $this->assertEquals(422, $caught->getCode());
        $this->assertEquals('Saldo stok tidak mencukupi untuk melakukan transaksi ini.', $caught->getMessage());

        // ASSERTION INTEGRITAS ROLLBACK
        // 1. Dokumen status harus tetap DRAFT (bukan POSTED)
        $this->assertEquals(IssueStatus::DRAFT, $issue->fresh()->status);

        // 2. Saldo Prod1 harus tetap 50.0000 (item 1 tidak ter-commit parsial)
        $balance1 = InventoryBalance::where('product_id', $this->prod1->id)
            ->where('location_id', $this->loc->id)
            ->first();
        $this->assertEquals('50.0000', $balance1->quantity);

        // 3. Stock Movement count tidak boleh bertambah
        $this->assertEquals($initialMovementCount, StockMovement::count());

        // 4. Tidak ada Stock Movement yang mengacu pada Stock Issue ini
        $this->assertDatabaseMissing('stock_movements', [
            'reference_type' => StockIssue::class,
            'reference_id' => $issue->id,
        ]);
    }
}
