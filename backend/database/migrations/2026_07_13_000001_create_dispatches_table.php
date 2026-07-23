<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Dispatch logs (FR-15, FR-16, FR-17). Agency-scoped. Opening a dispatch marks
 * the vehicle Dispatched; closing it records the return status and (optionally)
 * the time-in odometer, which feeds the vehicle's current mileage. Active vs
 * completed is derived from time_in IS NULL.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dispatches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agency_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vehicle_id')->constrained()->cascadeOnDelete();
            $table->foreignId('driver_id')->constrained('users')->cascadeOnDelete();
            $table->enum('mission_type', [
                'Fire Response', 'Medical Response', 'Rescue Operation',
                'Patrol', 'Administrative Travel', 'Others',
            ]);
            $table->string('mission_other', 255)->nullable();
            $table->string('location', 255);
            $table->dateTime('time_out');
            $table->unsignedInteger('odometer_out')->nullable();
            $table->dateTime('time_in')->nullable();
            $table->unsignedInteger('odometer_in')->nullable();
            $table->enum('return_status', ['Operational', 'Not Operational', 'Under Preventive Maintenance'])->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->index('agency_id');
            $table->index('vehicle_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dispatches');
    }
};
