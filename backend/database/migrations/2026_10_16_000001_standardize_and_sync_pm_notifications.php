<?php

use App\Models\Notification;
use App\Models\PmSchedule;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Standardize and synchronize PM notifications across assigned co-drivers (2026-10-16).
 *
 * Ensures:
 * 1. All PM reminder titles are strictly standardized to 'Preventive Maintenance'
 *    (purging legacy 'Preventive Maintenance Approaching' and 'Upcoming Preventive Maintenance').
 * 2. Both primary driver (Rowel Gamao) and secondary driver (Idelfonso Balloca)
 *    assigned to SHA 2625 have identical, deduplicated PM notifications dated
 *    October 5, 2026 at 08:30 AM with identical payload and service details.
 * 3. Any duplicate live PM alerts (e.g. Oct 14 live triggers) are safely purged.
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1. System-wide standardization of legacy PM notification titles
        DB::table('notifications')
            ->whereIn('title', [
                'Preventive Maintenance Approaching',
                'Upcoming Preventive Maintenance',
            ])
            ->update(['title' => 'Preventive Maintenance']);

        // 2. Identify SHA 2625 and its drivers
        $vehicle = DB::table('vehicles')->where('plate_number', 'SHA 2625')->first();
        if (! $vehicle) {
            return;
        }

        $gamao = DB::table('users')->where('email', 'gamao.cho@email.com')->first()
            ?? ($vehicle->assigned_driver_id ? DB::table('users')->where('id', $vehicle->assigned_driver_id)->first() : null);

        $balloca = DB::table('users')->where('email', 'balloca.cho@email.com')->first()
            ?? ($vehicle->secondary_driver_id ? DB::table('users')->where('id', $vehicle->secondary_driver_id)->first() : null);

        $pm = DB::table('pm_schedules')
            ->where('vehicle_id', $vehicle->id)
            ->where('service_target', 'like', '%60,000 km%')
            ->first();

        $pmId = $pm?->id;

        $targetDrivers = array_filter([$gamao, $balloca]);

        foreach ($targetDrivers as $driver) {
            // Purge all legacy and duplicate PM reminder notifications for this driver on SHA 2625
            DB::table('notifications')
                ->where('user_id', $driver->id)
                ->where(function ($query) use ($vehicle) {
                    $query->where('title', 'like', '%Preventive Maintenance%')
                        ->orWhere('type', Notification::TYPE_PM_REMINDER)
                        ->orWhere('message', 'like', "%{$vehicle->plate_number}%");
                })
                ->where('type', Notification::TYPE_PM_REMINDER)
                ->delete();

            // Insert a single, canonical, standardized PM notification for the driver
            DB::table('notifications')->insert([
                'agency_id' => $vehicle->agency_id,
                'user_id' => $driver->id,
                'type' => Notification::TYPE_PM_REMINDER,
                'title' => 'Preventive Maintenance',
                'message' => 'SHA 2625 — 60,000 km Major Preventive Maintenance Service (PMS) is due soon.',
                'data' => json_encode([
                    'pm_schedule_id' => $pmId,
                    'vehicle_id' => $vehicle->id,
                    'plate' => 'SHA 2625',
                    'service_target' => '60,000 km Major Preventive Maintenance Service (PMS)',
                    'state' => 'Due Soon',
                ]),
                'is_read' => 0,
                'read_at' => null,
                'created_at' => '2026-10-05 08:30:00',
                'updated_at' => '2026-10-05 08:30:00',
            ]);
        }

        // 3. Deduplicate Admin PM Due Soon notifications for SHA 2625
        $admin = DB::table('users')
            ->where('agency_id', $vehicle->agency_id)
            ->where('role', 'admin')
            ->first();

        if ($admin) {
            $adminPmNotifs = DB::table('notifications')
                ->where('user_id', $admin->id)
                ->where('type', Notification::TYPE_PM_DUE_SOON)
                ->where('message', 'like', "%{$vehicle->plate_number}%")
                ->orderBy('created_at', 'asc')
                ->get();

            if ($adminPmNotifs->count() > 1) {
                // Keep the earliest, delete subsequent duplicates
                $keepId = $adminPmNotifs->first()->id;
                DB::table('notifications')
                    ->where('user_id', $admin->id)
                    ->where('type', Notification::TYPE_PM_DUE_SOON)
                    ->where('message', 'like', "%{$vehicle->plate_number}%")
                    ->where('id', '!=', $keepId)
                    ->delete();
            }
        }
    }

    public function down(): void
    {
        // Safe no-op on rollback since data standardization is idempotent
    }
};
