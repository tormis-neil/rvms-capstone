<?php

namespace Database\Factories;

use App\Models\Agency;
use App\Models\Inspection;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Inspection>
 */
class InspectionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'agency_id' => Agency::factory(),
            'vehicle_id' => fn (array $attributes) => Vehicle::factory()
                ->create(['agency_id' => $attributes['agency_id']])->id,
            'driver_id' => fn (array $attributes) => User::factory()->driver()
                ->create(['agency_id' => $attributes['agency_id']])->id,
            'inspection_date' => fake()->dateTimeBetween('-2 weeks', 'now')->format('Y-m-d'),
            'review_status' => Inspection::REVIEW_PENDING,
        ];
    }

    public function reviewed(): static
    {
        return $this->state(fn (array $attributes) => [
            'review_status' => Inspection::REVIEW_REVIEWED,
            'reviewed_at' => now(),
        ]);
    }
}
