<?php

use App\Models\PmSchedule;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds the third PM schedule type: 'Mileage & Time-Based' (FR-14, 2026-10).
 *
 * Allows mileage intervals and calendar dates to operate concurrently on the
 * same preventive maintenance schedule (e.g., every 5,000 km or 6 months),
 * triggering Due Soon / Due based on whichever threshold is reached first.
 *
 * A separate migration rather than an edit to the create migration, so an
 * existing database picks it up with `php artisan migrate` and nobody has to
 * run `migrate:fresh` and lose their records.
 */
return new class extends Migration
{
    private const TYPES = [
        'Mileage-Based',
        'Time-Based',
        'Mileage & Time-Based',
    ];

    private const PREVIOUS_TYPES = [
        'Mileage-Based',
        'Time-Based',
    ];

    public function up(): void
    {
        Schema::table('pm_schedules', function (Blueprint $table) {
            $table->enum('pm_type', self::TYPES)->change();
        });
    }

    public function down(): void
    {
        PmSchedule::query()
            ->withoutGlobalScopes()
            ->where('pm_type', 'Mileage & Time-Based')
            ->update(['pm_type' => 'Mileage-Based']);

        Schema::table('pm_schedules', function (Blueprint $table) {
            $table->enum('pm_type', self::PREVIOUS_TYPES)->change();
        });
    }
};

