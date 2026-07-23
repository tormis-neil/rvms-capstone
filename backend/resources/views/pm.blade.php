@extends('layouts.app')

@section('title', 'RVMS - PM Schedules')

@section('content')
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h3 class="fw-bold mb-0" style="color: var(--primary);">Preventive Maintenance</h3>
                        <p class="text-secondary mb-0">Track and schedule vehicle maintenance cycles</p>
                    </div>
                    <button class="btn btn-navy text-white fw-medium px-4 py-2 bg-navy rounded-3" data-bs-toggle="modal" data-bs-target="#createPmModal">
                        <i class="bi bi-plus-lg me-2"></i>Create PM Schedule
                    </button>
                </div>

                @if (session('status'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        {{ session('status') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                <!-- Tabs -->
                <ul class="nav nav-tabs mb-4" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="active-tab" data-bs-toggle="tab" data-bs-target="#active" type="button" role="tab">Active Schedules <span class="badge bg-navy text-white ms-1">{{ $active->count() }}</span></button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="completed-tab" data-bs-toggle="tab" data-bs-target="#completed" type="button" role="tab">Completed Records</button>
                    </li>
                </ul>

                <div class="tab-content">
                    <!-- Active Tab Pane -->
                    <div class="tab-pane fade show active" id="active" role="tabpanel">
                        <div class="card border-0 shadow-sm rounded-3 overflow-hidden">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="py-3 text-secondary fw-semibold small">VEHICLE</th>
                                            <th class="py-3 text-secondary fw-semibold small">MAINTENANCE TARGET</th>
                                            <th class="py-3 text-secondary fw-semibold small">SCHEDULE TYPE</th>
                                            <th class="py-3 text-secondary fw-semibold small">DUE AT</th>
                                            <th class="py-3 text-secondary fw-semibold small">STATUS</th>
                                            <th class="py-3 text-secondary fw-semibold small text-end">ACTIONS</th>
                                        </tr>
                                    </thead>
                                    <tbody id="rows-pm-active">
                                        @forelse ($active as $pm)
                                        <tr
                                            data-id="{{ $pm->id }}"
                                            data-vehicle-id="{{ $pm->vehicle_id }}"
                                            data-plate="{{ $pm->vehicle->plate_number ?? '—' }}"
                                            data-type="{{ $pm->vehicle->type ?? '' }}"
                                            data-target="{{ $pm->service_target }}"
                                            data-pm-type="{{ $pm->pm_type }}"
                                            data-interval-km="{{ $pm->interval_km }}"
                                            data-last-pm-mileage="{{ $pm->last_pm_mileage }}"
                                            data-due-soon-km="{{ $pm->due_soon_threshold_km }}"
                                            data-due-date="{{ $pm->due_date?->toDateString() }}"
                                            data-due-soon-days="{{ $pm->due_soon_threshold_days }}">
                                            <td>
                                                <div class="fw-bold">{{ $pm->vehicle->plate_number ?? '—' }}</div>
                                                <div class="small text-secondary">{{ $pm->vehicle->type ?? '' }}</div>
                                            </td>
                                            <td><div class="fw-semibold">{{ $pm->service_target }}</div></td>
                                            <td><div class="badge bg-light text-dark border">{{ $pm->pm_type }}</div></td>
                                            <td>
                                                <div class="fw-bold {{ $pm->status === \App\Models\PmSchedule::STATUS_DUE ? 'text-danger' : ($pm->status === \App\Models\PmSchedule::STATUS_DUE_SOON ? 'text-warning' : 'text-dark') }}">{{ $pm->targetLabel() }}</div>
                                                @if ($pm->pm_type === \App\Models\PmSchedule::TYPE_MILEAGE)
                                                <div class="small text-secondary">Current: {{ number_format($pm->vehicle->current_mileage ?? 0) }} km{{ $pm->status === \App\Models\PmSchedule::STATUS_DUE ? ' — overdue' : '' }}</div>
                                                @endif
                                            </td>
                                            <td><span class="badge {{ $pm->statusBadgeClass() }} px-3 py-2 rounded-pill">{{ $pm->status }}</span></td>
                                            <td class="text-end">
                                                <div class="d-flex align-items-center justify-content-end gap-2">
                                                    <button class="btn btn-sm btn-success fw-medium js-complete" data-bs-toggle="modal" data-bs-target="#markCompletedModal">Mark Completed</button>
                                                    <button class="btn btn-sm btn-light border js-edit" title="Edit PM Schedule" data-bs-toggle="modal" data-bs-target="#editPmModal"><i class="bi bi-pencil"></i></button>
                                                </div>
                                            </td>
                                        </tr>
                                        @empty
                                        <tr><td colspan="6" class="text-center text-secondary py-4">No active PM schedules.</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Completed Tab Pane -->
                    <div class="tab-pane fade" id="completed" role="tabpanel">
                        <div class="card border-0 shadow-sm rounded-3 overflow-hidden">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="py-3 text-secondary fw-semibold small">VEHICLE</th>
                                            <th class="py-3 text-secondary fw-semibold small">MAINTENANCE PERFORMED</th>
                                            <th class="py-3 text-secondary fw-semibold small">DATE SERVICED</th>
                                            <th class="py-3 text-secondary fw-semibold small">REPAIR SOURCE</th>
                                            <th class="py-3 text-secondary fw-semibold small">PARTS REPLACED</th>
                                            <th class="py-3 text-secondary fw-semibold small">STATUS</th>
                                        </tr>
                                    </thead>
                                    <tbody id="rows-pm-completed">
                                        @forelse ($completed as $pm)
                                        <tr>
                                            <td>
                                                <div class="fw-bold text-dark">{{ $pm->vehicle->plate_number ?? '—' }}</div>
                                                <div class="small text-secondary">{{ $pm->vehicle->type ?? '' }}</div>
                                            </td>
                                            <td><div class="fw-semibold text-dark">{{ $pm->service_target }}</div></td>
                                            <td><div class="fw-medium text-dark">{{ $pm->date_serviced?->format('M j, Y') ?? '—' }}</div></td>
                                            <td><span class="badge bg-light text-dark border">{{ $pm->completion_repair_source ?? '—' }}</span></td>
                                            <td><div class="fw-medium text-dark">{{ $pm->completion_parts_replaced ?? '—' }}</div></td>
                                            <td><span class="badge bg-secondary px-3 py-2 rounded-pill">Completed</span></td>
                                        </tr>
                                        @empty
                                        <tr><td colspan="6" class="text-center text-secondary py-4">No completed PM records yet.</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
@endsection

@section('modals')
    <!-- Create PM Modal -->
    <div class="modal fade" id="createPmModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-navy text-white">
                    <h5 class="modal-title fw-bold">Create PM Schedule</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="{{ route('pm.store') }}">
                @csrf
                <div class="modal-body p-4">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Vehicle</label>
                            <select class="form-select" name="vehicle_id" required>
                                @foreach ($vehicles as $v)
                                <option value="{{ $v->id }}">{{ $v->plate_number }} ({{ $v->type }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Specific Part(s)</label>
                            <input type="text" class="form-control" name="service_target" placeholder="e.g., Oil Change & Filter, Tire Replacement" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">PM Type</label>
                            <select class="form-select js-pm-type" name="pm_type">
                                <option value="Mileage-Based">Mileage-Based</option>
                                <option value="Time-Based">Time-Based</option>
                            </select>
                        </div>
                        {{-- Structured, type-appropriate fields replace the prototype's ambiguous
                             free-text "Interval"/"Threshold" so the schedule can persist and be
                             recalculated (documented refinement of the prototype form). --}}
                        <div class="js-mileage-fields">
                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Interval (km)</label>
                                    <input type="number" min="1" class="form-control" name="interval_km" placeholder="e.g., 5000">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Last PM Mileage</label>
                                    <input type="number" min="0" class="form-control" name="last_pm_mileage" placeholder="e.g., 41000">
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Due Soon Threshold (km)</label>
                                <input type="number" min="0" class="form-control" name="due_soon_threshold_km" placeholder="e.g., 500">
                            </div>
                        </div>
                        <div class="js-time-fields" style="display:none;">
                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Target Date</label>
                                    <input type="date" class="form-control" name="due_date">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Due Soon Threshold (days)</label>
                                    <input type="number" min="0" class="form-control" name="due_soon_threshold_days" placeholder="e.g., 14">
                                </div>
                            </div>
                        </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-navy bg-navy text-white">Create Schedule</button>
                </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Mark Completed Modal (success flow) -->
    <div class="modal fade" id="markCompletedModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title fw-bold">Mark Maintenance Completed</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="completePmForm" action="#">
                @csrf
                @method('PATCH')
                <div class="modal-body p-4">
                    <p class="text-secondary small mb-4">Record the completion of this preventive maintenance schedule. Remember to update the vehicle's status to Operational in Vehicle Management afterwards.</p>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Date Serviced</label>
                            <input type="date" class="form-control" name="date_serviced" value="{{ now()->toDateString() }}" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Repair Source</label>
                            <select class="form-select" name="completion_repair_source">
                                @foreach (\App\Models\RepairLog::SOURCES as $source)
                                <option value="{{ $source }}">{{ $source }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Parts Replaced</label>
                            <input type="text" class="form-control" name="completion_parts_replaced" placeholder="e.g., Engine oil, oil filter">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Remarks & Findings</label>
                            <textarea class="form-control" name="completion_remarks" rows="2" placeholder="Any additional notes..."></textarea>
                        </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Mark Completed</button>
                </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit PM Modal -->
    <div class="modal fade" id="editPmModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-navy text-white">
                    <h5 class="modal-title fw-bold">Edit PM Schedule</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="editPmForm" action="#">
                @csrf
                @method('PUT')
                <div class="modal-body p-4">
                        <input type="hidden" name="vehicle_id" id="epVehicleId">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Vehicle</label>
                            <input type="text" class="form-control bg-light" id="epVehicle" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Specific Part(s)</label>
                            <input type="text" class="form-control" name="service_target" id="epTarget" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">PM Type</label>
                            <select class="form-select js-pm-type" name="pm_type" id="epType">
                                <option value="Mileage-Based">Mileage-Based</option>
                                <option value="Time-Based">Time-Based</option>
                            </select>
                        </div>
                        <div class="js-mileage-fields">
                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Interval (km)</label>
                                    <input type="number" min="1" class="form-control" name="interval_km" id="epIntervalKm">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Last PM Mileage</label>
                                    <input type="number" min="0" class="form-control" name="last_pm_mileage" id="epLastMileage">
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Due Soon Threshold (km)</label>
                                <input type="number" min="0" class="form-control" name="due_soon_threshold_km" id="epDueSoonKm">
                            </div>
                        </div>
                        <div class="js-time-fields" style="display:none;">
                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Target Date</label>
                                    <input type="date" class="form-control" name="due_date" id="epDueDate">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Due Soon Threshold (days)</label>
                                    <input type="number" min="0" class="form-control" name="due_soon_threshold_days" id="epDueSoonDays">
                                </div>
                            </div>
                        </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-navy bg-navy text-white">Save Changes</button>
                </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        const completePmTemplate = @json(route('pm.complete', ['pmSchedule' => '__ID__']));
        const editPmTemplate = @json(route('pm.update', ['pmSchedule' => '__ID__']));

        // Show the mileage-based or time-based fields for a modal's PM Type select.
        function bindTypeToggle(root) {
            const select = root.querySelector('.js-pm-type');
            const mileage = root.querySelector('.js-mileage-fields');
            const time = root.querySelector('.js-time-fields');
            if (!select || !mileage || !time) return;
            const sync = () => {
                const isMileage = select.value === 'Mileage-Based';
                mileage.style.display = isMileage ? '' : 'none';
                time.style.display = isMileage ? 'none' : '';
            };
            select.addEventListener('change', sync);
            sync();
        }

        bindTypeToggle(document.getElementById('createPmModal'));
        const editModal = document.getElementById('editPmModal');
        bindTypeToggle(editModal);

        // Mark Completed — set the per-row action.
        document.getElementById('markCompletedModal').addEventListener('show.bs.modal', event => {
            const row = event.relatedTarget && event.relatedTarget.closest('tr');
            if (!row) return;
            document.getElementById('completePmForm').action = completePmTemplate.replace('__ID__', row.dataset.id);
        });

        // Edit — populate from the clicked row.
        editModal.addEventListener('show.bs.modal', event => {
            const row = event.relatedTarget && event.relatedTarget.closest('tr');
            if (!row) return;
            const d = row.dataset;
            document.getElementById('editPmForm').action = editPmTemplate.replace('__ID__', d.id);
            document.getElementById('epVehicleId').value = d.vehicleId;
            document.getElementById('epVehicle').value = d.plate + (d.type ? ' (' + d.type + ')' : '');
            document.getElementById('epTarget').value = d.target || '';
            document.getElementById('epType').value = d.pmType;
            document.getElementById('epIntervalKm').value = d.intervalKm || '';
            document.getElementById('epLastMileage').value = d.lastPmMileage || '';
            document.getElementById('epDueSoonKm').value = d.dueSoonKm || '';
            document.getElementById('epDueDate').value = d.dueDate || '';
            document.getElementById('epDueSoonDays').value = d.dueSoonDays || '';
            const isMileage = d.pmType === 'Mileage-Based';
            editModal.querySelector('.js-mileage-fields').style.display = isMileage ? '' : 'none';
            editModal.querySelector('.js-time-fields').style.display = isMileage ? 'none' : '';
        });
    </script>
@endsection
