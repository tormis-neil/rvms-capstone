@extends('layouts.app')

@section('title', 'Inspections & Damage')

@php
    use App\Models\Inspection;
    use App\Models\Vehicle;
    use Illuminate\Support\Carbon;
    use Illuminate\Support\Str;

    $pendingCount = $inspections->getCollection()
        ->where('review_status', Inspection::REVIEW_PENDING)->count();

    $maxIssueCount = $frequentIssues->max('count') ?: 1;

    $relativeDate = function ($date) {
        if ($date->isToday()) return 'Today';
        if ($date->isYesterday()) return 'Yesterday';
        return $date->format('M j, Y');
    };
@endphp

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold mb-0" style="color: var(--primary);">Inspections &amp; Damage</h3>
            <p class="text-secondary mb-0">Monitor daily BLOWBAGETS and review defect reports</p>
        </div>
    </div>

    <!-- Daily Inspections Section -->
    <div class="d-flex align-items-center gap-2 mb-3">
        <h5 class="fw-bold mb-0"><i class="bi bi-card-checklist me-2"></i>Daily BLOWBAGETS Inspections</h5>
        @if ($pendingCount > 0)
            <span class="badge badge-pending rounded-pill px-3 py-2">{{ $pendingCount }} Pending Review</span>
        @endif
    </div>

    <!-- Filters (FR-10: history per vehicle / per driver / date) -->
    <form method="GET" action="{{ route('inspections.index') }}" class="card border-0 shadow-sm rounded-3 mb-4">
        <div class="card-body p-3">
            <div class="row g-3">
                <div class="col-md-4">
                    <select name="vehicle_id" class="form-select">
                        <option value="">All Vehicles</option>
                        @foreach ($vehicles as $vehicle)
                            <option value="{{ $vehicle->id }}" @selected(request('vehicle_id') == $vehicle->id)>{{ $vehicle->plate_number }} ({{ $vehicle->type }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <select name="driver_id" class="form-select">
                        <option value="">All Drivers</option>
                        @foreach ($drivers as $driver)
                            <option value="{{ $driver->id }}" @selected(request('driver_id') == $driver->id)>{{ $driver->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <input type="date" name="date" value="{{ request('date') }}" class="form-control">
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-outline-secondary w-100">Filter</button>
                    @if (request()->hasAny(['vehicle_id', 'driver_id', 'date']))
                        <a href="{{ route('inspections.index') }}" class="btn btn-light border" title="Clear filters"><i class="bi bi-x-lg"></i></a>
                    @endif
                </div>
            </div>
        </div>
    </form>

    <div class="card border-0 shadow-sm rounded-3 overflow-hidden mb-5">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="py-3 text-secondary fw-semibold small">DATE SUBMITTED</th>
                        <th class="py-3 text-secondary fw-semibold small">VEHICLE &amp; DRIVER</th>
                        <th class="py-3 text-secondary fw-semibold small">RESULT</th>
                        <th class="py-3 text-secondary fw-semibold small">REMARKS</th>
                        <th class="py-3 text-secondary fw-semibold small">REVIEW STATUS</th>
                        <th class="py-3 text-secondary fw-semibold small text-end">ACTIONS</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($inspections as $inspection)
                        @php
                            $flagged = $inspection->items->where('status', 'Has Issue');
                            $firstFlag = $flagged->first();
                            $remarkSummary = $firstFlag
                                ? Str::limit($firstFlag->checklistItem->name.' — '.$firstFlag->remarks, 60)
                                  .($flagged->count() > 1 ? ' (+'.($flagged->count() - 1).' more)' : '')
                                : null;
                        @endphp
                        <tr>
                            <td>
                                <div class="fw-bold text-dark">{{ $relativeDate($inspection->inspection_date) }}</div>
                                <div class="small text-secondary">{{ $inspection->created_at->format('h:i A') }}</div>
                            </td>
                            <td>
                                <div class="fw-bold text-dark">{{ $inspection->vehicle->plate_number }}</div>
                                <div class="small text-secondary">{{ $inspection->driver->name }}</div>
                            </td>
                            <td>
                                @if ($inspection->issueCount() === 0)
                                    <span class="badge badge-operational px-3 py-2 rounded-pill">All OK</span>
                                @else
                                    <span class="badge badge-not-operational px-3 py-2 rounded-pill">Has Issue</span>
                                @endif
                            </td>
                            <td class="text-secondary">
                                @if ($remarkSummary)
                                    {{ $remarkSummary }}
                                @else
                                    <em class="small">None</em>
                                @endif
                            </td>
                            <td>
                                <span class="badge {{ $inspection->review_status === Inspection::REVIEW_REVIEWED ? 'badge-reviewed' : 'badge-pending' }} px-3 py-2 rounded-pill">
                                    {{ $inspection->review_status }}
                                </span>
                            </td>
                            <td class="text-end">
                                <button class="btn btn-sm btn-light border" data-bs-toggle="modal" data-bs-target="#viewInspection{{ $inspection->id }}">View Checklist</button>
                                @if ($inspection->review_status !== Inspection::REVIEW_REVIEWED)
                                    <button class="btn btn-sm btn-navy text-white fw-medium" data-bs-toggle="modal" data-bs-target="#reviewInspection{{ $inspection->id }}">Review</button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-secondary py-4">No inspections submitted yet. Drivers submit them from the mobile app.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @include('partials.table-footer', ['paginator' => $inspections, 'label' => 'inspections'])
    </div>

    <!-- Frequently Reported Issues (FR-10) -->
    <div class="d-flex align-items-center gap-2 mb-3">
        <h5 class="fw-bold mb-0"><i class="bi bi-graph-up-arrow me-2"></i>Frequently Reported Issues</h5>
        <span class="badge bg-light text-dark border rounded-pill px-3 py-2">This agency</span>
    </div>
    <div class="card border-0 shadow-sm rounded-3 mb-5">
        <div class="card-body p-4">
            <p class="text-secondary small mb-3">Recurring vehicle issues across recent BLOWBAGETS inspections, ranked by how often they appear. Use this to spot fleet-wide maintenance patterns.</p>
            @if ($frequentIssues->isEmpty())
                <div class="text-secondary small">No recurring issues recorded.</div>
            @else
                @foreach ($frequentIssues as $issue)
                    <div class="d-flex align-items-center gap-3 py-2 {{ $loop->first ? '' : 'border-top' }}">
                        <span class="text-secondary fw-bold" style="min-width:1.5rem;">#{{ $loop->iteration }}</span>
                        <div class="flex-grow-1">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="fw-semibold text-dark">{{ $issue->name }}</span>
                                <span class="small text-secondary ms-2">Last: {{ $relativeDate(Carbon::parse($issue->last_reported)) }}</span>
                            </div>
                            <div class="progress mt-1" style="height:6px;">
                                <div class="progress-bar bg-warning" role="progressbar" style="width: {{ round(($issue->count / $maxIssueCount) * 100) }}%"></div>
                            </div>
                        </div>
                        <span class="badge bg-warning text-dark rounded-pill">{{ $issue->count }}&times;</span>
                    </div>
                @endforeach
            @endif
        </div>
    </div>

    {{-- Damage Reports section joins this page in Phase 4 (FR-11/FR-12). --}}

    @foreach ($inspections as $inspection)
        @php
            $flagged = $inspection->items->where('status', 'Has Issue');
            $standardItems = $inspection->items->filter(fn ($i) => ! $i->checklistItem->is_bfp_only)->sortBy(fn ($i) => $i->checklistItem->sort_order);
            $bfpItems = $inspection->items->filter(fn ($i) => $i->checklistItem->is_bfp_only)->sortBy(fn ($i) => $i->checklistItem->sort_order);
        @endphp

        <!-- View Checklist Modal -->
        <div class="modal fade" id="viewInspection{{ $inspection->id }}" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header bg-light">
                        <h5 class="modal-title fw-bold">BLOWBAGETS Checklist</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="row mb-4">
                            <div class="col-sm-4">
                                <p class="mb-1 text-secondary small">Vehicle</p>
                                <h6 class="fw-bold">{{ $inspection->vehicle->plate_number }} ({{ $inspection->vehicle->type }})</h6>
                            </div>
                            <div class="col-sm-4 text-sm-center">
                                <p class="mb-1 text-secondary small">Submitted By</p>
                                <h6 class="fw-bold">{{ $inspection->driver->name }}</h6>
                            </div>
                            <div class="col-sm-4 text-sm-end">
                                <p class="mb-1 text-secondary small">Date</p>
                                <h6 class="fw-bold">{{ $inspection->inspection_date->format('M j, Y') }}</h6>
                            </div>
                        </div>

                        <h6 class="fw-bold border-bottom pb-2 mb-3">Standard Items ({{ $standardItems->count() }})</h6>
                        <div class="row g-3 {{ $bfpItems->isNotEmpty() ? 'mb-4' : '' }}">
                            @foreach ($standardItems as $item)
                                <div class="col-md-6">
                                    @if ($item->status === 'OK')
                                        <i class="bi bi-check-circle-fill text-success me-2"></i>{{ $item->checklistItem->name }}
                                    @else
                                        <i class="bi bi-x-circle-fill text-danger me-2"></i>{{ $item->checklistItem->name }}
                                        <div class="small text-danger ms-4">{{ $item->remarks }}</div>
                                    @endif
                                </div>
                            @endforeach
                        </div>

                        @if ($bfpItems->isNotEmpty())
                            <h6 class="fw-bold border-bottom pb-2 mb-3 text-warning">BFP Additional Items ({{ $bfpItems->count() }})</h6>
                            <div class="row g-3">
                                @foreach ($bfpItems as $item)
                                    <div class="col-md-6">
                                        @if ($item->status === 'OK')
                                            <i class="bi bi-check-circle-fill text-success me-2"></i>{{ $item->checklistItem->name }}
                                        @else
                                            <i class="bi bi-x-circle-fill text-danger me-2"></i>{{ $item->checklistItem->name }}
                                            <div class="small text-danger ms-4">{{ $item->remarks }}</div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @endif

                        @if ($inspection->reviewer)
                            <p class="text-secondary small mt-4 mb-0">
                                Reviewed by {{ $inspection->reviewer->name }} on {{ $inspection->reviewed_at->format('M j, Y h:i A') }}.
                            </p>
                        @endif
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Review Inspection Modal -->
        @if ($inspection->review_status !== Inspection::REVIEW_REVIEWED)
            <div class="modal fade" id="reviewInspection{{ $inspection->id }}" tabindex="-1">
                <div class="modal-dialog">
                    <form method="POST" action="{{ route('inspections.review', $inspection) }}" class="modal-content">
                        @csrf @method('PATCH')
                        <div class="modal-header bg-navy text-white">
                            <h5 class="modal-title fw-bold">Review Inspection</h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body p-4">
                            <div class="d-flex justify-content-between align-items-center bg-light rounded-3 p-3 mb-3">
                                <div>
                                    <div class="fw-bold">{{ $inspection->vehicle->plate_number }} ({{ $inspection->vehicle->type }})</div>
                                    <div class="small text-secondary">{{ $inspection->driver->name }} &middot; {{ $relativeDate($inspection->inspection_date) }}, {{ $inspection->created_at->format('h:i A') }}</div>
                                </div>
                                @if ($inspection->issueCount() === 0)
                                    <span class="badge badge-operational px-3 py-2 rounded-pill">All OK</span>
                                @else
                                    <span class="badge badge-not-operational px-3 py-2 rounded-pill">Has Issue</span>
                                @endif
                            </div>
                            <div class="border rounded-3 p-3 mb-4">
                                <div class="small text-secondary fw-semibold mb-1">Driver's Submission</div>
                                <div class="fw-medium">
                                    @if ($flagged->isEmpty())
                                        All {{ $inspection->items->count() }} items OK — no issues reported.
                                    @else
                                        @foreach ($flagged as $item)
                                            <div>{{ $item->checklistItem->name }} — {{ $item->remarks }}</div>
                                        @endforeach
                                    @endif
                                </div>
                            </div>
                            <p class="text-secondary small mb-4">Evaluate the findings, update the vehicle's operational status if action is required, and mark the inspection as Reviewed.</p>
                            <div class="mb-2">
                                <label class="form-label fw-semibold">Vehicle Status</label>
                                <select name="vehicle_status" class="form-select">
                                    <option value="">Keep current — {{ $inspection->vehicle->status }} (no action needed)</option>
                                    <option value="{{ Vehicle::STATUS_OPERATIONAL }}">Operational (cleared for deployment)</option>
                                    <option value="{{ Vehicle::STATUS_NOT_OPERATIONAL }}">Not Operational (unsafe for deployment)</option>
                                    <option value="{{ Vehicle::STATUS_UNDER_PM }}">Under Preventive Maintenance (maintenance required)</option>
                                </select>
                            </div>
                        </div>
                        <div class="modal-footer border-0">
                            <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-navy text-white">Mark Reviewed &amp; Update Status</button>
                        </div>
                    </form>
                </div>
            </div>
        @endif
    @endforeach
@endsection
