<?php

namespace Tests\Feature\Reporting;

use App\Features\Auth\Enums\RoleCode;
use App\Features\Auth\Models\Role;
use App\Features\Auth\Models\User;
use App\Features\Category\Models\Category;
use App\Features\Inventory\Models\InventoryBalance;
use App\Features\Location\Models\Location;
use App\Features\Product\Models\Product;
use App\Features\Unit\Models\Unit;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class FieldBalanceReportTest extends TestCase
{
    use DatabaseTransactions;

    private User $admin;

    private User $technician;

    private User $unauthorizedUser;

    private Location $fieldLocation;

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);

        $this->admin = User::factory()->create();
        $adminRole = Role::where('code', RoleCode::ADMIN->value)->first();
        $this->admin->roles()->attach($adminRole->id);

        $this->technician = User::factory()->create(['name' => 'Budi Teknisi Lapangan']);
        $this->unauthorizedUser = User::factory()->create();

        $category = Category::create(['name' => 'POS Peripheral', 'code' => 'POS-'.uniqid()]);
        $unit = Unit::create(['name' => 'Unit', 'code' => 'UNT-'.uniqid(), 'symbol' => 'U']);

        $this->fieldLocation = Location::create([
            'code' => 'LOC-BUDI-'.uniqid(),
            'name' => 'Van Mobile Budi',
            'type' => 'FIELD_PERSONNEL',
            'user_id' => $this->technician->id,
            'is_active' => true,
        ]);
        $this->admin->locations()->attach($this->fieldLocation->id);

        $this->product = Product::create([
            'name' => 'Thermal Receipt Printer',
            'sku' => 'SKU-PRN-'.uniqid(),
            'category_id' => $category->id,
            'unit_id' => $unit->id,
            'unit_price' => 1200000,
            'is_active' => true,
        ]);

        // Create GOOD balance: 5 units
        InventoryBalance::create([
            'product_id' => $this->product->id,
            'location_id' => $this->fieldLocation->id,
            'condition' => 'GOOD',
            'quantity' => '5.0000',
        ]);

        // Create DEFECTIVE balance: 2 units
        InventoryBalance::create([
            'product_id' => $this->product->id,
            'location_id' => $this->fieldLocation->id,
            'condition' => 'DEFECTIVE',
            'quantity' => '2.0000',
        ]);
    }

    public function test_authorized_user_can_view_field_balances_with_dual_condition(): void
    {
        $response = $this->actingAs($this->admin)->getJson('/api/v1/reports/field-balances');

        $response->assertOk()
            ->assertJsonStructure([
                'meta' => ['summary' => ['total_good', 'total_defective', 'total_units']],
                'data' => [
                    '*' => [
                        'technician_name',
                        'location_name',
                        'product_sku',
                        'product_name',
                        'good_quantity',
                        'defective_quantity',
                        'total_quantity',
                    ],
                ],
                'pagination',
            ]);

        $item = collect($response->json('data'))->firstWhere('product_sku', $this->product->sku);
        $this->assertNotNull($item);
        $this->assertSame('5.0000', $item['good_quantity']);
        $this->assertSame('2.0000', $item['defective_quantity']);
        $this->assertSame('7.0000', $item['total_quantity']);
    }

    public function test_can_export_field_balances_to_csv(): void
    {
        $response = $this->actingAs($this->admin)->get('/api/v1/reports/field-balances/export');

        $response->assertOk();
        $this->assertStringContainsString('text/csv', $response->headers->get('content-type'));
        $content = $response->streamedContent();
        $this->assertStringContainsString('Siap Pasang (GOOD)', $content);
        $this->assertStringContainsString('Rusak Lapangan (DEFECTIVE)', $content);
        $this->assertStringContainsString($this->product->sku, $content);
    }

    public function test_unauthorized_user_is_forbidden_from_field_balances(): void
    {
        $response = $this->actingAs($this->unauthorizedUser)->getJson('/api/v1/reports/field-balances');
        $response->assertForbidden();
    }
}
