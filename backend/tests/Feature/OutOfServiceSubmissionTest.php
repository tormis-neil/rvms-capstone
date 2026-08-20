<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\InspectionChecklistItem;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * What a driver may submit for a vehicle that is off the road (2026-08,
 * adviser-reported).
 *
 * The two modules are deliberately asymmetric, and the asymmetry is the whole
 * point of this file:
 *
 *   - A daily BLOWBAGETS inspection is a PRE-TRIP check. A vehicle that is Not
 *     Operational or Under Preventive Maintenance is not making a trip, so the
 *     submission records something that did not happen — and an all-OK result
 *     on a vehicle the system calls broken is a contradiction between FR-09 and
 *     FR-18 stored in the database. Refused.
 *
 *   - A damage report is how a fault gets reported at all. A vehicle is usually
 *     Not Operational BECAUSE it is damaged, so refusing reports would make a
 *     second, unrelated fault unreportable, and would close the door behind the
 *     very report that took the vehicle off the road. It would also contradict
 *     the GSO Motorpool finding recorded in Chapter 1 — that further defects
 *     are routinely discovered after the original report. Always allowed.
 *
 * The adviser asked for both to be blocked. Only the first is.
 */
class OutOfServiceSubmissionTest extends TestCase
{
    use RefreshDatabase;

    private Agency $agency;

    private User $driver;

    protected function setUp(): void
    {
        parent::setUp();

        $this->agency = Agency::factory()->create(['code' => 'CHO']);
        $this->driver = User::factory()->driver()->create(['agency_id' => $this->agency->id]);

        $this->seed(\Database\Seeders\InspectionChecklistSeeder::class);
    }

    /** @return list<array{string}> */
    public static function outOfServiceStatuses(): array
    {
        return [
            'not operational' => [Vehicle::STATUS_NOT_OPERATIONAL],
            'under preventive maintenance' => [Vehicle::STATUS_UNDER_PM],
        ];
    }

    /** @return list<array{string}> */
    public static function inServiceStatuses(): array
    {
        return [
            'operational' => [Vehicle::STATUS_OPERATIONAL],
            // Out on a mission, but it was inspected before it left and can be
            // inspected when it returns — deliberately still allowed.
            'dispatched' => [Vehicle::STATUS_DISPATCHED],
        ];
    }

    /* ----------------------- inspections: refused ------------------------ */

    #[DataProvider('outOfServiceStatuses')]
    public function test_an_inspection_is_refused_for_a_vehicle_off_the_road(string $status): void
    {
        $vehicle = $this->vehicle($status);

        Sanctum::actingAs($this->driver);

        $this->postJson('/api/v1/inspections', $this->fullChecklist($vehicle))
            ->assertStatus(422)
            ->assertJsonValidationErrors('vehicle_id');

        $this->assertDatabaseCount('inspections', 0);
    }

    /** The refusal has to say WHY, or it reads as a broken form. */
    public function test_the_refusal_names_the_plate_and_the_status(): void
    {
        $vehicle = $this->vehicle(Vehicle::STATUS_UNDER_PM, 'CHO-4321');

        Sanctum::actingAs($this->driver);

        $message = $this->postJson('/api/v1/inspections', $this->fullChecklist($vehicle))
            ->assertStatus(422)
            ->json('errors.vehicle_id.0');

        $this->assertStringContainsString('CHO-4321', $message);
        $this->assertStringContainsString(Vehicle::STATUS_UNDER_PM, $message);
        // It points the driver at the module that IS open to them.
        $this->assertStringContainsString('damage report', $message);
    }

    #[DataProvider('inServiceStatuses')]
    public function test_an_inspection_is_accepted_for_a_vehicle_in_service(string $status): void
    {
        $vehicle = $this->vehicle($status);

        Sanctum::actingAs($this->driver);

        $this->postJson('/api/v1/inspections', $this->fullChecklist($vehicle))
            ->assertCreated();

        $this->assertDatabaseCount('inspections', 1);
    }

    /**
     * The guard must not swallow the checklist rules that were already there —
     * a flagged item with no remark is still refused, on its own error key.
     */
    public function test_the_existing_checklist_rules_still_apply(): void
    {
        $vehicle = $this->vehicle(Vehicle::STATUS_OPERATIONAL);

        Sanctum::actingAs($this->driver);

        $payload = $this->fullChecklist($vehicle);
        $payload['items'][0]['status'] = 'Has Issue';
        $payload['items'][0]['remarks'] = '';

        $this->postJson('/api/v1/inspections', $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors('items.0.remarks');
    }

    /* --------------------- damage reports: allowed ----------------------- */

    #[DataProvider('outOfServiceStatuses')]
    public function test_a_damage_report_is_allowed_for_a_vehicle_off_the_road(string $status): void
    {
        $vehicle = $this->vehicle($status);

        Sanctum::actingAs($this->driver);

        $this->postJson('/api/v1/damage-reports', [
            'vehicle_id' => $vehicle->id,
            'nature_of_damage' => 'Second, unrelated fault found while it was in the workshop.',
        ])->assertCreated();

        $this->assertDatabaseCount('damage_reports', 1);
    }

    /**
     * The circularity the block would have created: the report that takes a
     * vehicle off the road must not be the last one anybody can file on it.
     */
    public function test_a_second_fault_stays_reportable_after_the_first_took_it_off_the_road(): void
    {
        $vehicle = $this->vehicle(Vehicle::STATUS_OPERATIONAL);

        Sanctum::actingAs($this->driver);

        $this->postJson('/api/v1/damage-reports', [
            'vehicle_id' => $vehicle->id,
            'nature_of_damage' => 'Brake failure on the way back from a call.',
        ])->assertCreated();

        // The admin reviews it and takes the vehicle off the road.
        $vehicle->update(['status' => Vehicle::STATUS_NOT_OPERATIONAL]);

        $this->postJson('/api/v1/damage-reports', [
            'vehicle_id' => $vehicle->id,
            'nature_of_damage' => 'Mechanic also found the rear tyre worn through.',
            'photo' => UploadedFile::fake()->image('tyre.jpg'),
        ])->assertCreated();

        $this->assertDatabaseCount('damage_reports', 2);
    }

    /* ------------------------------ helpers ------------------------------ */

    private function vehicle(string $status, string $plate = 'CHO-1111'): Vehicle
    {
        return Vehicle::factory()->create([
            'agency_id' => $this->agency->id,
            'assigned_driver_id' => $this->driver->id,
            'plate_number' => $plate,
            'status' => $status,
        ]);
    }

    /** Every checklist item for this agency, all OK — a valid submission. */
    private function fullChecklist(Vehicle $vehicle): array
    {
        $items = InspectionChecklistItem::query()
            ->forAgencyCode($this->agency->code)
            ->pluck('id')
            ->map(fn (int $id) => ['checklist_item_id' => $id, 'status' => 'OK', 'remarks' => null])
            ->all();

        return ['vehicle_id' => $vehicle->id, 'items' => $items];
    }
}
