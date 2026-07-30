<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\ReportRequest;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\ReportBuilder;
use Illuminate\Http\JsonResponse;

/**
 * GET /api/v1/reports/{type} (FR-20) — one of the six reports, as data.
 *
 * Admin-only and agency-scoped like every other admin endpoint. Shares
 * ReportBuilder with the Blade page, so the JSON and the printout can never
 * describe the same records differently.
 *
 * The response carries the generating administrator and the generation
 * timestamp for the same reason the printout stamps them: FR-20 requires a
 * report to record who produced it and when, and a consumer rendering this
 * JSON elsewhere must be able to reproduce that.
 */
class ReportController extends Controller
{
    public function show(ReportRequest $request, ReportBuilder $builder, string $type): JsonResponse
    {
        abort_unless(array_key_exists($type, ReportBuilder::TYPES), 404);

        $agencyId = $request->user()->agency_id;
        $filters = $request->filters();

        $filters['vehicle_label'] = $filters['vehicle_id']
            ? Vehicle::query()->whereKey($filters['vehicle_id'])->value('plate_number') ?? 'All'
            : 'All';
        $filters['driver_label'] = $filters['driver_id']
            ? User::query()->whereKey($filters['driver_id'])->value('name') ?? 'All'
            : 'All';

        $report = $builder->build($type, $agencyId, $filters);

        return response()->json([
            'data' => $report + [
                'type' => $type,
                'record_count' => count($report['rows']),
                'generated_by' => $request->user()->name,
                'generated_at' => now()->toIso8601String(),
            ],
        ]);
    }
}
