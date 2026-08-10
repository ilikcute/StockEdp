<?php

namespace Tests\Feature\MasterDataImport;

use App\Features\Auth\Enums\RoleCode;
use App\Features\Auth\Models\User;
use App\Features\Category\Models\Category;
use App\Features\Location\Models\Location;
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

        $csvContent = "sku,barcode,name,description,category_code,unit_code,minimum_stock\nprd-laptop,000123456789,Laptop Pro,Laptop High End,cat-elec,unit-pcs,10.50\nprd-mouse,,Mouse Wireless,Mouse Ergonomic,CAT-ELEC,UNIT-PCS,0\n";
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
            'minimum_stock' => '10.50',
        ]);

        $this->assertDatabaseHas('products', [
            'sku' => 'PRD-MOUSE',
            'barcode' => null,
            'name' => 'Mouse Wireless',
            'category_id' => $this->category->id,
            'unit_id' => $this->unit->id,
            'minimum_stock' => '0.00',
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

    public function test_prod_17_and_prod_18_sku_length_boundary(): void
    {
        $sku50 = str_repeat('A', 50);
        $sku51 = str_repeat('B', 51);

        $csvPass = "sku,barcode,name,description,category_code,unit_code,minimum_stock\n{$sku50},,Product 50,,CAT-ELEC,UNIT-PCS,0\n";
        $filePass = UploadedFile::fake()->createWithContent('products.csv', $csvPass);
        $resPass = $this->actingAs($this->admin)->postJson('/api/v1/master-data-import/products/validate', ['file' => $filePass]);
        $resPass->assertOk()->assertJsonPath('data.valid_rows', 1);

        $csvFail = "sku,barcode,name,description,category_code,unit_code,minimum_stock\n{$sku51},,Product 51,,CAT-ELEC,UNIT-PCS,0\n";
        $fileFail = UploadedFile::fake()->createWithContent('products.csv', $csvFail);
        $resFail = $this->actingAs($this->admin)->postJson('/api/v1/master-data-import/products/validate', ['file' => $fileFail]);
        $resFail->assertOk()->assertJsonPath('data.invalid_rows', 1)->assertJsonPath('data.errors.0.code', 'FIELD_TOO_LONG');
    }

    public function test_prod_19_and_prod_20_name_length_boundary(): void
    {
        $name150 = str_repeat('A', 150);
        $name151 = str_repeat('B', 151);

        $csvPass = "sku,barcode,name,description,category_code,unit_code,minimum_stock\nSKU-N150,,{$name150},,CAT-ELEC,UNIT-PCS,0\n";
        $filePass = UploadedFile::fake()->createWithContent('products.csv', $csvPass);
        $resPass = $this->actingAs($this->admin)->postJson('/api/v1/master-data-import/products/validate', ['file' => $filePass]);
        $resPass->assertOk()->assertJsonPath('data.valid_rows', 1);

        $csvFail = "sku,barcode,name,description,category_code,unit_code,minimum_stock\nSKU-N151,,{$name151},,CAT-ELEC,UNIT-PCS,0\n";
        $fileFail = UploadedFile::fake()->createWithContent('products.csv', $csvFail);
        $resFail = $this->actingAs($this->admin)->postJson('/api/v1/master-data-import/products/validate', ['file' => $fileFail]);
        $resFail->assertOk()->assertJsonPath('data.invalid_rows', 1)->assertJsonPath('data.errors.0.code', 'FIELD_TOO_LONG');
    }

    public function test_prod_21_and_prod_22_description_length_boundary(): void
    {
        $desc2000 = str_repeat('A', 2000);
        $desc2001 = str_repeat('B', 2001);

        $csvPass = "sku,barcode,name,description,category_code,unit_code,minimum_stock\nSKU-D2000,,Prod Name,\"{$desc2000}\",CAT-ELEC,UNIT-PCS,0\n";
        $filePass = UploadedFile::fake()->createWithContent('products.csv', $csvPass);
        $resPass = $this->actingAs($this->admin)->postJson('/api/v1/master-data-import/products/validate', ['file' => $filePass]);
        $resPass->assertOk()->assertJsonPath('data.valid_rows', 1);

        $csvFail = "sku,barcode,name,description,category_code,unit_code,minimum_stock\nSKU-D2001,,Prod Name,\"{$desc2001}\",CAT-ELEC,UNIT-PCS,0\n";
        $fileFail = UploadedFile::fake()->createWithContent('products.csv', $csvFail);
        $resFail = $this->actingAs($this->admin)->postJson('/api/v1/master-data-import/products/validate', ['file' => $fileFail]);
        $resFail->assertOk()->assertJsonPath('data.invalid_rows', 1)->assertJsonPath('data.errors.0.code', 'FIELD_TOO_LONG');
    }

    public function test_prod_23_and_prod_24_minimum_stock_normalization_and_precision(): void
    {
        $csv = "sku,barcode,name,description,category_code,unit_code,minimum_stock\nSKU-PREC1,,Prod 1,,CAT-ELEC,UNIT-PCS,10.50\nSKU-PREC2,,Prod 2,,CAT-ELEC,UNIT-PCS,10.5\n";
        $file = UploadedFile::fake()->createWithContent('products.csv', $csv);
        $sha = hash_file('sha256', $file->getRealPath());

        $this->actingAs($this->admin)
            ->postJson('/api/v1/master-data-import/products/commit', [
                'file' => $file,
                'expected_sha256' => $sha,
            ])
            ->assertCreated();

        $prod1 = Product::where('sku', 'SKU-PREC1')->firstOrFail();
        $prod2 = Product::where('sku', 'SKU-PREC2')->firstOrFail();

        $this->assertSame('10.50', (string) $prod1->minimum_stock);
        $this->assertSame('10.50', (string) $prod2->minimum_stock);

        $raw1 = DB::table('products')->where('sku', 'SKU-PREC1')->value('minimum_stock');
        $raw2 = DB::table('products')->where('sku', 'SKU-PREC2')->value('minimum_stock');
        $this->assertSame('10.50', (string) $raw1);
        $this->assertSame('10.50', (string) $raw2);
    }

    public function test_prod_25_and_prod_26_minimum_stock_rejects_more_than_two_decimals(): void
    {
        $csv = "sku,barcode,name,description,category_code,unit_code,minimum_stock\nSKU-F1,,Prod 1,,CAT-ELEC,UNIT-PCS,10.5000\nSKU-F2,,Prod 2,,CAT-ELEC,UNIT-PCS,10.501\n";
        $file = UploadedFile::fake()->createWithContent('products.csv', $csv);

        $res = $this->actingAs($this->admin)
            ->postJson('/api/v1/master-data-import/products/validate', ['file' => $file]);

        $res->assertOk()
            ->assertJsonPath('data.invalid_rows', 2)
            ->assertJsonPath('data.errors.0.code', 'INVALID_MINIMUM_STOCK')
            ->assertJsonPath('data.errors.1.code', 'INVALID_MINIMUM_STOCK');
    }

    public function test_audit_fields_for_all_imported_entities(): void
    {
        // 1. Product Audit Fields
        $prodCsv = "sku,barcode,name,description,category_code,unit_code,minimum_stock\nSKU-AUDIT,,Audit Prod,,CAT-ELEC,UNIT-PCS,0\n";
        $prodFile = UploadedFile::fake()->createWithContent('products.csv', $prodCsv);
        $prodSha = hash_file('sha256', $prodFile->getRealPath());

        $this->actingAs($this->admin)
            ->postJson('/api/v1/master-data-import/products/commit', [
                'file' => $prodFile,
                'expected_sha256' => $prodSha,
            ])
            ->assertCreated();

        $product = Product::where('sku', 'SKU-AUDIT')->firstOrFail();
        $this->assertSame($this->admin->id, $product->created_by);
        $this->assertSame($this->admin->id, $product->updated_by);

        // 2. Category Audit Fields
        $catCsv = "code,name,description\nCAT-AUDIT,Category Audit,Desc\n";
        $catFile = UploadedFile::fake()->createWithContent('categories.csv', $catCsv);
        $catSha = hash_file('sha256', $catFile->getRealPath());

        $this->actingAs($this->admin)
            ->postJson('/api/v1/master-data-import/categories/commit', [
                'file' => $catFile,
                'expected_sha256' => $catSha,
            ])
            ->assertCreated();

        $category = Category::where('code', 'CAT-AUDIT')->firstOrFail();
        $this->assertSame($this->admin->id, $category->created_by);

        // 3. Unit Audit Fields
        $unitCsv = "code,name,symbol,description\nUNT-AUD,Unit Audit,ua,Desc\n";
        $unitFile = UploadedFile::fake()->createWithContent('units.csv', $unitCsv);
        $unitSha = hash_file('sha256', $unitFile->getRealPath());

        $this->actingAs($this->admin)
            ->postJson('/api/v1/master-data-import/units/commit', [
                'file' => $unitFile,
                'expected_sha256' => $unitSha,
            ])
            ->assertCreated();

        $unit = Unit::where('code', 'UNT-AUD')->firstOrFail();
        $this->assertSame($this->admin->id, $unit->created_by);

        // 4. Location Audit Fields
        $locCsv = "code,name,description,address,phone\nLOC-AUD,Loc Audit,Desc,Addr,08123\n";
        $locFile = UploadedFile::fake()->createWithContent('locations.csv', $locCsv);
        $locSha = hash_file('sha256', $locFile->getRealPath());

        $this->actingAs($this->admin)
            ->postJson('/api/v1/master-data-import/locations/commit', [
                'file' => $locFile,
                'expected_sha256' => $locSha,
            ])
            ->assertCreated();

        $location = Location::where('code', 'LOC-AUD')->firstOrFail();
        $this->assertSame($this->admin->id, $location->created_by);
    }
}
