<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Deleting a vehicle or a driver (FR-05, FR-06 — extended 2026-08).
 *
 * Soft, not hard, and the reason is in the existing foreign keys: every child
 * table declares `cascadeOnDelete()`. A real DELETE on one vehicle would take
 * its inspections, damage reports, repair logs, PM schedules and dispatches
 * with it; on one driver, every inspection and damage report they ever filed.
 * That is not a delete button, it is a shredder wired to the maintenance
 * history the system exists to keep.
 *
 * `deleted_at` gives the administrator exactly what they asked for — the record
 * disappears from every list, table and dropdown — while the history it is
 * attached to survives and the record can be restored. Every relation that
 * reads back into a vehicle or a driver is marked `withTrashed()` so a deleted
 * driver's past inspections still show their name rather than a blank.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
