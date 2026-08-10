<?php

namespace Tests\Feature\MasterDataImport;

use App\Features\Auth\Enums\RoleCode;
use App\Features\Auth\Models\User;
use App\Features\Category\Models\Category;
use App\Features\Product\Models\Product;
use App\Features\Unit\Models\Unit;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ProductImportTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected Category $category;

    protected Unit $unit;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);

        $this->admin = User::factory()->create();
        $this->admin->assignRole(RoleCode::ADMIN->value);

        $this->category = Category::factory()->create(['code' => 'CAT-ELEC', 'name' => 'Elektronik']);
        $this->unit = Unit::factory()->create(['code' => 'UNIT-PCS', 'name' => 'Pieces', 'symbol' => 'pcs']);
    }

    public function test_prod_01_02_06_07_12_13_15_16_can_validate_and_commit_valid_products(): void
    {
        $initialMovements = DB::table('stock_movements')->count();
        $initialBalances = DB::table('inventory_balances')->count();

        $csvContent = "sku,barcode,name,description,category_code,unit_code,minimum_stock\nprd-laptop,000123456789,Laptop Pro,Laptop High End,cat-elec,unit-pcs,10.5000\nprd-mouse,,Mouse Wireless,Mouse Ergonomic,CAT-ELEC,UNIT-PCS,0\n";
        $file = UploadedFile::fake()->createWithContent('products.csv', $csvContent);

        $valRes = $this->actingAs($this->admin)
            ->postJson('/api/v1/master-data-import/products/validate', ['file' => $file]);

        $valRes->assertOk()
            ->assertJsonPath('data.total_rows', 2)
            ->assertJsonPath('data.valid_rows', 2)
            ->assertJsonPath('data.invalid_rows', 0);

        $sha256 = $valRes->json('data.sha256');

        $commitRes = $this->actingAs($this->admin)
            ->postJson('/api/v1/master-data-import/products/commit', [
                'file' => $file,
                'expected_sha256' => $sha256,
            ]);

        $commitRes->assertCreated()
            ->assertJsonPath('data.imported_rows', 2);

        $this->assertDatabaseHas('products', [
            'sku' => 'PRD-LAPTOP',
            'barcode' => '000123456789',
            'name' => 'Laptop Pro',
            'category_id' => $this->category->id,
            'unit_id' => $this->unit->id,
            'minimum_stock' => '10.5000',
        ]);

        $this->assertDatabaseHas('products', [
            'sku' => 'PRD-MOUSE',
            'barcode' => null,
            'name' => 'Mouse Wireless',
            'category_id' => $this->category->id,
            'unit_id' => $this->unit->id,
            'minimum_stock' => '0.0000',
        ]);

        // Verify barcode string leading zeros preserved
        $laptop = Product::where('sku', 'PRD-LAPTOP')->first();
        $this->assertSame('000123456789', $laptop->barcode);

        // Verify zero stock movements or balances created
        $this->assertSame($initialMovements, DB::table('stock_movements')->count());
        $this->assertSame($initialBalances, DB::table('inventory_balances')->count());
    }

    public function test_prod_03_fails_when_duplicate_sku_in_file(): void
    {
        $csvContent = "sku,barcode,name,description,category_code,unit_code,minimum_stock\nPRD-01,,Product 1,,CAT-ELEC,UNIT-PCS,0\nprd-01,,Product 2,,CAT-ELEC,UNIT-PCS,0\n";
        $file = UploadedFile::fake()->createWithContent('products.csv', $csvContent);

        $response = $this->actingAs($this->admin)
            ->postJson('/api/v1/master-data-import/products/validate', ['file' => $file]);

        $response->assertOk()
            ->assertJsonPath('data.invalid_rows', 1)
            ->assertJsonPath('data.errors.0.code', 'DUPLICATE_SKU_IN_FILE');
    }

    public function test_prod_04_fails_when_duplicate_sku_in_db(): void
    {
        Product::factory()->create([
            'sku' => 'EXISTING-SKU',
            'category_id' => $this->category->id,
            'unit_id' => $this->unit->id,
        ]);

        $csvContent = "sku,barcode,name,description,category_code,unit_code,minimum_stock\nEXISTING-SKU,,Product Name,,CAT-ELEC,UNIT-PCS,0\n";
        $file = UploadedFile::fake()->createWithContent('products.csv', $csvContent);

        $response = $this->actingAs($this->admin)
            ->postJson('/api/v1/master-data-import/products/validate', ['file' => $file]);

        $response->assertOk()
            ->assertJsonPath('data.invalid_rows', 1)
            ->assertJsonPath('data.errors.0.code', 'DUPLICATE_SKU_IN_DB');
    }

    public function test_prod_05_fails_when_duplicate_barcode_in_file(): void
    {
        $csvContent = "sku,barcode,name,description,category_code,unit_code,minimum_stock\nPRD-01,12345,Product 1,,CAT-ELEC,UNIT-PCS,0\nPRD-02,12345,Product 2,,CAT-ELEC,UNIT-PCS,0\n";
        $file = UploadedFile::fake()->createWithContent('products.csv', $csvContent);

        $response = $this->actingAs($this->admin)
            ->postJson('/api/v1/master-data-import/products/validate', ['file' => $file]);

        $response->assertOk()
            ->assertJsonPath('data.invalid_rows', 1)
            ->assertJsonPath('data.errors.0.code', 'DUPLICATE_BARCODE_IN_FILE');
    }

    public function test_prod_08_fails_when_category_not_found(): void
    {
        $csvContent = "sku,barcode,name,description,category_code,unit_code,minimum_stock\nPRD-01,,Product 1,,CAT-UNKNOWN,UNIT-PCS,5\n";
        $file = UploadedFile::fake()->createWithContent('products.csv', $csvContent);

        $response = $this->actingAs($this->admin)
            ->postJson('/api/v1/master-data-import/products/validate', ['file' => $file]);

        $response->assertOk()
            ->assertJsonPath('data.invalid_rows', 1)
            ->assertJsonPath('data.errors.0.code', 'CATEGORY_NOT_FOUND');
    }

    public function test_prod_09_fails_when_unit_not_found(): void
    {
        $csvContent = "sku,barcode,name,description,category_code,unit_code,minimum_stock\nPRD-01,,Product 1,,CAT-ELEC,UNIT-UNKNOWN,5\n";
        $file = UploadedFile::fake()->createWithContent('products.csv', $csvContent);

        $response = $this->actingAs($this->admin)
            ->postJson('/api/v1/master-data-import/products/validate', ['file' => $file]);

        $response->assertOk()
            ->assertJsonPath('data.invalid_rows', 1)
            ->assertJsonPath('data.errors.0.code', 'UNIT_NOT_FOUND');
    }

    public function test_prod_10_and_prod_11_fails_when_minimum_stock_is_invalid(): void
    {
        $csvContent = "sku,barcode,name,description,category_code,unit_code,minimum_stock\nPRD-01,,Product 1,,CAT-ELEC,UNIT-PCS,-5.0\nPRD-02,,Product 2,,CAT-ELEC,UNIT-PCS,abc\n";
        $file = UploadedFile::fake()->createWithContent('products.csv', $csvContent);

        $response = $this->actingAs($this->admin)
            ->postJson('/api/v1/master-data-import/products/validate', ['file' => $file]);

        $response->assertOk()
            ->assertJsonPath('data.invalid_rows', 2)
            ->assertJsonPath('data.errors.0.code', 'INVALID_MINIMUM_STOCK')
            ->assertJsonPath('data.errors.1.code', 'INVALID_MINIMUM_STOCK');
    }

    public function test_prod_14_mixed_failure_zero_imported(): void
    {
        $csvContent = "sku,barcode,name,description,category_code,unit_code,minimum_stock\nPRD-VALID,,Valid Prod,,CAT-ELEC,UNIT-PCS,0\nPRD-INVALID,,Invalid Prod,,CAT-NONEXIST,UNIT-PCS,0\n";
        $file = UploadedFile::fake()->createWithContent('products.csv', $csvContent);
        $sha = hash_file('sha256', $file->getRealPath());

        $response = $this->actingAs($this->admin)
            ->postJson('/api/v1/master-data-import/products/commit', [
                'file' => $file,
                'expected_sha256' => $sha,
            ]);

        $response->assertStatus(422);
        $this->assertDatabaseMissing('products', ['sku' => 'PRD-VALID']);
    }
}
