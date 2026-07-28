<?php

namespace Database\Seeders;

use App\Models\Agency;
use App\Models\Notification;
use App\Models\PmSchedule;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Database\Seeder;

/**
 * Sample notifications so the bell and the notifications page demonstrate
 * themselves (FR-21), the way every other module's seeder does.
 *
 * Deliberately spread across Today / Yesterday / Earlier so all three group
 * headings render, and deliberately mixed read/unread so the unread badge and
 * the "Mark All as Read" button both have something to act on.
 *
 * Every agency's administrators get their own copies — an alert addressed to one
 * admin must never appear in another's inbox, not even in sample data.
 */
class NotificationSeeder extends Seeder
{
    public function run(): void
    {
        foreach (Agency::all() as $agency) {
            $admins = User::query()
                ->where('agency_id', $agency->id)
                ->where('role', User::ROLE_ADMIN)
                ->get();

            if ($admins->isEmpty()) {
                continue;
            }

            $vehicle = Vehicle::withoutGlobalScopes()->where('agency_id', $agency->id)->first();
            $driver = User::query()
                ->where('agency_id', $agency->id)
                ->where('role', User::ROLE_DRIVER)
                ->first();
            $plate = $vehicle?->plate_number ?? 'ABC-1234';

            $rows = [
                // Today — unread, so the bell carries a count on first login.
                [
                    'type' => Notification::TYPE_NEW_DAMAGE_REPORT,
                    'title' => 'New Damage Report Submitted',
                    'message' => "{$plate} — Cracked side mirror (driver side).",
                    'data' => ['plate' => $plate],
                    'is_read' => false,
                    'created_at' => now()->setTime(8, 10),
                ],
                [
                    'type' => Notification::TYPE_INSPECTION_FLAGGED,
                    'title' => 'Inspection Reported an Issue',
                    'message' => "{$plate} — 1 item flagged: Brakes.",
                    'data' => ['plate' => $plate, 'flagged_count' => 1, 'flagged_items' => ['Brakes']],
                    'is_read' => false,
                    'created_at' => now()->setTime(7, 5),
                ],
                // Yesterday — one unread, one already read.
                [
                    'type' => Notification::TYPE_LICENSE_EXPIRING,
                    'title' => 'License Expiring Soon',
                    'message' => ($driver?->name ?? 'A driver').' — licence expires '
                        .now()->addDays(12)->format('M j, Y').'.',
                    'data' => ['driver_id' => $driver?->id],
                    'is_read' => false,
                    'created_at' => now()->subDay()->setTime(9, 0),
                ],
                [
                    'type' => Notification::TYPE_PM_DUE_SOON,
                    'title' => 'PM Due Soon',
                    'message' => "{$plate} — ".($this->serviceTarget($agency->id) ?? 'Oil Change & Filter').' is due soon.',
                    'data' => ['plate' => $plate],
                    'is_read' => true,
                    'read_at' => now()->subDay()->setTime(9, 30),
                    'created_at' => now()->subDay()->setTime(8, 0),
                ],
                // Earlier — read, so the "Earlier" group renders too.
                [
                    'type' => Notification::TYPE_NEW_ACCESS_REQUEST,
                    'title' => 'New Driver Access Request',
                    'message' => ($driver?->name ?? 'A driver').' has requested a driver account.',
                    'data' => ['driver_id' => $driver?->id],
                    'is_read' => true,
                    'read_at' => now()->subDays(4),
                    'created_at' => now()->subDays(5)->setTime(14, 20),
                ],
            ];

            foreach ($admins as $admin) {
                foreach ($rows as $row) {
                    Notification::query()->create($row + [
                        'agency_id' => $agency->id,
                        'user_id' => $admin->id,
                    ]);
                }
            }
        }
    }

    private function serviceTarget(int $agencyId): ?string
    {
        return PmSchedule::withoutGlobalScopes()
            ->where('agency_id', $agencyId)
            ->value('service_target');
    }
}
