<?php

namespace Tests\Feature\Product;

use App\Features\Auth\Enums\PermissionCode;
use App\Features\Auth\Enums\RoleCode;
use App\Features\Auth\Models\Permission;
use App\Features\Auth\Models\Role;
use App\Features\Auth\Models\User;
use App\Features\Category\Models\Category;
use App\Features\Product\Models\Product;
use App\Features\Unit\Models\Unit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $viewerUser;

    protected User $regularUser;

    protected Category $category;

    protected Unit $unit;

    protected function setUp(): void
    {
        parent::setUp();

        $adminRole = Role::create([
            'code' => RoleCode::ADMIN->value,
            'name' => 'Administrator',
        ]);

        $viewPerm = Permission::create([
            'code' => PermissionCode::PRODUCTS_VIEW->value,
            'name' => 'Melihat Produk',
            'group' => 'products',
        ]);
        $createPerm = Permission::create([
            'code' => PermissionCode::PRODUCTS_CREATE->value,
            'name' => 'Membuat Produk',
            'group' => 'products',
        ]);
        $updatePerm = Permission::create([
            'code' => PermissionCode::PRODUCTS_UPDATE->value,
            'name' => 'Mengubah Produk',
            'group' => 'products',
        ]);
        $statusPerm = Permission::create([
            'code' => PermissionCode::PRODUCTS_CHANGE_STATUS->value,
            'name' => 'Mengubah Status Produk',
            'group' => 'products',
        ]);

        $adminRole->permissions()->attach([$viewPerm->id, $createPerm->id, $updatePerm->id, $statusPerm->id]);

        $this->admin = User::factory()->create(['is_active' => true]);
        $this->admin->roles()->attach($adminRole->id);

        $viewerRole = Role::create([
            'code' => RoleCode::WAREHOUSE_OFFICER->value,
            'name' => 'Petugas Gudang',
        ]);
        $viewerRole->permissions()->attach($viewPerm->id);
        $this->viewerUser = User::factory()->create(['is_active' => true]);
        $this->viewerUser->roles()->attach($viewerRole->id);

        $this->regularUser = User::factory()->create(['is_active' => true]);

        $this->category = Category::create([
            'code' => 'CAT-01',
            'name' => 'Elektronik',
        ]);

        $this->unit = Unit::create([
            'code' => 'PCS',
            'name' => 'Pieces',
            'symbol' => 'pcs',
        ]);
    }

    public function test_unauthenticated_user_cannot_access_products(): void
    {
        $this->getJson('/api/v1/products')->assertStatus(401);
    }

    public function test_user_without_permission_cannot_list_products(): void
    {
        $this->actingAs($this->regularUser)
            ->getJson('/api/v1/products')
            ->assertStatus(403);
    }

    public function test_viewer_can_list_products(): void
    {
        Product::factory()->create([
            'category_id' => $this->category->id,
            'unit_id' => $this->unit->id,
        ]);

        $response = $this->actingAs($this->viewerUser)
            ->getJson('/api/v1/products');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data',
                'meta' => ['current_page', 'last_page', 'per_page', 'total'],
            ]);
    }

    public function test_products_include_category_and_unit_names(): void
    {
        Product::factory()->create([
            'category_id' => $this->category->id,
            'unit_id' => $this->unit->id,
        ]);

        $response = $this->actingAs($this->viewerUser)
            ->getJson('/api/v1/products');

        $response->assertStatus(200);
        $data = $response->json('data.0');
        $this->assertEquals('Elektronik', $data['category_name']);
        $this->assertEquals('Pieces', $data['unit_name']);
    }

    public function test_user_without_create_permission_cannot_create_product(): void
    {
        $this->actingAs($this->viewerUser)
            ->postJson('/api/v1/products', [
                'sku' => 'SKU-001',
                'name' => 'Laptop X',
                'category_id' => $this->category->id,
                'unit_id' => $this->unit->id,
            ])
            ->assertStatus(403);
    }

    public function test_admin_can_create_product(): void
    {
        $response = $this->actingAs($this->admin)
            ->postJson('/api/v1/products', [
                'sku' => 'sku-001',
                'name' => 'Laptop X',
                'category_id' => $this->category->id,
                'unit_id' => $this->unit->id,
                'minimum_stock' => 5,
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.sku', 'SKU-001')
            ->assertJsonPath('data.name', 'Laptop X')
            ->assertJsonPath('data.is_active', true);

        $this->assertDatabaseHas('products', [
            'sku' => 'SKU-001',
            'name' => 'Laptop X',
            'created_by' => $this->admin->id,
        ]);
    }

    public function test_sku_is_uppercased_automatically(): void
    {
        $this->actingAs($this->admin)
            ->postJson('/api/v1/products', [
                'sku' => 'sku-auto',
                'name' => 'Test',
                'category_id' => $this->category->id,
                'unit_id' => $this->unit->id,
            ]);

        $this->assertDatabaseHas('products', ['sku' => 'SKU-AUTO']);
    }

    public function test_product_sku_must_be_unique(): void
    {
        Product::factory()->create([
            'sku' => 'SKU-001',
            'category_id' => $this->category->id,
            'unit_id' => $this->unit->id,
        ]);

        $this->actingAs($this->admin)
            ->postJson('/api/v1/products', [
                'sku' => 'SKU-001',
                'name' => 'Produk Baru',
                'category_id' => $this->category->id,
                'unit_id' => $this->unit->id,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['sku']);
    }

    public function test_barcode_must_be_unique_if_provided(): void
    {
        Product::factory()->create([
            'barcode' => '1234567890',
            'category_id' => $this->category->id,
            'unit_id' => $this->unit->id,
        ]);

        $this->actingAs($this->admin)
            ->postJson('/api/v1/products', [
                'sku' => 'SKU-NEW',
                'name' => 'Produk',
                'barcode' => '1234567890',
                'category_id' => $this->category->id,
                'unit_id' => $this->unit->id,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['barcode']);
    }

    public function test_empty_barcode_is_normalized_to_null(): void
    {
        $this->actingAs($this->admin)
            ->postJson('/api/v1/products', [
                'sku' => 'SKU-NULL',
                'name' => 'Product Null Barcode',
                'barcode' => '',
                'category_id' => $this->category->id,
                'unit_id' => $this->unit->id,
            ])
            ->assertStatus(201);

        $this->assertDatabaseHas('products', [
            'sku' => 'SKU-NULL',
            'barcode' => null,
        ]);
    }

    public function test_product_requires_valid_category_and_unit(): void
    {
        $this->actingAs($this->admin)
            ->postJson('/api/v1/products', [
                'sku' => 'SKU-002',
                'name' => 'Produk Test',
                'category_id' => 9999,
                'unit_id' => 9999,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['category_id', 'unit_id']);
    }

    public function test_minimum_stock_cannot_be_negative(): void
    {
        $this->actingAs($this->admin)
            ->postJson('/api/v1/products', [
                'sku' => 'SKU-NEG',
                'name' => 'Negative Test',
                'category_id' => $this->category->id,
                'unit_id' => $this->unit->id,
                'minimum_stock' => -5,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['minimum_stock']);
    }

    public function test_minimum_stock_accepts_decimal(): void
    {
        $this->actingAs($this->admin)
            ->postJson('/api/v1/products', [
                'sku' => 'SKU-DEC',
                'name' => 'Decimal Test',
                'category_id' => $this->category->id,
                'unit_id' => $this->unit->id,
                'minimum_stock' => 2.5,
            ])
            ->assertStatus(201);

        $this->assertDatabaseHas('products', ['sku' => 'SKU-DEC', 'minimum_stock' => 2.50]);
    }

    public function test_admin_can_update_product(): void
    {
        $product = Product::factory()->create([
            'category_id' => $this->category->id,
            'unit_id' => $this->unit->id,
        ]);

        $response = $this->actingAs($this->admin)
            ->putJson("/api/v1/products/{$product->id}", [
                'sku' => $product->sku,
                'name' => 'Laptop Baru',
                'category_id' => $this->category->id,
                'unit_id' => $this->unit->id,
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.name', 'Laptop Baru');

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => 'Laptop Baru',
            'updated_by' => $this->admin->id,
        ]);
    }

    public function test_admin_can_change_product_status(): void
    {
        $product = Product::factory()->create([
            'is_active' => true,
            'category_id' => $this->category->id,
            'unit_id' => $this->unit->id,
        ]);

        $this->actingAs($this->admin)
            ->patchJson("/api/v1/products/{$product->id}/status", ['is_active' => false])
            ->assertStatus(200)
            ->assertJsonPath('data.is_active', false);

        $this->assertDatabaseHas('products', ['id' => $product->id, 'is_active' => false]);
    }

    public function test_product_is_not_permanently_deleted(): void
    {
        $product = Product::factory()->create([
            'category_id' => $this->category->id,
            'unit_id' => $this->unit->id,
        ]);

        $this->actingAs($this->admin)
            ->deleteJson("/api/v1/products/{$product->id}")
            ->assertStatus(405);
    }

    public function test_search_products_by_sku_name_barcode(): void
    {
        Product::factory()->create([
            'sku' => 'ABC-001',
            'name' => 'Laptop ABC',
            'barcode' => '99988877',
            'category_id' => $this->category->id,
            'unit_id' => $this->unit->id,
        ]);
        Product::factory()->create([
            'sku' => 'XYZ-001',
            'name' => 'Mouse XYZ',
            'category_id' => $this->category->id,
            'unit_id' => $this->unit->id,
        ]);

        $response = $this->actingAs($this->viewerUser)
            ->getJson('/api/v1/products?search=ABC');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
    }

    public function test_filter_products_by_category(): void
    {
        $cat2 = Category::create(['code' => 'CAT-02', 'name' => 'Furniture']);

        Product::factory()->create([
            'category_id' => $this->category->id,
            'unit_id' => $this->unit->id,
        ]);
        Product::factory()->create([
            'category_id' => $cat2->id,
            'unit_id' => $this->unit->id,
        ]);

        $response = $this->actingAs($this->viewerUser)
            ->getJson("/api/v1/products?category_id={$cat2->id}");

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
    }

    public function test_filter_products_by_status(): void
    {
        Product::factory()->create([
            'is_active' => true,
            'category_id' => $this->category->id,
            'unit_id' => $this->unit->id,
        ]);
        Product::factory()->inactive()->create([
            'category_id' => $this->category->id,
            'unit_id' => $this->unit->id,
        ]);

        $response = $this->actingAs($this->viewerUser)
            ->getJson('/api/v1/products?is_active=false');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
    }

    public function test_show_product_detail(): void
    {
        $product = Product::factory()->create([
            'category_id' => $this->category->id,
            'unit_id' => $this->unit->id,
        ]);

        $response = $this->actingAs($this->viewerUser)
            ->getJson("/api/v1/products/{$product->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $product->id)
            ->assertJsonPath('data.category_name', 'Elektronik')
            ->assertJsonPath('data.unit_name', 'Pieces');
    }
}
