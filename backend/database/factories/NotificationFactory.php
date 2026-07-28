<?php

namespace Database\Factories;

use App\Models\Agency;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Notification>
 */
class NotificationFactory extends Factory
{
    protected $model = Notification::class;

    public function definition(): array
    {
        return [
            'agency_id' => Agency::factory(),
            'user_id' => User::factory(),
            'type' => Notification::TYPE_NEW_DAMAGE_REPORT,
            'title' => 'New Damage Report Submitted',
            'message' => 'ABC-1234 — Cracked side mirror (driver side).',
            'data' => ['plate' => 'ABC-1234'],
            'is_read' => false,
            'read_at' => null,
        ];
    }

    public function read(): static
    {
        return $this->state(fn () => ['is_read' => true, 'read_at' => now()]);
    }

    public function ofType(string $type): static
    {
        return $this->state(fn () => ['type' => $type]);
    }

    /** Anything older than yesterday groups under "Earlier". */
    public function earlier(): static
    {
        return $this->state(fn () => ['created_at' => now()->subDays(5)]);
    }

    public function yesterday(): static
    {
        return $this->state(fn () => ['created_at' => now()->subDay()]);
    }
}
