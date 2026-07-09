<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Vehicles per the approved Data Dictionary (FR-05, FR-07, FR-17, FR-18).
 * One shared operational status (exactly four values) written from every module.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agency_id')->constrained()->cascadeOnDelete();
            $table->foreignId('assigned_driver_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type', 100);
            $table->string('plate_number', 20);
            $table->string('make', 100);
            $table->string('model', 100);
            $table->string('engine_number', 50)->nullable();
            $table->string('chassis_number', 50)->nullable();
            $table->unsignedInteger('current_mileage')->default(0);
            $table->enum('status', [
                'Operational',
                'Dispatched',
                'Not Operational',
                'Under Preventive Maintenance',
            ])->default('Operational');
            $table->timestamps();

            $table->unique(['agency_id', 'plate_number']); // plate unique per agency
            $table->index('agency_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};
