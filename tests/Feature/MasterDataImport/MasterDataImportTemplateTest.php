<?php

namespace Tests\Feature\MasterDataImport;

use App\Features\Auth\Enums\RoleCode;
use App\Features\Auth\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MasterDataImportTemplateTest extends TestCase
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

    public function test_can_download_categories_template(): void
    {
        $response = $this->actingAs($this->admin)->get('/api/v1/master-data-import/categories/template');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $response->assertHeader('Content-Disposition', 'attachment; filename="template_categories.csv"');

        $content = $response->getContent();
        $bom = pack('CCC', 0xEF, 0xBB, 0xBF);
        $this->assertStringStartsWith($bom, $content);
        $this->assertStringContainsString('code,name,description', $content);
    }

    public function test_can_download_units_template(): void
    {
        $response = $this->actingAs($this->admin)->get('/api/v1/master-data-import/units/template');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $response->assertHeader('Content-Disposition', 'attachment; filename="template_units.csv"');

        $content = $response->getContent();
        $this->assertStringContainsString('code,name,symbol,description', $content);
    }

    public function test_can_download_locations_template(): void
    {
        $response = $this->actingAs($this->admin)->get('/api/v1/master-data-import/locations/template');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $response->assertHeader('Content-Disposition', 'attachment; filename="template_locations.csv"');

        $content = $response->getContent();
        $this->assertStringContainsString('code,name,description,address,phone', $content);
    }

    public function test_can_download_products_template(): void
    {
        $response = $this->actingAs($this->admin)->get('/api/v1/master-data-import/products/template');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $response->assertHeader('Content-Disposition', 'attachment; filename="template_products.csv"');

        $content = $response->getContent();
        $this->assertStringContainsString('sku,barcode,name,description,category_code,unit_code,minimum_stock', $content);
    }

    public function test_invalid_type_template_returns_error(): void
    {
        $response = $this->actingAs($this->admin)->get('/api/v1/master-data-import/unknown_type/template');

        $response->assertStatus(422);
    }
}
