<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * PM completion records WHICH external shop did the work (FR-14, 2026-08,
 * lead-reported).
 *
 * Repair Logs and PM completion capture the same fact — who performed a piece
 * of work — from the same three sources, and Repair Logs has asked for the shop
 * name since R4 (`repair_logs.external_shop_name`). PM completion did not, so
 * an oil change sent to an outside shop recorded "External Repair Shop" and
 * nothing more. The name could only go in the remarks, where it cannot be
 * filtered or reported on, and the two modules disagreed about how much of the
 * same event was worth recording.
 *
 * Nullable, like its Repair Logs counterpart: the name is required only when
 * the source IS External Repair Shop, and that is enforced in the form request
 * rather than the schema, so the other two sources leave it blank.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pm_schedules', function (Blueprint $table) {
            $table->string('completion_external_shop_name', 255)
                ->nullable()
                ->after('completion_repair_source');
        });
    }

    public function down(): void
    {
        Schema::table('pm_schedules', function (Blueprint $table) {
            $table->dropColumn('completion_external_shop_name');
        });
    }
};
