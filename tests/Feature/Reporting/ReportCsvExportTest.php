<?php

namespace Tests\Feature\Reporting;

use App\Features\Auth\Enums\PermissionCode;
use App\Features\Auth\Enums\RoleCode;
use App\Features\Auth\Models\Permission;
use App\Features\Auth\Models\Role;
use App\Features\Auth\Models\User;
use App\Features\Category\Models\Category;
use App\Features\Inventory\Models\InventoryBalance;
use App\Features\Inventory\Models\StockOpname;
use App\Features\Inventory\Models\StockOpnameItem;
use App\Features\Location\Models\Location;
use App\Features\Product\Models\Product;
use App\Features\Supplier\Models\Supplier;
use App\Features\Unit\Models\Unit;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportCsvExportTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $staffLoc1;

    private User $unauthorizedUser;

    private Location $loc1;

    private Location $loc2;

    private Category $category;

    private Unit $unit;

    private Product $product;

    private Supplier $supplier;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);

        $this->admin = User::factory()->create(['username' => 'admin_csv']);
        $adminRole = Role::where('code', RoleCode::ADMIN->value)->first();
        $this->admin->roles()->attach($adminRole->id);

        $this->loc1 = Location::factory()->create(['code' => 'LCSV-01', 'name' => 'Gudang Utama']);
        $this->loc2 = Location::factory()->create(['code' => 'LCSV-02', 'name' => 'Gudang Cabang']);
        $this->admin->locations()->attach([$this->loc1->id, $this->loc2->id]);

        $this->staffLoc1 = User::factory()->create(['username' => 'staff_csv']);
        $staffRole = Role::where('code', RoleCode::WAREHOUSE_OFFICER->value)->first();
        $this->staffLoc1->roles()->attach($staffRole->id);

        $perms = Permission::whereIn('code', [
            PermissionCode::REPORTS_INVENTORY_BALANCE_VIEW->value,
            PermissionCode::REPORTS_LOW_STOCK_VIEW->value,
            PermissionCode::REPORTS_STOCK_CARD_VIEW->value,
            PermissionCode::REPORTS_STOCK_RECEIPTS_VIEW->value,
            PermissionCode::REPORTS_STOCK_ISSUES_VIEW->value,
            PermissionCode::REPORTS_STOCK_TRANSFERS_VIEW->value,
            PermissionCode::REPORTS_STOCK_ADJUSTMENTS_VIEW->value,
            PermissionCode::REPORTS_STOCK_OPNAMES_VIEW->value,
        ])->get();
        $staffRole->permissions()->syncWithoutDetaching($perms->pluck('id')->toArray());
        $this->staffLoc1->locations()->attach([$this->loc1->id]);

        $this->unauthorizedUser = User::factory()->create(['username' => 'unauth_csv']);

        $this->category = Category::factory()->create(['name' => 'Kategori CSV']);
        $this->unit = Unit::factory()->create(['name' => 'Pcs', 'symbol' => 'Pcs']);
        $this->product = Product::factory()->create([
            'sku' => 'SKU-CSV-001',
            'name' => '=SUM(A1:A2) Item',
            'category_id' => $this->category->id,
            'unit_id' => $this->unit->id,
            'minimum_stock' => 100.0000,
        ]);
        $this->supplier = Supplier::factory()->create(['name' => 'Supplier CSV']);
    }

    public function test_export_endpoints_require_authentication()
    {
        $endpoints = [
            '/api/v1/reports/inventory-balances/export',
            '/api/v1/reports/low-stock/export',
            '/api/v1/reports/stock-card/export',
            '/api/v1/reports/stock-receipts/export',
            '/api/v1/reports/stock-issues/export',
            '/api/v1/reports/stock-transfers/export',
            '/api/v1/reports/stock-adjustments/export',
            '/api/v1/reports/stock-opnames/export',
        ];

        foreach ($endpoints as $url) {
            $this->getJson($url)->assertStatus(401);
        }
    }

    public function test_export_endpoints_require_authorization()
    {
        $endpoints = [
            '/api/v1/reports/inventory-balances/export',
            '/api/v1/reports/low-stock/export?location_id='.$this->loc1->id,
            '/api/v1/reports/stock-card/export?product_id='.$this->product->id.'&location_id='.$this->loc1->id,
            '/api/v1/reports/stock-receipts/export',
            '/api/v1/reports/stock-issues/export',
            '/api/v1/reports/stock-transfers/export',
            '/api/v1/reports/stock-adjustments/export',
            '/api/v1/reports/stock-opnames/export',
        ];

        foreach ($endpoints as $url) {
            $this->actingAs($this->unauthorizedUser, 'sanctum')
                ->getJson($url)
                ->assertStatus(403);
        }
    }

    public function test_inventory_balance_csv_export()
    {
        InventoryBalance::create([
            'product_id' => $this->product->id,
            'location_id' => $this->loc1->id,
            'quantity' => 150.5000,
        ]);

        $response = $this->actingAs($this->staffLoc1, 'sanctum')
            ->get('/api/v1/reports/inventory-balances/export')
            ->assertStatus(200);

        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $response->assertHeader('Cache-Control', 'no-store, private');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');

        $content = $response->streamedContent();

        // UTF-8 BOM check
        $this->assertStringStartsWith("\xEF\xBB\xBF", $content);
        $this->assertStringContainsString('SKU', $content);
        $this->assertStringContainsString('Nama Produk', $content);
        $this->assertStringContainsString('Saldo', $content);
        // Formula injection protection on product name starting with '='
        $this->assertStringContainsString("'=SUM(A1:A2)", $content);
        $this->assertStringContainsString('150.5000', $content);
    }

    public function test_export_ignores_pagination_and_exports_all_rows()
    {
        for ($i = 2; $i <= 4; $i++) {
            $p = Product::factory()->create([
                'sku' => "SKU-CSV-00{$i}",
                'name' => "Product {$i}",
                'category_id' => $this->category->id,
                'unit_id' => $this->unit->id,
            ]);

            InventoryBalance::create([
                'product_id' => $p->id,
                'location_id' => $this->loc1->id,
                'quantity' => 10.0000 * $i,
            ]);
        }

        $response = $this->actingAs($this->staffLoc1, 'sanctum')
            ->get('/api/v1/reports/inventory-balances/export?per_page=1&page=1')
            ->assertStatus(200);

        $content = $response->streamedContent();
        $lines = explode("\n", trim($content));

        // 1 header row + 3 newly created + 1 initial product row = 5 lines minimum
        $this->assertGreaterThanOrEqual(4, count($lines));
    }

    public function test_empty_export_returns_header_only_csv()
    {
        $response = $this->actingAs($this->staffLoc1, 'sanctum')
            ->get('/api/v1/reports/stock-receipts/export')
            ->assertStatus(200);

        $content = $response->streamedContent();
        $this->assertStringStartsWith("\xEF\xBB\xBF", $content);
        $this->assertStringContainsString('Nomor Penerimaan', $content);
        $this->assertStringContainsString('Supplier', $content);
    }

    public function test_stock_transfer_invalid_sent_status_with_received_at_date_basis_returns_422()
    {
        $response = $this->actingAs($this->staffLoc1, 'sanctum')
            ->get('/api/v1/reports/stock-transfers/export?status=SENT&date_basis=RECEIVED_AT')
            ->assertStatus(422);

        $response->assertJsonValidationErrors(['date_basis']);
    }

    public function test_stock_adjustment_export_accepts_canonical_and_rejects_legacy_reasons()
    {
        $response = $this->actingAs($this->staffLoc1, 'sanctum')
            ->get('/api/v1/reports/stock-adjustments/export?reason_code=FOUND')
            ->assertStatus(200);

        $responseLegacy = $this->actingAs($this->staffLoc1, 'sanctum')
            ->get('/api/v1/reports/stock-adjustments/export?reason_code=DAMAGE')
            ->assertStatus(422);

        $responseLegacy->assertJsonValidationErrors(['reason_code']);
    }

    public function test_stock_opname_export_preserves_unexpected_and_signed_variance()
    {
        $opname = StockOpname::create([
            'opname_number' => 'OP-CSV-001',
            'location_id' => $this->loc1->id,
            'opname_date' => '2026-08-01',
            'status' => 'POSTED',
            'posted_at' => now(),
            'created_by' => $this->admin->id,
        ]);

        StockOpnameItem::create([
            'stock_opname_id' => $opname->id,
            'product_id' => $this->product->id,
            'snapshot_quantity' => 10.0000,
            'counted_quantity' => 8.0000,
            'variance_quantity' => -2.0000,
            'is_unexpected' => false,
        ]);

        $response = $this->actingAs($this->staffLoc1, 'sanctum')
            ->get('/api/v1/reports/stock-opnames/export?is_unexpected=0')
            ->assertStatus(200);

        $content = $response->streamedContent();
        $this->assertStringContainsString('-2.0000', $content);
        // Ensure negative decimal isn't corrupted by formula sanitizer
        $this->assertStringNotContainsString("'-2.0000", $content);
        $this->assertStringContainsString('Selisih Keluar', $content);
    }
}
