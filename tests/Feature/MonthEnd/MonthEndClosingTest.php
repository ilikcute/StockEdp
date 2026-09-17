<?php

namespace Tests\Feature\MonthEnd;

use App\Features\Auth\Enums\PermissionCode;
use App\Features\Auth\Enums\RoleCode;
use App\Features\Auth\Models\Permission;
use App\Features\Auth\Models\Role;
use App\Features\Auth\Models\User;
use App\Features\Category\Models\Category;
use App\Features\Inventory\Models\InventoryBalance;
use App\Features\Location\Enums\LocationType;
use App\Features\Location\Models\Location;
use App\Features\MonthEnd\Enums\PeriodStatus;
use App\Features\MonthEnd\Models\InventoryPeriod;
use App\Features\MonthEnd\Models\InventoryPeriodSnapshot;
use App\Features\Product\Models\Product;
use App\Features\Unit\Models\Unit;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class MonthEndClosingTest extends TestCase
{
    use DatabaseTransactions;

    protected User $admin;
    protected User $regularUser;
    protected Location $warehouse;
    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $uid = substr(uniqid(), -5);

        // Setup Admin User with month_end permissions
        $this->admin = User::factory()->create([
            'username' => 'admin_close_'.$uid,
            'is_active' => true,
        ]);

        $adminRole = Role::firstOrCreate(['code' => RoleCode::ADMIN->value], ['name' => 'Administrator']);
        $this->admin->roles()->syncWithoutDetaching([$adminRole->id]);

        $perms = [
            PermissionCode::MONTH_END_VIEW->value,
            PermissionCode::MONTH_END_CLOSE->value,
            PermissionCode::MONTH_END_REOPEN->value,
        ];
        foreach ($perms as $permCode) {
            $perm = Permission::firstOrCreate(['code' => $permCode], ['name' => $permCode, 'group' => 'month_end']);
            $adminRole->permissions()->syncWithoutDetaching([$perm->id]);
        }

        // Regular user without month_end permissions
        $this->regularUser = User::factory()->create([
            'username' => 'regular_'.$uid,
            'is_active' => true,
        ]);

        $category = Category::firstOrCreate(['code' => 'CAT-CLOSE'], ['name' => 'Hardware', 'is_active' => true]);
        $unit = Unit::firstOrCreate(['code' => 'PCS'], ['name' => 'Pieces', 'symbol' => 'pcs', 'is_active' => true]);

        $this->product = Product::create([
            'sku' => 'SKU-CLOSE-'.$uid,
            'name' => 'Produk Tutup Buku',
            'category_id' => $category->id,
            'unit_id' => $unit->id,
            'unit_price' => 200000,
            'is_active' => true,
        ]);

        $this->warehouse = Location::create([
            'code' => 'WH-CLOSE-'.$uid,
            'name' => 'Gudang Tutup Buku',
            'type' => LocationType::MAIN_WAREHOUSE->value,
            'is_active' => true,
        ]);

        // Beri stok saldo berjalan
        InventoryBalance::create([
            'product_id' => $this->product->id,
            'location_id' => $this->warehouse->id,
            'condition' => 'GOOD',
            'quantity' => 25,
        ]);
    }

    public function test_regular_user_cannot_close_period(): void
    {
        $payload = [
            'year' => 2026,
            'month' => 5,
            'notes' => 'Tutup buku oleh user biasa',
        ];

        $response = $this->actingAs($this->regularUser)->postJson('/api/v1/month-end/periods/close', $payload);

        $response->assertStatus(403);
    }

    public function test_admin_can_close_period_and_freeze_snapshots(): void
    {
        $payload = [
            'year' => 2026,
            'month' => 5,
            'notes' => 'Tutup buku resmi Mei 2026',
            'force' => true,
        ];

        $response = $this->actingAs($this->admin)->postJson('/api/v1/month-end/periods/close', $payload);

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        // Verifikasi database record InventoryPeriod
        $period = InventoryPeriod::where('period_key', '2026-05')->first();
        $this->assertNotNull($period);
        $this->assertEquals(PeriodStatus::CLOSED, $period->status);
        $this->assertEquals($this->admin->id, $period->closed_by);
        $this->assertNotNull($period->closed_at);

        // Verifikasi snapshot persediaan terbuat
        $snapshot = InventoryPeriodSnapshot::where('inventory_period_id', $period->id)
            ->where('product_id', $this->product->id)
            ->where('location_id', $this->warehouse->id)
            ->where('condition', 'GOOD')
            ->first();

        $this->assertNotNull($snapshot);
        $this->assertEquals(25, (float) $snapshot->closing_balance);
        $this->assertEquals(200000, (float) $snapshot->unit_price);
        $this->assertEquals(5000000, (float) $snapshot->total_value); // 25 * 200,000 = 5,000,000
    }

    public function test_it_lists_closed_periods_and_snapshots(): void
    {
        // Tutup periode terlebih dahulu
        $this->actingAs($this->admin)->postJson('/api/v1/month-end/periods/close', [
            'year' => 2026,
            'month' => 6,
            'force' => true,
        ]);

        $period = InventoryPeriod::where('period_key', '2026-06')->first();

        // 1. List periods
        $response = $this->actingAs($this->admin)->getJson('/api/v1/month-end/periods');
        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        // 2. View Snapshots for this period
        $snapshotRes = $this->actingAs($this->admin)->getJson("/api/v1/month-end/periods/{$period->id}/snapshots");
        $snapshotRes->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.period.period_key', '2026-06');
    }

    public function test_it_returns_approaching_period_closing_alert(): void
    {
        $today = \Carbon\Carbon::today();

        InventoryPeriod::create([
            'period_key' => $today->format('Y-m'),
            'year' => $today->year,
            'month' => $today->month,
            'start_date' => $today->copy()->startOfMonth()->toDateString(),
            'end_date' => $today->copy()->addDays(2)->toDateString(),
            'status' => PeriodStatus::OPEN,
        ]);

        $response = $this->actingAs($this->admin)->getJson('/api/v1/month-end/approaching-alert');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.days_remaining', 2);
    }
}
