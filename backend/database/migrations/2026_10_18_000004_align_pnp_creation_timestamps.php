<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Align account and vehicle creation timestamps for PNP (2026-10-18).
 *
 * Ensures:
 * 1. Admin (pnp.admin@email.com) was created on Sep 14, 2026 at 08:00 AM before any reviews.
 * 2. Assigned vehicle created on Sep 14, 2026 at 08:30 AM before first inspection on Sep 30.
 * 3. Unassigned vehicle SHA-7901 created on Sep 14, 2026 at 08:45 AM.
 * 4. Driver Manuel Tan created on Sep 14, 2026 at 09:00 AM before first inspection on Sep 30.
 * 5. Strictly resolves timestamp mismatch where users/vehicles were created after operational logs (Rule 11).
 */
return new class extends Migration
{
    public function up(): void
    {
        $agency = DB::table('agencies')->where('code', 'PNP')->first();
        if (! $agency) {
            return;
        }

        // 1. Align PNP Admin
        DB::table('users')
            ->where('agency_id', $agency->id)
            ->where('role', 'admin')
            ->update(['created_at' => '2026-09-14 08:00:00']);

        // 2. Align Driver Manuel Tan
        DB::table('users')
            ->where('agency_id', $agency->id)
            ->where(function ($q) {
                $q->where('name', 'like', '%Manuel Tan%')
                  ->orWhere('email', 'like', '%tan%');
            })
            ->update(['created_at' => '2026-09-14 09:00:00']);

        // 3. Align Unassigned Vehicle SHA-7901
        DB::table('vehicles')
            ->where('agency_id', $agency->id)
            ->where(function ($q) {
                $q->where('plate_number', 'SHA-7901')
                  ->orWhere('plate_number', 'SHA 7901');
            })
            ->update(['created_at' => '2026-09-14 08:45:00']);

        // 4. Align Assigned Patrol Vehicle
        DB::table('vehicles')
            ->where('agency_id', $agency->id)
            ->where(function ($q) {
                $q->where('plate_number', 'PNP-1067')
                  ->orWhere('plate_number', 'PNP 1067')
                  ->orWhere('plate_number', 'PNP-1021')
                  ->orWhere('plate_number', 'PNP 1021');
            })
            ->update(['created_at' => '2026-09-14 08:30:00']);
    }

    public function down(): void
    {
        // Safe no-op on rollback
    }
};
