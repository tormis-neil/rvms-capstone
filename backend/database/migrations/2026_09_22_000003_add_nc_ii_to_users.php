<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Driver TESDA National Certificate II (Driving) — number and expiry
 * (FR-08, FR-10, 2026-09, CHO/PNP adviser consultation).
 *
 * The agencies track a second expiring credential for their drivers beyond the
 * professional driver's licence: the TESDA NC II in Driving. It is monitored the
 * same way as the licence — an approaching-expiry / expired flag on the Drivers
 * page — so its expiry date lives beside `license_expiry_date`.
 *
 * Both columns are nullable: not every agency requires an NC II of every driver
 * (a driver may hold only the professional licence), so a driver with no NC II
 * simply leaves them blank — the same nullable pattern as engine/chassis number.
 * No unique index: NC II numbers are not the record's identifier the way a
 * licence number is, and a spurious collision would only block a legitimate save.
 *
 * Attributes on the existing users entity — no new entity or relationship, so
 * the ERD is unchanged. Mirrored into the Chapter 4 data dictionary because an
 * FR (FR-08) asks for them.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('nc_ii_number', 50)->nullable()->after('license_expiry_date');
            $table->date('nc_ii_expiry_date')->nullable()->after('nc_ii_number');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['nc_ii_number', 'nc_ii_expiry_date']);
        });
    }
};
