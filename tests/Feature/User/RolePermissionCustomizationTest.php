<?php

namespace Tests\Feature\User;

use App\Features\Auth\Enums\PermissionCode;
use App\Features\Auth\Enums\RoleCode;
use App\Features\Auth\Models\Permission;
use App\Features\Auth\Models\Role;
use App\Features\Auth\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RolePermissionCustomizationTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $warehouseOfficer;

    protected Role $adminRole;

    protected Role $warehouseRole;

    protected Role $supervisorRole;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);

        $this->adminRole = Role::where('code', RoleCode::ADMIN->value)->first();
        $this->warehouseRole = Role::where('code', RoleCode::WAREHOUSE_OFFICER->value)->first();
        $this->supervisorRole = Role::where('code', RoleCode::INVENTORY_SUPERVISOR->value)->first();

        $this->admin = User::factory()->create(['is_active' => true]);
        $this->admin->roles()->attach($this->adminRole->id);

        $this->warehouseOfficer = User::factory()->create(['is_active' => true]);
        $this->warehouseOfficer->roles()->attach($this->warehouseRole->id);
    }

    public function test_admin_can_update_warehouse_officer_permissions(): void
    {
        $productsView = Permission::where('code', PermissionCode::PRODUCTS_VIEW->value)->first();
        $stockAdjustmentsView = Permission::where('code', PermissionCode::STOCK_ADJUSTMENTS_VIEW->value)->first();
        $replenishmentView = Permission::where('code', PermissionCode::REPLENISHMENT_VIEW->value)->first();

        $payload = [
            'permission_ids' => [
                $productsView->id,
                $stockAdjustmentsView->id,
                $replenishmentView->id,
            ],
        ];

        $response = $this->actingAs($this->admin)->putJson("/api/v1/roles/{$this->warehouseRole->id}/permissions", $payload);
        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonCount(3, 'data.permissions')
            ->assertJsonPath('data.permission_ids.0', $productsView->id);

        $this->warehouseRole->refresh();
        $this->assertCount(3, $this->warehouseRole->permissions);
        $this->assertTrue($this->warehouseRole->permissions->contains('id', $stockAdjustmentsView->id));
        $this->assertTrue($this->warehouseRole->permissions->contains('id', $replenishmentView->id));
    }

    public function test_non_admin_cannot_update_role_permissions(): void
    {
        $response = $this->actingAs($this->warehouseOfficer)->putJson("/api/v1/roles/{$this->warehouseRole->id}/permissions", [
            'permission_ids' => [1, 2],
        ]);
        $response->assertStatus(403);
    }

    public function test_cannot_modify_admin_role_permissions(): void
    {
        $response = $this->actingAs($this->admin)->putJson("/api/v1/roles/{$this->adminRole->id}/permissions", [
            'permission_ids' => [],
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Hak akses peran Administrator bersifat penuh secara permanen dan tidak dapat dikurangi.');
    }

    public function test_invalid_permission_ids_are_rejected(): void
    {
        $response = $this->actingAs($this->admin)->putJson("/api/v1/roles/{$this->warehouseRole->id}/permissions", [
            'permission_ids' => [999999], // non-existent ID
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['permission_ids.0']);
    }
}
