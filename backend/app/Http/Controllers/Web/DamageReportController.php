<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\DamageReport;
use App\Models\Vehicle;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Damage-report review from the Inspections & Damage dashboard page (FR-12) —
 * the Blade twin of the damage review API. Agency scoping is automatic
 * (BelongsToAgency on DamageReport), so a cross-agency binding 404s.
 */
class DamageReportController extends Controller
{
    public function review(Request $request, DamageReport $damageReport): RedirectResponse
    {
        // Only the three manual statuses; Dispatched is written by the Dispatch
        // module alone (FR-18). No admin-remarks column (design decision 7).
        $validated = $request->validate([
            'vehicle_status' => ['nullable', Rule::in(Vehicle::MANUAL_STATUSES)],
        ]);

        $damageReport->update([
            'status' => DamageReport::STATUS_REVIEWED,
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        if (! empty($validated['vehicle_status'])) {
            $damageReport->vehicle()->update(['status' => $validated['vehicle_status']]);
        }

        return redirect()->route('inspections')->with('status', 'Damage report reviewed.');
    }
}
