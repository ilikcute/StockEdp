<?php

namespace Tests\Feature\Reporting;

use App\Features\Auth\Enums\RoleCode;
use App\Features\Auth\Models\Role;
use App\Features\Auth\Models\User;
use App\Features\Category\Models\Category;
use App\Features\Inventory\Models\InventoryBalance;
use App\Features\Location\Models\Location;
use App\Features\Product\Models\Product;
use App\Features\Unit\Models\Unit;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use OpenSpout\Reader\XLSX\Reader;
use Tests\TestCase;

class ReportXlsxExportTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Location $location;

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);

        $this->admin = User::factory()->create(['username' => 'admin_xlsx']);
        $adminRole = Role::where('code', RoleCode::ADMIN->value)->first();
        $this->admin->roles()->attach($adminRole->id);

        $this->location = Location::factory()->create(['code' => 'LXLSX-01', 'name' => 'Gudang Pusat']);
        $this->admin->locations()->attach([$this->location->id]);

        $category = Category::factory()->create(['name' => 'Elektronik']);
        $unit = Unit::factory()->create(['name' => 'Unit', 'symbol' => 'UNT']);

        $this->product = Product::factory()->create([
            'sku' => '012345', // Starts with 0, test string preservation
            'barcode' => '8991234567890',
            'name' => '=HYPERLINK("http://attacker.com") Mouse', // Formula injection attempt
            'category_id' => $category->id,
            'unit_id' => $unit->id,
            'minimum_stock' => 10.0000,
        ]);

        InventoryBalance::create([
            'location_id' => $this->location->id,
            'product_id' => $this->product->id,
            'quantity' => 125.5000,
        ]);
    }

    public function test_all_11_endpoints_support_xlsx_export()
    {
        $endpoints = [
            ['url' => '/api/v1/reports/inventory-balances/export', 'params' => ['location_id' => $this->location->id]],
            ['url' => '/api/v1/reports/low-stock/export', 'params' => ['location_id' => $this->location->id]],
            ['url' => '/api/v1/reports/stock-card/export', 'params' => ['location_id' => $this->location->id, 'product_id' => $this->product->id, 'start_date' => '2026-09-01', 'end_date' => '2026-09-16']],
            ['url' => '/api/v1/reports/stock-receipts/export', 'params' => ['start_date' => '2026-09-01', 'end_date' => '2026-09-16']],
            ['url' => '/api/v1/reports/stock-issues/export', 'params' => ['start_date' => '2026-09-01', 'end_date' => '2026-09-16']],
            ['url' => '/api/v1/reports/stock-transfers/export', 'params' => ['start_date' => '2026-09-01', 'end_date' => '2026-09-16']],
            ['url' => '/api/v1/reports/stock-adjustments/export', 'params' => ['start_date' => '2026-09-01', 'end_date' => '2026-09-16']],
            ['url' => '/api/v1/reports/stock-opnames/export', 'params' => ['start_date' => '2026-09-01', 'end_date' => '2026-09-16']],
            ['url' => '/api/v1/reports/store-allocations/export', 'params' => []],
            ['url' => '/api/v1/reports/field-balances/export', 'params' => []],
            ['url' => '/api/v1/reports/inventory-movement/export', 'params' => ['type' => 'slow-moving', 'period' => 90]],
        ];

        foreach ($endpoints as $endpoint) {
            $query = array_merge($endpoint['params'], ['format' => 'xlsx']);
            $response = $this->actingAs($this->admin)->get($endpoint['url'] . '?' . http_build_query($query));

            $response->assertStatus(200);
            $response->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

            $disposition = (string) $response->headers->get('content-disposition');
            $this->assertStringContainsString('attachment;', $disposition, "Endpoint {$endpoint['url']} missing attachment disposition");
            $this->assertStringContainsString('.xlsx', $disposition, "Endpoint {$endpoint['url']} missing .xlsx extension in filename");

            $content = $response->streamedContent();
            $this->assertNotEmpty($content, "Endpoint {$endpoint['url']} produced empty content");
            // Standard ZIP/XLSX magic number
            $this->assertSame("PK\x03\x04", substr($content, 0, 4), "Endpoint {$endpoint['url']} content does not match XLSX ZIP magic header");
        }
    }

    public function test_omitted_format_defaults_to_csv_for_backward_compatibility()
    {
        $response = $this->actingAs($this->admin)->get('/api/v1/reports/inventory-balances/export?location_id=' . $this->location->id);

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $disposition = (string) $response->headers->get('content-disposition');
        $this->assertStringContainsString('.csv', $disposition);
    }

    public function test_backward_compatibility_csv_export_still_works()
    {
        $response = $this->actingAs($this->admin)->get('/api/v1/reports/inventory-balances/export?location_id=' . $this->location->id . '&format=csv');

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $disposition = (string) $response->headers->get('content-disposition');
        $this->assertStringContainsString('.csv', $disposition);
    }

    public function test_xlsx_file_is_valid_and_parseable_by_openspout_reader()
    {
        $response = $this->actingAs($this->admin)->get('/api/v1/reports/inventory-balances/export?location_id=' . $this->location->id . '&format=xlsx');
        $response->assertStatus(200);

        $binary = $response->streamedContent();
        $tempFile = tempnam(sys_get_temp_dir(), 'xlsx_test_') . '.xlsx';
        file_put_contents($tempFile, $binary);

        try {
            $reader = new Reader();
            $reader->open($tempFile);

            $rows = [];
            foreach ($reader->getSheetIterator() as $sheet) {
                foreach ($sheet->getRowIterator() as $row) {
                    $rows[] = $row->toArray();
                }
            }
            $reader->close();

            $this->assertGreaterThanOrEqual(2, count($rows), 'XLSX should have at least a header row and data rows');

            // Header row checks
            $header = $rows[0];
            $this->assertContains('Kode Lokasi', $header);
            $this->assertContains('Nama Lokasi', $header);
            $this->assertContains('SKU', $header);
            $this->assertContains('Nama Produk', $header);
            $this->assertContains('Saldo', $header);

            // Data row checks
            $dataRow = $rows[1];
            // Check that leading zero is preserved for SKU '012345'
            $this->assertSame('012345', $dataRow[0]);

            // Check formula injection escape on product name: '=HYPERLINK...' should be escaped as "'=HYPERLINK..."
            $this->assertSame("'=HYPERLINK(\"http://attacker.com\") Mouse", $dataRow[1]);

            // Quantity is numeric float/int, not a string
            $this->assertTrue(is_float($dataRow[7]) || is_int($dataRow[7]), 'Quantity in XLSX should be numeric');
            $this->assertEquals(125.5, $dataRow[7]);
        } finally {
            if (file_exists($tempFile)) {
                @unlink($tempFile);
            }
        }
    }

    public function test_invalid_format_parameter_returns_validation_error()
    {
        $response = $this->actingAs($this->admin)->getJson('/api/v1/reports/inventory-balances/export?format=pdf');
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['format']);
    }
}
