<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * BLOWBAGETS checklist catalog (FR-09). A global reference/seed table — NOT
 * agency-scoped: the 12 standard items apply to every agency, and the two
 * BFP-only items (Hydraulic System, Fire Pump) are flagged with is_bfp_only.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inspection_checklist_items', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->boolean('is_bfp_only')->default(false);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inspection_checklist_items');
    }
};
