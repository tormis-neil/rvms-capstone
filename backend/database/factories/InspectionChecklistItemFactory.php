<?php

namespace Database\Factories;

use App\Models\InspectionChecklistItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InspectionChecklistItem>
 */
class InspectionChecklistItemFactory extends Factory
{
    protected $model = InspectionChecklistItem::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->word(),
            'is_bfp_only' => false,
            'sort_order' => fake()->numberBetween(1, 20),
        ];
    }
}
