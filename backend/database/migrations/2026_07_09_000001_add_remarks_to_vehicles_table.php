<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Optional note on the vehicle's most recent manual status change.
 * Documented deviation from design decision 7 (CLAUDE.md): unlike the
 * admin-remarks columns excluded elsewhere, this single field mirrors the
 * prototype's "Remarks (Optional)" status-change field. It is NOT a change
 * log — each update overwrites the previous note, same as current_mileage.
 * No FR backs it; it does not appear in the manuscript's data dictionary.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->text('remarks')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->dropColumn('remarks');
        });
    }
};
