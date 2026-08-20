<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Proof of an external repair (FR-13, FR-14 — adviser consultation, 2026-08).
 *
 * Both modules that can name an external repair shop stored only a typed name,
 * with nothing to verify it against. These agencies are government offices
 * spending public money at private shops, so the supporting document belongs
 * with the record rather than in a folder somewhere else.
 *
 * Nullable in both cases, deliberately. The columns hold evidence for repairs
 * carried out by an EXTERNAL shop; work done by the Internal Office or the GSO
 * Motorpool has no third-party receipt to attach, and every record that existed
 * before this migration has none either. The requirement is enforced in the
 * form requests, where it can be scoped to the source that actually needs it,
 * rather than by a NOT NULL that would make the other two sources impossible.
 *
 * Unlike vehicles.remarks and the status_source pair, these ARE mirrored into
 * the manuscript's Chapter 4 data dictionary: FR-13 and FR-14 describe them.
 * The ERD diagram is unchanged — two attributes on existing entities, the same
 * precedent as the dispatch odometer columns (design decision 8).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('repair_logs', function (Blueprint $table) {
            $table->string('receipt_path')->nullable()->after('external_shop_name');
        });

        Schema::table('pm_schedules', function (Blueprint $table) {
            $table->string('completion_receipt_path')->nullable()->after('completion_external_shop_name');
        });
    }

    public function down(): void
    {
        Schema::table('repair_logs', function (Blueprint $table) {
            $table->dropColumn('receipt_path');
        });

        Schema::table('pm_schedules', function (Blueprint $table) {
            $table->dropColumn('completion_receipt_path');
        });
    }
};
