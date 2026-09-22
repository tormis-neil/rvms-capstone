<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Optional SECONDARY driver on a vehicle (FR-07, FR-08, FR-09, 2026-09,
 * CHO/PNP adviser consultation).
 *
 * The interviews established that some vehicles are crewed by two drivers (CHO's
 * shift-based ambulances; PNP raised the same). The vehicle keeps ONE primary
 * driver (`assigned_driver_id`) — the default attribution on the record and on
 * the driver's mobile My Vehicle screen — and now also carries an optional
 * secondary/backup driver.
 *
 * A designated primary + optional secondary is deliberate rather than two
 * co-equal drivers: the primary answers "whose vehicle is this by default", so a
 * co-equal pair would only add ambiguity. A single nullable column is likewise
 * deliberate rather than a many-to-many pivot: the agencies asked for two
 * drivers, not an arbitrary crew, and a pivot would restructure the ERD for a
 * need that does not exist. If an agency later rotates three or more, this is
 * revisited.
 *
 * Nullable: agencies that assign a single driver leave it blank, so the field is
 * usable by all four. `nullOnDelete` mirrors `assigned_driver_id`, though records
 * are retained permanently (FR-08) so a driver is reassigned rather than deleted.
 *
 * ERD impact: ONE new relationship line (users → vehicles as secondary_driver_id)
 * and one attribute; no new entity. Mirrored into the Chapter 4 data dictionary
 * and the FR wording (FR-07/FR-08/FR-09).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->foreignId('secondary_driver_id')
                ->nullable()
                ->after('assigned_driver_id')
                ->constrained('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->dropConstrainedForeignId('secondary_driver_id');
        });
    }
};
