<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Supporting document (travel order) attached when a dispatch is OPENED
 * (FR-15, 2026-09, CHO adviser consultation).
 *
 * A travel order authorises the trip, so it exists BEFORE the vehicle leaves —
 * it belongs to opening the dispatch, not closing it. Closing records only the
 * time in, the odometer and the return status; there is nothing new to
 * authorise there.
 *
 * Optional (nullable) on purpose: emergency responses (Fire, Medical, Rescue)
 * roll out immediately and do not wait on paperwork, so requiring a travel order
 * would block the very dispatches the system exists to record. Patrol and
 * Administrative Travel usually have one, and can attach it. Optional keeps the
 * field usable by all four agencies and every mission type.
 *
 * One attribute on the existing Dispatch entity — no new entity or relationship,
 * so the ERD is unchanged (same precedent as the dispatch odometer columns,
 * design decision 8). Mirrored into the Chapter 4 data dictionary because an FR
 * (FR-15) asks for it.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dispatches', function (Blueprint $table) {
            $table->string('travel_order_path', 255)->nullable()->after('time_out');
        });
    }

    public function down(): void
    {
        Schema::table('dispatches', function (Blueprint $table) {
            $table->dropColumn('travel_order_path');
        });
    }
};
