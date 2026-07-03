<?php

namespace Tests\Unit;

use App\Models\Agency;
use App\Models\Concerns\BelongsToAgency;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Test-only fixture standing in for the agency-scoped domain models
 * (vehicles, inspections, ...) that arrive in later phases.
 */
class ScopedFixture extends Model
{
    use BelongsToAgency;

    protected $table = 'scoped_fixtures';

    protected $guarded = [];

    public $timestamps = false;
}

class AgencyScopeUnitTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('scoped_fixtures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agency_id')->nullable();
            $table->string('name');
        });
    }

    public function test_global_scope_adds_agency_filter_for_authenticated_admin(): void
    {
        $admin = User::factory()->admin()->create();

        Sanctum::actingAs($admin);

        $sql = ScopedFixture::query()->toSql();

        $this->assertStringContainsString('agency_id', $sql);
        $this->assertContains($admin->agency_id, ScopedFixture::query()->getBindings());
    }

    public function test_scope_returns_only_own_agency_records(): void
    {
        $agencyA = Agency::factory()->create();
        $agencyB = Agency::factory()->create();

        ScopedFixture::withoutGlobalScopes()->insert([
            ['agency_id' => $agencyA->id, 'name' => 'A-record'],
            ['agency_id' => $agencyB->id, 'name' => 'B-record'],
        ]);

        Sanctum::actingAs(User::factory()->admin()->create(['agency_id' => $agencyA->id]));

        $names = ScopedFixture::pluck('name');

        $this->assertSame(['A-record'], $names->all());
    }

    public function test_agency_id_is_auto_stamped_on_create(): void
    {
        $admin = User::factory()->admin()->create();
        Sanctum::actingAs($admin);

        $record = ScopedFixture::create(['name' => 'stamped']);

        $this->assertSame($admin->agency_id, $record->agency_id);
    }

    public function test_unauthenticated_context_is_not_filtered(): void
    {
        $agencyA = Agency::factory()->create();
        $agencyB = Agency::factory()->create();

        ScopedFixture::withoutGlobalScopes()->insert([
            ['agency_id' => $agencyA->id, 'name' => 'A-record'],
            ['agency_id' => $agencyB->id, 'name' => 'B-record'],
        ]);

        // Console/seeder/scheduled-job context: no authenticated user.
        $this->assertSame(2, ScopedFixture::count());
    }

    public function test_ensure_role_rejects_driver_token_on_admin_route(): void
    {
        Route::middleware(['auth:sanctum', 'role:admin'])
            ->get('/api/v1/_test-admin-only', fn () => response()->json(['ok' => true]));

        Sanctum::actingAs(User::factory()->driver()->create());
        $this->getJson('/api/v1/_test-admin-only')->assertStatus(403);
    }

    public function test_ensure_role_allows_admin_token_on_admin_route(): void
    {
        Route::middleware(['auth:sanctum', 'role:admin'])
            ->get('/api/v1/_test-admin-only', fn () => response()->json(['ok' => true]));

        Sanctum::actingAs(User::factory()->admin()->create());
        $this->getJson('/api/v1/_test-admin-only')->assertOk();
    }

    public function test_ensure_role_returns_401_without_token(): void
    {
        Route::middleware(['auth:sanctum', 'role:admin'])
            ->get('/api/v1/_test-admin-only', fn () => response()->json(['ok' => true]));

        $this->getJson('/api/v1/_test-admin-only')->assertStatus(401);
    }
}
