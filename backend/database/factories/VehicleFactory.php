<?php

namespace Database\Factories;

use App\Models\Agency;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Vehicle>
 */
class VehicleFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'agency_id' => Agency::factory(),
            'assigned_driver_id' => null,
            'type' => fake()->randomElement(['Fire Truck', 'Ambulance', 'Patrol Car', 'Rescue Truck']),
            'plate_number' => strtoupper(fake()->bothify('???-####')),
            'make' => fake()->randomElement(['Isuzu', 'Toyota', 'Mitsubishi', 'Ford']),
            'model' => fake()->randomElement(['FTR 850', 'Hiace', 'Ranger', 'L300']),
            'engine_number' => strtoupper(fake()->bothify('ENG####??')),
            'chassis_number' => strtoupper(fake()->bothify('CHS####??')),
            'current_mileage' => fake()->numberBetween(0, 150000),
            'status' => Vehicle::STATUS_OPERATIONAL,
        ];
    }

    public function status(string $status): static
    {
        return $this->state(fn (array $attributes) => ['status' => $status]);
    }
}
