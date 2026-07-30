<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\ReportRequest;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\ReportBuilder;
use Illuminate\View\View;

/**
 * Report Generation page (FR-20).
 *
 * The prototype builds its report in the browser and drops it into
 * #reportOutput. Server-side the equivalent is the same page rendered with a
 * report attached: same layout, same placement, and the URL now carries the
 * filters, so a printout can be reproduced or re-opened from history rather
 * than existing only until the tab is closed.
 */
class ReportController extends Controller
{
    public function index(ReportRequest $request, ReportBuilder $builder): View
    {
        $agencyId = $request->user()->agency_id;

        $vehicles = Vehicle::query()
            ->where('agency_id', $agencyId)
            ->orderBy('plate_number')
            ->get(['id', 'plate_number', 'type']);

        $drivers = User::query()
            ->drivers()
            ->where('agency_id', $agencyId)
            ->where('status', User::STATUS_ACTIVE)
            ->orderBy('name')
            ->get(['id', 'name']);

        $type = $request->query('type');
        $report = null;

        if (is_string($type) && array_key_exists($type, ReportBuilder::TYPES)) {
            $filters = $request->filters();

            // Labels for the printout's filter line, resolved from the ids the
            // request already validated as belonging to this agency.
            $filters['vehicle_label'] = $vehicles->firstWhere('id', $filters['vehicle_id'])?->plate_number ?? 'All';
            $filters['driver_label'] = $drivers->firstWhere('id', $filters['driver_id'])?->name ?? 'All';

            $report = $builder->build($type, $agencyId, $filters);
            $report['type'] = $type;
        }

        return view('reports', [
            'vehicles' => $vehicles,
            'drivers' => $drivers,
            'report' => $report,
            // FR-20: a generated report records WHO produced it and WHEN.
            'generatedBy' => $request->user(),
            'generatedAt' => now(),
        ]);
    }
}
