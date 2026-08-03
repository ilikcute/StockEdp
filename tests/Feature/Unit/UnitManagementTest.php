<?php

namespace Tests\Feature\Unit;

use App\Features\Auth\Enums\PermissionCode;
use App\Features\Auth\Enums\RoleCode;
use App\Features\Auth\Models\Permission;
use App\Features\Auth\Models\Role;
use App\Features\Auth\Models\User;
use App\Features\Unit\Models\Unit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UnitManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $viewer;

    protected User $noAccessUser;

    protected function setUp(): void
    {
        parent::setUp();

        $adminRole = Role::firstOrCreate(
            ['code' => RoleCode::ADMIN->value],
            ['name' => 'Administrator']
        );
        $permissions = [
            PermissionCode::UNITS_VIEW->value => 'Melihat Satuan',
            PermissionCode::UNITS_CREATE->value => 'Membuat Satuan',
            PermissionCode::UNITS_UPDATE->value => 'Mengubah Satuan',
            PermissionCode::UNITS_CHANGE_STATUS->value => 'Mengubah Status Satuan',
        ];
        foreach ($permissions as $code => $name) {
            $p = Permission::firstOrCreate(['code' => $code], ['name' => $name, 'group' => 'units']);
            $adminRole->permissions()->syncWithoutDetaching([$p->id]);
        }

        $viewerRole = Role::firstOrCreate(
            ['code' => RoleCode::WAREHOUSE_OFFICER->value],
            ['name' => 'Petugas Gudang']
        );
        $viewPerm = Permission::where('code', PermissionCode::UNITS_VIEW->value)->first();
        $viewerRole->permissions()->syncWithoutDetaching([$viewPerm->id]);

        $this->admin = User::factory()->create(['is_active' => true]);
        $this->admin->roles()->attach($adminRole->id);

        $this->viewer = User::factory()->create(['is_active' => true]);
        $this->viewer->roles()->attach($viewerRole->id);

        $this->noAccessUser = User::factory()->create(['is_active' => true]);
    }

    // ─── 401 / 403 ───────────────────────────────────────────────────────────

    public function test_unauthenticated_user_cannot_access_units(): void
    {
        $this->getJson('/api/v1/units')->assertStatus(401);
    }

    public function test_user_without_permission_cannot_create_unit(): void
    {
        $this->actingAs($this->noAccessUser)
            ->postJson('/api/v1/units', ['code' => 'PCS', 'name' => 'Pieces', 'symbol' => 'pcs'])
            ->assertStatus(403);
    }

    public function test_user_without_permission_cannot_update_unit(): void
    {
        $unit = Unit::create(['code' => 'PCS', 'name' => 'Pieces', 'symbol' => 'pcs']);

        $this->actingAs($this->noAccessUser)
            ->putJson("/api/v1/units/{$unit->id}", ['code' => 'PCS', 'name' => 'New', 'symbol' => 'pcs'])
            ->assertStatus(403);
    }

    public function test_user_without_permission_cannot_change_status(): void
    {
        $unit = Unit::create(['code' => 'PCS', 'name' => 'Pieces', 'symbol' => 'pcs']);

        $this->actingAs($this->noAccessUser)
            ->patchJson("/api/v1/units/{$unit->id}/status", ['is_active' => false])
            ->assertStatus(403);
    }

    public function test_viewer_can_list_units(): void
    {
        Unit::create(['code' => 'PCS', 'name' => 'Pieces', 'symbol' => 'pcs']);

        $this->actingAs($this->viewer)
            ->getJson('/api/v1/units')
            ->assertStatus(200)
            ->assertJsonStructure(['success', 'data']);
    }

    public function test_viewer_cannot_create_unit(): void
    {
        $this->actingAs($this->viewer)
            ->postJson('/api/v1/units', ['code' => 'PCS', 'name' => 'Pieces', 'symbol' => 'pcs'])
            ->assertStatus(403);
    }

    // ─── Create ──────────────────────────────────────────────────────────────

    public function test_admin_can_create_unit(): void
    {
        $response = $this->actingAs($this->admin)
            ->postJson('/api/v1/units', [
                'code' => 'pcs',
                'name' => 'Pieces',
                'symbol' => 'pcs',
                'description' => 'Satuan buah/pieces',
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'code' => 'PCS',
                    'name' => 'Pieces',
                    'symbol' => 'pcs',
                    'is_active' => true,
                ],
            ]);

        $this->assertDatabaseHas('units', [
            'code' => 'PCS',
            'name' => 'Pieces',
            'symbol' => 'pcs',
            'created_by' => $this->admin->id,
        ]);
    }

    public function test_unit_code_is_normalized_to_uppercase(): void
    {
        $this->actingAs($this->admin)
            ->postJson('/api/v1/units', ['code' => 'unt-new', 'name' => 'Test', 'symbol' => 'tn'])
            ->assertStatus(201)
            ->assertJsonPath('data.code', 'UNT-NEW');

        $this->assertDatabaseHas('units', ['code' => 'UNT-NEW']);
    }

    public function test_unit_code_must_be_unique(): void
    {
        Unit::create(['code' => 'PCS', 'name' => 'Pieces', 'symbol' => 'pcs']);

        $this->actingAs($this->admin)
            ->postJson('/api/v1/units', ['code' => 'PCS', 'name' => 'Pcs Lain', 'symbol' => 'pc'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['code']);
    }

    public function test_unit_code_is_case_insensitive_unique(): void
    {
        Unit::create(['code' => 'PCS', 'name' => 'Pieces', 'symbol' => 'pcs']);

        $this->actingAs($this->admin)
            ->postJson('/api/v1/units', ['code' => 'pcs', 'name' => 'Pcs Lain', 'symbol' => 'pc'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['code']);
    }

    public function test_unit_name_is_required(): void
    {
        $this->actingAs($this->admin)
            ->postJson('/api/v1/units', ['code' => 'PCS', 'symbol' => 'pcs'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_unit_code_is_required(): void
    {
        $this->actingAs($this->admin)
            ->postJson('/api/v1/units', ['name' => 'Pieces', 'symbol' => 'pcs'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['code']);
    }

    public function test_unit_symbol_is_required(): void
    {
        $this->actingAs($this->admin)
            ->postJson('/api/v1/units', ['code' => 'PCS', 'name' => 'Pieces'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['symbol']);
    }

    // ─── Update ──────────────────────────────────────────────────────────────

    public function test_admin_can_update_unit(): void
    {
        $unit = Unit::create(['code' => 'PCS', 'name' => 'Pieces', 'symbol' => 'pcs']);

        $this->actingAs($this->admin)
            ->putJson("/api/v1/units/{$unit->id}", [
                'code' => 'pcs-updated',
                'name' => 'Pieces Update',
                'symbol' => 'p',
            ])
            ->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'code' => 'PCS-UPDATED',
                    'name' => 'Pieces Update',
                    'symbol' => 'p',
                ],
            ]);

        $this->assertDatabaseHas('units', [
            'id' => $unit->id,
            'updated_by' => $this->admin->id,
        ]);
    }

    public function test_update_code_unique_ignores_self(): void
    {
        $unit = Unit::create(['code' => 'PCS', 'name' => 'Pieces', 'symbol' => 'pcs']);

        $this->actingAs($this->admin)
            ->putJson("/api/v1/units/{$unit->id}", [
                'code' => 'PCS',
                'name' => 'Pieces Diperbarui',
                'symbol' => 'pcs',
            ])
            ->assertStatus(200);
    }

    public function test_update_code_must_be_unique_across_others(): void
    {
        $u1 = Unit::create(['code' => 'PCS', 'name' => 'Pieces', 'symbol' => 'pcs']);
        $u2 = Unit::create(['code' => 'BOX', 'name' => 'Box', 'symbol' => 'bx']);

        $this->actingAs($this->admin)
            ->putJson("/api/v1/units/{$u2->id}", [
                'code' => 'PCS',
                'name' => 'Box Baru',
                'symbol' => 'bx',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['code']);
    }

    // ─── Status Change ───────────────────────────────────────────────────────

    public function test_admin_can_deactivate_unit(): void
    {
        $unit = Unit::create(['code' => 'PCS', 'name' => 'Pieces', 'symbol' => 'pcs', 'is_active' => true]);

        $this->actingAs($this->admin)
            ->patchJson("/api/v1/units/{$unit->id}/status", ['is_active' => false])
            ->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => ['is_active' => false],
            ]);

        $this->assertDatabaseHas('units', ['id' => $unit->id, 'is_active' => false]);
    }

    public function test_admin_can_activate_unit(): void
    {
        $unit = Unit::create(['code' => 'PCS', 'name' => 'Pieces', 'symbol' => 'pcs', 'is_active' => false]);

        $this->actingAs($this->admin)
            ->patchJson("/api/v1/units/{$unit->id}/status", ['is_active' => true])
            ->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => ['is_active' => true],
            ]);
    }

    public function test_status_change_requires_is_active_field(): void
    {
        $unit = Unit::create(['code' => 'PCS', 'name' => 'Pieces', 'symbol' => 'pcs']);

        $this->actingAs($this->admin)
            ->patchJson("/api/v1/units/{$unit->id}/status", [])
            ->assertStatus(422);
    }

    // ─── List / Filter / Search / Pagination / Sort ──────────────────────────

    public function test_admin_can_list_units_with_pagination(): void
    {
        Unit::factory()->count(20)->create();

        $response = $this->actingAs($this->admin)
            ->getJson('/api/v1/units?per_page=5')
            ->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'meta' => ['current_page', 'last_page', 'per_page', 'total'],
            ]);

        $this->assertCount(5, $response->json('data'));
    }

    public function test_search_by_name_works(): void
    {
        Unit::create(['code' => 'KIL', 'name' => 'Kilogram', 'symbol' => 'kg']);
        Unit::create(['code' => 'MET', 'name' => 'Meter', 'symbol' => 'm']);

        $this->actingAs($this->admin)
            ->getJson('/api/v1/units?search=kilo')
            ->assertStatus(200)
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.name', 'Kilogram');
    }

    public function test_search_by_code_works(): void
    {
        Unit::create(['code' => 'KIL', 'name' => 'Kilogram', 'symbol' => 'kg']);
        Unit::create(['code' => 'MET', 'name' => 'Meter', 'symbol' => 'm']);

        $this->actingAs($this->admin)
            ->getJson('/api/v1/units?search=MET')
            ->assertStatus(200)
            ->assertJsonPath('meta.total', 1);
    }

    public function test_search_by_symbol_works(): void
    {
        Unit::create(['code' => 'KIL', 'name' => 'Kilogram', 'symbol' => 'kg']);
        Unit::create(['code' => 'MET', 'name' => 'Meter', 'symbol' => 'm']);

        $this->actingAs($this->admin)
            ->getJson('/api/v1/units?search=kg')
            ->assertStatus(200)
            ->assertJsonPath('meta.total', 1);
    }

    public function test_filter_by_is_active_true(): void
    {
        Unit::create(['code' => 'ACT', 'name' => 'Aktif', 'symbol' => 'a', 'is_active' => true]);
        Unit::create(['code' => 'INA', 'name' => 'Nonaktif', 'symbol' => 'n', 'is_active' => false]);

        $this->actingAs($this->admin)
            ->getJson('/api/v1/units?is_active=true')
            ->assertStatus(200)
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.code', 'ACT');
    }

    public function test_filter_by_is_active_false(): void
    {
        Unit::create(['code' => 'ACT', 'name' => 'Aktif', 'symbol' => 'a', 'is_active' => true]);
        Unit::create(['code' => 'INA', 'name' => 'Nonaktif', 'symbol' => 'n', 'is_active' => false]);

        $this->actingAs($this->admin)
            ->getJson('/api/v1/units?is_active=false')
            ->assertStatus(200)
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.code', 'INA');
    }

    public function test_allowed_sort_by_name_asc(): void
    {
        Unit::create(['code' => 'ZZZ', 'name' => 'Zulkifli', 'symbol' => 'z']);
        Unit::create(['code' => 'AAA', 'name' => 'Angin', 'symbol' => 'a']);

        $response = $this->actingAs($this->admin)
            ->getJson('/api/v1/units?sort_by=name&sort_order=asc')
            ->assertStatus(200);

        $this->assertEquals('Angin', $response->json('data.0.name'));
    }

    public function test_invalid_sort_column_falls_back_to_default(): void
    {
        $this->actingAs($this->admin)
            ->getJson('/api/v1/units?sort_by=password&sort_order=asc')
            ->assertStatus(200);
    }

    // ─── Show / Detail ───────────────────────────────────────────────────────

    public function test_admin_can_view_unit_detail(): void
    {
        $unit = Unit::create(['code' => 'PCS', 'name' => 'Pieces', 'symbol' => 'pcs']);

        $this->actingAs($this->admin)
            ->getJson("/api/v1/units/{$unit->id}")
            ->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => ['code' => 'PCS', 'symbol' => 'pcs'],
            ]);
    }

    // ─── Response format ─────────────────────────────────────────────────────

    public function test_api_response_contains_standard_fields(): void
    {
        $this->actingAs($this->admin)
            ->postJson('/api/v1/units', ['code' => 'PCS', 'name' => 'Pieces', 'symbol' => 'pcs'])
            ->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => ['id', 'code', 'name', 'symbol', 'is_active', 'created_at', 'updated_at'],
            ]);
    }
}
