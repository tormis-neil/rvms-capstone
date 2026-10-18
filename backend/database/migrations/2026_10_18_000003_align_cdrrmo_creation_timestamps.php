<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Align account and vehicle creation timestamps for CDRRMO (2026-10-18).
 *
 * Ensures:
 * 1. Admin (cdrrmo.admin@email.com) was created on Sep 14, 2026 at 08:00 AM before any reviews or dispatches.
 * 2. Assigned Vehicles (CHO-4467, SAB-5120) created on Sep 14, 2026 between 08:30 AM and 08:45 AM.
 * 3. Reserve Fleet Vehicles (SHA-2006, AND-9023) created on Sep 14, 2026 between 08:45 AM and 09:00 AM.
 * 4. Active Drivers (Dela Peña, Calbiga, Mabini) created on Sep 14, 2026 between 09:00 AM and 09:30 AM.
 * 5. Strictly resolves timestamp mismatch where users/vehicles were created after operational logs (Sep 15 - Oct 23, 2026).
 */
return new class extends Migration
{
    public function up(): void
    {
        $agency = DB::table('agencies')->where('code', 'CDRRMO')->first();
        if (! $agency) {
            return;
        }

        // 1. Align CDRRMO Admin
        DB::table('users')
            ->where('agency_id', $agency->id)
            ->where('role', 'admin')
            ->update(['created_at' => '2026-09-14 08:00:00']);

        // 2. Align CDRRMO Vehicles (Assigned & Reserve)
        $vehicleTimestamps = [
            'CHO-4467' => '2026-09-14 08:30:00',
            'CHO 4467' => '2026-09-14 08:30:00',
            'SAB-5120' => '2026-09-14 08:45:00',
            'SAB 5120' => '2026-09-14 08:45:00',
            'SHA-2006' => '2026-09-14 08:45:00',
            'SHA 2006' => '2026-09-14 08:45:00',
            'AND-9023' => '2026-09-14 09:00:00',
            'AND 9023' => '2026-09-14 09:00:00',
        ];

        foreach ($vehicleTimestamps as $plate => $createdAt) {
            DB::table('vehicles')
                ->where('agency_id', $agency->id)
                ->where('plate_number', $plate)
                ->update(['created_at' => $createdAt]);
        }

        // 3. Align CDRRMO Drivers
        $driverTimestamps = [
            'jonathan@email.com' => '2026-09-14 09:00:00',
            'calbiga@email.com'  => '2026-09-14 09:15:00',
            'mabini@email.com'   => '2026-09-14 09:30:00',
        ];

        foreach ($driverTimestamps as $email => $createdAt) {
            DB::table('users')
                ->where('agency_id', $agency->id)
                ->where('email', $email)
                ->update(['created_at' => $createdAt]);
        }
    }

    public function down(): void
    {
        // Safe no-op on rollback
    }
};
