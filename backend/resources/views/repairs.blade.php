@extends('layouts.app')

@section('title', 'RVMS - Repair Logs')

@section('content')
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h3 class="fw-bold mb-0" style="color: var(--primary);">Repair Logging</h3>
                        <p class="text-secondary mb-0">Document repair activities and maintain vehicle repair history</p>
                    </div>
                    <button class="btn btn-navy text-white fw-medium px-4 py-2 bg-navy rounded-3" data-bs-toggle="modal" data-bs-target="#logRepairModal">
                        <i class="bi bi-plus-lg me-2"></i>Log Repair
                    </button>
                </div>

                @if (session('status'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        {{ session('status') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                <!-- Status note (Plan §6.6: status is updated separately) -->
                <div class="alert alert-light border shadow-sm d-flex align-items-center mb-4" role="alert">
                    <i class="bi bi-info-circle text-primary fs-5 me-3"></i>
                    <div class="small text-secondary">
                        Repair logs are saved permanently to the vehicle's maintenance history.
                        The vehicle's operational status is updated <strong>separately</strong> after repair &mdash; use the <strong>Update Vehicle Status</strong> action on each log entry.
                    </div>
                </div>

                <!-- Filters -->
                <form method="GET" action="{{ route('repairs') }}">
                <div class="card border-0 shadow-sm rounded-3 mb-4">
                    <div class="card-body p-3">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <select class="form-select" name="vehicle_id">
                                    <option value="">All Vehicles</option>
                                    @foreach ($vehicles as $v)
                                    <option value="{{ $v->id }}" @selected(request('vehicle_id') == $v->id)>{{ $v->plate_number }} ({{ $v->type }})</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <select class="form-select" name="source">
                                    <option value="">All Repair Sources</option>
                                    @foreach (\App\Models\RepairLog::SOURCES as $source)
                                    <option value="{{ $source }}" @selected(request('source') === $source)>{{ $source }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <button type="submit" class="btn btn-outline-secondary w-100">Filter</button>
                            </div>
                        </div>
                    </div>
                </div>
                </form>

                <!-- Repair Log Table -->
                <div class="card border-0 shadow-sm rounded-3 overflow-hidden">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0 repairs-table">
                            <thead class="table-light">
                                <tr>
                                    <th class="py-3 text-secondary fw-semibold small">DATE</th>
                                    <th class="py-3 text-secondary fw-semibold small">VEHICLE & DRIVER</th>
                                    <th class="py-3 text-secondary fw-semibold small w-25">SCOPE OF WORK</th>
                                    <th class="py-3 text-secondary fw-semibold small">PARTS REPLACED</th>
                                    <th class="py-3 text-secondary fw-semibold small">COST</th>
                                    <th class="py-3 text-secondary fw-semibold small">REPAIR SOURCE</th>
                                    <th class="py-3 text-secondary fw-semibold small">REMARKS</th>
                                    <th class="py-3 text-secondary fw-semibold small">VEHICLE STATUS</th>
                                    <th class="py-3 text-secondary fw-semibold small text-end">ACTIONS</th>
                                </tr>
                            </thead>
                            <tbody id="rows-repairs">
                                @forelse ($repairs as $repair)
                                <tr
                                    data-id="{{ $repair->id }}"
                                    data-vehicle-id="{{ $repair->vehicle_id }}"
                                    data-plate="{{ $repair->vehicle->plate_number ?? '—' }}"
                                    data-type="{{ $repair->vehicle->type ?? '' }}"
                                    data-driver="{{ $repair->driver->name ?? 'Unassigned' }}"
                                    data-date="{{ $repair->repair_date?->toDateString() }}"
                                    data-scope="{{ $repair->scope_of_work }}"
                                    data-parts="{{ $repair->parts_replaced }}"
                                    data-cost="{{ $repair->cost }}"
                                    data-source="{{ $repair->repair_source }}"
                                    data-shop="{{ $repair->external_shop_name }}"
                                    data-remarks="{{ $repair->remarks }}"
                                    data-vehicle-status="{{ $repair->vehicle->status ?? '' }}">
                                    <td class="fw-medium">{{ $repair->dateLabel() }}</td>
                                    <td>
                                        <div class="fw-bold">{{ $repair->vehicle->plate_number ?? '—' }}</div>
                                        <div class="small text-secondary">{{ $repair->driver->name ?? 'Unassigned' }}</div>
                                    </td>
                                    <td>{{ $repair->scope_of_work }}</td>
                                    <td>
                                        @if ($repair->parts_replaced)
                                        {{ $repair->parts_replaced }}
                                        @else
                                        <em class="text-secondary small">None</em>
                                        @endif
                                    </td>
                                    <td>{{ $repair->costLabel() }}</td>
                                    <td><span class="badge bg-light text-dark border">{{ $repair->sourceLabel() }}</span></td>
                                    <td class="small text-secondary">
                                        @if ($repair->remarks)
                                        {{ $repair->remarks }}
                                        @else
                                        <em>—</em>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <div class="d-flex align-items-center justify-content-end gap-2">
                                            @if ($repair->vehicle)
                                            <span class="badge {{ $repair->vehicle->badgeClass() }} px-3 py-2 rounded-pill">{{ $repair->vehicle->status }}</span>
                                            <button class="btn btn-sm btn-light border js-edit" title="Edit Repair Log" data-bs-toggle="modal" data-bs-target="#editRepairModal"><i class="bi bi-pencil"></i></button>
                                            <button class="btn btn-sm btn-light border js-status" title="Update Vehicle Status" data-bs-toggle="modal" data-bs-target="#updateStatusModal"><i class="bi bi-arrow-repeat"></i></button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="9" class="text-center text-secondary py-4">No repair logs recorded yet.</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @include('partials.table-footer', ['paginator' => $repairs, 'label' => 'repair logs'])
                </div>
@endsection

@section('modals')
    <!-- Log Repair Modal -->
    <div class="modal fade" id="logRepairModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-navy text-white">
                    <h5 class="modal-title fw-bold">Log Repair Activity</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="{{ route('repairs.store') }}">
                @csrf
                <div class="modal-body p-4">
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Vehicle</label>
                                <select class="form-select js-vehicle" name="vehicle_id" required>
                                    @foreach ($vehicles as $v)
                                    <option value="{{ $v->id }}" data-driver="{{ $v->assignedDriver->name ?? 'Unassigned' }}">{{ $v->plate_number }} ({{ $v->type }})</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Assigned Driver</label>
                                <input type="text" class="form-control bg-light js-driver" value="" readonly>
                                <div class="form-text">Auto-filled from the vehicle record.</div>
                            </div>
                        </div>
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Date</label>
                                <input type="date" class="form-control" name="repair_date" value="{{ now()->toDateString() }}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Repair Source</label>
                                <select class="form-select js-source" name="repair_source">
                                    @foreach (\App\Models\RepairLog::SOURCES as $source)
                                    <option value="{{ $source }}">{{ $source }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="mb-3 js-shop-wrap" style="display:none;">
                            <label class="form-label fw-semibold">External Shop Name</label>
                            <input type="text" class="form-control js-shop" name="external_shop_name" placeholder="Name of the external repair shop">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Scope of Work</label>
                            <textarea class="form-control" name="scope_of_work" rows="2" placeholder="Describe the repair work performed..." required></textarea>
                        </div>
                        <div class="row g-3 mb-3">
                            <div class="col-md-8">
                                <label class="form-label fw-semibold">Parts Replaced</label>
                                <input type="text" class="form-control" name="parts_replaced" placeholder="e.g. Brake pads (front set)">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Cost (Optional)</label>
                                <input type="number" step="0.01" min="0" class="form-control" name="cost" placeholder="₱ 0.00">
                            </div>
                        </div>
                        <div class="mb-2">
                            <label class="form-label fw-semibold">Remarks</label>
                            <textarea class="form-control" name="remarks" rows="2" placeholder="Additional notes..."></textarea>
                        </div>
                        <div class="form-text">
                            <i class="bi bi-info-circle me-1"></i>Saving this log does not change the vehicle's status.
                            After saving, use <strong>Update Vehicle Status</strong> in the log table (or Vehicle Management) as a separate step.
                        </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-navy bg-navy text-white">Save Repair Log</button>
                </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Repair Modal -->
    <div class="modal fade" id="editRepairModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-navy text-white">
                    <h5 class="modal-title fw-bold">Edit Repair Log</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="editRepairForm" action="#">
                @csrf
                @method('PUT')
                <div class="modal-body p-4">
                        <input type="hidden" name="vehicle_id" id="erVehicleId">
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Vehicle</label>
                                <input type="text" class="form-control bg-light" id="erVehicle" readonly>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Assigned Driver</label>
                                <input type="text" class="form-control bg-light" id="erDriver" readonly>
                            </div>
                        </div>
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Date</label>
                                <input type="date" class="form-control" name="repair_date" id="erDate" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Repair Source</label>
                                <select class="form-select js-source" name="repair_source" id="erSource">
                                    @foreach (\App\Models\RepairLog::SOURCES as $source)
                                    <option value="{{ $source }}">{{ $source }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="mb-3 js-shop-wrap" id="erShopWrap" style="display:none;">
                            <label class="form-label fw-semibold">External Shop Name</label>
                            <input type="text" class="form-control js-shop" name="external_shop_name" id="erShop" placeholder="Name of the external repair shop">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Scope of Work</label>
                            <textarea class="form-control" name="scope_of_work" rows="2" id="erScope" required></textarea>
                        </div>
                        <div class="row g-3 mb-3">
                            <div class="col-md-8">
                                <label class="form-label fw-semibold">Parts Replaced</label>
                                <input type="text" class="form-control" name="parts_replaced" id="erParts">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Cost (Optional)</label>
                                <input type="number" step="0.01" min="0" class="form-control" name="cost" id="erCost">
                            </div>
                        </div>
                        <div class="mb-2">
                            <label class="form-label fw-semibold">Remarks</label>
                            <textarea class="form-control" name="remarks" rows="2" id="erRemarks"></textarea>
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

    <!-- Update Vehicle Status Modal (reuses the Vehicles module route, FR-18) -->
    <div class="modal fade" id="updateStatusModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-navy text-white">
                    <h5 class="modal-title fw-bold">Update Vehicle Status</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="updateStatusForm" action="#">
                @csrf
                @method('PATCH')
                <div class="modal-body p-4">
                    <div class="d-flex justify-content-between align-items-center bg-light rounded-3 p-3 mb-4">
                        <div>
                            <div class="fw-bold" id="usVehicle">—</div>
                            <div class="small text-secondary" id="usMeta">—</div>
                        </div>
                        <span class="badge px-3 py-2 rounded-pill" id="usBadge">—</span>
                    </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">New Operational Status</label>
                            {{-- Only the three manual statuses; Dispatched is set by the Dispatch module (FR-18). --}}
                            <select class="form-select" name="status">
                                <option value="Operational">Operational (repair completed)</option>
                                <option value="Not Operational">Not Operational (repair still ongoing)</option>
                                <option value="Under Preventive Maintenance">Under Preventive Maintenance (follow-up maintenance needed)</option>
                            </select>
                            <div class="form-text">Dispatched status is set automatically by the Dispatch module and cannot be assigned here.</div>
                        </div>
                        <div class="mb-2">
                            <label class="form-label fw-semibold">Remarks (Optional)</label>
                            <textarea class="form-control" name="remarks" rows="2" placeholder="Reason for the status change..."></textarea>
                        </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn bg-navy text-white">Update Status</button>
                </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        const editRepairTemplate = @json(route('repairs.update', ['repair' => '__ID__']));
        const statusTemplate = @json(route('vehicles.status', ['vehicle' => '__ID__']));

        // Toggle the External Shop Name field within a given modal root.
        function bindShopToggle(root) {
            const source = root.querySelector('.js-source');
            const wrap = root.querySelector('.js-shop-wrap');
            if (!source || !wrap) return;
            const sync = () => { wrap.style.display = source.value === 'External Repair Shop' ? '' : 'none'; };
            source.addEventListener('change', sync);
            sync();
        }

        // Log Repair modal: auto-fill driver from the selected vehicle + shop toggle.
        const logModal = document.getElementById('logRepairModal');
        const logVehicle = logModal.querySelector('.js-vehicle');
        const logDriver = logModal.querySelector('.js-driver');
        function syncLogDriver() {
            const opt = logVehicle.options[logVehicle.selectedIndex];
            logDriver.value = opt ? (opt.dataset.driver || 'Unassigned') : '';
        }
        logVehicle.addEventListener('change', syncLogDriver);
        logModal.addEventListener('show.bs.modal', () => { syncLogDriver(); });
        bindShopToggle(logModal);

        // Edit Repair modal — populated from the clicked row.
        const editModal = document.getElementById('editRepairModal');
        bindShopToggle(editModal);
        editModal.addEventListener('show.bs.modal', event => {
            const row = event.relatedTarget && event.relatedTarget.closest('tr');
            if (!row) return;
            const d = row.dataset;
            document.getElementById('editRepairForm').action = editRepairTemplate.replace('__ID__', d.id);
            document.getElementById('erVehicleId').value = d.vehicleId;
            document.getElementById('erVehicle').value = d.plate + (d.type ? ' (' + d.type + ')' : '');
            document.getElementById('erDriver').value = d.driver;
            document.getElementById('erDate').value = d.date || '';
            document.getElementById('erSource').value = d.source;
            document.getElementById('erScope').value = d.scope || '';
            document.getElementById('erParts').value = d.parts || '';
            document.getElementById('erCost').value = d.cost || '';
            document.getElementById('erShop').value = d.shop || '';
            document.getElementById('erRemarks').value = d.remarks || '';
            editModal.querySelector('.js-shop-wrap').style.display = d.source === 'External Repair Shop' ? '' : 'none';
        });

        // Update Vehicle Status modal — reuses the Vehicles module route.
        const statusModal = document.getElementById('updateStatusModal');
        statusModal.addEventListener('show.bs.modal', event => {
            const row = event.relatedTarget && event.relatedTarget.closest('tr');
            if (!row) return;
            const d = row.dataset;
            document.getElementById('updateStatusForm').action = statusTemplate.replace('__ID__', d.vehicleId);
            document.getElementById('usVehicle').textContent = d.plate + (d.type ? ' (' + d.type + ')' : '');
            document.getElementById('usMeta').textContent = d.driver;
            const badge = document.getElementById('usBadge');
            badge.textContent = d.vehicleStatus;
            document.querySelector('#updateStatusForm select[name=status]').value = d.vehicleStatus || 'Operational';
        });
    </script>
@endsection
