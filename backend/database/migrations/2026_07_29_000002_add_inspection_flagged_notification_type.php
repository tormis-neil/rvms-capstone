<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds the ninth notification type: Inspection_Flagged (FR-09 → FR-21,
 * project-lead approved 2026-07).
 *
 * Administrators are alerted when a submitted BLOWBAGETS inspection reports one
 * or more items as Has Issue. Deliberately NOT every submission: the prototype's
 * bell shows "Daily Inspection Submitted" rows, but a driver submits daily per
 * vehicle, so with two vehicles and two admins that is roughly 120 alerts a
 * month, almost all "all items OK" — the urgent ones would be buried. The
 * flagged subset is the actionable one.
 *
 * A separate migration rather than an edit to the create migration, so an
 * existing database picks it up with `php artisan migrate` and nobody has to
 * run `migrate:fresh` and lose their records.
 */
return new class extends Migration
{
    private const TYPES = [
        'PM_Reminder',
        'Vehicle_Status_Update',
        'New_Damage_Report',
        'Inspection_Flagged',
        'License_Expiring',
        'License_Expired',
        'PM_Due_Soon',
        'PM_Due',
        'New_Access_Request',
    ];

    private const PREVIOUS_TYPES = [
        'PM_Reminder',
        'Vehicle_Status_Update',
        'New_Damage_Report',
        'License_Expiring',
        'License_Expired',
        'PM_Due_Soon',
        'PM_Due',
        'New_Access_Request',
    ];

    public function up(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->enum('type', self::TYPES)->change();
        });
    }

    public function down(): void
    {
        // Any rows of the removed type would violate the narrower enum.
        \App\Models\Notification::query()
            ->withoutGlobalScopes()
            ->where('type', 'Inspection_Flagged')
            ->delete();

        Schema::table('notifications', function (Blueprint $table) {
            $table->enum('type', self::PREVIOUS_TYPES)->change();
        });
    }
};
