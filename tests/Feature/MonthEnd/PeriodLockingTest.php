<?php

namespace Tests\Feature\MonthEnd;

use App\Features\Auth\Enums\PermissionCode;
use App\Features\Auth\Enums\RoleCode;
use App\Features\Auth\Models\Permission;
use App\Features\Auth\Models\Role;
use App\Features\Auth\Models\User;
use App\Features\Category\Models\Category;
use App\Features\Inventory\Enums\AdjustmentReason;
use App\Features\Inventory\Models\InventoryBalance;
use App\Features\Location\Enums\LocationType;
use App\Features\Location\Models\Location;
use App\Features\MonthEnd\Enums\PeriodStatus;
use App\Features\MonthEnd\Models\InventoryPeriod;
use App\Features\Product\Models\Product;
use App\Features\Store\Models\Store;
use App\Features\Supplier\Models\Supplier;
use App\Features\Unit\Models\Unit;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class PeriodLockingTest extends TestCase
{
    use DatabaseTransactions;

    protected User $admin;
    protected Location $warehouse;
    protected Product $product;
    protected Supplier $supplier;
    protected InventoryPeriod $closedPeriod;
    protected InventoryPeriod $openPeriod;

    protected function setUp(): void
    {
        parent::setUp();

        $uid = substr(uniqid(), -5);

        // Setup Admin User with all necessary permissions
        $this->admin = User::factory()->create([
            'username' => 'admin_'.$uid,
            'is_active' => true,
        ]);

        $adminRole = Role::firstOrCreate(['code' => RoleCode::ADMIN->value], ['name' => 'Administrator']);
        $this->admin->roles()->syncWithoutDetaching([$adminRole->id]);

        $perms = [
            PermissionCode::STOCK_RECEIPTS_VIEW->value,
            PermissionCode::STOCK_RECEIPTS_CREATE->value,
            PermissionCode::STOCK_ISSUES_VIEW->value,
            PermissionCode::STOCK_ISSUES_CREATE->value,
            PermissionCode::STOCK_ADJUSTMENTS_VIEW->value,
            PermissionCode::STOCK_ADJUSTMENTS_CREATE->value,
            PermissionCode::STORE_ALLOCATIONS_VIEW->value,
            PermissionCode::STORE_ALLOCATIONS_CREATE->value,
            PermissionCode::MONTH_END_VIEW->value,
            PermissionCode::MONTH_END_CLOSE->value,
            PermissionCode::MONTH_END_REOPEN->value,
        ];
        foreach ($perms as $permCode) {
            $perm = Permission::firstOrCreate(['code' => $permCode], ['name' => $permCode, 'group' => 'inventory']);
            $adminRole->permissions()->syncWithoutDetaching([$perm->id]);
        }

        $category = Category::firstOrCreate(['code' => 'CAT-TEST'], ['name' => 'Hardware', 'is_active' => true]);
        $unit = Unit::firstOrCreate(['code' => 'PCS'], ['name' => 'Pieces', 'symbol' => 'pcs', 'is_active' => true]);

        $this->product = Product::create([
            'sku' => 'SKU-LOCK-'.$uid,
            'name' => 'Produk Uji Period Lock',
            'category_id' => $category->id,
            'unit_id' => $unit->id,
            'unit_price' => 150000,
            'is_active' => true,
        ]);

        $this->warehouse = Location::create([
            'code' => 'WH-LOCK-'.$uid,
            'name' => 'Gudang Utama Uji',
            'type' => LocationType::MAIN_WAREHOUSE->value,
            'is_active' => true,
        ]);

        $this->supplier = Supplier::create([
            'code' => 'SUP-'.$uid,
            'name' => 'Supplier Test '.$uid,
            'is_active' => true,
        ]);

        // Beri stok awal
        InventoryBalance::create([
            'product_id' => $this->product->id,
            'location_id' => $this->warehouse->id,
            'condition' => 'GOOD',
            'quantity' => 100,
        ]);

        // Buat periode tertutup: 2026-07 (Juli 2026)
        $this->closedPeriod = InventoryPeriod::create([
            'period_key' => '2026-07',
            'year' => 2026,
            'month' => 7,
            'start_date' => '2026-07-01',
            'end_date' => '2026-07-31',
            'status' => PeriodStatus::CLOSED,
            'closed_at' => now(),
            'closed_by' => $this->admin->id,
            'notes' => 'Periode Juli 2026 ditutup',
        ]);

        // Buat periode terbuka: 2026-08 (Agustus 2026)
        $this->openPeriod = InventoryPeriod::create([
            'period_key' => '2026-08',
            'year' => 2026,
            'month' => 8,
            'start_date' => '2026-08-01',
            'end_date' => '2026-08-31',
            'status' => PeriodStatus::OPEN,
        ]);
    }

    public function test_it_rejects_stock_receipt_in_closed_period(): void
    {
        $payload = [
            'supplier_id' => $this->supplier->id,
            'date' => '2026-07-15', // Di dalam periode CLOSED
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'location_id' => $this->warehouse->id,
                    'quantity' => 10,
                ],
            ],
        ];

        $response = $this->actingAs($this->admin)->postJson('/api/v1/stock-receipts', $payload);

        $response->assertStatus(422)
            ->assertJsonPath('success', false);

        $this->assertStringContainsString('telah ditutup buku (CLOSED)', $response->json('message'));
    }

    public function test_it_allows_stock_receipt_in_open_period(): void
    {
        $payload = [
            'supplier_id' => $this->supplier->id,
            'date' => '2026-08-15', // Di dalam periode OPEN
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'location_id' => $this->warehouse->id,
                    'quantity' => 10,
                ],
            ],
        ];

        $response = $this->actingAs($this->admin)->postJson('/api/v1/stock-receipts', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('success', true);
    }

    public function test_it_rejects_stock_issue_in_closed_period(): void
    {
        $payload = [
            'purpose' => 'Pengujian pengeluaran',
            'date' => '2026-07-20', // CLOSED
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'location_id' => $this->warehouse->id,
                    'quantity' => 5,
                ],
            ],
        ];

        $response = $this->actingAs($this->admin)->postJson('/api/v1/stock-issues', $payload);

        $response->assertStatus(422)
            ->assertJsonPath('success', false);

        $this->assertStringContainsString('telah ditutup buku (CLOSED)', $response->json('message'));
    }

    public function test_it_rejects_stock_adjustment_in_closed_period(): void
    {
        $payload = [
            'location_id' => $this->warehouse->id,
            'adjustment_date' => '2026-07-25', // CLOSED
            'direction' => 'INCREASE',
            'reason_code' => AdjustmentReason::FOUND->value,
            'notes' => 'Koreksi stok barang',
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 2,
                    'condition' => 'GOOD',
                ],
            ],
        ];

        $response = $this->actingAs($this->admin)->postJson('/api/v1/stock-adjustments', $payload);

        $response->assertStatus(422)
            ->assertJsonPath('success', false);

        $this->assertStringContainsString('telah ditutup buku (CLOSED)', $response->json('message'));
    }

    public function test_store_allocation_ignores_client_backdated_date_and_uses_server_date(): void
    {
        $store = Store::create([
            'code' => 'STR-LOCK-'.uniqid(),
            'name' => 'Toko Uji',
            'is_active' => true,
        ]);

        // Klien mencoba backdate ke periode CLOSED (2026-07-10), tetapi nilai ini diabaikan.
        $payload = [
            'store_id' => $store->id,
            'technician_user_id' => $this->admin->id,
            'technician_location_id' => $this->warehouse->id,
            'allocated_at' => '2026-07-10', // CLOSED — sengaja diabaikan
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 1,
                ],
            ],
        ];

        $response = $this->actingAs($this->admin)->postJson('/api/v1/store-allocations', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.allocated_at', now()->toDateString());

        $this->assertDatabaseHas('store_allocations', [
            'store_id' => $store->id,
            'allocated_at' => now()->toDateString(),
        ]);
    }
}
