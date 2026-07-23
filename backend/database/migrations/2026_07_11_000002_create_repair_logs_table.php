<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Repair logs (FR-13) — the vehicle's repair history. Agency-scoped; logged by
 * an admin, with the source of repair (Internal Office / GSO Motorpool /
 * External Repair Shop) and, for an external shop, the shop name.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('repair_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agency_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vehicle_id')->constrained()->cascadeOnDelete();
            $table->foreignId('driver_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('repair_date');
            $table->text('scope_of_work');
            $table->text('parts_replaced')->nullable();
            $table->decimal('cost', 10, 2)->nullable();
            $table->enum('repair_source', ['Internal Office', 'GSO Motorpool', 'External Repair Shop']);
            $table->string('external_shop_name', 255)->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->index('agency_id');
            $table->index('vehicle_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('repair_logs');
    }
};
