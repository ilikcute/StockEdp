<?php

namespace Tests\Feature\Audit;

use App\Features\Audit\Models\ActivityLog;
use App\Features\Audit\Services\ActivityLogger;
use App\Features\Auth\Enums\PermissionCode;
use App\Features\Auth\Enums\RoleCode;
use App\Features\Auth\Models\Permission;
use App\Features\Auth\Models\Role;
use App\Features\Auth\Models\User;
use App\Features\Product\Models\Product;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditLogTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $regularStaff;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);

        $this->admin = User::factory()->create(['username' => 'admin_audit']);
        $adminRole = Role::where('code', RoleCode::ADMIN->value)->first();
        $this->admin->roles()->attach($adminRole->id);

        $this->regularStaff = User::factory()->create(['username' => 'staff_no_audit']);
        $staffRole = Role::where('code', RoleCode::WAREHOUSE_OFFICER->value)->first();
        $this->regularStaff->roles()->attach($staffRole->id);
    }

    public function test_unauthenticated_user_cannot_access_audit_logs()
    {
        $this->getJson('/api/v1/audit-logs')->assertStatus(401);
        $this->getJson('/api/v1/audit-logs/modules')->assertStatus(401);
    }

    public function test_user_without_permission_receives_forbidden()
    {
        $this->actingAs($this->regularStaff)
            ->getJson('/api/v1/audit-logs')
            ->assertStatus(403);

        $this->actingAs($this->regularStaff)
            ->getJson('/api/v1/audit-logs/modules')
            ->assertStatus(403);
    }

    public function test_user_with_permission_can_list_audit_logs()
    {
        ActivityLog::create([
            'user_id' => $this->admin->id,
            'module' => 'auth',
            'action' => 'login',
            'description' => 'User admin_audit berhasil login ke dalam sistem.',
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit Test Browser',
            'created_at' => now(),
        ]);

        $response = $this->actingAs($this->admin)->getJson('/api/v1/audit-logs');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'message',
            'data' => [
                '*' => [
                    'id',
                    'user_id',
                    'module',
                    'action',
                    'description',
                    'subject_type',
                    'subject_id',
                    'properties',
                    'ip_address',
                    'user_agent',
                    'user' => [
                        'id',
                        'name',
                        'username',
                    ],
                    'created_at',
                ],
            ],
            'meta' => [
                'current_page',
                'last_page',
                'per_page',
                'total',
            ],
        ]);

        $this->assertCount(1, $response->json('data'));
        $this->assertSame('auth', $response->json('data.0.module'));
        $this->assertSame('login', $response->json('data.0.action'));
        $this->assertSame($this->admin->username, $response->json('data.0.user.username'));
    }

    public function test_audit_logs_can_be_filtered_by_module_and_action()
    {
        ActivityLog::create([
            'user_id' => $this->admin->id,
            'module' => 'auth',
            'action' => 'login',
            'description' => 'Login admin',
            'created_at' => now()->subMinutes(10),
        ]);

        ActivityLog::create([
            'user_id' => $this->admin->id,
            'module' => 'month_end',
            'action' => 'close',
            'description' => 'Tutup buku periode 2026-08',
            'created_at' => now()->subMinutes(5),
        ]);

        ActivityLog::create([
            'user_id' => $this->admin->id,
            'module' => 'month_end',
            'action' => 'reopen',
            'description' => 'Buka kembali periode 2026-08',
            'created_at' => now(),
        ]);

        // Filter by module
        $resModule = $this->actingAs($this->admin)->getJson('/api/v1/audit-logs?module=month_end');
        $resModule->assertStatus(200);
        $this->assertCount(2, $resModule->json('data'));

        // Filter by module and action
        $resAction = $this->actingAs($this->admin)->getJson('/api/v1/audit-logs?module=month_end&action=reopen');
        $resAction->assertStatus(200);
        $this->assertCount(1, $resAction->json('data'));
        $this->assertSame('reopen', $resAction->json('data.0.action'));
    }

    public function test_audit_logs_can_be_filtered_by_user_id()
    {
        $anotherUser = User::factory()->create(['username' => 'another_actor']);

        ActivityLog::create([
            'user_id' => $this->admin->id,
            'module' => 'users',
            'action' => 'create',
            'description' => 'Menambahkan user baru',
            'created_at' => now()->subMinutes(5),
        ]);

        ActivityLog::create([
            'user_id' => $anotherUser->id,
            'module' => 'products',
            'action' => 'update',
            'description' => 'Mengubah harga produk',
            'created_at' => now(),
        ]);

        $res = $this->actingAs($this->admin)->getJson('/api/v1/audit-logs?user_id=' . $anotherUser->id);
        $res->assertStatus(200);
        $this->assertCount(1, $res->json('data'));
        $this->assertSame($anotherUser->id, $res->json('data.0.user_id'));
    }

    public function test_audit_logs_can_be_filtered_by_search_keyword()
    {
        ActivityLog::create([
            'user_id' => $this->admin->id,
            'module' => 'store_allocations',
            'action' => 'post',
            'description' => 'Memposting alokasi penggantian unit router RB750Gr3',
            'created_at' => now(),
        ]);

        ActivityLog::create([
            'user_id' => $this->admin->id,
            'module' => 'products',
            'action' => 'create',
            'description' => 'Membuat produk baru kabel optik',
            'created_at' => now(),
        ]);

        $res = $this->actingAs($this->admin)->getJson('/api/v1/audit-logs?search=RB750Gr3');
        $res->assertStatus(200);
        $this->assertCount(1, $res->json('data'));
        $this->assertStringContainsString('RB750Gr3', $res->json('data.0.description'));
    }

    public function test_audit_logs_can_be_filtered_by_date_range()
    {
        ActivityLog::create([
            'user_id' => $this->admin->id,
            'module' => 'auth',
            'action' => 'login',
            'description' => 'Login awal bulan',
            'created_at' => '2026-09-01 08:00:00',
        ]);

        ActivityLog::create([
            'user_id' => $this->admin->id,
            'module' => 'auth',
            'action' => 'login',
            'description' => 'Login pertengahan bulan',
            'created_at' => '2026-09-15 08:00:00',
        ]);

        ActivityLog::create([
            'user_id' => $this->admin->id,
            'module' => 'auth',
            'action' => 'login',
            'description' => 'Login akhir bulan',
            'created_at' => '2026-09-30 08:00:00',
        ]);

        $res = $this->actingAs($this->admin)->getJson('/api/v1/audit-logs?date_from=2026-09-10&date_to=2026-09-20');
        $res->assertStatus(200);
        $this->assertCount(1, $res->json('data'));
        $this->assertSame('Login pertengahan bulan', $res->json('data.0.description'));
    }

    public function test_audit_logs_pagination_works_correctly()
    {
        for ($i = 1; $i <= 30; $i++) {
            ActivityLog::create([
                'user_id' => $this->admin->id,
                'module' => 'users',
                'action' => 'update',
                'description' => "Update user {$i}",
                'created_at' => now()->subMinutes(35 - $i),
            ]);
        }

        $res = $this->actingAs($this->admin)->getJson('/api/v1/audit-logs?per_page=10&page=2');
        $res->assertStatus(200);
        $this->assertSame(10, $res->json('meta.per_page'));
        $this->assertSame(2, $res->json('meta.current_page'));
        $this->assertSame(30, $res->json('meta.total'));
        $this->assertSame(3, $res->json('meta.last_page'));
        $this->assertCount(10, $res->json('data'));
    }

    public function test_modules_endpoint_returns_distinct_modules_with_labels()
    {
        ActivityLog::create([
            'module' => 'month_end',
            'action' => 'close',
            'created_at' => now(),
        ]);

        ActivityLog::create([
            'module' => 'auth',
            'action' => 'login',
            'created_at' => now(),
        ]);

        $res = $this->actingAs($this->admin)->getJson('/api/v1/audit-logs/modules');
        $res->assertStatus(200);

        $modules = $res->json('data');
        $this->assertIsArray($modules);

        $values = array_column($modules, 'value');
        $this->assertContains('auth', $values);
        $this->assertContains('month_end', $values);

        $labels = array_column($modules, 'label', 'value');
        $this->assertSame('Autentikasi', $labels['auth']);
        $this->assertSame('Tutup Buku Bulanan', $labels['month_end']);
    }

    public function test_activity_logger_records_activity_and_subject()
    {
        $logger = app(ActivityLogger::class);

        $product = Product::factory()->create(['name' => 'Switch Gigabit 24 Port']);

        $log = $logger->record(
            module: 'products',
            action: 'update_price',
            description: 'Penyesuaian harga jual produk',
            subject: $product,
            properties: ['old_price' => 1500000, 'new_price' => 1750000],
            userId: $this->admin->id
        );

        $this->assertInstanceOf(ActivityLog::class, $log);
        $this->assertDatabaseHas('activity_logs', [
            'id' => $log->id,
            'module' => 'products',
            'action' => 'update_price',
            'user_id' => $this->admin->id,
            'subject_type' => Product::class,
            'subject_id' => $product->id,
        ]);

        $freshLog = ActivityLog::find($log->id);
        $this->assertIsArray($freshLog->properties);
        $this->assertSame(1500000, $freshLog->properties['old_price']);
        $this->assertSame(1750000, $freshLog->properties['new_price']);
        $this->assertSame($this->admin->id, $freshLog->user->id);
    }

    public function test_user_service_mutations_create_activity_logs()
    {
        $userService = app(\App\Features\User\Services\UserService::class);

        // 1. Create user
        $newUser = $userService->createUser([
            'name' => 'Audit Test User',
            'username' => 'audit_user',
            'email' => 'audit_user@example.com',
            'password' => 'password123',
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('activity_logs', [
            'module' => 'users',
            'action' => 'create',
            'subject_id' => $newUser->id,
        ]);

        // 2. Update user
        $userService->updateUser(
            $newUser,
            ['name' => 'Updated Audit User', 'username' => 'audit_user', 'email' => 'audit_user@example.com'],
            $this->admin
        );

        $this->assertDatabaseHas('activity_logs', [
            'module' => 'users',
            'action' => 'update',
            'subject_id' => $newUser->id,
            'user_id' => $this->admin->id,
        ]);

        // 3. Change user status
        $userService->updateUserStatus($newUser, false, $this->admin);

        $this->assertDatabaseHas('activity_logs', [
            'module' => 'users',
            'action' => 'change_status',
            'subject_id' => $newUser->id,
            'user_id' => $this->admin->id,
        ]);
    }

    public function test_auth_login_records_activity_log()
    {
        $user = User::factory()->create([
            'username' => 'login_actor',
            'password' => bcrypt('secret123'),
            'is_active' => true,
        ]);

        $action = app(\App\Features\Auth\Actions\AuthenticateUserAction::class);
        $action->execute('login_actor', 'secret123');

        $this->assertDatabaseHas('activity_logs', [
            'module' => 'auth',
            'action' => 'login',
            'user_id' => $user->id,
            'subject_id' => $user->id,
        ]);
    }

    public function test_month_end_reopen_records_activity_log_with_reason()
    {
        $period = \App\Features\MonthEnd\Models\InventoryPeriod::create([
            'period_key' => '2026-07',
            'year' => 2026,
            'month' => 7,
            'start_date' => '2026-07-01',
            'end_date' => '2026-07-31',
            'status' => \App\Features\MonthEnd\Enums\PeriodStatus::CLOSED,
            'closed_at' => now(),
            'closed_by' => $this->admin->id,
        ]);

        $reopenAction = app(\App\Features\MonthEnd\Actions\ReopenInventoryPeriodAction::class);
        $reopenAction->execute($period->id, $this->admin->id, 'Revisi selisih stok gudang cabang');

        $this->assertDatabaseHas('activity_logs', [
            'module' => 'month_end',
            'action' => 'reopen',
            'user_id' => $this->admin->id,
            'subject_id' => $period->id,
        ]);

        $log = ActivityLog::where('module', 'month_end')->where('action', 'reopen')->first();
        $this->assertNotNull($log);
        $this->assertStringContainsString('Revisi selisih', $log->description);
        $this->assertSame('Revisi selisih stok gudang cabang', $log->properties['reason']);
    }
}
