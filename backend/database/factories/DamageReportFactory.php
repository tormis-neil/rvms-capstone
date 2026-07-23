<?php

namespace Database\Factories;

use App\Models\Agency;
use App\Models\DamageReport;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DamageReport>
 */
class DamageReportFactory extends Factory
{
    protected $model = DamageReport::class;

    public function definition(): array
    {
        $agency = Agency::factory();

        return [
            'agency_id' => $agency,
            'vehicle_id' => Vehicle::factory()->for($agency),
            'driver_id' => User::factory()->driver()->for($agency),
            'nature_of_damage' => $this->faker->sentence(),
            'suspected_parts' => $this->faker->words(2, true),
            'photo_path' => null,
            'date_reported' => now()->toDateString(),
            'status' => DamageReport::STATUS_PENDING,
            'reviewed_by' => null,
            'reviewed_at' => null,
        ];
    }

    public function reviewed(): static
    {
        return $this->state(fn () => [
            'status' => DamageReport::STATUS_REVIEWED,
            'reviewed_at' => now(),
        ]);
    }
}
