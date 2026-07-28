<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Uniqueness for the identifiers that name one physical thing (FR-05, FR-06).
 *
 * Validation alone leaves a gap: two admins of the same agency submitting in the
 * same instant can both pass the check before either has inserted. The plate
 * number already had a unique index for exactly that reason; the engine number,
 * chassis number and driver license number did not, so the same vehicle or the
 * same person could be entered twice. A duplicate license number also
 * double-counts one physical licence in the FR-08 expiring-licence figures.
 *
 * Scoped per agency, matching the existing plate index — a global index would
 * make a rejection reveal that another agency holds that identifier (FR-02).
 *
 * All three columns are nullable, and SQL treats NULLs as distinct in a unique
 * index, so any number of records may leave them blank.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->unique(['agency_id', 'engine_number'], 'vehicles_agency_engine_unique');
            $table->unique(['agency_id', 'chassis_number'], 'vehicles_agency_chassis_unique');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->unique(['agency_id', 'license_number'], 'users_agency_license_unique');
        });
    }

    public function down(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->dropUnique('vehicles_agency_engine_unique');
            $table->dropUnique('vehicles_agency_chassis_unique');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique('users_agency_license_unique');
        });
    }
};
