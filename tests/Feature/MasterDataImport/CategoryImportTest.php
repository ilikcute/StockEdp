<?php

namespace Tests\Feature\MasterDataImport;

use App\Features\Auth\Enums\RoleCode;
use App\Features\Auth\Models\User;
use App\Features\Category\Models\Category;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class CategoryImportTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);

        $this->admin = User::factory()->create();
        $this->admin->assignRole(RoleCode::ADMIN->value);
    }

    public function test_cat_01_and_cat_02_can_validate_and_commit_valid_categories(): void
    {
        $csvContent = "code,name,description\ncat-elec,Elektronik,Peralatan elektronik\ncat-atk,ATK,Alat tulis kantor\n";
        $file = UploadedFile::fake()->createWithContent('categories.csv', $csvContent);

        // 1. Validate
        $validateResponse = $this->actingAs($this->admin)
            ->postJson('/api/v1/master-data-import/categories/validate', ['file' => $file]);

        $validateResponse->assertOk()
            ->assertJsonPath('data.total_rows', 2)
            ->assertJsonPath('data.valid_rows', 2)
            ->assertJsonPath('data.invalid_rows', 0)
            ->assertJsonPath('data.errors', []);

        $sha256 = $validateResponse->json('data.sha256');

        // 2. Commit
        $commitResponse = $this->actingAs($this->admin)
            ->postJson('/api/v1/master-data-import/categories/commit', [
                'file' => $file,
                'expected_sha256' => $sha256,
            ]);

        $commitResponse->assertCreated()
            ->assertJsonPath('data.total_rows', 2)
            ->assertJsonPath('data.imported_rows', 2);

        $this->assertDatabaseHas('categories', [
            'code' => 'CAT-ELEC',
            'name' => 'Elektronik',
            'is_active' => true,
        ]);
        $this->assertDatabaseHas('categories', [
            'code' => 'CAT-ATK',
            'name' => 'ATK',
            'is_active' => true,
        ]);
    }

    public function test_cat_03_fails_when_in_file_duplicate_code_exists(): void
    {
        $csvContent = "code,name,description\nCAT-01,Category 1,Desc\ncat-01,Category Duplicate,Desc\n";
        $file = UploadedFile::fake()->createWithContent('categories.csv', $csvContent);

        $response = $this->actingAs($this->admin)
            ->postJson('/api/v1/master-data-import/categories/validate', ['file' => $file]);

        $response->assertOk()
            ->assertJsonPath('data.total_rows', 2)
            ->assertJsonPath('data.valid_rows', 1)
            ->assertJsonPath('data.invalid_rows', 1)
            ->assertJsonPath('data.errors.0.code', 'DUPLICATE_CODE_IN_FILE');
    }

    public function test_cat_04_fails_when_in_db_duplicate_code_exists(): void
    {
        Category::factory()->create(['code' => 'EXISTING-CAT']);

        $csvContent = "code,name,description\nEXISTING-CAT,Category Name,Desc\n";
        $file = UploadedFile::fake()->createWithContent('categories.csv', $csvContent);

        $response = $this->actingAs($this->admin)
            ->postJson('/api/v1/master-data-import/categories/validate', ['file' => $file]);

        $response->assertOk()
            ->assertJsonPath('data.invalid_rows', 1)
            ->assertJsonPath('data.errors.0.code', 'DUPLICATE_CODE_IN_DB');
    }

    public function test_hash_02_cannot_commit_with_invalid_checksum(): void
    {
        $csvContent = "code,name,description\nCAT-01,Category 1,\n";
        $file = UploadedFile::fake()->createWithContent('categories.csv', $csvContent);

        $fakeSha = str_repeat('a', 64);

        $response = $this->actingAs($this->admin)
            ->postJson('/api/v1/master-data-import/categories/commit', [
                'file' => $file,
                'expected_sha256' => $fakeSha,
            ]);

        $response->assertStatus(409);
        $this->assertDatabaseMissing('categories', ['code' => 'CAT-01']);
    }

    public function test_cat_05_all_or_nothing_rollback_on_commit_error(): void
    {
        Category::factory()->create(['code' => 'DUP-CAT']);

        // First row is valid, second row causes in-db duplicate
        $csvContent = "code,name,description\nVALID-CAT,Valid Category,\nDUP-CAT,Duplicate Category,\n";
        $file = UploadedFile::fake()->createWithContent('categories.csv', $csvContent);
        $sha = hash_file('sha256', $file->getRealPath());

        $response = $this->actingAs($this->admin)
            ->postJson('/api/v1/master-data-import/categories/commit', [
                'file' => $file,
                'expected_sha256' => $sha,
            ]);

        $response->assertStatus(422);
        // Ensure ALL-OR-NOTHING: VALID-CAT was not inserted
        $this->assertDatabaseMissing('categories', ['code' => 'VALID-CAT']);
    }

    public function test_hdr_01_fails_when_mandatory_header_is_missing(): void
    {
        $csvContent = "code,description\nCAT-01,Description only\n";
        $file = UploadedFile::fake()->createWithContent('categories.csv', $csvContent);

        $response = $this->actingAs($this->admin)
            ->postJson('/api/v1/master-data-import/categories/validate', ['file' => $file]);

        $response->assertOk()
            ->assertJsonPath('data.errors.0.code', 'MISSING_REQUIRED_HEADER')
            ->assertJsonPath('data.errors.0.field', 'name');
    }

    public function test_hdr_02_fails_when_unknown_header_present(): void
    {
        $csvContent = "code,name,description,extra_col\nCAT-01,Cat 1,Desc,extra\n";
        $file = UploadedFile::fake()->createWithContent('categories.csv', $csvContent);

        $response = $this->actingAs($this->admin)
            ->postJson('/api/v1/master-data-import/categories/validate', ['file' => $file]);

        $response->assertOk()
            ->assertJsonPath('data.errors.0.code', 'UNKNOWN_HEADER')
            ->assertJsonPath('data.errors.0.field', 'extra_col');
    }

    public function test_hdr_04_accepts_reordered_valid_headers(): void
    {
        $csvContent = "description,name,code\nDesc 1,Cat 1,CAT-REORDER\n";
        $file = UploadedFile::fake()->createWithContent('categories.csv', $csvContent);

        $response = $this->actingAs($this->admin)
            ->postJson('/api/v1/master-data-import/categories/validate', ['file' => $file]);

        $response->assertOk()
            ->assertJsonPath('data.valid_rows', 1)
            ->assertJsonPath('data.invalid_rows', 0)
            ->assertJsonPath('data.preview.0.code', 'CAT-REORDER')
            ->assertJsonPath('data.preview.0.name', 'Cat 1');
    }

    public function test_idemp_01_repeated_commit_cannot_duplicate_records(): void
    {
        $csvContent = "code,name,description\nCAT-ONCE,Category Once,Desc\n";
        $file = UploadedFile::fake()->createWithContent('categories.csv', $csvContent);
        $sha = hash_file('sha256', $file->getRealPath());

        // First commit
        $this->actingAs($this->admin)
            ->postJson('/api/v1/master-data-import/categories/commit', [
                'file' => $file,
                'expected_sha256' => $sha,
            ])
            ->assertCreated();

        $this->assertSame(1, Category::where('code', 'CAT-ONCE')->count());

        // Second repeated commit with same file fails because record already in DB
        $this->actingAs($this->admin)
            ->postJson('/api/v1/master-data-import/categories/commit', [
                'file' => $file,
                'expected_sha256' => $sha,
            ])
            ->assertStatus(422);

        $this->assertSame(1, Category::where('code', 'CAT-ONCE')->count());
    }
}
