<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds the tenth notification type: Password_Reset (FR-22 → FR-21,
 * project-lead approved 2026-08).
 *
 * FR-22 lets an administrator set a new password for one of their drivers, and
 * lets administrators reset each other. Both were silent: the person whose
 * password changed found out by failing to sign in.
 *
 * That silence is the real gap, not the capability. Resetting a peer
 * administrator hands over an account with the same reach as your own, and the
 * only thing standing behind it is the current-password confirmation. An
 * unannounced takeover leaves nothing at all; an announced one leaves a record
 * in the affected user's own inbox, which is what every comparable system
 * (Google Workspace, Microsoft 365, AWS IAM) does. It also answers the
 * everyday case: a driver whose password stopped working learns WHY instead of
 * assuming the app is broken.
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
            ->where('type', 'Password_Reset')
            ->delete();

        Schema::table('notifications', function (Blueprint $table) {
            $table->enum('type', self::PREVIOUS_TYPES)->change();
        });
    }
};
