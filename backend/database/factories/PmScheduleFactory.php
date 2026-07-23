<?php

namespace Database\Factories;

use App\Models\Agency;
use App\Models\PmSchedule;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PmSchedule>
 */
class PmScheduleFactory extends Factory
{
    protected $model = PmSchedule::class;

    public function definition(): array
    {
        $agency = Agency::factory();

        return [
            'agency_id' => $agency,
            'vehicle_id' => Vehicle::factory()->for($agency),
            'service_target' => 'Oil Change & Filter',
            'pm_type' => PmSchedule::TYPE_MILEAGE,
            'interval_km' => 5000,
            'last_pm_mileage' => 40000,
            'due_mileage' => 45000,
            'due_soon_threshold_km' => 500,
            'due_date' => null,
            'due_soon_threshold_days' => null,
            'status' => PmSchedule::STATUS_UPCOMING,
        ];
    }

    public function timeBased(): static
    {
        return $this->state(fn () => [
            'pm_type' => PmSchedule::TYPE_TIME,
            'interval_km' => null,
            'last_pm_mileage' => null,
            'due_mileage' => null,
            'due_date' => now()->addDays(20)->toDateString(),
            'due_soon_threshold_km' => null,
            'due_soon_threshold_days' => 7,
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn () => [
            'status' => PmSchedule::STATUS_COMPLETED,
            'date_serviced' => now()->toDateString(),
            'completion_repair_source' => 'Internal Office',
            'completion_parts_replaced' => 'Oil filter',
            'completion_remarks' => 'Serviced on schedule.',
        ]);
    }
}
