<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds the twelfth and thirteenth notification types: NC_II_Expiring and
 * NC_II_Expired (2026-09, CHO/PNP adviser consultation — NC II parity).
 *
 * The TESDA National Certificate II (Driving) is now monitored exactly like the
 * driver's licence (FR-08 → FR-21): an expired NC II is the same class of
 * legally-required credential lapse as an expired licence, so it earns the same
 * admin alert. Distinct types rather than reusing License_Expiring/Expired so
 * the alert can say "NC II" and route without ambiguity. This reverses the
 * earlier design-decision-11 note that NC II would carry no push type.
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
        'NC_II_Expiring',
        'NC_II_Expired',
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
        'New_Admin',
    ];

    public function up(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->enum('type', self::TYPES)->change();
        });
    }

    public function down(): void
    {
        // Any rows of the removed types would violate the narrower enum.
        \App\Models\Notification::query()
            ->withoutGlobalScopes()
            ->whereIn('type', ['NC_II_Expiring', 'NC_II_Expired'])
            ->delete();

        Schema::table('notifications', function (Blueprint $table) {
            $table->enum('type', self::PREVIOUS_TYPES)->change();
        });
    }
};
