@extends('layouts.app')

@section('title', 'Inspections')

@php
    use App\Models\Vehicle;
    use App\Models\Inspection;
@endphp

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="fw-bold mb-0" style="color: var(--primary);">Daily BLOWBAGETS Inspections</h3>
    </div>

    {{-- Frequently reported issues (FR-10) --}}
    @if ($frequentIssues->isNotEmpty())
        <div class="card card-stat mb-4">
            <div class="card-body">
                <h6 class="fw-bold mb-3"><i class="bi bi-graph-up-arrow me-2"></i>Frequently Reported Issues</h6>
                <div class="d-flex flex-wrap gap-2">
                    @foreach ($frequentIssues as $issue)
                        <span class="badge badge-not-operational status-badge px-3 py-2">
                            {{ $issue->name }} &times;{{ $issue->count }}
                        </span>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    {{-- Filters (FR-10: per vehicle / per driver / date history) --}}
    <form method="GET" action="{{ route('inspections.index') }}" class="card card-stat mb-4">
        <div class="card-body row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label small fw-semibold text-secondary">Vehicle</label>
                <select name="vehicle_id" class="form-select form-select-sm">
                    <option value="">All vehicles</option>
                    @foreach ($vehicles as $vehicle)
                        <option value="{{ $vehicle->id }}" @selected(request('vehicle_id') == $vehicle->id)>{{ $vehicle->plate_number }} — {{ $vehicle->type }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold text-secondary">Driver</label>
                <select name="driver_id" class="form-select form-select-sm">
                    <option value="">All drivers</option>
                    @foreach ($drivers as $driver)
                        <option value="{{ $driver->id }}" @selected(request('driver_id') == $driver->id)>{{ $driver->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold text-secondary">Date</label>
                <input type="date" name="date" value="{{ request('date') }}" class="form-control form-control-sm">
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-sm btn-navy">Filter</button>
                <a href="{{ route('inspections.index') }}" class="btn btn-sm btn-light border">Clear</a>
            </div>
        </div>
    </form>

    <div class="card card-stat">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Date</th>
                        <th>Vehicle</th>
                        <th>Driver</th>
                        <th>Result</th>
                        <th>Review</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($inspections as $inspection)
                        <tr>
                            <td>{{ $inspection->inspection_date->format('M j, Y') }}</td>
                            <td class="fw-semibold">{{ $inspection->vehicle->plate_number }}</td>
                            <td>{{ $inspection->driver->name }}</td>
                            <td>
                                @if ($inspection->issueCount() === 0)
                                    <span class="badge badge-operational status-badge">All OK</span>
                                @else
                                    <span class="badge badge-not-operational status-badge">{{ $inspection->resultLabel() }}</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge {{ $inspection->review_status === Inspection::REVIEW_REVIEWED ? 'badge-reviewed' : 'badge-pending' }}">
                                    {{ $inspection->review_status }}
                                </span>
                            </td>
                            <td class="text-end">
                                <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#viewInspection{{ $inspection->id }}">View</button>
                                @if ($inspection->review_status !== Inspection::REVIEW_REVIEWED)
                                    <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#reviewInspection{{ $inspection->id }}">Review</button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-secondary py-4">No inspections submitted yet. Drivers submit them from the mobile app.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">{{ $inspections->links() }}</div>

    {{-- Per-row View + Review modals --}}
    @foreach ($inspections as $inspection)
        {{-- View checklist --}}
        <div class="modal fade" id="viewInspection{{ $inspection->id }}" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            Inspection — {{ $inspection->vehicle->plate_number }},
                            {{ $inspection->inspection_date->format('M j, Y') }}
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p class="text-secondary small mb-3">
                            Submitted by {{ $inspection->driver->name }} &middot; {{ $inspection->items->count() }} items
                            @if ($inspection->reviewer)
                                &middot; Reviewed by {{ $inspection->reviewer->name }}
                            @endif
                        </p>
                        <table class="table table-sm align-middle">
                            <thead class="table-light"><tr><th>Item</th><th>Status</th><th>Remarks</th></tr></thead>
                            <tbody>
                                @foreach ($inspection->items->sortBy(fn ($i) => $i->checklistItem->sort_order) as $item)
                                    <tr @class(['table-warning' => $item->checklistItem->is_bfp_only])>
                                        <td>
                                            {{ $item->checklistItem->name }}
                                            @if ($item->checklistItem->is_bfp_only)
                                                <span class="badge bg-warning-subtle text-warning-emphasis ms-1">BFP</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge {{ $item->status === 'OK' ? 'badge-operational' : 'badge-not-operational' }}">{{ $item->status }}</span>
                                        </td>
                                        <td class="small">{{ $item->remarks ?? '—' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        {{-- Review & assess --}}
        <div class="modal fade" id="reviewInspection{{ $inspection->id }}" tabindex="-1">
            <div class="modal-dialog">
                <form method="POST" action="{{ route('inspections.review', $inspection) }}" class="modal-content">
                    @csrf @method('PATCH')
                    <div class="modal-header">
                        <h5 class="modal-title">Review — {{ $inspection->vehicle->plate_number }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p class="small text-secondary">
                            {{ $inspection->resultLabel() }} on this inspection. Marking it reviewed records you as
                            the assessor; you may also update the vehicle's operational status based on the findings.
                        </p>
                        <label class="form-label fw-semibold">Vehicle status (optional)</label>
                        <select name="vehicle_status" class="form-select">
                            <option value="">Keep current ({{ $inspection->vehicle->status }})</option>
                            @foreach (Vehicle::STATUSES as $status)
                                <option value="{{ $status }}">{{ $status }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-navy">Mark Reviewed</button>
                    </div>
                </form>
            </div>
        </div>
    @endforeach
@endsection
