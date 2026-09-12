<?php

namespace Tests\Feature\Category;

use App\Features\Auth\Enums\PermissionCode;
use App\Features\Auth\Enums\RoleCode;
use App\Features\Auth\Models\Permission;
use App\Features\Auth\Models\Role;
use App\Features\Auth\Models\User;
use App\Features\Category\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $viewer;

    protected User $noAccessUser;

    protected function setUp(): void
    {
        parent::setUp();

        // Role dengan semua category permissions
        $adminRole = Role::firstOrCreate(
            ['code' => RoleCode::ADMIN->value],
            ['name' => 'Administrator']
        );
        $permissions = [
            PermissionCode::CATEGORIES_VIEW->value => 'Melihat Kategori',
            PermissionCode::CATEGORIES_CREATE->value => 'Membuat Kategori',
            PermissionCode::CATEGORIES_UPDATE->value => 'Mengubah Kategori',
            PermissionCode::CATEGORIES_CHANGE_STATUS->value => 'Mengubah Status Kategori',
        ];
        foreach ($permissions as $code => $name) {
            $p = Permission::firstOrCreate(['code' => $code], ['name' => $name, 'group' => 'categories']);
            $adminRole->permissions()->syncWithoutDetaching([$p->id]);
        }

        // Role hanya view (warehouse officer hanya dapat categories.view)
        $viewerRole = Role::firstOrCreate(
            ['code' => RoleCode::WAREHOUSE_OFFICER->value],
            ['name' => 'Petugas Gudang']
        );
        $viewPerm = Permission::where('code', PermissionCode::CATEGORIES_VIEW->value)->first();
        $viewerRole->permissions()->syncWithoutDetaching([$viewPerm->id]);

        $this->admin = User::factory()->create(['is_active' => true]);
        $this->admin->roles()->attach($adminRole->id);

        $this->viewer = User::factory()->create(['is_active' => true]);
        $this->viewer->roles()->attach($viewerRole->id);

        $this->noAccessUser = User::factory()->create(['is_active' => true]);
    }

    // ─── 401 / 403 ───────────────────────────────────────────────────────────

    public function test_unauthenticated_user_cannot_access_categories(): void
    {
        $this->getJson('/api/v1/categories')->assertStatus(401);
    }

    public function test_user_without_permission_cannot_create_category(): void
    {
        $this->actingAs($this->noAccessUser)
            ->postJson('/api/v1/categories', ['code' => 'CAT-01', 'name' => 'Elektronik'])
            ->assertStatus(403);
    }

    public function test_user_without_permission_cannot_update_category(): void
    {
        $category = Category::create(['code' => 'CAT-01', 'name' => 'Elektronik']);

        $this->actingAs($this->noAccessUser)
            ->putJson("/api/v1/categories/{$category->id}", ['code' => 'CAT-01', 'name' => 'New'])
            ->assertStatus(403);
    }

    public function test_user_without_permission_cannot_change_status(): void
    {
        $category = Category::create(['code' => 'CAT-01', 'name' => 'Elektronik']);

        $this->actingAs($this->noAccessUser)
            ->patchJson("/api/v1/categories/{$category->id}/status", ['is_active' => false])
            ->assertStatus(403);
    }

    public function test_viewer_can_list_categories(): void
    {
        Category::create(['code' => 'CAT-01', 'name' => 'Elektronik']);

        $this->actingAs($this->viewer)
            ->getJson('/api/v1/categories')
            ->assertStatus(200)
            ->assertJsonStructure(['success', 'data']);
    }

    public function test_viewer_cannot_create_category(): void
    {
        $this->actingAs($this->viewer)
            ->postJson('/api/v1/categories', ['code' => 'CAT-01', 'name' => 'Elektronik'])
            ->assertStatus(403);
    }

    // ─── Create ──────────────────────────────────────────────────────────────

    public function test_admin_can_create_category(): void
    {
        $response = $this->actingAs($this->admin)
            ->postJson('/api/v1/categories', [
                'code' => 'cat-01',
                'name' => 'Elektronik',
                'description' => 'Kategori barang elektronik',
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'code' => 'CAT-01',
                    'name' => 'Elektronik',
                    'is_active' => true,
                ],
            ]);

        $this->assertDatabaseHas('categories', [
            'code' => 'CAT-01',
            'name' => 'Elektronik',
            'created_by' => $this->admin->id,
        ]);
    }

    public function test_category_code_is_normalized_to_uppercase(): void
    {
        $this->actingAs($this->admin)
            ->postJson('/api/v1/categories', ['code' => 'cat-new', 'name' => 'Test'])
            ->assertStatus(201)
            ->assertJsonPath('data.code', 'CAT-NEW');

        $this->assertDatabaseHas('categories', ['code' => 'CAT-NEW']);
    }

    public function test_category_code_must_be_unique(): void
    {
        Category::create(['code' => 'CAT-01', 'name' => 'Elektronik']);

        $this->actingAs($this->admin)
            ->postJson('/api/v1/categories', ['code' => 'CAT-01', 'name' => 'Lainnya'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['code']);
    }

    public function test_category_code_is_case_insensitive_unique(): void
    {
        Category::create(['code' => 'CAT-01', 'name' => 'Elektronik']);

        $this->actingAs($this->admin)
            ->postJson('/api/v1/categories', ['code' => 'cat-01', 'name' => 'Lainnya'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['code']);
    }

    public function test_category_name_is_required(): void
    {
        $this->actingAs($this->admin)
            ->postJson('/api/v1/categories', ['code' => 'CAT-01'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_category_code_is_required(): void
    {
        $this->actingAs($this->admin)
            ->postJson('/api/v1/categories', ['name' => 'Elektronik'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['code']);
    }

    // ─── Update ──────────────────────────────────────────────────────────────

    public function test_admin_can_update_category(): void
    {
        $category = Category::create(['code' => 'CAT-01', 'name' => 'Elektronik']);

        $this->actingAs($this->admin)
            ->putJson("/api/v1/categories/{$category->id}", [
                'code' => 'cat-updated',
                'name' => 'Elektronik Update',
            ])
            ->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'code' => 'CAT-UPDATED',
                    'name' => 'Elektronik Update',
                ],
            ]);

        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'updated_by' => $this->admin->id,
        ]);
    }

    public function test_update_code_unique_ignores_self(): void
    {
        $category = Category::create(['code' => 'CAT-01', 'name' => 'Elektronik']);

        $this->actingAs($this->admin)
            ->putJson("/api/v1/categories/{$category->id}", [
                'code' => 'CAT-01',
                'name' => 'Elektronik Diperbarui',
            ])
            ->assertStatus(200);
    }

    public function test_update_code_must_be_unique_across_others(): void
    {
        $cat1 = Category::create(['code' => 'CAT-01', 'name' => 'Elektronik']);
        $cat2 = Category::create(['code' => 'CAT-02', 'name' => 'Furnitur']);

        $this->actingAs($this->admin)
            ->putJson("/api/v1/categories/{$cat2->id}", [
                'code' => 'CAT-01',
                'name' => 'Furnitur Baru',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['code']);
    }

    // ─── Status Change ───────────────────────────────────────────────────────

    public function test_admin_can_deactivate_category(): void
    {
        $category = Category::create(['code' => 'CAT-01', 'name' => 'Elektronik', 'is_active' => true]);

        $this->actingAs($this->admin)
            ->patchJson("/api/v1/categories/{$category->id}/status", ['is_active' => false])
            ->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => ['is_active' => false],
            ]);

        $this->assertDatabaseHas('categories', ['id' => $category->id, 'is_active' => false]);
    }

    public function test_admin_can_activate_category(): void
    {
        $category = Category::create(['code' => 'CAT-01', 'name' => 'Elektronik', 'is_active' => false]);

        $this->actingAs($this->admin)
            ->patchJson("/api/v1/categories/{$category->id}/status", ['is_active' => true])
            ->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => ['is_active' => true],
            ]);
    }

    public function test_status_change_requires_is_active_field(): void
    {
        $category = Category::create(['code' => 'CAT-01', 'name' => 'Elektronik']);

        $this->actingAs($this->admin)
            ->patchJson("/api/v1/categories/{$category->id}/status", [])
            ->assertStatus(422);
    }

    // ─── List / Filter / Search / Pagination / Sort ──────────────────────────

    public function test_admin_can_list_categories_with_pagination(): void
    {
        Category::factory()->count(20)->create();

        $response = $this->actingAs($this->admin)
            ->getJson('/api/v1/categories?per_page=5')
            ->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'meta' => ['current_page', 'last_page', 'per_page', 'total'],
            ]);

        $this->assertCount(5, $response->json('data'));
    }

    public function test_search_by_name_works(): void
    {
        Category::create(['code' => 'ELE', 'name' => 'Elektronik']);
        Category::create(['code' => 'FUR', 'name' => 'Furnitur']);

        $this->actingAs($this->admin)
            ->getJson('/api/v1/categories?search=elektro')
            ->assertStatus(200)
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.name', 'Elektronik');
    }

    public function test_search_by_code_works(): void
    {
        Category::create(['code' => 'ELE', 'name' => 'Elektronik']);
        Category::create(['code' => 'FUR', 'name' => 'Furnitur']);

        $this->actingAs($this->admin)
            ->getJson('/api/v1/categories?search=FUR')
            ->assertStatus(200)
            ->assertJsonPath('meta.total', 1);
    }

    public function test_filter_by_is_active_true(): void
    {
        Category::create(['code' => 'ACT', 'name' => 'Aktif', 'is_active' => true]);
        Category::create(['code' => 'INA', 'name' => 'Nonaktif', 'is_active' => false]);

        $this->actingAs($this->admin)
            ->getJson('/api/v1/categories?is_active=true')
            ->assertStatus(200)
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.code', 'ACT');
    }

    public function test_filter_by_is_active_false(): void
    {
        Category::create(['code' => 'ACT', 'name' => 'Aktif', 'is_active' => true]);
        Category::create(['code' => 'INA', 'name' => 'Nonaktif', 'is_active' => false]);

        $this->actingAs($this->admin)
            ->getJson('/api/v1/categories?is_active=false')
            ->assertStatus(200)
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.code', 'INA');
    }

    public function test_allowed_sort_by_name_asc(): void
    {
        Category::create(['code' => 'ZZZ', 'name' => 'Zulkifli']);
        Category::create(['code' => 'AAA', 'name' => 'Angin']);

        $response = $this->actingAs($this->admin)
            ->getJson('/api/v1/categories?sort_by=name&sort_order=asc')
            ->assertStatus(200);

        $this->assertEquals('Angin', $response->json('data.0.name'));
    }

    public function test_invalid_sort_column_falls_back_to_default(): void
    {
        // Sort tidak valid tidak boleh menghasilkan SQL error
        $this->actingAs($this->admin)
            ->getJson('/api/v1/categories?sort_by=password&sort_order=asc')
            ->assertStatus(200);
    }

    // ─── Show / Detail ───────────────────────────────────────────────────────

    public function test_admin_can_view_category_detail(): void
    {
        $category = Category::create(['code' => 'CAT-01', 'name' => 'Elektronik']);

        $this->actingAs($this->admin)
            ->getJson("/api/v1/categories/{$category->id}")
            ->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => ['code' => 'CAT-01'],
            ]);
    }

    // ─── Response format ─────────────────────────────────────────────────────

    public function test_api_response_contains_standard_fields(): void
    {
        $this->actingAs($this->admin)
            ->postJson('/api/v1/categories', ['code' => 'CAT-01', 'name' => 'Elektronik'])
            ->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => ['id', 'code', 'name', 'is_active', 'created_at', 'updated_at'],
            ]);
    }
}
