<?php

namespace Database\Seeders;

use App\Models\Agency;
use App\Models\Inspection;
use App\Models\InspectionChecklistItem;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Database\Seeder;

/**
 * Sample inspections so the page demonstrates itself (plan R3 Day 6.11):
 * per agency, yesterday's all-OK inspection already Reviewed, and today's
 * inspection with 2 flagged items still Pending.
 */
class InspectionSeeder extends Seeder
{
    public function run(): void
    {
        foreach (Agency::all() as $agency) {
            $driver = User::query()
                ->where('agency_id', $agency->id)
                ->where('role', User::ROLE_DRIVER)
                ->orderBy('id')
                ->first();

            $vehicle = Vehicle::query()
                ->where('agency_id', $agency->id)
                ->orderBy('id')
                ->first();

            $admin = User::query()
                ->where('agency_id', $agency->id)
                ->where('role', User::ROLE_ADMIN)
                ->orderBy('id')
                ->first();

            if (! $driver || ! $vehicle) {
                continue;
            }

            $checklist = InspectionChecklistItem::forAgencyCode($agency->code)->get();

            // Yesterday — all OK, already Reviewed. Submission time set so the
            // DATE SUBMITTED column reads "Yesterday, 07:15 AM".
            $yesterday = Inspection::create([
                'agency_id' => $agency->id,
                'vehicle_id' => $vehicle->id,
                'driver_id' => $driver->id,
                'inspection_date' => now()->subDay()->toDateString(),
                'review_status' => Inspection::STATUS_REVIEWED,
                'reviewed_by' => $admin?->id,
                'reviewed_at' => now()->subDay(),
                'created_at' => now()->subDay()->setTime(7, 15),
                'updated_at' => now()->subDay()->setTime(7, 15),
            ]);
            foreach ($checklist as $item) {
                $yesterday->items()->create([
                    'checklist_item_id' => $item->id,
                    'status' => 'OK',
                ]);
            }

            // Today — two flagged items, still Pending.
            $flagged = ['Brakes' => 'Unusual noise during braking', 'Tires' => 'Low tire pressure (front right)'];
            $today = Inspection::create([
                'agency_id' => $agency->id,
                'vehicle_id' => $vehicle->id,
                'driver_id' => $driver->id,
                'inspection_date' => now()->toDateString(),
                'review_status' => Inspection::STATUS_PENDING,
                'created_at' => now()->setTime(7, 30),
                'updated_at' => now()->setTime(7, 30),
            ]);
            foreach ($checklist as $item) {
                $isFlagged = array_key_exists($item->name, $flagged);
                $today->items()->create([
                    'checklist_item_id' => $item->id,
                    'status' => $isFlagged ? 'Has Issue' : 'OK',
                    'remarks' => $isFlagged ? $flagged[$item->name] : null,
                ]);
            }
        }
    }
}
