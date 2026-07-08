<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Agency>
 */
class AgencyFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => strtoupper(fake()->unique()->lexify('??????')),
            'name' => fake()->company(),
            'location' => 'Calbayog City, Samar',
            'contact_number' => fake()->phoneNumber(),
            'email' => fake()->unique()->companyEmail(),
            'logo_path' => null,
            'license_expiry_warning_days' => 30,
        ];
    }
}
