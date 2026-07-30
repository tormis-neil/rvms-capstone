<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\DamageReport;
use App\Models\Inspection;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * R8 Day 14 — the Fleet Overview page (FR-19), Blade twin of
 * /api/v1/dashboard/summary.
 *
 * The counts themselves are proven in DashboardSummaryTest; what matters here
 * is that the PAGE shows them — the prototype's markup carried hardcoded demo
 * numbers for seven phases, and a card still displaying "6" would look
 * perfectly correct to anyone not counting the fleet by hand.
 */
class WebDashboardPageTest extends TestCase
{
    use RefreshDatabase;

    private Agency $agency;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->agency = Agency::factory()->create(['code' => 'BFP', 'license_expiry_warning_days' => 30]);
        $this->admin = User::factory()->admin()->create(['agency_id' => $this->agency->id]);
    }

    /** None of the prototype's demo figures may survive into the live page. */
    public function test_the_metric_cards_show_live_counts_not_the_prototypes_demo_numbers(): void
    {
        Vehicle::factory()->count(3)->create([
            'agency_id' => $this->agency->id, 'status' => Vehicle::STATUS_OPERATIONAL,
        ]);

        $html = $this->actingAs($this->admin)->get('/dashboard')->assertOk()->getContent();

        $this->assertStringContainsString('js-metric-total">3<', $html);
        $this->assertStringContainsString('js-metric-operational">3<', $html);
        $this->assertStringContainsString('js-metric-dispatched">0<', $html);
        $this->assertStringContainsString('js-metric-drivers">0<', $html);
    }

    public function test_the_date_line_is_today_not_the_prototypes_fixed_date(): void
    {
        $html = $this->actingAs($this->admin)->get('/dashboard')->assertOk()->getContent();

        $this->assertStringContainsString('Today: '.now()->format('F j, Y'), $html);
        $this->assertStringNotContainsString('June 8, 2026', $html);
    }

    /** Quick Action tiles were prototype .html hrefs; they must be real routes. */
    public function test_quick_actions_point_at_real_routes(): void
    {
        $response = $this->actingAs($this->admin)->get('/dashboard')->assertOk();

        foreach (['vehicles', 'drivers', 'inspections', 'pm', 'dispatch', 'reports'] as $name) {
            $response->assertSee(route($name), false);
        }

        $response->assertDontSee('href="vehicles.html"', false);
        $response->assertDontSee('href="reports.html"', false);
    }

    public function test_the_action_lists_show_real_pending_records(): void
    {
        $vehicle = Vehicle::factory()->create([
            'agency_id' => $this->agency->id, 'plate_number' => 'ABC-1234', 'type' => 'Fire Truck',
        ]);
        $driver = User::factory()->driver()->create(['agency_id' => $this->agency->id]);

        DamageReport::factory()->create([
            'agency_id' => $this->agency->id, 'vehicle_id' => $vehicle->id, 'driver_id' => $driver->id,
            'status' => DamageReport::STATUS_PENDING, 'nature_of_damage' => 'Cracked side mirror',
        ]);

        $html = $this->actingAs($this->admin)->get('/dashboard')->assertOk()->getContent();

        $this->assertStringContainsString('Fire Truck (ABC-1234)', $html);
        $this->assertStringContainsString('Damage: Cracked side mirror', $html);
        $this->assertStringContainsString('js-action-pending-count">1 New<', $html);
        // The prototype's demo rows must be gone.
        $this->assertStringNotContainsString('Transmission slipping', $html);
    }

    /** An all-OK inspection is still a submission awaiting review (FR-10). */
    public function test_a_pending_inspection_with_no_issues_still_appears(): void
    {
        $vehicle = Vehicle::factory()->create(['agency_id' => $this->agency->id, 'plate_number' => 'XYZ-9999']);
        Inspection::factory()->create([
            'agency_id' => $this->agency->id, 'vehicle_id' => $vehicle->id,
            'driver_id' => User::factory()->driver()->create(['agency_id' => $this->agency->id])->id,
            'review_status' => Inspection::STATUS_PENDING,
        ]);

        $this->actingAs($this->admin)->get('/dashboard')->assertOk()
            ->assertSee('XYZ-9999', false)
            ->assertSee('BLOWBAGETS inspection: all items OK', false);
    }

    public function test_the_licence_list_separates_expiring_from_expired(): void
    {
        User::factory()->driver()->create([
            'agency_id' => $this->agency->id, 'name' => 'Ricardo Bautista',
            'license_number' => 'N01-14-220815', 'license_expiry_date' => now()->addDays(10),
        ]);
        User::factory()->driver()->create([
            'agency_id' => $this->agency->id, 'name' => 'Ramon Cruz',
            'license_number' => 'N01-09-778899', 'license_expiry_date' => now()->subDays(3),
        ]);

        $html = $this->actingAs($this->admin)->get('/dashboard')->assertOk()->getContent();

        $this->assertStringContainsString('Ricardo Bautista', $html);
        $this->assertStringContainsString('Ramon Cruz', $html);
        $this->assertStringContainsString('renewal required', $html);
        $this->assertStringContainsString('js-action-licenses-count">2 Warnings<', $html);
    }

    /** Empty is a legitimate state, not a broken page. */
    public function test_an_agency_with_nothing_pending_shows_empty_states(): void
    {
        $this->actingAs($this->admin)->get('/dashboard')->assertOk()
            ->assertSee('Nothing is waiting for review.')
            ->assertSee('No licences need attention.')
            ->assertSee('js-action-pending-count">0 New<', false);
    }

    public function test_the_page_never_shows_another_agencys_records(): void
    {
        $other = Agency::factory()->create(['code' => 'CHO']);
        $otherVehicle = Vehicle::factory()->create([
            'agency_id' => $other->id, 'plate_number' => 'CHO-4412',
        ]);
        DamageReport::factory()->create([
            'agency_id' => $other->id, 'vehicle_id' => $otherVehicle->id,
            'driver_id' => User::factory()->driver()->create(['agency_id' => $other->id])->id,
            'status' => DamageReport::STATUS_PENDING, 'nature_of_damage' => 'CHO business',
        ]);

        $this->actingAs($this->admin)->get('/dashboard')->assertOk()
            ->assertDontSee('CHO-4412')
            ->assertDontSee('CHO business')
            ->assertSee('js-metric-total">0<', false);
    }

    public function test_a_second_admin_of_the_same_agency_sees_the_same_figures(): void
    {
        Vehicle::factory()->count(2)->create(['agency_id' => $this->agency->id]);
        $second = User::factory()->admin()->create(['agency_id' => $this->agency->id]);

        foreach ([$this->admin, $second] as $user) {
            $this->actingAs($user)->get('/dashboard')->assertOk()
                ->assertSee('js-metric-total">2<', false);
        }
    }

    public function test_a_guest_is_redirected(): void
    {
        $this->get('/dashboard')->assertRedirect(route('login'));
    }
}
