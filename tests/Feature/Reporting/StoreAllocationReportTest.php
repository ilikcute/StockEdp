<?php

namespace Tests\Feature\Reporting;

use App\Features\Auth\Enums\RoleCode;
use App\Features\Auth\Models\Role;
use App\Features\Auth\Models\User;
use App\Features\Category\Models\Category;
use App\Features\Location\Models\Location;
use App\Features\Product\Models\Product;
use App\Features\Store\Models\Store;
use App\Features\StoreAllocation\Models\StoreAllocation;
use App\Features\StoreAllocation\Models\StoreAllocationItem;
use App\Features\Unit\Models\Unit;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class StoreAllocationReportTest extends TestCase
{
    use DatabaseTransactions;

    private User $admin;

    private User $technician;

    private User $unauthorizedUser;

    private Store $store;

    private Location $fieldLocation;

    private Product $productNew;

    private Product $productOld;

    private StoreAllocation $allocation;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);

        $this->admin = User::factory()->create();
        $adminRole = Role::where('code', RoleCode::ADMIN->value)->first();
        $this->admin->roles()->attach($adminRole->id);

        $this->technician = User::factory()->create();
        $this->unauthorizedUser = User::factory()->create();

        $category = Category::create(['name' => 'IT Hardware', 'code' => 'ITHW-'.uniqid()]);
        $unit = Unit::create(['name' => 'Unit', 'code' => 'UNT-'.uniqid(), 'symbol' => 'UNT']);

        $this->store = Store::create([
            'code' => 'ST-'.uniqid(),
            'name' => 'Toko Cabang Surabaya',
            'address' => 'Surabaya',
            'is_active' => true,
        ]);

        $this->fieldLocation = Location::create([
            'code' => 'LOC-TECH-'.uniqid(),
            'name' => 'Van Teknisi Joko',
            'type' => 'FIELD_PERSONNEL',
            'user_id' => $this->technician->id,
            'is_active' => true,
        ]);
        $this->admin->locations()->attach($this->fieldLocation->id);

        $this->productNew = Product::create([
            'name' => 'Scanner Barcode 2D',
            'sku' => 'SKU-SCN-'.uniqid(),
            'category_id' => $category->id,
            'unit_id' => $unit->id,
            'unit_price' => 1500000,
            'is_active' => true,
        ]);

        $this->productOld = Product::create([
            'name' => 'Scanner Barcode 1D',
            'sku' => 'SKU-OLD-'.uniqid(),
            'category_id' => $category->id,
            'unit_id' => $unit->id,
            'unit_price' => 800000,
            'is_active' => true,
        ]);

        $this->allocation = StoreAllocation::create([
            'allocation_number' => 'ALC-20260912-9999',
            'technician_user_id' => $this->technician->id,
            'technician_location_id' => $this->fieldLocation->id,
            'store_id' => $this->store->id,
            'allocated_at' => '2026-09-12',
            'notes' => 'Penggantian scanner kasir rusak',
            'created_by' => $this->admin->id,
        ]);

        StoreAllocationItem::create([
            'store_allocation_id' => $this->allocation->id,
            'product_id' => $this->productNew->id,
            'quantity' => 1,
            'serial_number' => 'SN-NEW-12345',
            'pulled_product_id' => $this->productOld->id,
            'pulled_quantity' => 1,
            'pulled_serial_number' => 'SN-OLD-67890',
            'defective_reason' => 'Laser optik mati total',
        ]);
    }

    public function test_authorized_user_can_view_store_allocation_report(): void
    {
        $response = $this->actingAs($this->admin)->getJson('/api/v1/reports/store-allocations');

        $response->assertOk()
            ->assertJsonStructure([
                'meta' => ['summary' => ['total_allocations', 'total_installed', 'total_pulled', 'total_value']],
                'data' => [
                    '*' => [
                        'id',
                        'allocation_number',
                        'allocated_at',
                        'store_name',
                        'technician_name',
                        'product_name',
                        'unit_price',
                        'quantity',
                        'total_value',
                        'serial_number',
                        'pulled_product_name',
                        'pulled_quantity',
                        'defective_reason',
                    ],
                ],
                'pagination',
            ]);

        $data = $response->json('data');
        $this->assertNotEmpty($data);
        $found = collect($data)->firstWhere('allocation_number', 'ALC-20260912-9999');
        $this->assertNotNull($found);
        $this->assertSame('Laser optik mati total', $found['defective_reason']);
        $this->assertEquals(1500000, $found['unit_price']);
        $this->assertEquals(1500000, $found['total_value']);
        $this->assertEquals(1500000, $response->json('meta.summary.total_value'));
    }

    public function test_can_filter_store_allocation_report_by_store_and_search(): void
    {
        $response = $this->actingAs($this->admin)->getJson('/api/v1/reports/store-allocations?store_id='.$this->store->id);
        $response->assertOk();
        $this->assertCount(1, $response->json('data'));

        $searchRes = $this->actingAs($this->admin)->getJson('/api/v1/reports/store-allocations?search=mati+total');
        $searchRes->assertOk();
        $this->assertCount(1, $searchRes->json('data'));

        $emptyRes = $this->actingAs($this->admin)->getJson('/api/v1/reports/store-allocations?search=nonexistentterm');
        $emptyRes->assertOk();
        $this->assertCount(0, $emptyRes->json('data'));
    }

    public function test_can_export_store_allocation_report_to_csv(): void
    {
        $response = $this->actingAs($this->admin)->get('/api/v1/reports/store-allocations/export');

        $response->assertOk();
        $this->assertStringContainsString('text/csv', $response->headers->get('content-type'));
        $content = $response->streamedContent();
        $this->assertStringContainsString('Nomor Alokasi', $content);
        $this->assertStringContainsString('Harga Satuan', $content);
        $this->assertStringContainsString('Total Nilai (Rp)', $content);
        $this->assertStringContainsString('ALC-20260912-9999', $content);
        $this->assertStringContainsString('Laser optik mati total', $content);
        $this->assertStringContainsString('1500000', $content);
    }

    public function test_unauthorized_user_is_forbidden(): void
    {
        $response = $this->actingAs($this->unauthorizedUser)->getJson('/api/v1/reports/store-allocations');
        $response->assertForbidden();
    }
}
