<?php

namespace Tests\Feature\Dashboard;

use App\Features\Auth\Enums\RoleCode;
use App\Features\Auth\Models\Role;
use App\Features\Auth\Models\User;
use App\Features\Category\Models\Category;
use App\Features\Inventory\Enums\MovementType;
use App\Features\Inventory\Enums\TransferStatus;
use App\Features\Inventory\Models\StockIssue;
use App\Features\Inventory\Models\StockMovement;
use App\Features\Inventory\Models\StockReceipt;
use App\Features\Inventory\Models\StockTransfer;
use App\Features\Location\Models\Location;
use App\Features\Product\Models\Product;
use App\Features\Unit\Models\Unit;
use Carbon\CarbonImmutable;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardPeriodActivityTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Location $loc1;

    private Location $loc2;

    private Product $product;

    private Category $cat;

    private Unit $unit;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);

        $adminRole = Role::where('code', RoleCode::ADMIN->value)->first();
        $this->admin = User::factory()->create();
        $this->admin->roles()->attach($adminRole);

        $this->loc1 = Location::create(['code' => 'LOC-PA1', 'name' => 'Loc PA1', 'is_active' => true]);
        $this->loc2 = Location::create(['code' => 'LOC-PA2', 'name' => 'Loc PA2', 'is_active' => true]);
        $this->admin->locations()->attach([$this->loc1->id, $this->loc2->id]);

        $this->cat = Category::create(['code' => 'CAT-PA', 'name' => 'Cat PA', 'is_active' => true]);
        $this->unit = Unit::create(['code' => 'UNT-PA', 'name' => 'Unit PA', 'symbol' => 'upa', 'is_active' => true]);

        $this->product = Product::create([
            'sku' => 'PRD-PA-001',
            'name' => 'Product PA',
            'category_id' => $this->cat->id,
            'unit_id' => $this->unit->id,
            'is_active' => true,
        ]);
    }

    public function test_period_activity_uses_posting_event_created_at_basis_and_distinct_documents(): void
    {
        CarbonImmutable::setTestNow('2026-08-11 10:00:00');

        $p2 = Product::create([
            'sku' => 'PRD-PA-002',
            'name' => 'Product PA 2',
            'category_id' => $this->cat->id,
            'unit_id' => $this->unit->id,
            'is_active' => true,
        ]);

        // 1. Receipt with document date outside period (2026-08-01), but RECEIPT movement created today (2026-08-11)
        // Multi-item receipt (2 items for 2 products) -> 2 movements, but document count must be 1
        StockMovement::create([
            'movement_id' => 'MOV-PA-01',
            'reference_type' => StockReceipt::class,
            'reference_id' => 100,
            'product_id' => $this->product->id,
            'location_id' => $this->loc1->id,
            'movement_type' => MovementType::RECEIPT->value,
            'quantity' => '10.0000',
            'quantity_before' => '0.0000',
            'quantity_after' => '10.0000',
            'reference_number' => 'RC-PA-001',
            'created_by' => $this->admin->id,
            'occurred_at' => '2026-08-01 10:00:00',
            'created_at' => now(),
        ]);
        StockMovement::create([
            'movement_id' => 'MOV-PA-02',
            'reference_type' => StockReceipt::class,
            'reference_id' => 100, // Same receipt document ID
            'product_id' => $p2->id,
            'location_id' => $this->loc1->id,
            'movement_type' => MovementType::RECEIPT->value,
            'quantity' => '5.0000',
            'quantity_before' => '0.0000',
            'quantity_after' => '5.0000',
            'reference_number' => 'RC-PA-001',
            'created_by' => $this->admin->id,
            'occurred_at' => '2026-08-01 10:00:00',
            'created_at' => now(),
        ]);

        // 2. Receipt with document date today, but movement created yesterday (outside 'today' period)
        StockMovement::create([
            'movement_id' => 'MOV-PA-03',
            'reference_type' => StockReceipt::class,
            'reference_id' => 200,
            'product_id' => $this->product->id,
            'location_id' => $this->loc1->id,
            'movement_type' => MovementType::RECEIPT->value,
            'quantity' => '20.0000',
            'quantity_before' => '0.0000',
            'quantity_after' => '20.0000',
            'reference_number' => 'RC-PA-002',
            'created_by' => $this->admin->id,
            'occurred_at' => '2026-08-11 08:00:00',
            'created_at' => now()->subDay(), // 2026-08-10
        ]);

        // 3. Issue with ISSUE movement created today
        StockMovement::create([
            'movement_id' => 'MOV-PA-04',
            'reference_type' => StockIssue::class,
            'reference_id' => 300,
            'product_id' => $this->product->id,
            'location_id' => $this->loc1->id,
            'movement_type' => MovementType::ISSUE->value,
            'quantity' => '3.0000',
            'quantity_before' => '15.0000',
            'quantity_after' => '12.0000',
            'reference_number' => 'IS-PA-001',
            'created_by' => $this->admin->id,
            'occurred_at' => '2026-08-01 10:00:00',
            'created_at' => now(),
        ]);

        // 4. Transfer with transfer_date 2026-08-01, but received_at today
        StockTransfer::create([
            'transfer_number' => 'TR-PA-001',
            'transfer_date' => '2026-08-01',
            'date' => '2026-08-01',
            'origin_location_id' => $this->loc1->id,
            'destination_location_id' => $this->loc2->id,
            'status' => TransferStatus::RECEIVED->value,
            'created_by' => $this->admin->id,
            'received_at' => now(),
        ]);

        // 5. Transfer with transfer_date today, but received_at null (in transit)
        StockTransfer::create([
            'transfer_number' => 'TR-PA-002',
            'transfer_date' => '2026-08-11',
            'date' => '2026-08-11',
            'origin_location_id' => $this->loc1->id,
            'destination_location_id' => $this->loc2->id,
            'status' => TransferStatus::SENT->value,
            'created_by' => $this->admin->id,
            'received_at' => null,
        ]);

        $response = $this->actingAs($this->admin)->getJson('/api/v1/dashboard?period=today');
        $response->assertOk();

        $activity = $response->json('data.period_activity');

        // Multi-item receipt (100) created today is counted as 1 distinct document
        $this->assertSame(1, $activity['posted_receipt_count']);
        $this->assertSame(1, $activity['posted_issue_count']);
        $this->assertSame(1, $activity['received_transfer_count']);
        // 3 movements created today (MOV-PA-01, MOV-PA-02, MOV-PA-04)
        $this->assertSame(3, $activity['movement_count']);

        CarbonImmutable::setTestNow(null);
    }
}
