<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\DamageReport;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * R4 — Damage reports API (FR-11 submission, FR-12 review): drivers file
 * reports against their own agency's vehicle (photo optional, auto-dated,
 * Pending); a driver sees only their own; admins review agency-wide and may
 * change the vehicle status; cross-agency access 404s.
 */
class DamageReportTest extends TestCase
{
    use RefreshDatabase;

    private function driverWithVehicle(string $code = 'BFP'): array
    {
        $agency = Agency::factory()->create(['code' => $code]);
        $driver = User::factory()->driver()->create(['agency_id' => $agency->id]);
        $vehicle = Vehicle::factory()->create(['agency_id' => $agency->id]);

        return [$agency, $driver, $vehicle];
    }

    public function test_driver_files_a_damage_report_without_a_photo(): void
    {
        [$agency, $driver, $vehicle] = $this->driverWithVehicle();
        Sanctum::actingAs($driver);

        $response = $this->postJson('/api/v1/damage-reports', [
            'vehicle_id' => $vehicle->id,
            'nature_of_damage' => 'Cracked side mirror (driver side)',
            'suspected_parts' => 'Side mirror assembly',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.status', DamageReport::STATUS_PENDING)
            ->assertJsonPath('data.nature_of_damage', 'Cracked side mirror (driver side)')
            ->assertJsonPath('data.photo_url', null);

        $response->assertJsonPath('data.date_reported', now()->toDateString());

        $this->assertDatabaseHas('damage_reports', [
            'vehicle_id' => $vehicle->id,
            'driver_id' => $driver->id,
            'agency_id' => $agency->id,
            'status' => DamageReport::STATUS_PENDING,
        ]);
    }

    public function test_driver_files_a_damage_report_with_a_photo(): void
    {
        Storage::fake('public');
        [, $driver, $vehicle] = $this->driverWithVehicle();
        Sanctum::actingAs($driver);

        $response = $this->postJson('/api/v1/damage-reports', [
            'vehicle_id' => $vehicle->id,
            'nature_of_damage' => 'Dented rear bumper',
            'photo' => UploadedFile::fake()->image('damage.jpg'),
        ]);

        $response->assertCreated();
        $path = $response->json('data.photo_path');
        $this->assertNotNull($path);
        Storage::disk('public')->assertExists($path);
    }

    public function test_nature_of_damage_is_required(): void
    {
        [, $driver, $vehicle] = $this->driverWithVehicle();
        Sanctum::actingAs($driver);

        $this->postJson('/api/v1/damage-reports', ['vehicle_id' => $vehicle->id])
            ->assertStatus(422)
            ->assertJsonValidationErrors('nature_of_damage');
    }

    public function test_driver_cannot_report_against_another_agencys_vehicle(): void
    {
        [, $driver] = $this->driverWithVehicle('BFP');
        $foreignVehicle = Vehicle::factory()->create([
            'agency_id' => Agency::factory()->create(['code' => 'PNP'])->id,
        ]);
        Sanctum::actingAs($driver);

        $this->postJson('/api/v1/damage-reports', [
            'vehicle_id' => $foreignVehicle->id,
            'nature_of_damage' => 'x',
        ])->assertStatus(422)->assertJsonValidationErrors('vehicle_id');
    }

    public function test_admin_cannot_submit_a_damage_report(): void
    {
        [$agency, , $vehicle] = $this->driverWithVehicle();
        Sanctum::actingAs(User::factory()->admin()->create(['agency_id' => $agency->id]));

        $this->postJson('/api/v1/damage-reports', [
            'vehicle_id' => $vehicle->id,
            'nature_of_damage' => 'x',
        ])->assertForbidden();
    }

    public function test_driver_history_lists_only_their_own_reports(): void
    {
        [$agency, $driver, $vehicle] = $this->driverWithVehicle();
        $mine = DamageReport::factory()->create([
            'agency_id' => $agency->id, 'vehicle_id' => $vehicle->id, 'driver_id' => $driver->id,
        ]);
        $coworker = User::factory()->driver()->create(['agency_id' => $agency->id]);
        DamageReport::factory()->create([
            'agency_id' => $agency->id, 'vehicle_id' => $vehicle->id, 'driver_id' => $coworker->id,
        ]);

        Sanctum::actingAs($driver);

        $this->getJson('/api/v1/damage-reports')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $mine->id);
    }

    public function test_driver_cannot_open_another_drivers_report(): void
    {
        [$agency, $driver, $vehicle] = $this->driverWithVehicle();
        $coworker = User::factory()->driver()->create(['agency_id' => $agency->id]);
        $theirs = DamageReport::factory()->create([
            'agency_id' => $agency->id, 'vehicle_id' => $vehicle->id, 'driver_id' => $coworker->id,
        ]);

        Sanctum::actingAs($driver);

        $this->getJson("/api/v1/damage-reports/{$theirs->id}")->assertNotFound();
    }

    public function test_admin_sees_the_whole_agency_and_reviews_updating_the_vehicle(): void
    {
        [$agency, $driver, $vehicle] = $this->driverWithVehicle();
        $admin = User::factory()->admin()->create(['agency_id' => $agency->id]);
        $report = DamageReport::factory()->create([
            'agency_id' => $agency->id, 'vehicle_id' => $vehicle->id, 'driver_id' => $driver->id,
        ]);

        Sanctum::actingAs($admin);

        $this->getJson('/api/v1/damage-reports')->assertOk()->assertJsonCount(1, 'data');

        $this->patchJson("/api/v1/damage-reports/{$report->id}/review", [
            'vehicle_status' => Vehicle::STATUS_NOT_OPERATIONAL,
        ])->assertOk()->assertJsonPath('data.status', DamageReport::STATUS_REVIEWED);

        $this->assertSame(DamageReport::STATUS_REVIEWED, $report->fresh()->status);
        $this->assertSame($admin->id, $report->fresh()->reviewed_by);
        $this->assertSame(Vehicle::STATUS_NOT_OPERATIONAL, $vehicle->fresh()->status);
    }

    public function test_review_rejects_the_dispatched_status(): void
    {
        [$agency, $driver, $vehicle] = $this->driverWithVehicle();
        $admin = User::factory()->admin()->create(['agency_id' => $agency->id]);
        $report = DamageReport::factory()->create([
            'agency_id' => $agency->id, 'vehicle_id' => $vehicle->id, 'driver_id' => $driver->id,
        ]);

        Sanctum::actingAs($admin);

        $this->patchJson("/api/v1/damage-reports/{$report->id}/review", [
            'vehicle_status' => Vehicle::STATUS_DISPATCHED,
        ])->assertStatus(422)->assertJsonValidationErrors('vehicle_status');
    }

    public function test_cross_agency_report_is_not_found(): void
    {
        [$agency] = $this->driverWithVehicle('BFP');
        $admin = User::factory()->admin()->create(['agency_id' => $agency->id]);

        $foreignAgency = Agency::factory()->create(['code' => 'PNP']);
        $foreign = DamageReport::factory()->create([
            'agency_id' => $foreignAgency->id,
            'vehicle_id' => Vehicle::factory()->create(['agency_id' => $foreignAgency->id])->id,
            'driver_id' => User::factory()->driver()->create(['agency_id' => $foreignAgency->id])->id,
        ]);

        Sanctum::actingAs($admin);

        $this->getJson("/api/v1/damage-reports/{$foreign->id}")->assertNotFound();
        $this->patchJson("/api/v1/damage-reports/{$foreign->id}/review", [])->assertNotFound();
    }
}
