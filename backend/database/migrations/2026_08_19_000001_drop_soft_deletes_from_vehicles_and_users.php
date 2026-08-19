<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Vehicle and driver records are no longer deletable (FR-05, FR-06, revised
 * 2026-08).
 *
 * Delete and restore were built in August and removed the same month, once the
 * requirements were re-examined against the objectives. The reasoning is in the
 * FR revision: every inspection, damage report, repair log, preventive
 * maintenance schedule and dispatch refers to a vehicle and a driver, so
 * removing either breaks the history the system exists to keep. A vehicle that
 * leaves service is recorded through its operational status; a driver who
 * leaves is recorded by reassigning their vehicles.
 *
 * The columns go with the feature. Leaving them would put two fields in the
 * database that no requirement explains and that the Chapter 4 data dictionary
 * would have to either document or silently omit.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->softDeletes();
        });
    }
};
