<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Vehicle and driver records cannot be deleted (FR-05, FR-06, revised 2026-08).
 *
 * Delete and restore were built, then removed once the requirements were
 * re-examined against the objectives: every inspection, damage report, repair
 * log, preventive maintenance schedule and dispatch refers to a vehicle and a
 * driver, so removing either breaks the history the system exists to keep. A
 * vehicle that leaves service is recorded through its operational status; a
 * driver who leaves is recorded by reassigning their vehicles.
 *
 * This guards the decision rather than the code. A deletion is exactly the kind
 * of convenience that gets added back by someone who has not read the
 * manuscript, and the only visible consequence would be a Chapter 4 that no
 * longer describes the system.
 */
class RecordsAreNotDeletableTest extends TestCase
{
    use RefreshDatabase;

    private Agency $agency;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->agency = Agency::factory()->create(['code' => 'BFP']);
        $this->admin = User::factory()->admin()->create(['agency_id' => $this->agency->id]);
    }

    /** @return list<array{string,string}> */
    public static function removedRoutes(): array
    {
        return [
            'delete vehicle (api)' => ['DELETE', 'api/v1/vehicles/{vehicle}'],
            'restore vehicle (api)' => ['PATCH', 'api/v1/vehicles/{vehicle}/restore'],
            'delete driver (api)' => ['DELETE', 'api/v1/drivers/{driver}'],
            'restore driver (api)' => ['PATCH', 'api/v1/drivers/{driver}/restore'],
            'delete vehicle (web)' => ['DELETE', 'vehicles/{vehicle}'],
            'restore vehicle (web)' => ['PATCH', 'vehicles/{vehicle}/restore'],
            'delete driver (web)' => ['DELETE', 'drivers/{driver}'],
            'restore driver (web)' => ['PATCH', 'drivers/{driver}/restore'],
            'reset a colleague administrator (api)' => ['PATCH', 'api/v1/admins/{admin}/password'],
            'reset a colleague administrator (web)' => ['PATCH', 'admins/{admin}/password'],
            'administrator directory (api)' => ['GET', 'api/v1/admins'],
        ];
    }

    #[DataProvider('removedRoutes')]
    public function test_the_route_no_longer_exists(string $method, string $uri): void
    {
        $matched = collect(Route::getRoutes()->getRoutes())
            ->first(fn ($route) => $route->uri() === $uri && in_array($method, $route->methods(), true));

        $this->assertNull($matched, "The route {$method} /{$uri} was removed and must not come back.");
    }

    public function test_the_soft_delete_columns_are_gone(): void
    {
        $this->assertFalse(Schema::hasColumn('vehicles', 'deleted_at'));
        $this->assertFalse(Schema::hasColumn('users', 'deleted_at'));
    }

    /**
     * The real point: history survives because the record it points at cannot
     * leave. A vehicle taken out of service keeps its plate on every past
     * inspection, because the row is still there.
     */
    public function test_a_vehicle_out_of_service_is_recorded_by_status_not_removal(): void
    {
        $vehicle = Vehicle::factory()->create([
            'agency_id' => $this->agency->id,
            'status' => Vehicle::STATUS_OPERATIONAL,
        ]);

        Sanctum::actingAs($this->admin);

        $this->patchJson("/api/v1/vehicles/{$vehicle->id}/status", [
            'status' => Vehicle::STATUS_NOT_OPERATIONAL,
            'remarks' => 'Engine failure; awaiting parts.',
        ])->assertOk();

        $this->assertDatabaseHas('vehicles', [
            'id' => $vehicle->id,
            'status' => Vehicle::STATUS_NOT_OPERATIONAL,
        ]);
    }
}
