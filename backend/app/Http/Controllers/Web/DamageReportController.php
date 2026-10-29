<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\DamageReport;
use App\Models\Vehicle;
use App\Services\VehicleStatusWriter;
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
        // module alone (FR-18). Optional diagnostic remarks update vehicles.remarks
        // and ride in the status notification payload (FR-21).
        $validated = $request->validate([
            'vehicle_status' => ['nullable', Rule::in(Vehicle::MANUAL_STATUSES)],
            'remarks' => ['nullable', 'string', 'max:1000'],
        ]);

        $damageReport->update([
            'status' => DamageReport::STATUS_REVIEWED,
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        if (! empty($validated['vehicle_status'])) {
            $status = $validated['vehicle_status'];
            $extra = [];

            if ($request->filled('remarks')) {
                $extra['remarks'] = trim((string) $validated['remarks']);
            } elseif ($damageReport->vehicle->status !== $status) {
                $extra['remarks'] = filled($damageReport->nature_of_damage)
                    ? "Damage assessment: {$damageReport->nature_of_damage}"
                    : 'Assessed during damage report review.';
            }

            app(VehicleStatusWriter::class)->write(
                $damageReport->vehicle,
                $status,
                VehicleStatusWriter::SOURCE_DAMAGE,
                $extra,
            );
        }

        return redirect()->route('inspections')->with('status', 'Damage report reviewed.');
    }
}
