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
use Tests\TestCase;

class ReportFilterOptionsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Permission::firstOrCreate(['code' => 'reports.stock_receipts.view'], ['name' => 'Reports Receipts View', 'group' => 'reports']);
        Permission::firstOrCreate(['code' => 'reports.stock_issues.view'], ['name' => 'Reports Issues View', 'group' => 'reports']);
        Permission::firstOrCreate(['code' => 'reports.view'], ['name' => 'Reports Main View', 'group' => 'reports']);
        Permission::firstOrCreate(['code' => 'suppliers.view'], ['name' => 'Suppliers View', 'group' => 'suppliers']);
    }

    private function assignPermission(User $user, string $permissionCode): void
    {
        $perm = Permission::where('code', $permissionCode)->firstOrFail();
        $role = Role::firstOrCreate(['code' => RoleCode::WAREHOUSE_OFFICER], ['name' => 'Petugas Gudang']);
        $role->permissions()->syncWithoutDetaching([$perm->id]);
        $user->roles()->syncWithoutDetaching([$role->id]);
    }

    public function test_unauthenticated_user_receives_401(): void
    {
        $this->getJson('/api/v1/reports/filter-options/base')->assertStatus(401);
        $this->getJson('/api/v1/reports/filter-options/products')->assertStatus(401);
        $this->getJson('/api/v1/reports/filter-options/suppliers')->assertStatus(401);
    }

    public function test_user_without_report_permission_receives_403(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->getJson('/api/v1/reports/filter-options/base')->assertStatus(403);
        $this->actingAs($user)->getJson('/api/v1/reports/filter-options/products')->assertStatus(403);
    }

    public function test_each_report_permission_can_access_base_and_product_options(): void
    {
        $user1 = User::factory()->create();
        $this->assignPermission($user1, 'reports.stock_receipts.view');
        $this->actingAs($user1)->getJson('/api/v1/reports/filter-options/base')->assertStatus(200);

        $user2 = User::factory()->create();
        $this->assignPermission($user2, 'reports.stock_issues.view');
        $this->actingAs($user2)->getJson('/api/v1/reports/filter-options/products')->assertStatus(200);
    }

    public function test_suppliers_option_authorization_rules(): void
    {
        $issueOnlyUser = User::factory()->create();
        $this->assignPermission($issueOnlyUser, 'reports.stock_issues.view');
        $this->actingAs($issueOnlyUser)->getJson('/api/v1/reports/filter-options/suppliers')->assertStatus(403);

        $receiptUser = User::factory()->create();
        $this->assignPermission($receiptUser, 'reports.stock_receipts.view');
        $this->actingAs($receiptUser)->getJson('/api/v1/reports/filter-options/suppliers')->assertStatus(200);

        $mainReportUser = User::factory()->create();
        $this->assignPermission($mainReportUser, 'reports.view');
        $this->actingAs($mainReportUser)->getJson('/api/v1/reports/filter-options/suppliers')->assertStatus(200);

        $supplierMasterUser = User::factory()->create();
        $this->assignPermission($supplierMasterUser, 'suppliers.view');
        $this->actingAs($supplierMasterUser)->getJson('/api/v1/reports/filter-options/suppliers')->assertStatus(200);
    }

    public function test_location_scoping_behavior(): void
    {
        $user = User::factory()->create();
        $this->assignPermission($user, 'reports.stock_receipts.view');

        // Test user with no locations attached -> returns empty locations
        $responseNoLoc = $this->actingAs($user)->getJson('/api/v1/reports/filter-options/base');
        $responseNoLoc->assertStatus(200)->assertJsonPath('data.locations', []);

        // Test user with 2 locations attached -> only 2 locations returned, forbidden excluded
        $loc1 = Location::factory()->create(['name' => 'Loc 1']);
        $loc2 = Location::factory()->create(['name' => 'Loc 2']);
        $loc3 = Location::factory()->create(['name' => 'Forbidden Loc 3']);
        $user->locations()->attach([$loc1->id, $loc2->id]);

        Category::factory()->create(['is_active' => true]);
        Unit::factory()->create(['is_active' => true]);

        $responseWithLoc = $this->actingAs($user)->getJson('/api/v1/reports/filter-options/base');
        $responseWithLoc->assertStatus(200)
            ->assertJsonCount(2, 'data.locations')
            ->assertJsonFragment(['id' => $loc1->id])
            ->assertJsonFragment(['id' => $loc2->id])
            ->assertJsonMissing(['id' => $loc3->id]);
    }

    public function test_product_search_by_name_and_sku(): void
    {
        $user = User::factory()->create();
        $this->assignPermission($user, 'reports.stock_receipts.view');

        Product::factory()->create(['name' => 'Special Keyboard', 'sku' => 'KB-99']);
        Product::factory()->create(['name' => 'Mouse Gaming', 'sku' => 'MS-88']);

        // Search by name
        $resName = $this->actingAs($user)->getJson('/api/v1/reports/filter-options/products?search=Keyboard');
        $resName->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Special Keyboard');

        // Search by SKU
        $resSku = $this->actingAs($user)->getJson('/api/v1/reports/filter-options/products?search=MS-88');
        $resSku->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.sku', 'MS-88');
    }

    public function test_supplier_search_by_name_and_code(): void
    {
        $user = User::factory()->create();
        $this->assignPermission($user, 'reports.stock_receipts.view');

        Supplier::factory()->create(['name' => 'PT Logistics Jaya', 'code' => 'SUP-LOG-01', 'is_active' => true]);
        Supplier::factory()->create(['name' => 'CV Sumber Makmur', 'code' => 'SUP-SMB-02', 'is_active' => true]);

        // Search by name
        $resName = $this->actingAs($user)->getJson('/api/v1/reports/filter-options/suppliers?search=Logistics');
        $resName->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'PT Logistics Jaya');

        // Search by code
        $resCode = $this->actingAs($user)->getJson('/api/v1/reports/filter-options/suppliers?search=SUP-SMB-02');
        $resCode->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.code', 'SUP-SMB-02');
    }

    public function test_per_page_and_search_validation_rules(): void
    {
        $user = User::factory()->create();
        $this->assignPermission($user, 'reports.stock_receipts.view');

        // 1. Default per_page is 20
        Product::factory()->count(25)->create();
        $resDefault = $this->actingAs($user)->getJson('/api/v1/reports/filter-options/products');
        $resDefault->assertStatus(200)->assertJsonCount(20, 'data');

        // 2. per_page=1 accepted
        $res1 = $this->actingAs($user)->getJson('/api/v1/reports/filter-options/products?per_page=1');
        $res1->assertStatus(200)->assertJsonCount(1, 'data');

        // 3. per_page=20 accepted
        $res20 = $this->actingAs($user)->getJson('/api/v1/reports/filter-options/products?per_page=20');
        $res20->assertStatus(200)->assertJsonCount(20, 'data');

        // 4. per_page=21 rejected (422)
        $this->actingAs($user)->getJson('/api/v1/reports/filter-options/products?per_page=21')->assertStatus(422);

        // 5. per_page=0 rejected (422)
        $this->actingAs($user)->getJson('/api/v1/reports/filter-options/products?per_page=0')->assertStatus(422);

        // 6. per_page=-1 rejected (422)
        $this->actingAs($user)->getJson('/api/v1/reports/filter-options/products?per_page=-1')->assertStatus(422);

        // 7. non-integer per_page rejected (422)
        $this->actingAs($user)->getJson('/api/v1/reports/filter-options/products?per_page=abc')->assertStatus(422);

        // 8. oversized search (> 100 chars) rejected (422)
        $longSearch = str_repeat('a', 101);
        $this->actingAs($user)->getJson('/api/v1/reports/filter-options/products?search='.$longSearch)->assertStatus(422);
    }
}
