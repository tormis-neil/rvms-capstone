<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\PmSchedule;
use App\Models\RepairLog;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * R5 — PM schedules API (FR-14): mileage-based needs km fields, time-based
 * needs a date; due_mileage is derived; completion stores the fields and never
 * auto-creates the next cycle; drivers cannot access; cross-agency 404s.
 */
class PmScheduleTest extends TestCase
{
    use RefreshDatabase;

    private function adminWithVehicle(string $code = 'BFP', int $mileage = 44000): array
    {
        $agency = Agency::factory()->create(['code' => $code]);
        $admin = User::factory()->admin()->create(['agency_id' => $agency->id]);
        $vehicle = Vehicle::factory()->create(['agency_id' => $agency->id, 'current_mileage' => $mileage]);

        return [$agency, $admin, $vehicle];
    }

    public function test_admin_creates_a_mileage_based_schedule_and_due_mileage_is_derived(): void
    {
        [$agency, $admin, $vehicle] = $this->adminWithVehicle(mileage: 44000);
        Sanctum::actingAs($admin);

        $response = $this->postJson('/api/v1/pm-schedules', [
            'vehicle_id' => $vehicle->id,
            'service_target' => 'Oil Change & Filter',
            'pm_type' => PmSchedule::TYPE_MILEAGE,
            'interval_km' => 5000,
            'last_pm_mileage' => 40000,
            'due_soon_threshold_km' => 500,
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.due_mileage', 45000)          // 40000 + 5000
            ->assertJsonPath('data.status', PmSchedule::STATUS_UPCOMING); // 44000 < 45000-500

        $this->assertDatabaseHas('pm_schedules', [
            'vehicle_id' => $vehicle->id,
            'agency_id' => $agency->id,
            'due_mileage' => 45000,
        ]);
    }

    public function test_new_mileage_schedule_within_threshold_is_due_soon(): void
    {
        [, $admin, $vehicle] = $this->adminWithVehicle(mileage: 44700); // within 500 of 45000
        Sanctum::actingAs($admin);

        $this->postJson('/api/v1/pm-schedules', [
            'vehicle_id' => $vehicle->id,
            'service_target' => 'Oil Change',
            'pm_type' => PmSchedule::TYPE_MILEAGE,
            'interval_km' => 5000,
            'last_pm_mileage' => 40000,
            'due_soon_threshold_km' => 500,
        ])->assertCreated()->assertJsonPath('data.status', PmSchedule::STATUS_DUE_SOON);
    }

    public function test_mileage_based_requires_km_fields(): void
    {
        [, $admin, $vehicle] = $this->adminWithVehicle();
        Sanctum::actingAs($admin);

        $this->postJson('/api/v1/pm-schedules', [
            'vehicle_id' => $vehicle->id,
            'service_target' => 'Oil Change',
            'pm_type' => PmSchedule::TYPE_MILEAGE,
        ])->assertStatus(422)->assertJsonValidationErrors(['interval_km', 'last_pm_mileage', 'due_soon_threshold_km']);
    }

    public function test_time_based_requires_a_date(): void
    {
        [, $admin, $vehicle] = $this->adminWithVehicle();
        Sanctum::actingAs($admin);

        $this->postJson('/api/v1/pm-schedules', [
            'vehicle_id' => $vehicle->id,
            'service_target' => 'Annual inspection',
            'pm_type' => PmSchedule::TYPE_TIME,
        ])->assertStatus(422)->assertJsonValidationErrors(['due_date', 'due_soon_threshold_days']);
    }

    public function test_completion_stores_fields_and_does_not_create_a_next_cycle(): void
    {
        [$agency, $admin, $vehicle] = $this->adminWithVehicle();
        $schedule = PmSchedule::factory()->create(['agency_id' => $agency->id, 'vehicle_id' => $vehicle->id]);
        Sanctum::actingAs($admin);

        $this->patchJson("/api/v1/pm-schedules/{$schedule->id}/complete", [
            'date_serviced' => now()->toDateString(),
            'completion_repair_source' => RepairLog::SOURCE_INTERNAL,
            'completion_parts_replaced' => 'Oil filter',
            'completion_remarks' => 'Done.',
        ])->assertOk()->assertJsonPath('data.status', PmSchedule::STATUS_COMPLETED);

        $this->assertSame(PmSchedule::STATUS_COMPLETED, $schedule->fresh()->status);
        // No new schedule was created — still exactly one.
        $this->assertSame(1, PmSchedule::withoutGlobalScopes()->count());
    }

    public function test_driver_cannot_access_pm_schedules(): void
    {
        [$agency] = $this->adminWithVehicle();
        Sanctum::actingAs(User::factory()->driver()->create(['agency_id' => $agency->id]));

        $this->getJson('/api/v1/pm-schedules')->assertForbidden();
        $this->postJson('/api/v1/pm-schedules', [])->assertForbidden();
    }

    public function test_cross_agency_schedule_is_not_found(): void
    {
        [, $admin] = $this->adminWithVehicle('BFP');
        $foreignAgency = Agency::factory()->create(['code' => 'PNP']);
        $foreign = PmSchedule::factory()->create([
            'agency_id' => $foreignAgency->id,
            'vehicle_id' => Vehicle::factory()->create(['agency_id' => $foreignAgency->id])->id,
        ]);

        Sanctum::actingAs($admin);

        $this->getJson("/api/v1/pm-schedules/{$foreign->id}")->assertNotFound();
        $this->patchJson("/api/v1/pm-schedules/{$foreign->id}/complete", [])->assertNotFound();
    }
}
