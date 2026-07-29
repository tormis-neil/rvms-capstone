<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\Notification;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\VehicleStatusWriter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * The three event triggers FR-21 names: a new damage report and a new driver
 * access request reach EVERY administrator of the agency; a vehicle status
 * change reaches the vehicle's assigned driver.
 */
class NotificationTriggerTest extends TestCase
{
    use RefreshDatabase;

    private Agency $agency;

    private User $admin;

    private User $secondAdmin;

    private User $driver;

    private Vehicle $vehicle;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();

        $this->agency = Agency::factory()->create(['code' => 'BFP']);
        $this->admin = User::factory()->admin()->create(['agency_id' => $this->agency->id]);
        $this->secondAdmin = User::factory()->admin()->create(['agency_id' => $this->agency->id]);
        $this->driver = User::factory()->driver()->create(['agency_id' => $this->agency->id]);
        $this->vehicle = Vehicle::factory()->create([
            'agency_id' => $this->agency->id,
            'plate_number' => 'BFP-0001',
            'assigned_driver_id' => $this->driver->id,
            'status' => Vehicle::STATUS_OPERATIONAL,
        ]);
    }

    /* ------------------------- new damage report ------------------------- */

    public function test_a_damage_report_notifies_every_admin_of_the_agency(): void
    {
        Storage::fake('public');

        $this->actingAs($this->driver, 'sanctum')
            ->postJson('/api/v1/damage-reports', [
                'vehicle_id' => $this->vehicle->id,
                'nature_of_damage' => 'Cracked side mirror (driver side).',
            ])
            ->assertCreated();

        $this->assertDatabaseCount('notifications', 2);

        foreach ([$this->admin, $this->secondAdmin] as $recipient) {
            $this->assertDatabaseHas('notifications', [
                'user_id' => $recipient->id,
                'agency_id' => $this->agency->id,
                'type' => Notification::TYPE_NEW_DAMAGE_REPORT,
                'title' => 'New Damage Report Submitted',
            ]);
        }

        // The submitting driver is not notified of their own report.
        $this->assertDatabaseMissing('notifications', ['user_id' => $this->driver->id]);
    }

    public function test_a_damage_report_never_reaches_another_agency(): void
    {
        Storage::fake('public');
        $other = Agency::factory()->create(['code' => 'PNP']);
        $otherAdmin = User::factory()->admin()->create(['agency_id' => $other->id]);

        $this->actingAs($this->driver, 'sanctum')
            ->postJson('/api/v1/damage-reports', [
                'vehicle_id' => $this->vehicle->id,
                'nature_of_damage' => 'Cracked side mirror.',
            ])
            ->assertCreated();

        $this->assertDatabaseMissing('notifications', ['user_id' => $otherAdmin->id]);
    }

    public function test_the_damage_message_names_the_vehicle(): void
    {
        Storage::fake('public');

        $this->actingAs($this->driver, 'sanctum')
            ->postJson('/api/v1/damage-reports', [
                'vehicle_id' => $this->vehicle->id,
                'nature_of_damage' => 'Cracked side mirror (driver side).',
                'photo' => UploadedFile::fake()->image('damage.jpg'),
            ])
            ->assertCreated();

        $notification = Notification::withoutGlobalScopes()
            ->where('user_id', $this->admin->id)->firstOrFail();

        $this->assertStringContainsString('BFP-0001', $notification->message);
        $this->assertStringContainsString('Cracked side mirror', $notification->message);
        $this->assertSame('BFP-0001', $notification->data['plate']);
    }

    /* ------------------------ flagged inspections ------------------------ */

    private function submitInspection(array $flag = []): \Illuminate\Testing\TestResponse
    {
        $this->seed(\Database\Seeders\InspectionChecklistSeeder::class);

        $items = \App\Models\InspectionChecklistItem::forAgencyCode('BFP')->get()
            ->map(fn ($item) => in_array($item->name, $flag, true)
                ? ['checklist_item_id' => $item->id, 'status' => 'Has Issue', 'remarks' => 'unusual noise']
                : ['checklist_item_id' => $item->id, 'status' => 'OK'])
            ->all();

        return $this->actingAs($this->driver, 'sanctum')->postJson('/api/v1/inspections', [
            'vehicle_id' => $this->vehicle->id,
            'items' => $items,
        ]);
    }

    public function test_a_flagged_inspection_notifies_every_admin(): void
    {
        $this->submitInspection(['Brakes', 'Tires'])->assertCreated();

        $this->assertDatabaseCount('notifications', 2);

        foreach ([$this->admin, $this->secondAdmin] as $recipient) {
            $this->assertDatabaseHas('notifications', [
                'user_id' => $recipient->id,
                'type' => Notification::TYPE_INSPECTION_FLAGGED,
                'title' => 'Inspection Reported an Issue',
            ]);
        }
    }

    /** The whole reason for choosing "flagged only": routine submissions stay quiet. */
    public function test_an_all_ok_inspection_notifies_nobody(): void
    {
        $this->submitInspection()->assertCreated();

        $this->assertDatabaseCount('notifications', 0);
    }

    public function test_the_flagged_message_names_the_vehicle_and_the_items(): void
    {
        $this->submitInspection(['Brakes', 'Tires'])->assertCreated();

        $notification = Notification::withoutGlobalScopes()
            ->where('user_id', $this->admin->id)->firstOrFail();

        $this->assertSame('BFP-0001 — 2 items flagged: Brakes, Tires.', $notification->message);
        $this->assertSame(2, $notification->data['flagged_count']);
        $this->assertSame(['Brakes', 'Tires'], $notification->data['flagged_items']);
    }

    /** One flagged item reads "1 item", not "1 items". */
    public function test_a_single_flagged_item_is_worded_in_the_singular(): void
    {
        $this->submitInspection(['Brakes'])->assertCreated();

        $notification = Notification::withoutGlobalScopes()
            ->where('user_id', $this->admin->id)->firstOrFail();

        $this->assertSame('BFP-0001 — 1 item flagged: Brakes.', $notification->message);
    }

    public function test_a_flagged_inspection_never_reaches_another_agency(): void
    {
        $other = Agency::factory()->create(['code' => 'PNP']);
        $otherAdmin = User::factory()->admin()->create(['agency_id' => $other->id]);

        $this->submitInspection(['Brakes'])->assertCreated();

        $this->assertDatabaseMissing('notifications', ['user_id' => $otherAdmin->id]);
    }

    /* ---------------------- vehicle status change ------------------------ */

    public function test_a_status_change_notifies_the_assigned_driver(): void
    {
        app(VehicleStatusWriter::class)->write(
            $this->vehicle,
            Vehicle::STATUS_NOT_OPERATIONAL,
            VehicleStatusWriter::SOURCE_DAMAGE,
        );

        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->driver->id,
            'type' => Notification::TYPE_VEHICLE_STATUS_UPDATE,
            'title' => 'Vehicle Status Updated',
            'message' => 'BFP-0001 is now Not Operational.',
        ]);
    }

    /** Every module writes through the same gate, so all of them notify. */
    public function test_a_dispatch_status_write_also_notifies(): void
    {
        app(VehicleStatusWriter::class)->writeFromDispatch(
            $this->vehicle,
            Vehicle::STATUS_DISPATCHED,
            VehicleStatusWriter::SOURCE_DISPATCH_OPEN,
        );

        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->driver->id,
            'type' => Notification::TYPE_VEHICLE_STATUS_UPDATE,
            'message' => 'BFP-0001 is now Dispatched.',
        ]);
    }

    /**
     * Status writes are idempotent by design — reviewing an inspection without
     * moving the status re-writes the same value. That must not ring the driver.
     */
    public function test_rewriting_the_same_status_notifies_nobody(): void
    {
        app(VehicleStatusWriter::class)->write(
            $this->vehicle,
            Vehicle::STATUS_OPERATIONAL, // already Operational
            VehicleStatusWriter::SOURCE_INSPECTION,
        );

        $this->assertDatabaseCount('notifications', 0);
    }

    public function test_a_vehicle_with_no_assigned_driver_notifies_nobody(): void
    {
        $unassigned = Vehicle::factory()->create([
            'agency_id' => $this->agency->id,
            'assigned_driver_id' => null,
            'status' => Vehicle::STATUS_OPERATIONAL,
        ]);

        app(VehicleStatusWriter::class)->write(
            $unassigned,
            Vehicle::STATUS_UNDER_PM,
            VehicleStatusWriter::SOURCE_PM,
        );

        $this->assertDatabaseCount('notifications', 0);
    }

    /** A pending or rejected driver is not an active recipient. */
    public function test_a_non_active_assigned_driver_is_not_notified(): void
    {
        $this->driver->update(['status' => User::STATUS_PENDING]);

        app(VehicleStatusWriter::class)->write(
            $this->vehicle,
            Vehicle::STATUS_NOT_OPERATIONAL,
            VehicleStatusWriter::SOURCE_VEHICLES,
        );

        $this->assertDatabaseCount('notifications', 0);
    }

    /**
     * The status change carries where it came from, for the driver's context.
     *
     * The keys are `from_status`/`to_status` rather than the obvious
     * `from`/`to` because FCM reserves `from` in a data payload and rejects
     * the entire message — which is exactly how this alert came to store its
     * row and never reach a phone. FcmMessage refuses reserved keys outright
     * now, so a rename back would fail loudly instead of silently.
     */
    public function test_the_status_payload_records_the_transition(): void
    {
        app(VehicleStatusWriter::class)->write(
            $this->vehicle,
            Vehicle::STATUS_UNDER_PM,
            VehicleStatusWriter::SOURCE_PM,
        );

        $notification = Notification::withoutGlobalScopes()
            ->where('user_id', $this->driver->id)->firstOrFail();

        $this->assertSame('Operational', $notification->data['from_status']);
        $this->assertSame('Under Preventive Maintenance', $notification->data['to_status']);
        $this->assertSame('PM Schedules', $notification->data['source']);
    }

    /* ---------------------- driver self-registration --------------------- */

    public function test_self_registration_notifies_every_admin_of_that_agency(): void
    {
        $this->postJson('/api/v1/register', [
            'agency_id' => $this->agency->id,
            'name' => 'Bagong Drayber',
            'email' => 'bagong@rvms.local',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertCreated();

        $this->assertDatabaseCount('notifications', 2);

        foreach ([$this->admin, $this->secondAdmin] as $recipient) {
            $this->assertDatabaseHas('notifications', [
                'user_id' => $recipient->id,
                'type' => Notification::TYPE_NEW_ACCESS_REQUEST,
                'title' => 'New Driver Access Request',
                'message' => 'Bagong Drayber has requested a driver account.',
            ]);
        }
    }

    public function test_self_registration_only_notifies_the_chosen_agency(): void
    {
        $other = Agency::factory()->create(['code' => 'CHO']);
        $otherAdmin = User::factory()->admin()->create(['agency_id' => $other->id]);

        $this->postJson('/api/v1/register', [
            'agency_id' => $other->id,
            'name' => 'CHO Applicant',
            'email' => 'cho.applicant@rvms.local',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertCreated();

        $this->assertDatabaseHas('notifications', ['user_id' => $otherAdmin->id]);
        $this->assertDatabaseMissing('notifications', ['user_id' => $this->admin->id]);
    }

    /** A registration that fails validation must not notify anyone. */
    public function test_a_rejected_registration_notifies_nobody(): void
    {
        $this->postJson('/api/v1/register', [
            'agency_id' => $this->agency->id,
            'name' => 'Bad Applicant',
            'email' => 'not-an-email',
            'password' => 'short',
        ])->assertStatus(422);

        $this->assertDatabaseCount('notifications', 0);
    }
}
