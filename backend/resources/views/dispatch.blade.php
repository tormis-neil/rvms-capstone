@extends('layouts.app')

@section('title', 'RVMS - Dispatch Logs')

@section('content')
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h3 class="fw-bold mb-0" style="color: var(--primary);">Dispatch Logging</h3>
                        <p class="text-secondary mb-0">Record and monitor vehicle dispatches in real-time</p>
                    </div>
                    <button class="btn btn-navy text-white fw-medium px-4 py-2 bg-navy rounded-3" data-bs-toggle="modal" data-bs-target="#newDispatchModal">
                        <i class="bi bi-send-plus me-2"></i>New Dispatch
                    </button>
                </div>

                @if (session('status'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        {{ session('status') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                <!-- Active Missions Alert -->
                <div class="alert alert-primary border-0 shadow-sm d-flex align-items-center mb-4" role="alert">
                    <i class="bi bi-broadcast fs-4 me-3"></i>
                    <div>
                        <strong>Active Monitoring:</strong> There {{ $activeCount === 1 ? 'is' : 'are' }} currently <strong>{{ $activeCount }}</strong> {{ Str::plural('vehicle', $activeCount) }} deployed in the field.
                    </div>
                </div>

                <!-- Dispatch Table -->
                <div class="card border-0 shadow-sm rounded-3 overflow-hidden">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="py-3 text-secondary fw-semibold small">MISSION & LOCATION</th>
                                    <th class="py-3 text-secondary fw-semibold small">VEHICLE & DRIVER</th>
                                    <th class="py-3 text-secondary fw-semibold small">TIME OUT</th>
                                    <th class="py-3 text-secondary fw-semibold small">TIME IN</th>
                                    <th class="py-3 text-secondary fw-semibold small">STATUS</th>
                                    <th class="py-3 text-secondary fw-semibold small text-end">ACTIONS</th>
                                </tr>
                            </thead>
                            <tbody id="rows-dispatch">
                                @forelse ($dispatches as $d)
                                <tr
                                    data-id="{{ $d->id }}"
                                    data-vehicle-id="{{ $d->vehicle_id }}"
                                    data-driver-id="{{ $d->driver_id }}"
                                    data-plate="{{ $d->vehicle->plate_number ?? '—' }}"
                                    data-type="{{ $d->vehicle->type ?? '' }}"
                                    data-driver="{{ $d->driver->name ?? '—' }}"
                                    data-mission-type="{{ $d->mission_type }}"
                                    data-mission-other="{{ $d->mission_other }}"
                                    data-mission-label="{{ $d->missionLabel() }}"
                                    data-location="{{ $d->location }}"
                                    data-time-out="{{ $d->time_out?->format('Y-m-d\TH:i') }}"
                                    data-time-out-label="{{ $d->time_out?->format('M j, Y, h:i A') }}"
                                    data-time-in-label="{{ $d->time_in?->format('M j, Y, h:i A') }}"
                                    data-odometer-out="{{ $d->odometer_out }}"
                                    data-return-status="{{ $d->return_status }}"
                                    data-remarks="{{ $d->remarks }}">
                                    <td>
                                        <div class="fw-bold">{{ $d->missionLabel() }}</div>
                                        <div class="small text-secondary"><i class="bi bi-geo-alt me-1"></i>{{ $d->location }}</div>
                                    </td>
                                    <td>
                                        <div class="fw-semibold">{{ $d->vehicle->plate_number ?? '—' }}</div>
                                        <div class="small text-secondary">{{ $d->driver->name ?? '—' }}</div>
                                    </td>
                                    <td>
                                        <div class="fw-medium">{{ $d->time_out?->isToday() ? 'Today' : $d->time_out?->format('M j, Y') }}</div>
                                        <div class="small text-secondary">{{ $d->time_out?->format('h:i A') }}</div>
                                    </td>
                                    <td>
                                        @if ($d->time_in)
                                        <div class="fw-medium">{{ $d->time_in->isToday() ? 'Today' : $d->time_in->format('M j, Y') }}</div>
                                        <div class="small text-secondary">{{ $d->time_in->format('h:i A') }}</div>
                                        @else
                                        <em class="text-secondary small">--</em>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($d->isActive())
                                        <span class="badge bg-primary px-3 py-2 rounded-pill">Active</span>
                                        @else
                                        <span class="badge bg-secondary px-3 py-2 rounded-pill">Completed</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        @if ($d->isActive())
                                        <button class="btn btn-sm btn-light border js-edit" title="Edit Dispatch" data-bs-toggle="modal" data-bs-target="#editDispatchModal"><i class="bi bi-pencil"></i></button>
                                        <button class="btn btn-sm btn-primary fw-medium js-close" data-bs-toggle="modal" data-bs-target="#closeDispatchModal">Close Dispatch</button>
                                        @else
                                        <button class="btn btn-sm btn-light border js-view" data-bs-toggle="modal" data-bs-target="#viewDispatchModal"><i class="bi bi-eye"></i></button>
                                        @endif
                                    </td>
                                </tr>
                                @empty
                                <tr><td colspan="6" class="text-center text-secondary py-4">No dispatches recorded yet.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
@endsection

@section('modals')
    <!-- New Dispatch Modal -->
    <div class="modal fade" id="newDispatchModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-navy text-white">
                    <h5 class="modal-title fw-bold">New Vehicle Dispatch</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="{{ route('dispatch.store') }}">
                @csrf
                <div class="modal-body p-4">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Vehicle</label>
                            <select class="form-select" name="vehicle_id" required>
                                @forelse ($vehicles as $v)
                                <option value="{{ $v->id }}">{{ $v->plate_number }} ({{ $v->type }}) - {{ $v->status }}</option>
                                @empty
                                <option value="" disabled>No Operational vehicles available</option>
                                @endforelse
                            </select>
                            <div class="form-text">Only Operational vehicles can be dispatched. Opening a dispatch sets the vehicle status to Dispatched.</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Driver</label>
                            <select class="form-select" name="driver_id" required>
                                @foreach ($drivers as $driver)
                                <option value="{{ $driver->id }}">{{ $driver->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Mission Type</label>
                            <select class="form-select js-mission" name="mission_type">
                                @foreach (\App\Models\Dispatch::MISSION_TYPES as $mission)
                                <option value="{{ $mission }}">{{ $mission }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3 js-mission-other-wrap" style="display:none;">
                            <label class="form-label fw-semibold">Specify Mission</label>
                            <input type="text" class="form-control js-mission-other" name="mission_other" placeholder="Describe the mission type">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Destination / Location</label>
                            <input type="text" class="form-control" name="location" placeholder="Enter full address or landmark" required>
                        </div>
                        <div class="row g-3 mb-3">
                            <div class="col-md-7">
                                <label class="form-label fw-semibold">Date and Time Out</label>
                                <input type="datetime-local" class="form-control" name="time_out" value="{{ now()->format('Y-m-d\TH:i') }}" required>
                            </div>
                            <div class="col-md-5">
                                {{-- Documented addition (design decision 8): optional odometer reading
                                     keyed from the vehicle's own odometer at time out (FR-15). --}}
                                <label class="form-label fw-semibold">Odometer Out</label>
                                <input type="number" min="0" class="form-control" name="odometer_out" placeholder="Optional (km)">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Remarks</label>
                            <textarea class="form-control" name="remarks" rows="2" placeholder="Optional notes..."></textarea>
                        </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-navy bg-navy text-white">Dispatch Vehicle</button>
                </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Close Dispatch Modal -->
    <div class="modal fade" id="closeDispatchModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title fw-bold">Close Dispatch</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="closeDispatchForm" action="#">
                @csrf
                @method('PATCH')
                <div class="modal-body p-4">
                    <p class="text-secondary small mb-4">Complete the dispatch record. The vehicle's status will be updated to the selected return status.</p>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Date and Time In</label>
                            <input type="datetime-local" class="form-control" name="time_in" value="{{ now()->format('Y-m-d\TH:i') }}" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Set Return Status</label>
                            <select class="form-select" name="return_status">
                                <option value="Operational">Operational (vehicle returned fit for use)</option>
                                <option value="Not Operational">Not Operational (issue found on return)</option>
                                <option value="Under Preventive Maintenance">Under Preventive Maintenance (maintenance needed on return)</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            {{-- Documented addition (design decision 8): optional time-in odometer.
                                 When higher than the vehicle's current mileage it updates it
                                 (mileage-on-arrival → feeds mileage-based PM, FR-16 → FR-14). --}}
                            <label class="form-label fw-semibold">Odometer In</label>
                            <input type="number" min="0" class="form-control" name="odometer_in" placeholder="Optional (km)">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Remarks (Optional)</label>
                            <textarea class="form-control" name="remarks" rows="2" placeholder="Summary of the completed dispatch..."></textarea>
                        </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Close Dispatch</button>
                </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Dispatch Modal -->
    <div class="modal fade" id="editDispatchModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-navy text-white">
                    <h5 class="modal-title fw-bold">Edit Dispatch</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="editDispatchForm" action="#">
                @csrf
                @method('PUT')
                <div class="modal-body p-4">
                    <input type="hidden" name="vehicle_id" id="edVehicleId">
                    <input type="hidden" name="driver_id" id="edDriverId">
                    <div class="d-flex justify-content-between align-items-center bg-light rounded-3 p-3 mb-4">
                        <div>
                            <div class="fw-bold" id="edVehicle">—</div>
                            <div class="small text-secondary" id="edDriver">—</div>
                        </div>
                        <span class="badge bg-primary px-3 py-2 rounded-pill">Active</span>
                    </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Mission Type</label>
                            <select class="form-select js-mission" name="mission_type" id="edMission">
                                @foreach (\App\Models\Dispatch::MISSION_TYPES as $mission)
                                <option value="{{ $mission }}">{{ $mission }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3 js-mission-other-wrap" style="display:none;">
                            <label class="form-label fw-semibold">Specify Mission</label>
                            <input type="text" class="form-control js-mission-other" name="mission_other" id="edMissionOther" placeholder="Describe the mission type">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Destination / Location</label>
                            <input type="text" class="form-control" name="location" id="edLocation" required>
                        </div>
                        <div class="row g-3 mb-3">
                            <div class="col-md-7">
                                <label class="form-label fw-semibold">Date and Time Out</label>
                                <input type="datetime-local" class="form-control" name="time_out" id="edTimeOut" required>
                            </div>
                            <div class="col-md-5">
                                <label class="form-label fw-semibold">Odometer Out</label>
                                <input type="number" min="0" class="form-control" name="odometer_out" id="edOdometerOut" placeholder="Optional (km)">
                            </div>
                        </div>
                        <div class="mb-2">
                            <label class="form-label fw-semibold">Remarks (Optional)</label>
                            <textarea class="form-control" name="remarks" id="edRemarks" rows="2" placeholder="Update notes..."></textarea>
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

    <!-- View Dispatch Modal (read-only) -->
    <div class="modal fade" id="viewDispatchModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-light">
                    <h5 class="modal-title fw-bold">Dispatch Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <span class="badge bg-secondary px-3 py-2 rounded-pill mb-2">Completed</span>
                        <h5 class="fw-bold" id="vwMission">—</h5>
                        <p class="text-secondary mb-0"><i class="bi bi-geo-alt me-1"></i><span id="vwLocation">—</span></p>
                    </div>
                    <hr>
                    <div class="row g-3">
                        <div class="col-6">
                            <p class="mb-1 text-secondary small">Vehicle</p>
                            <h6 class="fw-bold" id="vwVehicle">—</h6>
                        </div>
                        <div class="col-6">
                            <p class="mb-1 text-secondary small">Driver</p>
                            <h6 class="fw-bold" id="vwDriver">—</h6>
                        </div>
                        <div class="col-6">
                            <p class="mb-1 text-secondary small">Time Out</p>
                            <h6 class="fw-bold" id="vwTimeOut">—</h6>
                        </div>
                        <div class="col-6">
                            <p class="mb-1 text-secondary small">Time In</p>
                            <h6 class="fw-bold" id="vwTimeIn">—</h6>
                        </div>
                        <div class="col-6">
                            <p class="mb-1 text-secondary small">Return Status</p>
                            <h6 class="fw-bold" id="vwReturn">—</h6>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        const closeDispatchTemplate = @json(route('dispatch.close', ['dispatch' => '__ID__']));
        const editDispatchTemplate = @json(route('dispatch.update', ['dispatch' => '__ID__']));

        // "Others" free-text reveal within a modal root.
        function bindMissionToggle(root) {
            const select = root.querySelector('.js-mission');
            const wrap = root.querySelector('.js-mission-other-wrap');
            if (!select || !wrap) return;
            const sync = () => { wrap.style.display = select.value === 'Others' ? '' : 'none'; };
            select.addEventListener('change', sync);
            sync();
        }
        bindMissionToggle(document.getElementById('newDispatchModal'));
        const editModal = document.getElementById('editDispatchModal');
        bindMissionToggle(editModal);

        // Close Dispatch — set the per-row action.
        document.getElementById('closeDispatchModal').addEventListener('show.bs.modal', event => {
            const row = event.relatedTarget && event.relatedTarget.closest('tr');
            if (!row) return;
            document.getElementById('closeDispatchForm').action = closeDispatchTemplate.replace('__ID__', row.dataset.id);
        });

        // Edit Dispatch — populate from the clicked row.
        editModal.addEventListener('show.bs.modal', event => {
            const row = event.relatedTarget && event.relatedTarget.closest('tr');
            if (!row) return;
            const d = row.dataset;
            document.getElementById('editDispatchForm').action = editDispatchTemplate.replace('__ID__', d.id);
            document.getElementById('edVehicleId').value = d.vehicleId;
            document.getElementById('edDriverId').value = d.driverId;
            document.getElementById('edVehicle').value = d.plate + (d.type ? ' (' + d.type + ')' : '');
            document.getElementById('edVehicle').textContent = d.plate + (d.type ? ' (' + d.type + ')' : '');
            document.getElementById('edDriver').textContent = d.driver;
            document.getElementById('edMission').value = d.missionType;
            document.getElementById('edMissionOther').value = d.missionOther || '';
            document.getElementById('edLocation').value = d.location || '';
            document.getElementById('edTimeOut').value = d.timeOut || '';
            document.getElementById('edOdometerOut').value = d.odometerOut || '';
            document.getElementById('edRemarks').value = d.remarks || '';
            editModal.querySelector('.js-mission-other-wrap').style.display = d.missionType === 'Others' ? '' : 'none';
        });

        // View Dispatch — populate read-only details.
        document.getElementById('viewDispatchModal').addEventListener('show.bs.modal', event => {
            const row = event.relatedTarget && event.relatedTarget.closest('tr');
            if (!row) return;
            const d = row.dataset;
            document.getElementById('vwMission').textContent = d.missionLabel;
            document.getElementById('vwLocation').textContent = d.location;
            document.getElementById('vwVehicle').textContent = d.plate;
            document.getElementById('vwDriver').textContent = d.driver;
            document.getElementById('vwTimeOut').textContent = d.timeOutLabel || '—';
            document.getElementById('vwTimeIn').textContent = d.timeInLabel || '—';
            document.getElementById('vwReturn').textContent = d.returnStatus || '—';
        });
    </script>
@endsection
