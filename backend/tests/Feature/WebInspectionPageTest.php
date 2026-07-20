<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\Inspection;
use App\Models\InspectionChecklistItem;
use App\Models\User;
use App\Models\Vehicle;
use Database\Seeders\InspectionChecklistSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * R3 Day 6 — the /inspections dashboard page (Blade twin of the inspection
 * monitoring/review API).
 */
class WebInspectionPageTest extends TestCase
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

    private function makeInspection(Agency $agency, string $review = Inspection::STATUS_PENDING, string $flag = 'Brakes'): Inspection
    {
        $driver = User::factory()->driver()->create(['agency_id' => $agency->id]);
        $vehicle = Vehicle::factory()->create(['agency_id' => $agency->id]);

        $inspection = Inspection::factory()->create([
            'agency_id' => $agency->id,
            'vehicle_id' => $vehicle->id,
            'driver_id' => $driver->id,
            'review_status' => $review,
        ]);

        foreach (InspectionChecklistItem::forAgencyCode($agency->code)->get() as $item) {
            $inspection->items()->create([
                'checklist_item_id' => $item->id,
                'status' => $item->name === $flag ? 'Has Issue' : 'OK',
                'remarks' => $item->name === $flag ? 'Noise on braking' : null,
            ]);
        }

        return $inspection;
    }

    public function test_page_lists_only_own_agency_inspections_with_pending_count(): void
    {
        $mine = $this->makeInspection($this->agency);
        $this->makeInspection(Agency::factory()->create(['code' => 'PNP'])); // other agency

        $this->actingAs($this->admin)
            ->get('/inspections')
            ->assertOk()
            ->assertSee('Daily BLOWBAGETS Inspections')
            ->assertSee('1 Pending Review')
            ->assertSee($mine->vehicle->plate_number)
            ->assertSee('Noise on braking');
    }

    public function test_frequent_issues_render_on_the_page(): void
    {
        $this->makeInspection($this->agency, flag: 'Brakes');
        $this->makeInspection($this->agency, flag: 'Brakes');

        $this->actingAs($this->admin)
            ->get('/inspections')
            ->assertOk()
            ->assertSee('Frequently Reported Issues')
            ->assertSee('Brakes');
    }

    public function test_review_marks_reviewed_and_updates_the_vehicle(): void
    {
        $inspection = $this->makeInspection($this->agency);

        $this->actingAs($this->admin)
            ->from('/inspections')
            ->patch("/inspections/{$inspection->id}/review", ['vehicle_status' => Vehicle::STATUS_NOT_OPERATIONAL])
            ->assertRedirect(route('inspections'));

        $this->assertSame(Inspection::STATUS_REVIEWED, $inspection->fresh()->review_status);
        $this->assertSame($this->admin->id, $inspection->fresh()->reviewed_by);
        $this->assertSame(Vehicle::STATUS_NOT_OPERATIONAL, $inspection->vehicle->fresh()->status);
    }

    public function test_review_without_status_change_still_marks_reviewed(): void
    {
        $inspection = $this->makeInspection($this->agency);
        $originalStatus = $inspection->vehicle->status;

        $this->actingAs($this->admin)
            ->patch("/inspections/{$inspection->id}/review", ['vehicle_status' => ''])
            ->assertRedirect(route('inspections'));

        $this->assertSame(Inspection::STATUS_REVIEWED, $inspection->fresh()->review_status);
        $this->assertSame($originalStatus, $inspection->vehicle->fresh()->status);
    }

    public function test_review_rejects_dispatched_status(): void
    {
        $inspection = $this->makeInspection($this->agency);

        $this->actingAs($this->admin)
            ->from('/inspections')
            ->patch("/inspections/{$inspection->id}/review", ['vehicle_status' => Vehicle::STATUS_DISPATCHED])
            ->assertSessionHasErrors('vehicle_status');
    }

    public function test_cross_agency_review_returns_404(): void
    {
        $foreign = $this->makeInspection(Agency::factory()->create(['code' => 'PNP']));

        $this->actingAs($this->admin)
            ->patch("/inspections/{$foreign->id}/review", [])
            ->assertNotFound();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/inspections')->assertRedirect(route('login'));
    }
}
