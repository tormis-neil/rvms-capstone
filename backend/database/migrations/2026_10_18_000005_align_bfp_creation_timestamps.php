<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Align account and vehicle creation timestamps for BFP (2026-10-18).
 *
 * Ensures:
 * 1. Admin (bfp@email.com) was created on Sep 14, 2026 at 08:00 AM before any reviews.
 * 2. Assigned Fire Truck RCA-908 created on Sep 14, 2026 at 08:30 AM before first inspection on Sep 25.
 * 3. Driver Alexander Kris Espina created on Sep 14, 2026 at 08:30 AM before first inspection on Sep 25.
 * 4. Assigned Water Tanker EFG-4532 created on Sep 14, 2026 at 08:45 AM before first inspection on Sep 26.
 * 5. Driver John Erol Reposo created on Sep 14, 2026 at 08:45 AM before first inspection on Sep 26.
 * 6. Strictly resolves timestamp mismatch where users/vehicles were created after operational logs (Rule 11).
 */
return new class extends Migration
{
    public function up(): void
    {
        $agency = DB::table('agencies')->where('code', 'BFP')->first();
        if (! $agency) {
            return;
        }

        // 1. Align BFP Admin
        DB::table('users')
            ->where('agency_id', $agency->id)
            ->where('role', 'admin')
            ->update(['created_at' => '2026-09-14 08:00:00']);

        // 2. Align Driver Alexander Kris Espina
        DB::table('users')
            ->where('agency_id', $agency->id)
            ->where(function ($q) {
                $q->where('name', 'like', '%Espina%')
                  ->orWhere('email', 'like', '%espina%');
            })
            ->update(['created_at' => '2026-09-14 08:30:00']);

        // 3. Align Driver John Erol Reposo
        DB::table('users')
            ->where('agency_id', $agency->id)
            ->where(function ($q) {
                $q->where('name', 'like', '%Reposo%')
                  ->orWhere('email', 'like', '%reposo%');
            })
            ->update(['created_at' => '2026-09-14 08:45:00']);

        // 4. Align Assigned Fire Truck RCA-908
        DB::table('vehicles')
            ->where('agency_id', $agency->id)
            ->where(function ($q) {
                $q->where('plate_number', 'RCA-908')
                  ->orWhere('plate_number', 'RCA 908');
            })
            ->update(['created_at' => '2026-09-14 08:30:00']);

        // 5. Align Assigned Water Tanker EFG-4532
        DB::table('vehicles')
            ->where('agency_id', $agency->id)
            ->where(function ($q) {
                $q->where('plate_number', 'EFG-4532')
                  ->orWhere('plate_number', 'EFG 4532');
            })
            ->update(['created_at' => '2026-09-14 08:45:00']);
    }

    public function down(): void
    {
        // One-way timestamp alignment migration
    }
};
