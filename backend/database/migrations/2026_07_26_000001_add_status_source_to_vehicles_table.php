<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Records WHERE and WHEN a vehicle's operational status was last set, so the
 * status-change confirmation can tell the admin what they are overriding
 * (design decision 9).
 *
 * Implementation-level addition, like `vehicles.remarks` (design decision 7
 * amendment): no FR requires the system to record the origin of a status, so
 * these columns live in the repo's data dictionary only and are deliberately
 * NOT mirrored into the manuscript's Chapter 4 dictionary. The ERD is unchanged
 * — two attributes on an existing entity, no new entity or relationship.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->string('status_source', 100)->nullable()->after('status');
            $table->dateTime('status_changed_at')->nullable()->after('status_source');
        });
    }

    public function down(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->dropColumn(['status_source', 'status_changed_at']);
        });
    }
};
