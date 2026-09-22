<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Optional SECOND driver on a dispatch (FR-17, 2026-09 — interview-backed,
 * adviser consultation).
 *
 * Some missions are crewed by two drivers who both go out with the vehicle. The
 * interviews raised this, and it applies to any agency, so a dispatch may now
 * record a second driver alongside the primary one.
 *
 * This is PER-DISPATCH, and distinct from the vehicle's standing primary/
 * secondary assignment (`vehicles.assigned_driver_id` / `secondary_driver_id`):
 * a dispatch records who actually went on THIS mission, which may be either of
 * the vehicle's assigned drivers, a substitute, or a two-person crew.
 *
 * Nullable, so the ordinary one-driver dispatch is unchanged and every agency
 * uses it only when it applies. It must differ from the primary `driver_id`, and
 * like the primary it is one-mission-at-a-time: a driver named as the second
 * driver here cannot be out on another active dispatch (enforced in
 * `DispatchGuard`, which now treats a driver as busy whether they appear as the
 * primary or the second driver).
 *
 * ERD: a second relationship line from users to dispatches (as second_driver_id);
 * no new entity. Mirrored into the manuscript (FR-17 + the dispatches
 * data-dictionary row).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dispatches', function (Blueprint $table) {
            $table->foreignId('second_driver_id')
                ->nullable()
                ->after('driver_id')
                ->constrained('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('dispatches', function (Blueprint $table) {
            $table->dropConstrainedForeignId('second_driver_id');
        });
    }
};
