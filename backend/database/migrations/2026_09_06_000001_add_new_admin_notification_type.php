<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds the eleventh notification type: New_Admin (2026-09, project-lead approved).
 *
 * An administrator can now create another administrator for their own agency
 * from the dashboard, instead of that only being possible through a server
 * command (design decision 6, revised). Creating a peer hands out an account
 * with the same reach across the agency's records as your own, so the existing
 * administrators are told when one appears — the same reasoning behind the
 * Password_Reset notification, and what every comparable system does for a new
 * privileged account. The acting administrator and the newly created one are
 * not notified: one already knows, the other is the subject.
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
        'Password_Reset',
        'New_Admin',
    ];

    private const PREVIOUS_TYPES = [
        'PM_Reminder',
        'Vehicle_Status_Update',
        'New_Damage_Report',
        'Inspection_Flagged',
        'License_Expiring',
        'License_Expired',
        'PM_Due_Soon',
        'PM_Due',
        'New_Access_Request',
        'Password_Reset',
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
            ->where('type', 'New_Admin')
            ->delete();

        Schema::table('notifications', function (Blueprint $table) {
            $table->enum('type', self::PREVIOUS_TYPES)->change();
        });
    }
};
