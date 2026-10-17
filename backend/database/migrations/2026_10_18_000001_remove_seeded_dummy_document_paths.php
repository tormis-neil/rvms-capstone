<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Remove all simulated/dummy document and photo paths from historical records (2026-10-18).
 *
 * Ensures:
 * 1. Seeded repair logs do not carry phantom receipt PDFs that 404 when clicked.
 * 2. Seeded damage reports, PM schedules, and dispatches do not carry phantom files.
 * 3. Live uploads made by actual users remain supported and untouched.
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1. Purge phantom receipt paths in repair logs
        DB::table('repair_logs')
            ->whereNotNull('receipt_path')
            ->update(['receipt_path' => null]);

        // 2. Purge phantom photo paths in damage reports if any were seeded
        DB::table('damage_reports')
            ->whereNotNull('photo_path')
            ->where(function ($q) {
                $q->where('photo_path', 'like', 'damage/%')
                  ->orWhere('photo_path', 'like', 'dummy%');
            })
            ->update(['photo_path' => null]);

        // 3. Purge phantom travel order paths in dispatches
        DB::table('dispatches')
            ->whereNotNull('travel_order_path')
            ->where('travel_order_path', 'like', 'travel_orders/%')
            ->update(['travel_order_path' => null]);

        // 4. Purge phantom document paths in PM schedules
        DB::table('pm_schedules')
            ->whereNotNull('schedule_document_path')
            ->update(['schedule_document_path' => null]);

        DB::table('pm_schedules')
            ->whereNotNull('completion_receipt_path')
            ->update(['completion_receipt_path' => null]);
    }

    public function down(): void
    {
        // Safe no-op on rollback
    }
};
