<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The odometer reading when a preventive maintenance was serviced (FR-14, 2026-08).
 *
 * Completing a mileage-based schedule recorded the date, the repair source, the
 * supporting document, the parts and the remarks — but not the mileage. So the
 * NEXT cycle's "Last PM Mileage" had to be typed from the vehicle's CURRENT
 * reading, which has moved on since the service. Every cycle's target therefore
 * drifted later by however far the vehicle travelled between the service and the
 * paperwork, and the drift accumulated: a truck serviced at 45,000 km but
 * recorded three weeks later at 45,600 has its next oil change set 600 km late,
 * then 1,200 km late, and so on.
 *
 * Nullable, and deliberately so on both counts: a TIME-based schedule has no
 * mileage to record, and every schedule completed before this migration has
 * none either. The completion form prefills it from the vehicle's current
 * mileage for mileage-based schedules, which is the right default on the day of
 * service and correctable when the paperwork is late.
 *
 * Mirrored into the manuscript's Chapter 4 data dictionary — FR-14 describes
 * the completion fields, and this is one of them. The ERD diagram is unchanged:
 * one attribute on an existing entity, the same precedent as the dispatch
 * odometer columns (design decision 8).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pm_schedules', function (Blueprint $table) {
            $table->unsignedInteger('completion_mileage')->nullable()->after('date_serviced');
        });
    }

    public function down(): void
    {
        Schema::table('pm_schedules', function (Blueprint $table) {
            $table->dropColumn('completion_mileage');
        });
    }
};
