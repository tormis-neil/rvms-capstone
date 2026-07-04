<?php

namespace Database\Seeders;

use App\Models\Agency;
use App\Models\Inspection;
use App\Models\InspectionChecklistItem;
use App\Models\InspectionItem;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Database\Seeder;

class InspectionSeeder extends Seeder
{
    /**
     * Sample BLOWBAGETS submissions per agency (FR-09/FR-10) so the
     * Inspections page has data out of the box: yesterday's all-OK
     * inspection already Reviewed, and today's with flagged items
     * still Pending. Idempotent per vehicle+date.
     */
    public function run(): void
    {
        // Item name => remarks used for today's flagged inspection.
        $flagged = [
            'Oil' => 'Oil level below minimum mark, needs top-up.',
            'Tires' => 'Front-left tire pressure low.',
        ];

        foreach (Agency::all() as $agency) {
            $vehicle = Vehicle::withoutGlobalScopes()
                ->where('agency_id', $agency->id)
                ->orderBy('id')
                ->first();
            $driver = User::drivers()
                ->where('agency_id', $agency->id)
                ->orderBy('id')
                ->first();
            $admin = User::where('role', User::ROLE_ADMIN)
                ->where('agency_id', $agency->id)
                ->first();

            if ($vehicle === null || $driver === null) {
                continue;
            }

            $checklist = InspectionChecklistItem::forAgency($agency)->get();

            // Yesterday: all OK, already reviewed by the agency admin.
            $this->seedInspection(
                $agency, $vehicle, $driver, $checklist,
                date: now()->subDay()->toDateString(),
                flagged: [],
                reviewer: $admin,
            );

            // Today: two flagged items, still pending review.
            $this->seedInspection(
                $agency, $vehicle, $driver, $checklist,
                date: now()->toDateString(),
                flagged: $flagged,
                reviewer: null,
            );
        }
    }

    private function seedInspection(
        Agency $agency,
        Vehicle $vehicle,
        User $driver,
        $checklist,
        string $date,
        array $flagged,
        ?User $reviewer,
    ): void {
        $existing = Inspection::withoutGlobalScopes()
            ->where('vehicle_id', $vehicle->id)
            ->whereDate('inspection_date', $date)
            ->exists();

        if ($existing) {
            return;
        }

        $inspection = Inspection::withoutGlobalScopes()->create([
            'agency_id' => $agency->id,
            'vehicle_id' => $vehicle->id,
            'driver_id' => $driver->id,
            'inspection_date' => $date,
            'review_status' => $reviewer ? Inspection::REVIEW_REVIEWED : Inspection::REVIEW_PENDING,
            'reviewed_by' => $reviewer?->id,
            'reviewed_at' => $reviewer ? now()->subDay()->setTime(9, 0) : null,
        ]);

        $inspection->items()->createMany(
            $checklist->map(fn ($item) => [
                'checklist_item_id' => $item->id,
                'status' => isset($flagged[$item->name])
                    ? InspectionItem::STATUS_HAS_ISSUE
                    : InspectionItem::STATUS_OK,
                'remarks' => $flagged[$item->name] ?? null,
            ])->all(),
        );
    }
}
