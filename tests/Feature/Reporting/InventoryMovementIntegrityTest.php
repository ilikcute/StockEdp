<?php

namespace Tests\Feature\Reporting;

use App\Features\Auth\Enums\RoleCode;
use App\Features\Auth\Models\Role;
use App\Features\Auth\Models\User;
use App\Features\Inventory\Enums\MovementType;
use App\Features\Inventory\Models\InventoryBalance;
use App\Features\Inventory\Models\StockMovement;
use App\Features\Location\Models\Location;
use App\Features\Product\Models\Product;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class InventoryMovementIntegrityTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Location $location;

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);

        $this->location = Location::factory()->create(['name' => 'Main Warehouse', 'is_active' => true]);
        $this->product = Product::factory()->create(['name' => 'Integrity Product', 'is_active' => true]);

        $this->user = User::factory()->create();
        $this->user->roles()->attach(Role::where('code', RoleCode::ADMIN->value)->first()->id);
        $this->user->locations()->attach($this->location->id);

        InventoryBalance::create([
            'product_id' => $this->product->id,
            'location_id' => $this->location->id,
            'quantity' => '150.0000',
        ]);

        StockMovement::create([
            'movement_id' => (string) Str::uuid(),
            'product_id' => $this->product->id,
            'location_id' => $this->location->id,
            'movement_type' => MovementType::ISSUE->value,
            'quantity' => '30.0000',
            'quantity_before' => '180.0000',
            'quantity_after' => '150.0000',
            'reference_type' => 'App\Features\Inventory\Models\StockIssue',
            'reference_id' => 1,
            'occurred_at' => now(),
            'created_by' => $this->user->id,
        ]);
    }

    public function test_read_only_invariant_zero_database_mutation(): void
    {
        $initialBalancesCount = InventoryBalance::count();
        $initialMovementsCount = StockMovement::count();
        $initialBalance = InventoryBalance::first()->quantity;

        // Perform multiple GET calls
        $this->actingAs($this->user)->getJson('/api/v1/dashboard/inventory-movement-summary');
        $this->actingAs($this->user)->getJson('/api/v1/reports/inventory-movement?type=slow-moving');
        $this->actingAs($this->user)->getJson('/api/v1/reports/inventory-movement?type=fast-moving');
        $this->actingAs($this->user)->get('/api/v1/reports/inventory-movement/export?type=slow-moving');
        $this->actingAs($this->user)->get('/api/v1/reports/inventory-movement/export?type=fast-moving');

        $this->assertSame($initialBalancesCount, InventoryBalance::count());
        $this->assertSame($initialMovementsCount, StockMovement::count());
        $this->assertSame($initialBalance, InventoryBalance::first()->quantity);
    }

    public function test_csv_export_slow_moving_streams_valid_content(): void
    {
        $dormant = Product::factory()->create(['name' => 'Slow Dormant Product', 'is_active' => true]);
        InventoryBalance::create([
            'product_id' => $dormant->id,
            'location_id' => $this->location->id,
            'quantity' => '50.0000',
        ]);

        $response = $this->actingAs($this->user)->get('/api/v1/reports/inventory-movement/export?type=slow-moving&period=90');
        $response->assertStatus(200);
        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'));

        $content = $response->streamedContent();
        $lines = explode("\n", trim($content));
        $this->assertGreaterThanOrEqual(2, count($lines));
        $headers = str_getcsv(ltrim($lines[0], "\xEF\xBB\xBF"));
        $this->assertSame([
            'SKU', 'Barcode', 'Nama Produk', 'Kategori', 'Satuan',
            'Kode Lokasi', 'Nama Lokasi', 'Stok Saat Ini', 'Pergerakan Terakhir',
            'Hari Tidak Bergerak', 'Jumlah Mutasi',
        ], $headers);
        $this->assertStringContainsString('Slow Dormant Product', $content);
    }

    public function test_csv_export_fast_moving_streams_valid_content(): void
    {
        $response = $this->actingAs($this->user)->get('/api/v1/reports/inventory-movement/export?type=fast-moving&period=90');
        $response->assertStatus(200);
        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'));

        $content = $response->streamedContent();
        $lines = explode("\n", trim($content));
        $this->assertGreaterThanOrEqual(2, count($lines));
        $headers = str_getcsv(ltrim($lines[0], "\xEF\xBB\xBF"));
        $this->assertSame([
            'SKU', 'Barcode', 'Nama Produk', 'Kategori', 'Satuan',
            'Kode Lokasi', 'Nama Lokasi', 'Stok Saat Ini', 'Total Kuantitas Keluar',
            'Jumlah Transaksi Keluar', 'Hari Aktif Bergerak', 'Rata-rata Keluar Harian',
            'Velocity Score', 'Pengeluaran Terakhir',
        ], $headers);
        $this->assertStringContainsString('Integrity Product', $content);
    }
}
