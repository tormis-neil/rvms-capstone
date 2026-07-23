<?php

namespace Database\Factories;

use App\Models\Agency;
use App\Models\Dispatch;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Dispatch>
 */
class DispatchFactory extends Factory
{
    protected $model = Dispatch::class;

    public function definition(): array
    {
        $agency = Agency::factory();

        return [
            'agency_id' => $agency,
            'vehicle_id' => Vehicle::factory()->for($agency),
            'driver_id' => User::factory()->driver()->for($agency),
            'mission_type' => 'Patrol',
            'mission_other' => null,
            'location' => $this->faker->city(),
            'time_out' => now()->subHours(2),
            'odometer_out' => null,
            'time_in' => null,
            'odometer_in' => null,
            'return_status' => null,
            'remarks' => null,
        ];
    }

    public function completed(): static
    {
        return $this->state(fn () => [
            'time_in' => now(),
            'return_status' => 'Operational',
        ]);
    }
}
