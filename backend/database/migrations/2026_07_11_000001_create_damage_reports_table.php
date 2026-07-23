<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Driver-submitted damage reports (FR-11, FR-12). Agency-scoped; a report is
 * filed by a driver against their vehicle (photo optional) and reviewed by an
 * agency admin, who may change the vehicle's operational status.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('damage_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agency_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vehicle_id')->constrained()->cascadeOnDelete();
            $table->foreignId('driver_id')->constrained('users')->cascadeOnDelete();
            $table->text('nature_of_damage');
            $table->string('suspected_parts', 255)->nullable();
            $table->string('photo_path', 255)->nullable();
            $table->date('date_reported');
            $table->enum('status', ['Pending', 'Reviewed'])->default('Pending');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('reviewed_at')->nullable();
            $table->timestamps();

            $table->index('agency_id');
            $table->index('vehicle_id');
            $table->index('driver_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('damage_reports');
    }
};
