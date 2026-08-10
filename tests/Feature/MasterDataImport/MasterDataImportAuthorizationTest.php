<?php

namespace Tests\Feature\MasterDataImport;

use App\Features\Auth\Enums\RoleCode;
use App\Features\Auth\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class MasterDataImportAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $warehouseOfficer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);

        $this->admin = User::factory()->create();
        $this->admin->assignRole(RoleCode::ADMIN->value);

        $this->warehouseOfficer = User::factory()->create();
        $this->warehouseOfficer->assignRole(RoleCode::WAREHOUSE_OFFICER->value);
    }

    public function test_guest_cannot_access_import_endpoints(): void
    {
        $this->getJson('/api/v1/master-data-import/categories/template')->assertUnauthorized();
        $this->postJson('/api/v1/master-data-import/categories/validate')->assertUnauthorized();
        $this->postJson('/api/v1/master-data-import/categories/commit')->assertUnauthorized();
    }

    public function test_user_without_import_permission_cannot_access_import(): void
    {
        $csv = UploadedFile::fake()->createWithContent('cat.csv', "code,name,description\nCAT-01,Category 1,\n");

        $this->actingAs($this->warehouseOfficer)
            ->get('/api/v1/master-data-import/categories/template')
            ->assertForbidden();

        $this->actingAs($this->warehouseOfficer)
            ->postJson('/api/v1/master-data-import/categories/validate', ['file' => $csv])
            ->assertForbidden();

        $this->actingAs($this->warehouseOfficer)
            ->postJson('/api/v1/master-data-import/categories/commit', [
                'file' => $csv,
                'expected_sha256' => hash('sha256', $csv->getContent()),
            ])
            ->assertForbidden();
    }

    public function test_admin_has_permission_and_can_validate_and_commit(): void
    {
        $csv = UploadedFile::fake()->createWithContent('cat.csv', "code,name,description\nCAT-01,Category 1,\n");

        $this->actingAs($this->admin)
            ->get('/api/v1/master-data-import/categories/template')
            ->assertOk();

        $valRes = $this->actingAs($this->admin)
            ->postJson('/api/v1/master-data-import/categories/validate', ['file' => $csv]);
        $valRes->assertOk();
        $sha = $valRes->json('data.sha256');

        $this->actingAs($this->admin)
            ->postJson('/api/v1/master-data-import/categories/commit', [
                'file' => $csv,
                'expected_sha256' => $sha,
            ])
            ->assertCreated();
    }
}
