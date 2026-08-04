<?php

namespace Tests\Feature\Reporting;

use App\Features\Auth\Enums\RoleCode;
use App\Features\Auth\Models\Permission;
use App\Features\Auth\Models\Role;
use App\Features\Auth\Models\User;
use App\Features\Category\Models\Category;
use App\Features\Location\Models\Location;
use App\Features\Product\Models\Product;
use App\Features\Supplier\Models\Supplier;
use App\Features\Unit\Models\Unit;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ReportFilterOptionsTest extends \Tests\TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Permission::firstOrCreate(['code' => 'reports.stock_receipts.view'], ['name' => 'Reports Receipts View', 'group' => 'reports']);
        Permission::firstOrCreate(['code' => 'reports.stock_issues.view'], ['name' => 'Reports Issues View', 'group' => 'reports']);
        Permission::firstOrCreate(['code' => 'suppliers.view'], ['name' => 'Suppliers View', 'group' => 'suppliers']);
    }

    private function assignPermission(User $user, string $permissionCode): void
    {
        $perm = Permission::where('code', $permissionCode)->firstOrFail();
        $role = Role::firstOrCreate(['code' => RoleCode::WAREHOUSE_OFFICER], ['name' => 'Petugas Gudang']);
        $role->permissions()->syncWithoutDetaching([$perm->id]);
        $user->roles()->syncWithoutDetaching([$role->id]);
    }

    public function test_user_without_report_permission_is_rejected(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson('/api/v1/reports/filter-options/base');

        $response->assertStatus(403);
    }

    public function test_user_with_report_permission_can_access_base_options_scoped_by_allowed_locations(): void
    {
        $user = User::factory()->create();
        $this->assignPermission($user, 'reports.stock_issues.view');

        $loc1 = Location::factory()->create(['name' => 'Allowed Loc']);
        $loc2 = Location::factory()->create(['name' => 'Forbidden Loc']);
        $user->locations()->attach($loc1->id);

        Category::factory()->create(['is_active' => true]);
        Unit::factory()->create(['is_active' => true]);

        $response = $this->actingAs($user)->getJson('/api/v1/reports/filter-options/base');

        $response->assertStatus(200)
            ->assertJsonPath('data.locations.0.id', $loc1->id)
            ->assertJsonCount(1, 'data.locations');
    }

    public function test_product_options_search_and_limit(): void
    {
        $user = User::factory()->create();
        $this->assignPermission($user, 'reports.stock_issues.view');

        Product::factory()->create(['name' => 'Alpha Item', 'sku' => 'SKU-A']);
        Product::factory()->create(['name' => 'Beta Item', 'sku' => 'SKU-B']);

        $response = $this->actingAs($user)->getJson('/api/v1/reports/filter-options/products?search=Alpha');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Alpha Item');
    }

    public function test_supplier_options_requires_receipt_or_supplier_permission(): void
    {
        $user = User::factory()->create();
        $this->assignPermission($user, 'reports.stock_issues.view');

        $response = $this->actingAs($user)->getJson('/api/v1/reports/filter-options/suppliers');
        $response->assertStatus(403);

        $receiptUser = User::factory()->create();
        $this->assignPermission($receiptUser, 'reports.stock_receipts.view');

        Supplier::factory()->create(['name' => 'Supplier X', 'is_active' => true]);

        $response2 = $this->actingAs($receiptUser)->getJson('/api/v1/reports/filter-options/suppliers');
        $response2->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }
}
