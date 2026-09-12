<?php

namespace Tests\Feature\Product;

use App\Features\Auth\Enums\RoleCode;
use App\Features\Auth\Models\Role;
use App\Features\Auth\Models\User;
use App\Features\Category\Models\Category;
use App\Features\Inventory\Models\InventoryBalance;
use App\Features\Inventory\Models\StockMovement;
use App\Features\Product\Models\Product;
use App\Features\Unit\Models\Unit;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductBarcodeLookupTest extends TestCase
{
    use RefreshDatabase;

    private Category $category;

    private Unit $unit;

    private User $authorizedUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);

        $adminRole = Role::where('code', RoleCode::ADMIN->value)->first();
        $this->authorizedUser = User::factory()->create();
        $this->authorizedUser->roles()->attach($adminRole);

        $this->category = Category::create(['code' => 'CAT-BAR', 'name' => 'Cat Barcode', 'is_active' => true]);
        $this->unit = Unit::create(['code' => 'UNT-BAR', 'name' => 'Unit Barcode', 'symbol' => 'ub', 'is_active' => true]);
    }

    public function test_bar_01_guest_cannot_access_barcode_lookup(): void
    {
        $response = $this->getJson('/api/v1/products/barcode-lookup?barcode=123456');
        $response->assertUnauthorized();
    }

    public function test_bar_02_user_without_products_view_permission_is_forbidden(): void
    {
        $userWithoutPerm = User::factory()->create(); // No roles attached

        $response = $this->actingAs($userWithoutPerm)->getJson('/api/v1/products/barcode-lookup?barcode=123456');
        $response->assertForbidden();
    }

    public function test_bar_03_valid_active_barcode_returns_200_with_product_data(): void
    {
        $product = Product::create([
            'sku' => 'PRD-BAR-01',
            'barcode' => '000123456789',
            'name' => 'Produk Barcode Utama',
            'category_id' => $this->category->id,
            'unit_id' => $this->unit->id,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->authorizedUser)->getJson('/api/v1/products/barcode-lookup?barcode=000123456789');
        $response->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $product->id,
                    'sku' => 'PRD-BAR-01',
                    'barcode' => '000123456789',
                    'name' => 'Produk Barcode Utama',
                    'is_active' => true,
                ],
            ]);
    }

    public function test_bar_04_and_05_leading_zeros_and_surrounding_whitespace_handled_correctly(): void
    {
        $product = Product::create([
            'sku' => 'PRD-BAR-02',
            'barcode' => '0000987654321',
            'name' => 'Produk Zero Barcode',
            'category_id' => $this->category->id,
            'unit_id' => $this->unit->id,
            'is_active' => true,
        ]);

        // Request with surrounding whitespace
        $response = $this->actingAs($this->authorizedUser)->getJson('/api/v1/products/barcode-lookup?barcode=%200000987654321%20');
        $response->assertOk()
            ->assertJsonPath('data.barcode', '0000987654321');
    }

    public function test_bar_06_unknown_barcode_returns_404(): void
    {
        $response = $this->actingAs($this->authorizedUser)->getJson('/api/v1/products/barcode-lookup?barcode=UNKNOWN_BARCODE_999');
        $response->assertNotFound()
            ->assertJson([
                'success' => false,
                'code' => 'BARCODE_NOT_FOUND',
            ]);
    }

    public function test_bar_07_inactive_product_returns_409(): void
    {
        Product::create([
            'sku' => 'PRD-BAR-INACTIVE',
            'barcode' => 'INACTIVE_BARCODE_123',
            'name' => 'Produk Nonaktif',
            'category_id' => $this->category->id,
            'unit_id' => $this->unit->id,
            'is_active' => false,
        ]);

        $response = $this->actingAs($this->authorizedUser)->getJson('/api/v1/products/barcode-lookup?barcode=INACTIVE_BARCODE_123');
        $response->assertStatus(409)
            ->assertJson([
                'success' => false,
                'code' => 'PRODUCT_INACTIVE',
            ]);
    }

    public function test_bar_08_empty_barcode_returns_422(): void
    {
        $response = $this->actingAs($this->authorizedUser)->getJson('/api/v1/products/barcode-lookup?barcode=');
        $response->assertUnprocessable();
    }

    public function test_bar_09_and_10_barcode_length_validation_100_and_101_chars(): void
    {
        $barcode100 = str_repeat('A', 100);
        $barcode101 = str_repeat('A', 101);

        Product::create([
            'sku' => 'PRD-BAR-100',
            'barcode' => $barcode100,
            'name' => 'Produk 100 Karakter Barcode',
            'category_id' => $this->category->id,
            'unit_id' => $this->unit->id,
            'is_active' => true,
        ]);

        // 100 chars -> valid query
        $res100 = $this->actingAs($this->authorizedUser)->getJson('/api/v1/products/barcode-lookup?barcode='.$barcode100);
        $res100->assertOk();

        // 100 chars with surrounding whitespace (102 chars raw) -> trimmed before validation -> valid 200
        $resPadded100 = $this->actingAs($this->authorizedUser)->getJson('/api/v1/products/barcode-lookup?barcode=%20'.$barcode100.'%20');
        $resPadded100->assertOk()->assertJsonPath('data.barcode', $barcode100);

        // 101 chars trimmed -> 422 Unprocessable Entity
        $res101 = $this->actingAs($this->authorizedUser)->getJson('/api/v1/products/barcode-lookup?barcode='.$barcode101);
        $res101->assertUnprocessable();
    }

    public function test_bar_11_exact_lookup_does_not_match_substrings(): void
    {
        Product::create([
            'sku' => 'PRD-EXACT-01',
            'barcode' => '123456789',
            'name' => 'Produk Full Barcode',
            'category_id' => $this->category->id,
            'unit_id' => $this->unit->id,
            'is_active' => true,
        ]);

        // Searching for substring "12345" must return 404 (not match "123456789")
        $response = $this->actingAs($this->authorizedUser)->getJson('/api/v1/products/barcode-lookup?barcode=12345');
        $response->assertNotFound();
    }

    public function test_bar_12_lookup_is_read_only_and_does_not_mutate_inventory(): void
    {
        Product::create([
            'sku' => 'PRD-READONLY',
            'barcode' => 'READONLY_123',
            'name' => 'Produk Read Only',
            'category_id' => $this->category->id,
            'unit_id' => $this->unit->id,
            'is_active' => true,
        ]);

        $movementCountBefore = StockMovement::count();
        $balanceCountBefore = InventoryBalance::count();

        for ($i = 0; $i < 5; $i++) {
            $this->actingAs($this->authorizedUser)->getJson('/api/v1/products/barcode-lookup?barcode=READONLY_123')->assertOk();
        }

        $this->assertSame($movementCountBefore, StockMovement::count());
        $this->assertSame($balanceCountBefore, InventoryBalance::count());
    }
}
