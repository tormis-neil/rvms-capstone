@extends('layouts.app')

@section('title', 'RVMS - Inspections & Damage')

@section('content')
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h3 class="fw-bold mb-0" style="color: var(--primary);">Inspections & Damage</h3>
                        <p class="text-secondary mb-0">Monitor daily BLOWBAGETS and review defect reports</p>
                    </div>
                </div>

                {{-- Success feedback — documented addition (the prototype has no alert state) --}}
                @if (session('status'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        {{ session('status') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                <!-- Daily Inspections Section -->
                <div class="d-flex align-items-center gap-2 mb-3">
                    <h5 class="fw-bold mb-0"><i class="bi bi-card-checklist me-2"></i>Daily BLOWBAGETS Inspections</h5>
                    <span class="badge badge-pending rounded-pill px-3 py-2 js-insp-pending">{{ $pendingCount }} Pending Review</span>
                </div>
                <div class="card border-0 shadow-sm rounded-3 overflow-hidden mb-5">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="py-3 text-secondary fw-semibold small">DATE SUBMITTED</th>
                                    <th class="py-3 text-secondary fw-semibold small">VEHICLE & DRIVER</th>
                                    <th class="py-3 text-secondary fw-semibold small">RESULT</th>
                                    <th class="py-3 text-secondary fw-semibold small">REMARKS</th>
                                    <th class="py-3 text-secondary fw-semibold small">REVIEW STATUS</th>
                                    <th class="py-3 text-secondary fw-semibold small text-end">ACTIONS</th>
                                </tr>
                            </thead>
                            <tbody id="rows-inspections">
                                {{-- Live rows. Per-row data attributes feed the View Checklist and
                                     Review modals (the prototype does this via agency.js). The action
                                     buttons follow the prototype's static markup + plan sub-task 10:
                                     light "View Checklist" + solid-navy "Review" (Pending rows only). --}}
                                @forelse ($inspections as $inspection)
                                <tr
                                    data-id="{{ $inspection->id }}"
                                    data-plate="{{ $inspection->vehicle->plate_number ?? '—' }}"
                                    data-type="{{ $inspection->vehicle->type ?? '' }}"
                                    data-driver="{{ $inspection->driver->name ?? '—' }}"
                                    data-when="{{ $inspection->dateLabel() }}, {{ $inspection->timeLabel() }}"
                                    data-result="{{ $inspection->resultLabel() }}"
                                    data-result-badge="{{ $inspection->resultBadgeClass() }}"
                                    data-remarks="{{ $inspection->remarksSummary() }}"
                                    data-is-bfp="{{ $inspection->items->contains(fn ($i) => $i->checklistItem?->is_bfp_only) ? '1' : '0' }}"
                                    data-items="{{ json_encode($inspection->items->map(fn ($i) => ['name' => $i->checklistItem->name ?? '', 'is_bfp_only' => (bool) ($i->checklistItem->is_bfp_only ?? false), 'status' => $i->status, 'remarks' => $i->remarks])->values()) }}">
                                    <td>
                                        <div class="fw-bold text-dark">{{ $inspection->dateLabel() }}</div>
                                        <div class="small text-secondary">{{ $inspection->timeLabel() }}</div>
                                    </td>
                                    <td>
                                        <div class="fw-bold text-dark">{{ $inspection->vehicle->plate_number ?? '—' }}</div>
                                        <div class="small text-secondary">{{ $inspection->driver->name ?? '—' }}</div>
                                    </td>
                                    <td><span class="badge {{ $inspection->resultBadgeClass() }} px-3 py-2 rounded-pill">{{ $inspection->resultLabel() }}</span></td>
                                    <td class="text-secondary">
                                        @if ($inspection->remarksSummary() === 'None')
                                        <em class="small">None</em>
                                        @else
                                        {{ $inspection->remarksSummary() }}
                                        @endif
                                    </td>
                                    <td><span class="badge {{ $inspection->reviewBadgeClass() }} px-3 py-2 rounded-pill">{{ $inspection->review_status }}</span></td>
                                    <td class="text-end">
                                        <button class="btn btn-sm btn-light border" data-bs-toggle="modal" data-bs-target="#viewChecklistModal">View Checklist</button>
                                        @if ($inspection->review_status === \App\Models\Inspection::STATUS_PENDING)
                                        <button class="btn btn-sm bg-navy text-white fw-medium" data-bs-toggle="modal" data-bs-target="#reviewInspectionModal">Review</button>
                                        @endif
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="6" class="text-center text-secondary py-4">No inspections submitted yet.</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Frequently Reported Issues (aggregated from inspections & damage) -->
                <div class="d-flex align-items-center gap-2 mb-3">
                    <h5 class="fw-bold mb-0"><i class="bi bi-graph-up-arrow me-2"></i>Frequently Reported Issues</h5>
                    <span class="badge bg-light text-dark border rounded-pill px-3 py-2">This agency</span>
                </div>
                <div class="card border-0 shadow-sm rounded-3 mb-5">
                    <div class="card-body p-4">
                        <p class="text-secondary small mb-3">Recurring vehicle issues across recent BLOWBAGETS inspections and damage reports, ranked by how often they appear. Use this to spot fleet-wide maintenance patterns.</p>
                        {{-- Live ranked bars — same markup the prototype's renderFrequentIssues() paints.
                             Populated server-side from Has-Issue inspection items (damage reports join
                             this aggregate in R4). --}}
                        <div id="freq-issues">
                            @forelse ($frequentIssues as $index => $issue)
                            <div class="d-flex align-items-center gap-3 py-2{{ $index ? ' border-top' : '' }}">
                                <span class="text-secondary fw-bold" style="min-width:1.5rem;">#{{ $index + 1 }}</span>
                                <div class="flex-grow-1">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="fw-semibold text-dark">{{ $issue['issue'] }}</span>
                                        <span class="small text-secondary ms-2">Last: {{ $issue['last'] }}</span>
                                    </div>
                                    <div class="progress mt-1" style="height:6px;">
                                        <div class="progress-bar bg-warning" role="progressbar" style="width:{{ (int) round($issue['count'] / $frequentIssues->max('count') * 100) }}%"></div>
                                    </div>
                                </div>
                                <span class="badge bg-warning text-dark rounded-pill">{{ $issue['count'] }}×</span>
                            </div>
                            @empty
                            <div class="text-secondary small">No recurring issues recorded.</div>
                            @endforelse
                        </div>
                    </div>
                </div>

@endsection

@section('modals')
    <!-- View Checklist Modal -->
    <div class="modal fade" id="viewChecklistModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-light">
                    <h5 class="modal-title fw-bold">BLOWBAGETS Checklist</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row mb-4">
                        <div class="col-sm-6">
                            <p class="mb-1 text-secondary small">Vehicle</p>
                            <h6 class="fw-bold" id="vcVehicle">—</h6>
                        </div>
                        <div class="col-sm-6 text-sm-end">
                            <p class="mb-1 text-secondary small">Submitted By</p>
                            <h6 class="fw-bold" id="vcDriver">—</h6>
                        </div>
                    </div>
                    {{-- Live: green ✓ for OK, red ✗ for Has Issue, with the flagged item's remarks
                         (the prototype's demo always showed all-green; the plan requires the real
                         per-item result here). --}}
                    <h6 class="fw-bold border-bottom pb-2 mb-3">Standard Items (12)</h6>
                    <div class="row g-3 mb-4" id="vcStandard"></div>
                    <div id="checklist-extra">
                        <h6 class="fw-bold border-bottom pb-2 mb-3 text-warning">BFP Additional Items (2)</h6>
                        <div class="row g-3" id="vcExtra"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Review Inspection Modal -->
    <div class="modal fade" id="reviewInspectionModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-navy text-white">
                    <h5 class="modal-title fw-bold">Review Inspection</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                {{-- Live form wraps body + footer (action set per-row by the page script) --}}
                <form method="POST" id="reviewInspectionForm" action="#">
                @csrf
                @method('PATCH')
                <div class="modal-body p-4">
                    <div class="d-flex justify-content-between align-items-center bg-light rounded-3 p-3 mb-3">
                        <div>
                            <div class="fw-bold" id="riVehicle">—</div>
                            <div class="small text-secondary"><span id="riDriver">—</span> &middot; <span id="riWhen">—</span></div>
                        </div>
                        <span class="badge px-3 py-2 rounded-pill" id="riResultBadge">—</span>
                    </div>
                    <div class="border rounded-3 p-3 mb-4">
                        <div class="small text-secondary fw-semibold mb-1">Driver's Submission</div>
                        <div class="fw-medium" id="riRemarks">—</div>
                    </div>
                    <p class="text-secondary small mb-4">Evaluate the findings, update the vehicle's operational status if action is required, and mark the inspection as Reviewed.</p>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Vehicle Status</label>
                            {{-- First option = no status change; the other two write the vehicle status
                                 (FR-10 + FR-18). Dispatched is set by the Dispatch module alone. --}}
                            <select class="form-select" name="vehicle_status">
                                <option value="">Leave as Operational (no action needed)</option>
                                <option value="Not Operational">Not Operational (unsafe for deployment)</option>
                                <option value="Under Preventive Maintenance">Under Preventive Maintenance (maintenance required)</option>
                            </select>
                        </div>
                        {{-- The prototype's "Admin Remarks (Optional)" textarea is omitted: the approved
                             schema deliberately excludes admin-remarks columns on inspection reviews
                             (design decision 7 — documented omission). --}}
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn bg-navy text-white">Mark Reviewed & Update Status</button>
                </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        // Live wiring: both inspection modals are populated from the clicked row's
        // data attributes (the prototype does the same via agency.js).
        const reviewActionTemplate = @json(route('inspections.review', ['inspection' => '__ID__']));

        function rowData(event) {
            const row = event.relatedTarget && event.relatedTarget.closest('tr');
            return row ? row.dataset : null;
        }

        function itemHtml(item) {
            const ok = item.status === 'OK';
            const icon = ok
                ? '<i class="bi bi-check-circle-fill text-success me-2"></i>'
                : '<i class="bi bi-x-circle-fill text-danger me-2"></i>';
            const remark = (!ok && item.remarks)
                ? '<div class="small text-danger ms-4">' + item.remarks + '</div>'
                : '';
            return '<div class="col-md-6">' + icon + item.name + remark + '</div>';
        }

        // View Checklist modal — grouped Standard / BFP Additional with per-item results.
        document.getElementById('viewChecklistModal').addEventListener('show.bs.modal', event => {
            const d = rowData(event);
            if (!d) return;
            document.getElementById('vcVehicle').textContent = d.plate + (d.type ? ' (' + d.type + ')' : '');
            document.getElementById('vcDriver').textContent = d.driver;

            const items = JSON.parse(d.items || '[]');
            const standard = items.filter(i => !i.is_bfp_only);
            const extra = items.filter(i => i.is_bfp_only);

            document.getElementById('vcStandard').innerHTML = standard.map(itemHtml).join('');

            // The BFP Additional section only appears for inspections that include those items.
            const extraWrap = document.getElementById('checklist-extra');
            if (extra.length) {
                extraWrap.style.display = '';
                document.getElementById('vcExtra').innerHTML = extra.map(itemHtml).join('');
            } else {
                extraWrap.style.display = 'none';
            }
        });

        // Review modal — context, driver's submission, and the per-row action URL.
        document.getElementById('reviewInspectionModal').addEventListener('show.bs.modal', event => {
            const d = rowData(event);
            if (!d) return;
            document.getElementById('reviewInspectionForm').action = reviewActionTemplate.replace('__ID__', d.id);
            document.getElementById('riVehicle').textContent = d.plate + (d.type ? ' (' + d.type + ')' : '');
            document.getElementById('riDriver').textContent = d.driver;
            document.getElementById('riWhen').textContent = d.when;

            const badge = document.getElementById('riResultBadge');
            badge.className = 'badge ' + d.resultBadge + ' px-3 py-2 rounded-pill';
            badge.textContent = d.result;

            document.getElementById('riRemarks').textContent = (d.remarks && d.remarks !== 'None')
                ? d.remarks
                : 'No issues reported — all BLOWBAGETS items OK.';

            document.querySelector('#reviewInspectionForm select[name=vehicle_status]').value = '';
        });
    </script>
@endsection
