<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\Dispatch;
use App\Models\Inspection;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * The dashboard half of delete, restore and password reset (FR-22, FR-05,
 * FR-06 — 2026-08).
 *
 * The API suites prove the rules; this proves an administrator can actually
 * reach them — the buttons exist on the page, the routes behind them work, and
 * the Deleted Records sections appear only when there is something to restore.
 */
class WebRecordDeletionTest extends TestCase
{
    use RefreshDatabase;

    private Agency $agency;

    private User $admin;

    private User $driver;

    private Vehicle $vehicle;

    protected function setUp(): void
    {
        parent::setUp();
        $this->agency = Agency::factory()->create(['code' => 'BFP']);
        $this->admin = User::factory()->admin()->create([
            'agency_id' => $this->agency->id,
            'password' => 'password',
        ]);
        $this->driver = User::factory()->driver()->create([
            'agency_id' => $this->agency->id,
            'name' => 'Ramon Villanueva',
        ]);
        $this->vehicle = Vehicle::factory()->create([
            'agency_id' => $this->agency->id,
            'plate_number' => 'BFP-0001',
            'assigned_driver_id' => $this->driver->id,
        ]);
    }

    /* ------------------------------ vehicles ------------------------------ */

    public function test_the_vehicles_page_offers_a_delete_button(): void
    {
        $this->actingAs($this->admin)->get('/vehicles')
            ->assertOk()
            ->assertSee('js-delete', escape: false)
            ->assertSee(route('vehicles.destroy', $this->vehicle), escape: false);
    }

    public function test_an_admin_deletes_and_restores_a_vehicle_from_the_page(): void
    {
        $this->actingAs($this->admin)
            ->from('/vehicles')
            ->delete(route('vehicles.destroy', $this->vehicle))
            ->assertRedirect('/vehicles')
            ->assertSessionHas('status');

        $this->assertSoftDeleted('vehicles', ['id' => $this->vehicle->id]);

        // It leaves the main table and appears under Deleted Vehicles.
        $page = $this->actingAs($this->admin)->get('/vehicles')->assertOk();
        $page->assertSee('Deleted Vehicles');

        $this->actingAs($this->admin)
            ->from('/vehicles')
            ->patch(route('vehicles.restore', $this->vehicle->id))
            ->assertRedirect('/vehicles');

        $this->assertNotSoftDeleted('vehicles', ['id' => $this->vehicle->id]);
    }

    /** The section is a documented addition; it must not appear unprompted. */
    public function test_the_deleted_section_is_hidden_when_nothing_is_deleted(): void
    {
        $this->actingAs($this->admin)->get('/vehicles')
            ->assertOk()
            ->assertDontSee('Deleted Vehicles');
    }

    public function test_deleting_a_dispatched_vehicle_is_refused_on_the_page(): void
    {
        Dispatch::factory()->create([
            'agency_id' => $this->agency->id,
            'vehicle_id' => $this->vehicle->id,
            'driver_id' => $this->driver->id,
            'time_in' => null,
        ]);

        $this->actingAs($this->admin)
            ->from('/vehicles')
            ->delete(route('vehicles.destroy', $this->vehicle))
            ->assertRedirect('/vehicles')
            ->assertSessionHasErrors('vehicle');

        $this->assertNotSoftDeleted('vehicles', ['id' => $this->vehicle->id]);
    }

    public function test_another_agencys_vehicle_cannot_be_deleted_from_the_web(): void
    {
        $other = Agency::factory()->create(['code' => 'PNP']);
        $foreign = Vehicle::factory()->create(['agency_id' => $other->id]);

        $this->actingAs($this->admin)
            ->delete(route('vehicles.destroy', $foreign))
            ->assertNotFound();
    }

    /* ------------------------------- drivers ------------------------------ */

    public function test_the_drivers_page_offers_reset_and_delete_buttons(): void
    {
        $this->actingAs($this->admin)->get('/drivers')
            ->assertOk()
            ->assertSee(route('drivers.password', $this->driver), escape: false)
            ->assertSee(route('drivers.destroy', $this->driver), escape: false);
    }

    public function test_an_admin_deletes_and_restores_a_driver_from_the_page(): void
    {
        $inspection = Inspection::factory()->create([
            'agency_id' => $this->agency->id,
            'vehicle_id' => $this->vehicle->id,
            'driver_id' => $this->driver->id,
        ]);

        $this->actingAs($this->admin)
            ->from('/drivers')
            ->delete(route('drivers.destroy', $this->driver))
            ->assertRedirect('/drivers');

        $this->assertSoftDeleted('users', ['id' => $this->driver->id]);
        // The history survived, and still names them.
        $this->assertSame('Ramon Villanueva', $inspection->fresh()->driver->name);

        $this->actingAs($this->admin)->get('/drivers')->assertSee('Deleted Drivers');

        $this->actingAs($this->admin)
            ->from('/drivers')
            ->patch(route('drivers.restore', $this->driver->id))
            ->assertRedirect('/drivers');

        $this->assertNotSoftDeleted('users', ['id' => $this->driver->id]);
    }

    public function test_an_admin_resets_a_drivers_password_from_the_page(): void
    {
        $this->actingAs($this->admin)
            ->from('/drivers')
            ->patch(route('drivers.password', $this->driver), ['password' => 'new-secret-123'])
            ->assertRedirect('/drivers')
            ->assertSessionHas('status');

        $this->assertTrue(Hash::check('new-secret-123', $this->driver->fresh()->password));
    }

    public function test_a_short_driver_password_is_refused_on_the_page(): void
    {
        $this->actingAs($this->admin)
            ->from('/drivers')
            ->patch(route('drivers.password', $this->driver), ['password' => 'short'])
            ->assertSessionHasErrors('password');
    }

    /* --------------------- administrators on the profile ------------------ */

    public function test_the_profile_lists_colleagues_but_not_yourself(): void
    {
        $colleague = User::factory()->admin()->create([
            'agency_id' => $this->agency->id,
            'name' => 'Deputy Officer',
        ]);

        $page = $this->actingAs($this->admin)->get('/profile')->assertOk();

        $page->assertSee('Agency Administrators');
        $page->assertSee('Deputy Officer');
        $page->assertSee(route('admins.password', $colleague), escape: false);
        $page->assertDontSee(route('admins.password', $this->admin), escape: false);
    }

    /** A sole administrator has nobody to reset — the section stays hidden. */
    public function test_a_sole_administrator_sees_no_colleagues_section(): void
    {
        $this->actingAs($this->admin)->get('/profile')
            ->assertOk()
            ->assertDontSee('Agency Administrators');
    }

    public function test_an_admin_resets_a_colleague_from_the_profile_page(): void
    {
        $colleague = User::factory()->admin()->create(['agency_id' => $this->agency->id]);

        $this->actingAs($this->admin)
            ->from('/profile')
            ->patch(route('admins.password', $colleague), [
                'current_password' => 'password',
                'password' => 'colleague-new-123',
            ])
            ->assertRedirect('/profile')
            ->assertSessionHas('status');

        $this->assertTrue(Hash::check('colleague-new-123', $colleague->fresh()->password));
    }

    public function test_the_colleague_reset_demands_your_own_password(): void
    {
        $colleague = User::factory()->admin()->create(['agency_id' => $this->agency->id]);

        $this->actingAs($this->admin)
            ->from('/profile')
            ->patch(route('admins.password', $colleague), [
                'current_password' => 'wrong-password',
                'password' => 'colleague-new-123',
            ])
            ->assertSessionHasErrors('current_password');

        $this->assertFalse(Hash::check('colleague-new-123', $colleague->fresh()->password));
    }

    public function test_another_agencys_admin_cannot_be_reset_from_the_web(): void
    {
        $other = Agency::factory()->create(['code' => 'PNP']);
        $foreign = User::factory()->admin()->create(['agency_id' => $other->id]);

        $this->actingAs($this->admin)
            ->patch(route('admins.password', $foreign), [
                'current_password' => 'password',
                'password' => 'foreign-new-123',
            ])
            ->assertNotFound();
    }

    /* ------------------------------- guests ------------------------------- */

    public function test_a_guest_cannot_delete_reset_or_restore(): void
    {
        $this->delete(route('vehicles.destroy', $this->vehicle))->assertRedirect('/login');
        $this->delete(route('drivers.destroy', $this->driver))->assertRedirect('/login');
        $this->patch(route('drivers.password', $this->driver), ['password' => 'x-new-password'])->assertRedirect('/login');
    }
}
