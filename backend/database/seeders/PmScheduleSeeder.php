<?php

namespace Database\Seeders;

use App\Models\Agency;
use App\Models\PmSchedule;
use App\Models\Vehicle;
use Illuminate\Database\Seeder;

/**
 * Sample PM schedules so the page demonstrates itself (plan R5 Day 10):
 * per agency, one mileage-based near its threshold, one time-based, and one
 * completed. Statuses are computed from the vehicle's current mileage / dates.
 */
class PmScheduleSeeder extends Seeder
{
    public function run(): void
    {
        foreach (Agency::all() as $agency) {
            $vehicle = Vehicle::query()
                ->where('agency_id', $agency->id)
                ->orderBy('id')
                ->first();

            if (! $vehicle) {
                continue;
            }

            // Dual-trigger (Mileage & Time-Based), due soon: demonstrates concurrent
            // mileage and date tracking (whichever comes first).
            $current = (int) $vehicle->current_mileage;
            $mileage = new PmSchedule([
                'agency_id' => $agency->id,
                'vehicle_id' => $vehicle->id,
                'service_target' => 'Oil Change & Filter',
                'pm_type' => PmSchedule::TYPE_BOTH,
                'interval_km' => 5000,
                'last_pm_mileage' => max(0, $current - 4700),
                'due_mileage' => $current + 300,
                'due_soon_threshold_km' => 500,
                'due_date' => now()->addMonths(6)->toDateString(),
                'due_soon_threshold_days' => 14,
            ]);
            $mileage->status = $mileage->recalculatedStatus($current);
            $mileage->save();

            // Time-based, upcoming.
            $time = new PmSchedule([
                'agency_id' => $agency->id,
                'vehicle_id' => $vehicle->id,
                'service_target' => 'Brake Fluid Flush',
                'pm_type' => PmSchedule::TYPE_TIME,
                'due_date' => now()->addMonths(5)->toDateString(),
                'due_soon_threshold_days' => 14,
            ]);
            $time->status = $time->recalculatedStatus();
            $time->save();

            // Completed record.
            PmSchedule::create([
                'agency_id' => $agency->id,
                'vehicle_id' => $vehicle->id,
                'service_target' => 'Brake Inspection & Fluid',
                'pm_type' => PmSchedule::TYPE_TIME,
                'due_date' => now()->subDays(20)->toDateString(),
                'due_soon_threshold_days' => 14,
                'status' => PmSchedule::STATUS_COMPLETED,
                'date_serviced' => now()->subDays(19)->toDateString(),
                'completion_repair_source' => 'GSO Motorpool',
                'completion_parts_replaced' => 'Brake fluid',
                'completion_remarks' => 'Completed on schedule.',
            ]);
        }
    }
}
