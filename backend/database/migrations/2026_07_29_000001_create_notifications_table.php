<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * In-app notifications (FR-21). Agency-scoped, addressed to one user.
 *
 * The eight types are exactly those FR-21 names: drivers receive preventive
 * maintenance reminders and vehicle status updates; administrators receive new
 * damage reports, new driver access requests (FR-03), license expiry alerts,
 * and preventive maintenance due alerts.
 *
 * `data` carries the reference payload (vehicle plate, record id, link target)
 * so a row can render and link without re-querying the originating module.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agency_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->enum('type', [
                'PM_Reminder',
                'Vehicle_Status_Update',
                'New_Damage_Report',
                'License_Expiring',
                'License_Expired',
                'PM_Due_Soon',
                'PM_Due',
                'New_Access_Request',
            ]);
            $table->string('title', 255);
            $table->text('message');
            $table->json('data')->nullable();
            $table->boolean('is_read')->default(false);
            $table->dateTime('read_at')->nullable();
            $table->timestamps();

            $table->index('agency_id');
            // The bell and the page both read "my unread, newest first".
            $table->index(['user_id', 'is_read']);
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
