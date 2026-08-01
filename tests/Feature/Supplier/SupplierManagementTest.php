<?php

namespace Tests\Feature\Supplier;

use App\Features\Auth\Enums\PermissionCode;
use App\Features\Auth\Enums\RoleCode;
use App\Features\Auth\Models\Permission;
use App\Features\Auth\Models\Role;
use App\Features\Auth\Models\User;
use App\Features\Supplier\Models\Supplier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupplierManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $viewerUser;

    protected User $regularUser;

    protected function setUp(): void
    {
        parent::setUp();

        $adminRole = Role::create([
            'code' => RoleCode::ADMIN->value,
            'name' => 'Administrator',
        ]);

        $viewPerm = Permission::create([
            'code' => PermissionCode::SUPPLIERS_VIEW->value,
            'name' => 'Melihat Supplier',
            'group' => 'suppliers',
        ]);
        $createPerm = Permission::create([
            'code' => PermissionCode::SUPPLIERS_CREATE->value,
            'name' => 'Membuat Supplier',
            'group' => 'suppliers',
        ]);
        $updatePerm = Permission::create([
            'code' => PermissionCode::SUPPLIERS_UPDATE->value,
            'name' => 'Mengubah Supplier',
            'group' => 'suppliers',
        ]);
        $statusPerm = Permission::create([
            'code' => PermissionCode::SUPPLIERS_CHANGE_STATUS->value,
            'name' => 'Mengubah Status Supplier',
            'group' => 'suppliers',
        ]);

        $adminRole->permissions()->attach([$viewPerm->id, $createPerm->id, $updatePerm->id, $statusPerm->id]);

        $this->admin = User::factory()->create(['is_active' => true]);
        $this->admin->roles()->attach($adminRole->id);

        $viewerRole = Role::create([
            'code' => RoleCode::WAREHOUSE_OFFICER->value,
            'name' => 'Petugas Gudang',
        ]);
        $viewerRole->permissions()->attach($viewPerm->id);

        $this->viewerUser = User::factory()->create(['is_active' => true]);
        $this->viewerUser->roles()->attach($viewerRole->id);

        $this->regularUser = User::factory()->create(['is_active' => true]);
    }

    public function test_unauthenticated_cannot_access_suppliers(): void
    {
        $this->getJson('/api/v1/suppliers')->assertStatus(401);
    }

    public function test_user_without_permission_cannot_list_suppliers(): void
    {
        $this->actingAs($this->regularUser)
            ->getJson('/api/v1/suppliers')
            ->assertStatus(403);
    }

    public function test_viewer_can_list_suppliers(): void
    {
        Supplier::factory()->count(3)->create();

        $response = $this->actingAs($this->viewerUser)
            ->getJson('/api/v1/suppliers');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data',
                'meta' => ['current_page', 'last_page', 'per_page', 'total'],
            ]);
    }

    public function test_viewer_can_see_sensitive_data(): void
    {
        $supplier = Supplier::factory()->create([
            'contact_person' => 'John Doe',
            'email' => 'john@example.com',
        ]);

        $response = $this->actingAs($this->viewerUser)
            ->getJson("/api/v1/suppliers/{$supplier->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.contact_person', 'John Doe')
            ->assertJsonPath('data.email', 'john@example.com');
    }

    public function test_user_without_create_permission_cannot_create(): void
    {
        $this->actingAs($this->viewerUser)
            ->postJson('/api/v1/suppliers', ['code' => 'SUP-001', 'name' => 'Test'])
            ->assertStatus(403);
    }

    public function test_admin_can_create_supplier(): void
    {
        $response = $this->actingAs($this->admin)
            ->postJson('/api/v1/suppliers', [
                'code' => 'sup-001',
                'name' => 'PT Maju Jaya',
                'contact_person' => 'Budi',
                'phone' => '08123456789',
                'email' => 'budi@example.com',
                'tax_number' => '00.000.000.0-000.000',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.code', 'SUP-001')
            ->assertJsonPath('data.name', 'PT Maju Jaya')
            ->assertJsonPath('data.is_active', true);

        $this->assertDatabaseHas('suppliers', [
            'code' => 'SUP-001',
            'name' => 'PT Maju Jaya',
            'created_by' => $this->admin->id,
        ]);
    }

    public function test_code_is_uppercased_automatically(): void
    {
        $this->actingAs($this->admin)
            ->postJson('/api/v1/suppliers', ['code' => 'sup-auto', 'name' => 'Test Supplier']);

        $this->assertDatabaseHas('suppliers', ['code' => 'SUP-AUTO']);
    }

    public function test_code_must_be_unique(): void
    {
        Supplier::factory()->create(['code' => 'SUP-001']);

        $this->actingAs($this->admin)
            ->postJson('/api/v1/suppliers', ['code' => 'SUP-001', 'name' => 'Lainnya'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['code']);
    }

    public function test_email_must_be_valid_if_provided(): void
    {
        $this->actingAs($this->admin)
            ->postJson('/api/v1/suppliers', [
                'code' => 'SUP-X',
                'name' => 'Test',
                'email' => 'bukan-email',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_admin_can_update_supplier(): void
    {
        $supplier = Supplier::factory()->create();

        $response = $this->actingAs($this->admin)
            ->putJson("/api/v1/suppliers/{$supplier->id}", [
                'code' => $supplier->code,
                'name' => 'Nama Baru',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.name', 'Nama Baru');

        $this->assertDatabaseHas('suppliers', [
            'id' => $supplier->id,
            'name' => 'Nama Baru',
            'updated_by' => $this->admin->id,
        ]);
    }

    public function test_admin_can_change_supplier_status(): void
    {
        $supplier = Supplier::factory()->create(['is_active' => true]);

        $this->actingAs($this->admin)
            ->patchJson("/api/v1/suppliers/{$supplier->id}/status", ['is_active' => false])
            ->assertStatus(200)
            ->assertJsonPath('data.is_active', false);

        $this->assertDatabaseHas('suppliers', ['id' => $supplier->id, 'is_active' => false]);
    }

    public function test_supplier_is_not_permanently_deleted(): void
    {
        $supplier = Supplier::factory()->create();

        // Tidak ada route DELETE untuk supplier
        $this->actingAs($this->admin)
            ->deleteJson("/api/v1/suppliers/{$supplier->id}")
            ->assertStatus(405); // Method Not Allowed
    }

    public function test_paginate_with_search_filter(): void
    {
        Supplier::factory()->create(['name' => 'PT ABC', 'code' => 'ABC-001']);
        Supplier::factory()->create(['name' => 'PT XYZ', 'code' => 'XYZ-001']);

        $response = $this->actingAs($this->viewerUser)
            ->getJson('/api/v1/suppliers?search=ABC&per_page=10');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
    }

    public function test_filter_by_status(): void
    {
        Supplier::factory()->create(['is_active' => true]);
        Supplier::factory()->inactive()->create();

        $response = $this->actingAs($this->viewerUser)
            ->getJson('/api/v1/suppliers?is_active=false');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
    }
}
