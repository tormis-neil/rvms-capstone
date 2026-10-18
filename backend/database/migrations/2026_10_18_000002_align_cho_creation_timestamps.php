<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Align account and vehicle creation timestamps for City Health Office (CHO).
 *
 * Ensures:
 * 1. Admin (cho@email.com) was created on Sep 14, 2026 at 08:00 AM before any reviews or dispatches.
 * 2. CHO Fleet (SHA 2625, SHA 1247, SHA 1248) was created on Sep 14, 2026 between 08:30 AM and 09:00 AM.
 * 3. CHO Drivers (Balloca, Gamao, Serrato, Canaman) were created on Sep 14, 2026 between 09:15 AM and 10:15 AM.
 * 4. Strictly resolves timestamp mismatch where users/vehicles were created after operational logs (Sep 15 - Oct 23, 2026).
 */
return new class extends Migration
{
    public function up(): void
    {
        $agency = DB::table('agencies')->where('code', 'CHO')->first();
        if (! $agency) {
            return;
        }

        // 1. Align CHO Admin
        DB::table('users')
            ->where('agency_id', $agency->id)
            ->where('role', 'admin')
            ->update(['created_at' => '2026-09-14 08:00:00']);

        // 2. Align CHO Vehicles
        $vehicleTimestamps = [
            'SHA 2625' => '2026-09-14 08:30:00',
            'SHA 1247' => '2026-09-14 08:45:00',
            'SHA 1248' => '2026-09-14 09:00:00',
        ];

        foreach ($vehicleTimestamps as $plate => $createdAt) {
            DB::table('vehicles')
                ->where('agency_id', $agency->id)
                ->where('plate_number', $plate)
                ->update(['created_at' => $createdAt]);
        }

        // 3. Align CHO Drivers
        $driverTimestamps = [
            'balloca.cho@email.com' => '2026-09-14 09:15:00',
            'gamao.cho@email.com'   => '2026-09-14 09:30:00',
            'serrato.cho@email.com' => '2026-09-14 10:00:00',
            'canaman.cho@email.com' => '2026-09-14 10:15:00',
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
