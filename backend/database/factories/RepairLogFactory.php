<?php

namespace Database\Factories;

use App\Models\Agency;
use App\Models\RepairLog;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RepairLog>
 */
class RepairLogFactory extends Factory
{
    protected $model = RepairLog::class;

    public function definition(): array
    {
        $agency = Agency::factory();

        return [
            'agency_id' => $agency,
            'vehicle_id' => Vehicle::factory()->for($agency),
            'driver_id' => null,
            'repair_date' => now()->toDateString(),
            'scope_of_work' => $this->faker->sentence(),
            'parts_replaced' => $this->faker->words(3, true),
            'cost' => $this->faker->randomFloat(2, 500, 25000),
            'repair_source' => RepairLog::SOURCE_INTERNAL,
            'external_shop_name' => null,
            'remarks' => null,
        ];
    }

    public function external(): static
    {
        return $this->state(fn () => [
            'repair_source' => RepairLog::SOURCE_EXTERNAL,
            'external_shop_name' => $this->faker->company(),
        ]);
    }
}
