<?php

namespace App\Services;

use App\Models\DamageReport;
use App\Models\Inspection;
use App\Models\InspectionItem;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * The dashboard's live figures (FR-19), computed in one place.
 *
 * The web page and `GET /api/v1/dashboard/summary` both read from here, so the
 * two surfaces cannot drift into reporting different numbers for the same
 * agency — the single-source rule NFR-04 asks for, applied to counts rather
 * than to a vehicle status.
 *
 * Nothing is cached or stored: FR-19 says "real-time", and every figure is a
 * count over records that already exist. There is no dashboard table.
 *
 * Every query takes the agency id explicitly rather than leaning on the global
 * scope alone. The scope still applies for an authenticated caller, so this is
 * belt-and-braces — but it also makes the service usable from a console
 * context and makes an isolation test able to assert the filter directly.
 */
class DashboardSummary
{
    /**
     * Rows shown in each Action Required list.
     *
     * The pill above the list always shows the TRUE total, so a busy agency
     * reads "12 New" over the five most recent rows rather than a number
     * quietly trimmed to fit. The rows link into the module page, which is
     * where the full list belongs.
     */
    public const ACTION_LIST_LIMIT = 5;

    /**
     * The eight metric cards, exactly the figures FR-19 enumerates.
     *
     * @return array<string, int>
     */
    public function counts(int $agencyId): array
    {
        $byStatus = Vehicle::query()
            ->where('agency_id', $agencyId)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return [
            'total_vehicles' => (int) $byStatus->sum(),
            'operational' => (int) $byStatus->get(Vehicle::STATUS_OPERATIONAL, 0),
            'dispatched' => (int) $byStatus->get(Vehicle::STATUS_DISPATCHED, 0),
            'under_pm' => (int) $byStatus->get(Vehicle::STATUS_UNDER_PM, 0),
            'not_operational' => (int) $byStatus->get(Vehicle::STATUS_NOT_OPERATIONAL, 0),
            'total_drivers' => $this->activeDrivers($agencyId)->count(),
            'expiring_licenses' => $this->expiringLicenseCount($agencyId),
            'pending_damage_reports' => DamageReport::query()
                ->where('agency_id', $agencyId)
                ->where('status', DamageReport::STATUS_PENDING)
                ->count(),
        ];
    }

    /**
     * Licences inside the agency's configurable warning window (FR-08).
     *
     * "Expiring Soon" only, deliberately — the Drivers page carries three
     * separate cards (Valid / Expiring Soon / Expired) and an admin will
     * compare this card against the one that shares its name. Already-expired
     * licences are not hidden: they appear in the Action Required list below,
     * badged red, where they read as worse rather than as more of the same.
     */
    public function expiringLicenseCount(int $agencyId): int
    {
        return $this->activeDrivers($agencyId)
            ->filter(fn (User $driver) => $driver->licenseStatus() === 'Expiring Soon')
            ->count();
    }

    /**
     * Everything waiting on the admin's review — pending damage reports and
     * pending inspections, newest first (FR-10, FR-12).
     *
     * ALL pending inspections, not only the flagged ones. The R7 notification
     * rule is the opposite (an all-OK submission pushes nobody) and that is
     * right for an alert, but this card answers "what is waiting for me?", and
     * FR-10 puts every submission in front of the admin. Counting only flagged
     * ones here would also make this card disagree with the Inspections page's
     * own "N Pending Review" pill, which reads as a bug.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function pendingReviews(int $agencyId, int $limit = self::ACTION_LIST_LIMIT): Collection
    {
        $damage = DamageReport::query()
            ->where('agency_id', $agencyId)
            ->where('status', DamageReport::STATUS_PENDING)
            ->with('vehicle')
            ->latest('created_at')
            ->limit($limit)
            ->get()
            ->map(fn (DamageReport $report) => [
                'vehicle' => $this->vehicleLabel($report->vehicle),
                'when' => trim($report->dateLabel().', '.$report->timeLabel(), ', '),
                'sorted_on' => $report->created_at,
                'detail' => 'Damage: '.$report->nature_of_damage,
            ]);

        $inspections = Inspection::query()
            ->where('agency_id', $agencyId)
            ->where('review_status', Inspection::STATUS_PENDING)
            ->with(['vehicle', 'items.checklistItem'])
            ->latest('created_at')
            ->limit($limit)
            ->get()
            ->map(fn (Inspection $inspection) => [
                'vehicle' => $this->vehicleLabel($inspection->vehicle),
                'when' => trim($inspection->dateLabel().', '.$inspection->timeLabel(), ', '),
                'sorted_on' => $inspection->created_at,
                'detail' => $this->inspectionDetail($inspection),
            ]);

        return $damage->concat($inspections)
            ->sortByDesc('sorted_on')
            ->take($limit)
            ->values();
    }

    /** The true total behind the list's pill. */
    public function pendingReviewCount(int $agencyId): int
    {
        return DamageReport::query()
            ->where('agency_id', $agencyId)
            ->where('status', DamageReport::STATUS_PENDING)
            ->count()
            + Inspection::query()
                ->where('agency_id', $agencyId)
                ->where('review_status', Inspection::STATUS_PENDING)
                ->count();
    }

    /**
     * Licences needing attention — expiring AND already expired (FR-08).
     *
     * Wider than the metric card above on purpose: the card answers "how many
     * are about to lapse", this list answers "whose licence do I have to do
     * something about", and an expired one is the most urgent case of that.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function expiringLicenses(int $agencyId, int $limit = self::ACTION_LIST_LIMIT): Collection
    {
        return $this->activeDrivers($agencyId)
            ->filter(fn (User $driver) => in_array($driver->licenseStatus(), ['Expiring Soon', 'Expired'], true))
            // Soonest first, so the expired ones lead.
            ->sortBy(fn (User $driver) => $driver->license_expiry_date)
            ->take($limit)
            ->map(fn (User $driver) => [
                'name' => $driver->name,
                'status' => $driver->licenseStatus(),
                'license_number' => $driver->license_number,
                'detail' => $this->licenseDetail($driver),
            ])
            ->values();
    }

    /** The true total behind the licences pill. */
    public function expiringLicenseAttentionCount(int $agencyId): int
    {
        return $this->activeDrivers($agencyId)
            ->filter(fn (User $driver) => in_array($driver->licenseStatus(), ['Expiring Soon', 'Expired'], true))
            ->count();
    }

    /**
     * Active drivers of one agency, with the agency loaded.
     *
     * `licenseStatus()` reads `agency.license_expiry_warning_days`, so without
     * the eager load this would fire one query per driver — the N+1 R10 audits
     * for, avoided at the source.
     *
     * @return Collection<int, User>
     */
    private function activeDrivers(int $agencyId): Collection
    {
        return User::query()
            ->drivers()
            ->where('agency_id', $agencyId)
            ->where('status', User::STATUS_ACTIVE)
            ->with('agency')
            ->get();
    }

    /** "Fire Truck (ABC-1234)" — the prototype's row heading. */
    private function vehicleLabel(?Vehicle $vehicle): string
    {
        if ($vehicle === null) {
            return 'Unassigned vehicle';
        }

        return "{$vehicle->type} ({$vehicle->plate_number})";
    }

    /** Names the flagged items, or says plainly that nothing was flagged. */
    private function inspectionDetail(Inspection $inspection): string
    {
        $flagged = $inspection->items
            ->filter(fn ($item) => $item->status === InspectionItem::STATUS_HAS_ISSUE)
            ->map(fn ($item) => $item->checklistItem?->name)
            ->filter()
            ->implode(', ');

        return $flagged !== ''
            ? 'BLOWBAGETS issue: '.$flagged
            : 'BLOWBAGETS inspection: all items OK';
    }

    /** "Expiry: July 8, 2026 (30 days left)" / "Expired: May 28, 2026 — renewal required". */
    private function licenseDetail(User $driver): string
    {
        $date = $driver->license_expiry_date;

        if ($date === null) {
            return 'No expiry date on file';
        }

        if ($driver->licenseStatus() === 'Expired') {
            return 'Expired: '.$date->format('F j, Y').' — renewal required';
        }

        $days = (int) now()->startOfDay()->diffInDays($date, false);

        return 'Expiry: '.$date->format('F j, Y')
            .' ('.$days.' '.Str::plural('day', $days).' left)';
    }
}
