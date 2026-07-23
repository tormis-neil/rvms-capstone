<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\DamageReport;
use App\Models\User;
use App\Models\Vehicle;
use Database\Seeders\InspectionChecklistSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * R4 Day 7 — the Damage Reports section on the /inspections dashboard page
 * (Blade twin of the damage review API).
 */
class WebDamagePageTest extends TestCase
{
    use RefreshDatabase;

    private Agency $agency;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(InspectionChecklistSeeder::class);

        $this->agency = Agency::factory()->create(['code' => 'BFP']);
        $this->admin = User::factory()->admin()->create(['agency_id' => $this->agency->id]);
    }

    private function makeReport(Agency $agency, string $status = DamageReport::STATUS_PENDING): DamageReport
    {
        return DamageReport::factory()->create([
            'agency_id' => $agency->id,
            'vehicle_id' => Vehicle::factory()->create(['agency_id' => $agency->id])->id,
            'driver_id' => User::factory()->driver()->create(['agency_id' => $agency->id])->id,
            'nature_of_damage' => 'Cracked side mirror',
            'status' => $status,
        ]);
    }

    public function test_page_shows_only_own_agency_damage_reports_with_pending_count(): void
    {
        $mine = $this->makeReport($this->agency);
        $this->makeReport(Agency::factory()->create(['code' => 'PNP'])); // other agency

        $this->actingAs($this->admin)
            ->get('/inspections')
            ->assertOk()
            ->assertSee('Damage Reports')
            ->assertSee('1 Pending Review')
            ->assertSee('Cracked side mirror');
    }

    public function test_review_marks_reviewed_and_updates_the_vehicle(): void
    {
        $report = $this->makeReport($this->agency);

        $this->actingAs($this->admin)
            ->from('/inspections')
            ->patch("/damage-reports/{$report->id}/review", ['vehicle_status' => Vehicle::STATUS_NOT_OPERATIONAL])
            ->assertRedirect(route('inspections'));

        $this->assertSame(DamageReport::STATUS_REVIEWED, $report->fresh()->status);
        $this->assertSame($this->admin->id, $report->fresh()->reviewed_by);
        $this->assertSame(Vehicle::STATUS_NOT_OPERATIONAL, $report->vehicle->fresh()->status);
    }

    public function test_review_rejects_dispatched_status(): void
    {
        $report = $this->makeReport($this->agency);

        $this->actingAs($this->admin)
            ->from('/inspections')
            ->patch("/damage-reports/{$report->id}/review", ['vehicle_status' => Vehicle::STATUS_DISPATCHED])
            ->assertSessionHasErrors('vehicle_status');
    }

    public function test_cross_agency_review_returns_404(): void
    {
        $foreign = $this->makeReport(Agency::factory()->create(['code' => 'PNP']));

        $this->actingAs($this->admin)
            ->patch("/damage-reports/{$foreign->id}/review", [])
            ->assertNotFound();
    }
}
