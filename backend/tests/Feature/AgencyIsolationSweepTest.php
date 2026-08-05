<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\DamageReport;
use App\Models\Dispatch;
use App\Models\Inspection;
use App\Models\Notification;
use App\Models\PmSchedule;
use App\Models\RepairLog;
use App\Models\User;
use App\Models\Vehicle;
use Database\Seeders\InspectionChecklistSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * R10 sub-task 3 — the agency wall, proven endpoint by endpoint (FR-02, NFR-02).
 *
 * Every module's own suite spot-checks isolation for its own records; this
 * sweep is the systematic version: ONE full record graph is seeded for BFP,
 * and a CHO administrator then attacks every parameterised route with the
 * BFP record's real id. Every attempt must come back 404 — not 403, because
 * "forbidden" confirms the id exists and 404 leaks nothing.
 *
 * The list of routes below is the contract. When a new `{id}` route is added,
 * it MUST be added here too — the companion test at the bottom counts the
 * app's parameterised API routes and fails when this file falls behind, so
 * forgetting is loud rather than silent.
 */
class AgencyIsolationSweepTest extends TestCase
{
    use RefreshDatabase;

    private Agency $bfp;

    private Agency $cho;

    private User $choAdmin;

    /** @var array<string, int> BFP record ids, keyed by placeholder name. */
    private array $ids = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(InspectionChecklistSeeder::class);

        $this->bfp = Agency::factory()->create(['code' => 'BFP']);
        $this->cho = Agency::factory()->create(['code' => 'CHO']);
        $this->choAdmin = User::factory()->admin()->create(['agency_id' => $this->cho->id]);

        // One of everything for BFP — the records CHO must never touch.
        $bfpAdmin = User::factory()->admin()->create(['agency_id' => $this->bfp->id]);
        $driver = User::factory()->driver()->create(['agency_id' => $this->bfp->id]);
        $vehicle = Vehicle::factory()->create([
            'agency_id' => $this->bfp->id,
            'assigned_driver_id' => $driver->id,
        ]);

        $this->ids = [
            'vehicle' => $vehicle->id,
            'driver' => $driver->id,
            'inspection' => Inspection::factory()->create([
                'agency_id' => $this->bfp->id, 'vehicle_id' => $vehicle->id, 'driver_id' => $driver->id,
            ])->id,
            'damage' => DamageReport::factory()->create([
                'agency_id' => $this->bfp->id, 'vehicle_id' => $vehicle->id, 'driver_id' => $driver->id,
            ])->id,
            'repair' => RepairLog::factory()->create([
                'agency_id' => $this->bfp->id, 'vehicle_id' => $vehicle->id,
            ])->id,
            'pm' => PmSchedule::factory()->create([
                'agency_id' => $this->bfp->id, 'vehicle_id' => $vehicle->id,
            ])->id,
            'dispatch' => Dispatch::factory()->create([
                'agency_id' => $this->bfp->id, 'vehicle_id' => $vehicle->id, 'driver_id' => $driver->id,
            ])->id,
            'notification' => Notification::factory()->create([
                'agency_id' => $this->bfp->id, 'user_id' => $bfpAdmin->id,
            ])->id,
        ];
    }

    /**
     * Every parameterised API route, as [method, uri with a {placeholder}].
     *
     * `/reports/{type}` is deliberately absent: its parameter is a report
     * NAME, not a record id — its isolation (agency-scoped rows, foreign
     * filter ids refused with 422) is proven in ReportGenerationTest.
     *
     * @return array<string, array{0: string, 1: string, 2: array<string, mixed>}>
     */
    public static function apiRoutes(): array
    {
        return [
            'show vehicle' => ['getJson', '/api/v1/vehicles/{vehicle}', []],
            'update vehicle' => ['putJson', '/api/v1/vehicles/{vehicle}', []],
            'vehicle status' => ['patchJson', '/api/v1/vehicles/{vehicle}/status', ['status' => 'Operational']],
            'delete vehicle' => ['deleteJson', '/api/v1/vehicles/{vehicle}', []],
            'restore vehicle' => ['patchJson', '/api/v1/vehicles/{vehicle}/restore', []],
            'show driver' => ['getJson', '/api/v1/drivers/{driver}', []],
            'update driver' => ['putJson', '/api/v1/drivers/{driver}', []],
            'approve driver' => ['patchJson', '/api/v1/drivers/{driver}/approve', []],
            'reject driver' => ['patchJson', '/api/v1/drivers/{driver}/reject', []],
            'driver license' => ['patchJson', '/api/v1/drivers/{driver}/license', ['license_expiry_date' => '2030-01-01']],
            'delete driver' => ['deleteJson', '/api/v1/drivers/{driver}', []],
            'restore driver' => ['patchJson', '/api/v1/drivers/{driver}/restore', []],
            'show inspection' => ['getJson', '/api/v1/inspections/{inspection}', []],
            'review inspection' => ['patchJson', '/api/v1/inspections/{inspection}/review', []],
            'show damage report' => ['getJson', '/api/v1/damage-reports/{damage}', []],
            'review damage report' => ['patchJson', '/api/v1/damage-reports/{damage}/review', []],
            'show repair' => ['getJson', '/api/v1/repairs/{repair}', []],
            'update repair' => ['putJson', '/api/v1/repairs/{repair}', []],
            'show pm schedule' => ['getJson', '/api/v1/pm-schedules/{pm}', []],
            'update pm schedule' => ['putJson', '/api/v1/pm-schedules/{pm}', []],
            'complete pm schedule' => ['patchJson', '/api/v1/pm-schedules/{pm}/complete', []],
            'show dispatch' => ['getJson', '/api/v1/dispatches/{dispatch}', []],
            'update dispatch' => ['putJson', '/api/v1/dispatches/{dispatch}', []],
            'close dispatch' => ['patchJson', '/api/v1/dispatches/{dispatch}/close', ['time_in' => '2026-07-30 12:00', 'return_status' => 'Operational']],
            'read notification' => ['patchJson', '/api/v1/notifications/{notification}/read', []],
        ];
    }

    #[DataProvider('apiRoutes')]
    public function test_a_foreign_record_is_not_found_on(string $method, string $uri, array $payload): void
    {
        Sanctum::actingAs($this->choAdmin);

        $response = $this->{$method}($this->resolve($uri), $payload);

        // 404, never 403: "forbidden" would confirm the id exists in some
        // other agency, which is itself an FR-02 leak. And never 422 —
        // validation must not run against a record the caller cannot see.
        $response->assertNotFound();
    }

    /** The same wall, from below: a driver of the OTHER agency gets 401/403/404, never data. */
    #[DataProvider('apiRoutes')]
    public function test_a_foreign_driver_never_reaches_the_record_either(string $method, string $uri, array $payload): void
    {
        $choDriver = User::factory()->driver()->create(['agency_id' => $this->cho->id]);
        Sanctum::actingAs($choDriver);

        $status = $this->{$method}($this->resolve($uri), $payload)->getStatusCode();

        $this->assertContains(
            $status,
            [403, 404],
            "A CHO driver got HTTP {$status} from {$uri} — expected the role gate (403) or not-found (404)."
        );
    }

    /** Every list endpoint: the foreign agency's records simply are not there. */
    public function test_no_list_endpoint_contains_a_foreign_record(): void
    {
        Sanctum::actingAs($this->choAdmin);

        $lists = [
            '/api/v1/vehicles', '/api/v1/drivers', '/api/v1/inspections',
            '/api/v1/damage-reports', '/api/v1/repairs', '/api/v1/pm-schedules',
            '/api/v1/dispatches', '/api/v1/notifications',
        ];

        foreach ($lists as $uri) {
            $body = $this->getJson($uri)->assertOk()->json('data');

            $this->assertSame([], $body, "{$uri} returned records to an agency that owns none.");
        }
    }

    /**
     * The guard on the guard: if a new parameterised API route appears and is
     * not swept above, this fails with its name — so the sweep cannot rot.
     */
    public function test_every_parameterised_api_route_is_covered_by_this_sweep(): void
    {
        $swept = collect(self::apiRoutes())
            ->map(fn ($case) => preg_replace('/\{[^}]+\}/', '{id}', $case[1]))
            ->map(fn ($uri) => ltrim($uri, '/'))
            ->unique();

        $actual = collect(app('router')->getRoutes()->getRoutes())
            ->filter(fn ($route) => str_starts_with($route->uri(), 'api/v1/') && str_contains($route->uri(), '{'))
            ->map(fn ($route) => preg_replace('/\{[^}]+\}/', '{id}', $route->uri()))
            ->unique()
            // The report type is a name, not a record id — covered in
            // ReportGenerationTest instead (see apiRoutes() docblock).
            ->reject(fn ($uri) => $uri === 'api/v1/reports/{id}');

        $missing = $actual->diff($swept);

        $this->assertTrue(
            $missing->isEmpty(),
            'New parameterised route(s) not covered by the isolation sweep: '.$missing->implode(', ')
        );
    }

    /** Swap the {placeholder} for the BFP record's real id. */
    private function resolve(string $uri): string
    {
        return preg_replace_callback('/\{(\w+)\}/', function ($m) {
            return (string) $this->ids[$m[1]];
        }, $uri);
    }
}
