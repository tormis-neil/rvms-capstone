<?php

namespace Database\Factories;

use App\Models\Agency;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Vehicle>
 */
class VehicleFactory extends Factory
{
    protected $model = Vehicle::class;

    public function definition(): array
    {
        return [
            'agency_id' => Agency::factory(),
            'assigned_driver_id' => null,
            'type' => fake()->randomElement(['Fire Truck', 'Rescue Van', 'Water Tanker', 'Service Vehicle', 'Ambulance']),
            'plate_number' => strtoupper(fake()->unique()->bothify('???-####')),
            'make' => fake()->randomElement(['Isuzu', 'Toyota', 'Hino', 'Mitsubishi', 'Nissan']),
            'model' => fake()->randomElement(['FTR 850', 'Hiace', '500', 'L300', 'Urvan']),
            'engine_number' => strtoupper(fake()->bothify('####-??-######')),
            'chassis_number' => strtoupper(fake()->bothify('???########')),
            'current_mileage' => fake()->numberBetween(1000, 120000),
            'status' => Vehicle::STATUS_OPERATIONAL,
        ];
    }

    public function status(string $status): static
    {
        return $this->state(fn () => ['status' => $status]);
    }
}
