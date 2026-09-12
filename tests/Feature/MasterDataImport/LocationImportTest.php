<?php

namespace Tests\Feature\MasterDataImport;

use App\Features\Auth\Enums\RoleCode;
use App\Features\Auth\Models\User;
use App\Features\Location\Models\Location;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class LocationImportTest extends TestCase
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

    public function test_loc_01_and_loc_04_to_07_can_validate_and_commit_valid_locations_and_triggers_observer(): void
    {
        $initialLockCount = DB::table('inventory_location_locks')->count();
        $initialUserLocationCount = DB::table('user_locations')->count();
        $initialMovements = DB::table('stock_movements')->count();
        $initialBalances = DB::table('inventory_balances')->count();

        $csvContent = "code,name,description,address,phone\ngdg-utama,Gudang Utama,Pusat,Jl. Merdeka No. 1,08123456789\ngdg-transit,Gudang Transit,Transit,Jl. Sudirman No. 2,08198765432\n";
        $file = UploadedFile::fake()->createWithContent('locations.csv', $csvContent);

        $valRes = $this->actingAs($this->admin)
            ->postJson('/api/v1/master-data-import/locations/validate', ['file' => $file]);

        $valRes->assertOk()
            ->assertJsonPath('data.total_rows', 2)
            ->assertJsonPath('data.valid_rows', 2);

        $sha256 = $valRes->json('data.sha256');

        $commitRes = $this->actingAs($this->admin)
            ->postJson('/api/v1/master-data-import/locations/commit', [
                'file' => $file,
                'expected_sha256' => $sha256,
            ]);

        $commitRes->assertCreated()
            ->assertJsonPath('data.imported_rows', 2);

        $this->assertDatabaseHas('locations', [
            'code' => 'GDG-UTAMA',
            'name' => 'Gudang Utama',
            'address' => 'Jl. Merdeka No. 1',
            'phone' => '08123456789',
        ]);
        $this->assertDatabaseHas('locations', [
            'code' => 'GDG-TRANSIT',
            'name' => 'Gudang Transit',
        ]);

        $locationUtama = Location::where('code', 'GDG-UTAMA')->first();
        $locationTransit = Location::where('code', 'GDG-TRANSIT')->first();

        // Check LocationObserver triggered: locks created
        $this->assertDatabaseHas('inventory_location_locks', [
            'location_id' => $locationUtama->id,
            'is_frozen' => 0,
        ]);
        $this->assertDatabaseHas('inventory_location_locks', [
            'location_id' => $locationTransit->id,
            'is_frozen' => 0,
        ]);

        $newLockCount = DB::table('inventory_location_locks')->count();
        $this->assertSame($initialLockCount + 2, $newLockCount);

        // Check NO user_locations automatically created (NEW_LOCATIONS_REQUIRE_MANUAL_ADMIN_ASSIGNMENT)
        $newUserLocationCount = DB::table('user_locations')->count();
        $this->assertSame($initialUserLocationCount, $newUserLocationCount);

        // Check NO stock movements or inventory balances created
        $this->assertSame($initialMovements, DB::table('stock_movements')->count());
        $this->assertSame($initialBalances, DB::table('inventory_balances')->count());
    }

    public function test_loc_02_fails_when_in_db_duplicate_location_code(): void
    {
        Location::factory()->create(['code' => 'EXISTING-LOC']);

        $csvContent = "code,name,description,address,phone\nEXISTING-LOC,Location Name,Desc,Addr,08123\n";
        $file = UploadedFile::fake()->createWithContent('locations.csv', $csvContent);

        $response = $this->actingAs($this->admin)
            ->postJson('/api/v1/master-data-import/locations/validate', ['file' => $file]);

        $response->assertOk()
            ->assertJsonPath('data.invalid_rows', 1)
            ->assertJsonPath('data.errors.0.code', 'DUPLICATE_CODE_IN_DB');
    }

    public function test_loc_03_mixed_invalid_zero_imported(): void
    {
        Location::factory()->create(['code' => 'DUP-LOC']);

        $csvContent = "code,name,description,address,phone\nVALID-LOC,Valid Location,Desc,Addr,08123\nDUP-LOC,Dup Location,Desc,Addr,08123\n";
        $file = UploadedFile::fake()->createWithContent('locations.csv', $csvContent);
        $sha = hash_file('sha256', $file->getRealPath());

        $response = $this->actingAs($this->admin)
            ->postJson('/api/v1/master-data-import/locations/commit', [
                'file' => $file,
                'expected_sha256' => $sha,
            ]);

        $response->assertStatus(422);
        $this->assertDatabaseMissing('locations', ['code' => 'VALID-LOC']);
    }
}
