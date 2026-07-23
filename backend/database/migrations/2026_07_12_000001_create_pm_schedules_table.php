<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Preventive maintenance schedules (FR-14). Agency-scoped. Mileage-based
 * schedules track a due mileage (last serviced + interval); time-based track a
 * due date. Due-Soon thresholds are configurable per schedule (columns, never
 * constants). Status is recalculated by the scheduled rvms:recalculate-pm job.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pm_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agency_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vehicle_id')->constrained()->cascadeOnDelete();
            $table->string('service_target', 255);
            $table->enum('pm_type', ['Mileage-Based', 'Time-Based']);

            // Mileage-based fields
            $table->unsignedInteger('interval_km')->nullable();
            $table->unsignedInteger('last_pm_mileage')->nullable();
            $table->unsignedInteger('due_mileage')->nullable();

            // Time-based field
            $table->date('due_date')->nullable();

            // Configurable Due-Soon thresholds (FR-14)
            $table->unsignedInteger('due_soon_threshold_km')->nullable();
            $table->unsignedSmallInteger('due_soon_threshold_days')->nullable();

            $table->enum('status', ['Upcoming', 'Due Soon', 'Due', 'Completed'])->default('Upcoming');

            // Completion fields
            $table->date('date_serviced')->nullable();
            $table->enum('completion_repair_source', ['Internal Office', 'GSO Motorpool', 'External Repair Shop'])->nullable();
            $table->text('completion_parts_replaced')->nullable();
            $table->text('completion_remarks')->nullable();

            $table->timestamps();

            $table->index('agency_id');
            $table->index('vehicle_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pm_schedules');
    }
};
