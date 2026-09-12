<?php

namespace Tests\Feature\MasterDataImport;

use App\Features\Auth\Enums\RoleCode;
use App\Features\Auth\Models\User;
use App\Features\Unit\Models\Unit;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class UnitImportTest extends TestCase
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

    public function test_unit_01_can_validate_and_commit_valid_units(): void
    {
        $csvContent = "code,name,symbol,description\npcs,Pieces,pcs,Satuan buah\nbox,Box,box,Satuan kotak\n";
        $file = UploadedFile::fake()->createWithContent('units.csv', $csvContent);

        $valRes = $this->actingAs($this->admin)
            ->postJson('/api/v1/master-data-import/units/validate', ['file' => $file]);

        $valRes->assertOk()
            ->assertJsonPath('data.total_rows', 2)
            ->assertJsonPath('data.valid_rows', 2)
            ->assertJsonPath('data.invalid_rows', 0);

        $sha256 = $valRes->json('data.sha256');

        $commitRes = $this->actingAs($this->admin)
            ->postJson('/api/v1/master-data-import/units/commit', [
                'file' => $file,
                'expected_sha256' => $sha256,
            ]);

        $commitRes->assertCreated();

        $this->assertDatabaseHas('units', [
            'code' => 'PCS',
            'name' => 'Pieces',
            'symbol' => 'pcs',
            'is_active' => true,
        ]);
        $this->assertDatabaseHas('units', [
            'code' => 'BOX',
            'name' => 'Box',
            'symbol' => 'box',
            'is_active' => true,
        ]);
    }

    public function test_unit_02_fails_when_symbol_is_missing(): void
    {
        $csvContent = "code,name,symbol,description\nKG,Kilogram,,Satuan berat\n";
        $file = UploadedFile::fake()->createWithContent('units.csv', $csvContent);

        $response = $this->actingAs($this->admin)
            ->postJson('/api/v1/master-data-import/units/validate', ['file' => $file]);

        $response->assertOk()
            ->assertJsonPath('data.invalid_rows', 1)
            ->assertJsonPath('data.errors.0.field', 'symbol')
            ->assertJsonPath('data.errors.0.code', 'REQUIRED_FIELD_MISSING');
    }

    public function test_unit_03_fails_when_in_db_duplicate_code(): void
    {
        Unit::factory()->create(['code' => 'EXISTING-UNIT']);

        $csvContent = "code,name,symbol,description\nEXISTING-UNIT,Unit Name,u,Desc\n";
        $file = UploadedFile::fake()->createWithContent('units.csv', $csvContent);

        $response = $this->actingAs($this->admin)
            ->postJson('/api/v1/master-data-import/units/validate', ['file' => $file]);

        $response->assertOk()
            ->assertJsonPath('data.invalid_rows', 1)
            ->assertJsonPath('data.errors.0.code', 'DUPLICATE_CODE_IN_DB');
    }

    public function test_unit_04_mixed_failure_zero_imported(): void
    {
        Unit::factory()->create(['code' => 'DUP-UNIT']);

        $csvContent = "code,name,symbol,description\nVALID-UNIT,Valid Unit,vu,Desc\nDUP-UNIT,Dup Unit,du,Desc\n";
        $file = UploadedFile::fake()->createWithContent('units.csv', $csvContent);
        $sha = hash_file('sha256', $file->getRealPath());

        $response = $this->actingAs($this->admin)
            ->postJson('/api/v1/master-data-import/units/commit', [
                'file' => $file,
                'expected_sha256' => $sha,
            ]);

        $response->assertStatus(422);
        $this->assertDatabaseMissing('units', ['code' => 'VALID-UNIT']);
    }
}
