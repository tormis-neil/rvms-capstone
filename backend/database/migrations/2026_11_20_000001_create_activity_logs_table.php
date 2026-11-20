<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Append-only system activity log for IAS 2 security & operational monitoring.
 *
 * Captures authentication events (Web Portal and Mobile API) alongside key
 * operational actions such as vehicle status transitions, normalized into a
 * single schema with Asia/Manila timestamps.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agency_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('actor_name', 255)->nullable();
            $table->string('actor_email', 255)->nullable();
            $table->string('actor_role', 32)->nullable();
            $table->string('event_type', 64);
            $table->string('severity', 16)->default('info');
            $table->string('source', 32)->default('Web Portal');
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 512)->nullable();
            $table->string('target_label', 255)->nullable();
            $table->string('description', 500);
            $table->json('metadata')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index('agency_id');
            $table->index('event_type');
            $table->index('severity');
            $table->index('created_at');
            $table->index(['agency_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
