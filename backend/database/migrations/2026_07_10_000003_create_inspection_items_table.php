<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-item results of an inspection (FR-09). status is OK or Has Issue;
 * remarks are required when the item is flagged Has Issue (enforced at
 * validation, not at the DB level).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inspection_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inspection_id')->constrained()->cascadeOnDelete();
            $table->foreignId('checklist_item_id')->constrained('inspection_checklist_items')->cascadeOnDelete();
            $table->enum('status', ['OK', 'Has Issue']);
            $table->text('remarks')->nullable();

            $table->index('inspection_id');
            $table->index('checklist_item_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inspection_items');
    }
};
