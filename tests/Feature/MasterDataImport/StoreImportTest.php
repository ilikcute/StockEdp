<?php

namespace Tests\Feature\MasterDataImport;

use App\Features\Auth\Enums\RoleCode;
use App\Features\Auth\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class StoreImportTest extends TestCase
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

    public function test_can_validate_and_commit_valid_stores_import(): void
    {
        $csvContent = "code,name,address,phone\ntk-01,Toko Cabang Mawar,Jl. Mawar No. 1,08123456789\ntk-02,Toko Cabang Melati,Jl. Melati No. 2,08198765432\n";
        $file = UploadedFile::fake()->createWithContent('stores.csv', $csvContent);

        $valRes = $this->actingAs($this->admin)
            ->postJson('/api/v1/master-data-import/stores/validate', ['file' => $file]);

        $valRes->assertOk()
            ->assertJsonPath('data.total_rows', 2)
            ->assertJsonPath('data.valid_rows', 2);

        $sha256 = $valRes->json('data.sha256');

        $commitRes = $this->actingAs($this->admin)
            ->postJson('/api/v1/master-data-import/stores/commit', [
                'file' => $file,
                'expected_sha256' => $sha256,
            ]);

        $commitRes->assertCreated()
            ->assertJsonPath('data.imported_rows', 2);

        $this->assertDatabaseHas('stores', [
            'code' => 'TK-01',
            'name' => 'Toko Cabang Mawar',
            'address' => 'Jl. Mawar No. 1',
        ]);

        $this->assertDatabaseHas('stores', [
            'code' => 'TK-02',
            'name' => 'Toko Cabang Melati',
            'address' => 'Jl. Melati No. 2',
        ]);
    }
}
