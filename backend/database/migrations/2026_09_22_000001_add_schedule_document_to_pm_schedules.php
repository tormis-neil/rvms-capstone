<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Supporting document attached when a PM schedule is CREATED (FR-14, 2026-09,
 * CHO adviser consultation).
 *
 * Completing a schedule already stores proof of the work done
 * (`completion_receipt_path`). What was missing was the document that justifies
 * SCHEDULING the maintenance in the first place — most often the pre-inspection
 * checklist the GSO Motorpool produces, which is exactly the pre-service record
 * the interviews described. This is that pre-service document, kept beside the
 * schedule from the moment it is created.
 *
 * Distinct from `completion_receipt_path` on purpose: that one is the receipt or
 * job order for the finished work; this one is the checklist or recommendation
 * that led to the schedule. Both moments can carry a document, and they are not
 * the same document.
 *
 * Optional (nullable): not every agency produces a pre-inspection checklist for
 * every schedule, so requiring it would block PNP/CDRRMO/CHO from scheduling at
 * all. Repair source is deliberately NOT captured here — at scheduling time no
 * work has happened yet and there is no source to name; that stays a completion
 * fact.
 *
 * One attribute on an existing entity — no new entity or relationship, so the
 * ERD is unchanged (same precedent as the dispatch odometer columns, design
 * decision 8). Mirrored into the Chapter 4 data dictionary because an FR (FR-14)
 * asks for it.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pm_schedules', function (Blueprint $table) {
            $table->string('schedule_document_path', 255)->nullable()->after('service_target');
        });
    }

    public function down(): void
    {
        Schema::table('pm_schedules', function (Blueprint $table) {
            $table->dropColumn('schedule_document_path');
        });
    }
};
