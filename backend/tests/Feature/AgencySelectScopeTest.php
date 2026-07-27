<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\DamageReport;
use App\Models\Dispatch;
use App\Models\Inspection;
use App\Models\PmSchedule;
use App\Models\RepairLog;
use App\Models\User;
use App\Models\Vehicle;
use Database\Seeders\InspectionChecklistSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Agency isolation for the DROPDOWNS, not just the tables (FR-02, NFR-02).
 *
 * Most models carry the BelongsToAgency global scope, so their lists are safe by
 * construction. `users` deliberately does not — a user is resolved before any
 * agency context exists at login — so every User query must filter by hand, and
 * Web\VehicleController's Assigned Driver select was missing that filter: a BFP
 * admin saw every agency's drivers, which is also why the Drivers page count and
 * the dropdown disagreed.
 *
 * This sweeps every select-populating query on every dashboard page, so the next
 * forgotten filter fails here instead of in manual testing.
 */
class AgencySelectScopeTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(InspectionChecklistSeeder::class);

        // The admin's own agency, with one of everything to populate the page.
        $mine = Agency::factory()->create(['code' => 'BFP']);
        $this->admin = User::factory()->admin()->create(['agency_id' => $mine->id]);
        $myDriver = User::factory()->driver()->create([
            'agency_id' => $mine->id, 'name' => 'Ramon Villanueva',
        ]);
        $myVehicle = Vehicle::factory()->create([
            'agency_id' => $mine->id,
            'plate_number' => 'BFP-0001',
            'assigned_driver_id' => $myDriver->id,
            'status' => Vehicle::STATUS_OPERATIONAL,
        ]);
        Inspection::factory()->create([
            'agency_id' => $mine->id, 'vehicle_id' => $myVehicle->id, 'driver_id' => $myDriver->id,
        ]);
        DamageReport::factory()->create([
            'agency_id' => $mine->id, 'vehicle_id' => $myVehicle->id, 'driver_id' => $myDriver->id,
        ]);
        RepairLog::factory()->create(['agency_id' => $mine->id, 'vehicle_id' => $myVehicle->id]);
        PmSchedule::factory()->create(['agency_id' => $mine->id, 'vehicle_id' => $myVehicle->id]);
        Dispatch::factory()->create([
            'agency_id' => $mine->id, 'vehicle_id' => $myVehicle->id, 'driver_id' => $myDriver->id,
        ]);

        // A second agency whose records must never surface. Distinctive values so a
        // leak is unambiguous rather than a coincidental substring match.
        $theirs = Agency::factory()->create(['code' => 'CHO']);
        $theirDriver = User::factory()->driver()->create([
            'agency_id' => $theirs->id, 'name' => 'Zenaida Foreignagency',
        ]);
        Vehicle::factory()->create([
            'agency_id' => $theirs->id,
            'plate_number' => 'CHO-9999',
            'assigned_driver_id' => $theirDriver->id,
            'status' => Vehicle::STATUS_OPERATIONAL,
        ]);
        // Also a pending applicant of the OWN agency: approved drivers only belong
        // in an assignment dropdown (FR-03).
        User::factory()->driver()->create([
            'agency_id' => $mine->id, 'name' => 'Pendro Pendingson', 'status' => User::STATUS_PENDING,
        ]);
    }

    public static function pages(): array
    {
        return [
            'vehicles' => ['/vehicles'],
            'drivers' => ['/drivers'],
            'inspections & damage' => ['/inspections'],
            'repairs' => ['/repairs'],
            'pm schedules' => ['/pm'],
            'dispatch' => ['/dispatch'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('pages')]
    public function test_no_select_offers_another_agencys_records(string $url): void
    {
        $html = $this->actingAs($this->admin)->get($url)->assertOk()->getContent();

        preg_match_all('#<select\b.*?</select>#s', $html, $selects);
        $this->assertNotEmpty($selects[0], "No select elements found on {$url}");

        foreach ($selects[0] as $select) {
            $this->assertStringNotContainsString(
                'Zenaida Foreignagency',
                $select,
                "A select on {$url} offers another agency's driver (FR-02)."
            );
            $this->assertStringNotContainsString(
                'CHO-9999',
                $select,
                "A select on {$url} offers another agency's vehicle (FR-02)."
            );
        }
    }

    /**
     * The Vehicles page's Assigned Driver select specifically: own agency, active
     * drivers only. This is the select that shipped unscoped.
     */
    public function test_assigned_driver_select_lists_only_own_active_drivers(): void
    {
        $html = $this->actingAs($this->admin)->get('/vehicles')->assertOk()->getContent();

        preg_match_all('#<select[^>]*name="assigned_driver_id".*?</select>#s', $html, $selects);
        $this->assertNotEmpty($selects[0], 'No Assigned Driver select on the Vehicles page.');

        foreach ($selects[0] as $select) {
            $this->assertStringContainsString('Ramon Villanueva', $select);
            $this->assertStringNotContainsString('Zenaida Foreignagency', $select);
            $this->assertStringNotContainsString('Pendro Pendingson', $select);
        }
    }
}
