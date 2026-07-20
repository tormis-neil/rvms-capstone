<?php

namespace Database\Factories;

use App\Models\Agency;
use App\Models\Inspection;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Inspection>
 */
class InspectionFactory extends Factory
{
    protected $model = Inspection::class;

    public function definition(): array
    {
        $agency = Agency::factory();

        return [
            'agency_id' => $agency,
            'vehicle_id' => Vehicle::factory()->for($agency),
            'driver_id' => User::factory()->driver()->for($agency),
            'inspection_date' => now()->toDateString(),
            'review_status' => Inspection::STATUS_PENDING,
            'reviewed_by' => null,
            'reviewed_at' => null,
        ];
    }

    public function reviewed(): static
    {
        return $this->state(fn () => [
            'review_status' => Inspection::STATUS_REVIEWED,
            'reviewed_at' => now(),
        ]);
    }
}
