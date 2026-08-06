<?php

namespace Tests\Feature\Security;

use App\Features\Auth\Enums\RoleCode;
use App\Features\Auth\Models\Role;
use App\Features\Auth\Models\User;
use App\Features\Category\Models\Category;
use App\Features\Inventory\Actions\CreateStockIssueAction;
use App\Features\Inventory\Actions\CreateStockReceiptAction;
use App\Features\Inventory\Actions\PostStockIssueAction;
use App\Features\Inventory\Actions\PostStockReceiptAction;
use App\Features\Location\Models\Location;
use App\Features\Product\Models\Product;
use App\Features\Supplier\Models\Supplier;
use App\Features\Unit\Models\Unit;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Tests\TestCase;

class RateLimitingTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_access_api_under_limit(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/auth/me');

        $response->assertStatus(200);
    }

    public function test_authenticated_user_throttled_when_exceeding_limit(): void
    {
        $user = User::factory()->create();

        for ($i = 0; $i < 60; $i++) {
            $this->actingAs($user, 'sanctum')
                ->getJson('/api/v1/auth/me');
        }

        $throttledResponse = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/auth/me');

        $throttledResponse->assertStatus(429);
        $throttledResponse->assertJson([
            'success' => false,
            'message' => 'Terlalu banyak permintaan. Silakan coba kembali nanti.',
        ]);
    }

    public function test_rate_limited_response_preserves_retry_after_header(): void
    {
        $user = User::factory()->create();

        for ($i = 0; $i < 60; $i++) {
            $this->actingAs($user, 'sanctum')
                ->getJson('/api/v1/auth/me');
        }

        $throttledResponse = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/auth/me');

        $throttledResponse->assertStatus(429);
        $throttledResponse->assertHeader('Content-Type', 'application/json');
        $this->assertNotNull($throttledResponse->headers->get('Retry-After'));
        $this->assertGreaterThan(0, (int) $throttledResponse->headers->get('Retry-After'));
    }

    public function test_numeric_throttle_middleware_preserves_headers(): void
    {
        Route::middleware(['api', 'throttle:2,1'])
            ->get('/api/v1/_test/numeric-throttle', fn () => response()->json(['ok' => true]));

        $res1 = $this->getJson('/api/v1/_test/numeric-throttle');
        $res1->assertStatus(200);

        $res2 = $this->getJson('/api/v1/_test/numeric-throttle');
        $res2->assertStatus(200);

        $res3 = $this->getJson('/api/v1/_test/numeric-throttle');
        $res3->assertStatus(429);
        $res3->assertHeader('Content-Type', 'application/json');
        $this->assertNotNull($res3->headers->get('Retry-After'));
        $this->assertNotNull($res3->headers->get('X-RateLimit-Limit'));
        $this->assertNotNull($res3->headers->get('X-RateLimit-Remaining'));
        $this->assertEquals('2', $res3->headers->get('X-RateLimit-Limit'));
        $this->assertEquals('0', $res3->headers->get('X-RateLimit-Remaining'));
    }

    public function test_rate_limiter_isolated_per_user(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        for ($i = 0; $i < 60; $i++) {
            $this->actingAs($user1, 'sanctum')
                ->getJson('/api/v1/auth/me');
        }

        $user1Response = $this->actingAs($user1, 'sanctum')
            ->getJson('/api/v1/auth/me');
        $user1Response->assertStatus(429);

        $user2Response = $this->actingAs($user2, 'sanctum')
            ->getJson('/api/v1/auth/me');
        $user2Response->assertStatus(200);
    }

    public function test_guest_ip_limiter_fallback(): void
    {
        Route::middleware(['api', 'throttle:api'])
            ->get('/api/v1/_test/rate-limit-guest', fn () => response()->json(['ok' => true]));

        for ($i = 0; $i < 60; $i++) {
            $this->withServerVariables(['REMOTE_ADDR' => '192.168.1.100'])
                ->getJson('/api/v1/_test/rate-limit-guest');
        }

        $ip1Response = $this->withServerVariables(['REMOTE_ADDR' => '192.168.1.100'])
            ->getJson('/api/v1/_test/rate-limit-guest');
        $ip1Response->assertStatus(429);

        $ip2Response = $this->withServerVariables(['REMOTE_ADDR' => '192.168.1.101'])
            ->getJson('/api/v1/_test/rate-limit-guest');
        $ip2Response->assertStatus(200);
    }

    public function test_login_rate_limiter_retains_five_attempts(): void
    {
        $login = 'target@example.com';
        $ip = '127.0.0.1';
        $throttleKey = Str::transliterate(Str::lower($login).'|'.$ip);

        RateLimiter::clear($throttleKey);
        Event::fake([Lockout::class]);

        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/v1/auth/login', [
                'login' => $login,
                'password' => 'wrong',
            ]);
        }

        $this->assertTrue(RateLimiter::tooManyAttempts($throttleKey, 5));

        $response = $this->postJson('/api/v1/auth/login', [
            'login' => $login,
            'password' => 'wrong',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['login']);
        Event::assertDispatched(Lockout::class);
    }

    public function test_unauthenticated_response_remains_401(): void
    {
        $response = $this->getJson('/api/v1/auth/me');

        $response->assertStatus(401);
        $response->assertJson([
            'success' => false,
            'message' => 'Unauthenticated.',
        ]);
    }

    public function test_forbidden_response_remains_403(): void
    {
        $userWithoutPermission = User::factory()->create(['is_active' => true]);

        $response = $this->actingAs($userWithoutPermission, 'sanctum')
            ->postJson('/api/v1/units', [
                'name' => 'Gram',
                'code' => 'GRM',
                'symbol' => 'g',
            ]);

        $response->assertStatus(403);
    }

    public function test_business_conflict_response_remains_409(): void
    {
        $this->seed(RoleAndPermissionSeeder::class);

        $admin = User::factory()->create(['is_active' => true]);
        $adminRole = Role::where('code', RoleCode::ADMIN->value)->first();
        $admin->roles()->attach($adminRole->id);

        $loc = Location::create(['name' => 'Gudang Conflict', 'code' => 'LOC-CF', 'is_active' => true]);
        $admin->locations()->attach($loc->id);

        $cat = Category::create(['name' => 'Cat', 'code' => 'CAT', 'is_active' => true]);
        $unit = Unit::create(['name' => 'Box', 'code' => 'BOX', 'symbol' => 'box', 'is_active' => true]);
        $prod = Product::create(['name' => 'Prod', 'sku' => 'SKU-CF', 'category_id' => $cat->id, 'unit_id' => $unit->id, 'is_active' => true, 'minimum_stock' => '0.0000']);
        $supplier = Supplier::create(['name' => 'Sup', 'code' => 'SUP-CF', 'is_active' => true]);

        $receipt = app(CreateStockReceiptAction::class)->execute([
            'supplier_id' => $supplier->id,
            'date' => now()->toDateString(),
            'notes' => 'Awal',
            'items' => [['product_id' => $prod->id, 'location_id' => $loc->id, 'quantity' => '100.0000']],
        ], $admin->id);
        app(PostStockReceiptAction::class)->execute($receipt, $admin->id);

        $issue = app(CreateStockIssueAction::class)->execute([
            'purpose' => 'Post Conflict',
            'date' => now()->toDateString(),
            'notes' => 'Conflict',
            'items' => [['product_id' => $prod->id, 'location_id' => $loc->id, 'quantity' => '10.0000']],
        ], $admin->id);
        app(PostStockIssueAction::class)->execute($issue, $admin->id);

        // Attempt second post on already POSTED issue -> HTTP 409 Conflict
        $response = $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/stock-issues/{$issue->id}/post");

        $response->assertStatus(409);
    }

    public function test_validation_response_remains_422(): void
    {
        $response = $this->postJson('/api/v1/auth/login', []);

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
            'message' => 'Data yang dikirim tidak valid.',
        ]);
    }
}
