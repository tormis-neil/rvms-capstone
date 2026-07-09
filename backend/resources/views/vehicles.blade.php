@extends('layouts.app')

@section('title', 'RVMS - Vehicles')

@section('content')
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h3 class="fw-bold mb-0" style="color: var(--primary);">Vehicle Management</h3>
                        <p class="text-secondary mb-0">Manage agency rescue vehicles</p>
                    </div>
                    <button class="btn btn-navy text-white fw-medium px-4 py-2 bg-navy rounded-3" data-bs-toggle="modal" data-bs-target="#addVehicleModal">
                        <i class="bi bi-plus-lg me-2"></i>Add Vehicle
                    </button>
                </div>

                {{-- Success/error feedback banners — documented addition (the prototype has no alert state) --}}
                @if (session('status'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        {{ session('status') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif
                @if ($errors->any())
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <ul class="mb-0 small">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                <!-- Filters (live server-side search/type/status) -->
                <div class="card border-0 shadow-sm rounded-3 mb-4">
                    <div class="card-body p-3">
                        <form method="GET" action="{{ route('vehicles') }}">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="Search plate no, make, model...">
                            </div>
                            <div class="col-md-3">
                                <select class="form-select" name="type">
                                    <option value="">All Types</option>
                                    {{-- Type options are live data — the prototype listed its 5 demo types --}}
                                    @foreach ($types as $type)
                                    <option value="{{ $type }}" @selected(request('type') === $type)>{{ $type }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <select class="form-select" name="status">
                                    <option value="">All Statuses</option>
                                    <option value="Operational" @selected(request('status') === 'Operational')>Operational</option>
                                    <option value="Dispatched" @selected(request('status') === 'Dispatched')>Dispatched</option>
                                    <option value="Under Preventive Maintenance" @selected(request('status') === 'Under Preventive Maintenance')>Under Preventive Maintenance</option>
                                    <option value="Not Operational" @selected(request('status') === 'Not Operational')>Not Operational</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <button class="btn btn-outline-secondary w-100">Filter</button>
                            </div>
                        </div>
                        </form>
                    </div>
                </div>

                <!-- Vehicle Table -->
                <div class="card border-0 shadow-sm rounded-3 overflow-hidden">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="py-3 text-secondary fw-semibold small">PLATE NO.</th>
                                    <th class="py-3 text-secondary fw-semibold small">VEHICLE DETAILS</th>
                                    <th class="py-3 text-secondary fw-semibold small">ASSIGNED DRIVER</th>
                                    <th class="py-3 text-secondary fw-semibold small">MILEAGE</th>
                                    <th class="py-3 text-secondary fw-semibold small">STATUS</th>
                                    <th class="py-3 text-secondary fw-semibold small text-end">ACTIONS</th>
                                </tr>
                            </thead>
                            <tbody id="rows-vehicles">
                                {{-- Live rows — same markup the prototype's demo JS painted per row --}}
                                @forelse ($vehicles as $vehicle)
                                <tr data-id="{{ $vehicle->id }}"
                                    data-plate="{{ $vehicle->plate_number }}"
                                    data-type="{{ $vehicle->type }}"
                                    data-make="{{ $vehicle->make }}"
                                    data-model="{{ $vehicle->model }}"
                                    data-makemodel="{{ $vehicle->make }} {{ $vehicle->model }}"
                                    data-driver="{{ $vehicle->assignedDriver->name ?? 'Unassigned' }}"
                                    data-driver-id="{{ $vehicle->assigned_driver_id }}"
                                    data-mileage="{{ $vehicle->mileageLabel() }}"
                                    data-status="{{ $vehicle->status }}"
                                    data-badge="{{ $vehicle->badgeClass() }}"
                                    data-engine="{{ $vehicle->engine_number }}"
                                    data-chassis="{{ $vehicle->chassis_number }}">
                                    <td class="fw-bold">{{ $vehicle->plate_number }}</td>
                                    <td>
                                        <div class="fw-semibold">{{ $vehicle->type }}</div>
                                        <div class="small text-secondary">{{ $vehicle->make }} {{ $vehicle->model }}</div>
                                    </td>
                                    <td>{{ $vehicle->assignedDriver->name ?? 'Unassigned' }}</td>
                                    <td>{{ $vehicle->mileageLabel() }}</td>
                                    <td>
                                        <span class="badge status-badge {{ $vehicle->badgeClass() }} px-3 py-2 rounded-pill">{{ $vehicle->status }}</span>
                                    </td>
                                    <td class="text-end">
                                        <button class="btn btn-sm btn-light border" title="View Details" data-bs-toggle="modal" data-bs-target="#viewVehicleModal"><i class="bi bi-eye"></i></button>
                                        <button class="btn btn-sm btn-light border" title="Edit" data-bs-toggle="modal" data-bs-target="#editVehicleModal"><i class="bi bi-pencil"></i></button>
                                        <button class="btn btn-sm btn-light border" title="Update Status" data-bs-toggle="modal" data-bs-target="#updateStatusModal"><i class="bi bi-arrow-repeat"></i></button>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="6" class="text-center text-secondary py-4">No vehicles found.</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @include('partials.table-footer', ['paginator' => $vehicles, 'label' => 'vehicles'])
                </div>

@endsection

@section('modals')
    <!-- Add Vehicle Modal -->
    <div class="modal fade" id="addVehicleModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-navy text-white">
                    <h5 class="modal-title fw-bold">Register New Vehicle</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                {{-- Live form — posts to vehicles.store (FR-05) --}}
                <form method="POST" action="{{ route('vehicles.store') }}">
                @csrf
                <div class="modal-body p-4">
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Plate Number</label>
                                <input type="text" name="plate_number" value="{{ old('plate_number') }}" class="form-control" placeholder="e.g. ABC-1234" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Vehicle Type</label>
                                <select class="form-select" name="type">
                                    {{-- Prototype's 5 demo types + any type already in the agency's fleet --}}
                                    @foreach ($types as $type)
                                    <option @selected(old('type') === $type)>{{ $type }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Make/Brand</label>
                                <input type="text" name="make" value="{{ old('make') }}" class="form-control" placeholder="e.g. Isuzu" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Model</label>
                                <input type="text" name="model" value="{{ old('model') }}" class="form-control" placeholder="e.g. FTR 850" required>
                            </div>
                        </div>
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Chassis Number</label>
                                <input type="text" name="chassis_number" value="{{ old('chassis_number') }}" class="form-control" placeholder="Chassis No.">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Engine Number</label>
                                <input type="text" name="engine_number" value="{{ old('engine_number') }}" class="form-control" placeholder="Engine No.">
                            </div>
                        </div>
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Current Mileage (km)</label>
                                <input type="number" name="current_mileage" class="form-control" value="{{ old('current_mileage', 0) }}" min="0" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Assigned Driver</label>
                                <select class="form-select" name="assigned_driver_id">
                                    <option value="">Unassigned</option>
                                    {{-- Live driver options — the agency's active drivers --}}
                                    @foreach ($drivers as $driver)
                                    <option value="{{ $driver->id }}" @selected(old('assigned_driver_id') == $driver->id)>{{ $driver->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-navy bg-navy text-white">Register Vehicle</button>
                </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Vehicle Modal -->
    <div class="modal fade" id="editVehicleModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-navy text-white">
                    <h5 class="modal-title fw-bold">Edit Vehicle Details</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                {{-- Live form — action is set per-row by the page script (vehicles.update) --}}
                <form method="POST" id="editVehicleForm" action="#">
                @csrf
                @method('PUT')
                <div class="modal-body p-4">
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Plate Number</label>
                                <input type="text" name="plate_number" class="form-control" id="evPlate" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Vehicle Type</label>
                                <select class="form-select" name="type" id="evType">
                                    @foreach ($types as $type)
                                    <option>{{ $type }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Make/Brand</label>
                                <input type="text" name="make" class="form-control" id="evMake" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Model</label>
                                <input type="text" name="model" class="form-control" id="evModel" required>
                            </div>
                        </div>
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Engine Number</label>
                                <input type="text" name="engine_number" class="form-control" id="evEngine">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Chassis Number</label>
                                <input type="text" name="chassis_number" class="form-control" id="evChassis">
                            </div>
                        </div>
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Current Mileage (km)</label>
                                <input type="number" name="current_mileage" class="form-control" id="evMileage" min="0" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Assigned Driver</label>
                                <select class="form-select" name="assigned_driver_id" id="evDriver">
                                    <option value="">Unassigned</option>
                                    @foreach ($drivers as $driver)
                                    <option value="{{ $driver->id }}">{{ $driver->name }}</option>
                                    @endforeach
                                </select>
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

    <!-- View Vehicle Modal -->
    <div class="modal fade" id="viewVehicleModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-light">
                    <h5 class="modal-title fw-bold">Vehicle Information</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="text-center mb-4">
                        <div class="bg-light rounded d-inline-flex p-4 mb-2">
                            <i class="bi bi-truck fs-1 text-secondary"></i>
                        </div>
                        <h4 class="fw-bold mb-1" id="vvPlate">ABC-1234</h4>
                        <span class="badge badge-operational px-3 py-1 rounded-pill" id="vvStatus">Operational</span>
                    </div>
                    
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between align-items-center py-3">
                            <span class="text-secondary small fw-semibold">Vehicle Type</span>
                            <span class="fw-medium" id="vvType">Fire Truck</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center py-3">
                            <span class="text-secondary small fw-semibold">Make & Model</span>
                            <span class="fw-medium" id="vvMakeModel">Isuzu FTR 850</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center py-3">
                            <span class="text-secondary small fw-semibold">Assigned Driver</span>
                            <span class="fw-medium" id="vvDriver">Juan Dela Cruz</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center py-3">
                            <span class="text-secondary small fw-semibold">Current Mileage</span>
                            <span class="fw-medium" id="vvMileage">45,230 km</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center py-3">
                            <span class="text-secondary small fw-semibold">Engine No.</span>
                            <span class="fw-medium text-uppercase" id="vvEngine">4HK1-TC-587234</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center py-3">
                            <span class="text-secondary small fw-semibold">Chassis No.</span>
                            <span class="fw-medium text-uppercase" id="vvChassis">JALC4W14697100345</span>
                        </li>
                    </ul>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Update Vehicle Status Modal -->
    <div class="modal fade" id="updateStatusModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-navy text-white">
                    <h5 class="modal-title fw-bold">Update Vehicle Status</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                {{-- Live form — action is set per-row by the page script (vehicles.status, FR-18) --}}
                <form method="POST" id="updateStatusForm" action="#">
                @csrf
                @method('PATCH')
                <div class="modal-body p-4">
                    <div class="d-flex justify-content-between align-items-center bg-light rounded-3 p-3 mb-4">
                        <div>
                            <div class="fw-bold" id="usVehicle">ABC-1234 (Fire Truck)</div>
                            <div class="small text-secondary" id="usDriver">Juan Dela Cruz</div>
                        </div>
                        <span class="badge badge-operational px-3 py-2 rounded-pill" id="usStatus">Operational</span>
                    </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">New Operational Status</label>
                            <select class="form-select" name="status">
                                <option>Operational</option>
                                <option>Not Operational</option>
                                <option>Under Preventive Maintenance</option>
                            </select>
                            <div class="form-text">Dispatched status is set automatically by the Dispatch module and cannot be assigned here.</div>
                        </div>
                        {{-- The prototype's "Remarks (Optional)" textarea is omitted: the approved
                             schema deliberately excludes admin-remarks columns on vehicle status
                             changes (design decision 7 — documented omission). --}}
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
        // Populate modals from the clicked row's data attributes
        const badgeClasses = ['badge-operational', 'badge-dispatched', 'badge-pm', 'badge-not-operational'];
        // Full status label — matches the driver mobile app wording.
        const STATUS_DISPLAY = { 'Under PM': 'Under Preventive Maintenance' };
        const showStatus = s => STATUS_DISPLAY[s] || s;

        function rowData(event) {
            const row = event.relatedTarget && event.relatedTarget.closest('tr');
            return row ? row.dataset : null;
        }

        document.getElementById('viewVehicleModal').addEventListener('show.bs.modal', event => {
            const d = rowData(event);
            if (!d) return;
            document.getElementById('vvPlate').textContent = d.plate;
            document.getElementById('vvType').textContent = d.type;
            document.getElementById('vvMakeModel').textContent = d.makemodel;
            document.getElementById('vvDriver').textContent = d.driver;
            document.getElementById('vvMileage').textContent = d.mileage;
            document.getElementById('vvEngine').textContent = d.engine;
            document.getElementById('vvChassis').textContent = d.chassis;
            const badge = document.getElementById('vvStatus');
            badge.classList.remove(...badgeClasses);
            badge.classList.add('status-badge', d.badge);
            badge.textContent = showStatus(d.status);
        });

        // Live wiring: per-row form action URLs (vehicles.update / vehicles.status)
        const editActionTemplate = @json(route('vehicles.update', ['vehicle' => '__ID__']));
        const statusActionTemplate = @json(route('vehicles.status', ['vehicle' => '__ID__']));

        document.getElementById('editVehicleModal').addEventListener('show.bs.modal', event => {
            const d = rowData(event);
            if (!d) return;
            document.getElementById('editVehicleForm').action = editActionTemplate.replace('__ID__', d.id);
            document.getElementById('evPlate').value = d.plate;
            document.getElementById('evType').value = d.type;
            document.getElementById('evMake').value = d.make;
            document.getElementById('evModel').value = d.model;
            document.getElementById('evEngine').value = d.engine;
            document.getElementById('evChassis').value = d.chassis;
            document.getElementById('evMileage').value = d.mileage.replace(/[^0-9]/g, '');
            document.getElementById('evDriver').value = d.driverId || '';
        });

        document.getElementById('updateStatusModal').addEventListener('show.bs.modal', event => {
            const d = rowData(event);
            if (!d) return;
            document.getElementById('updateStatusForm').action = statusActionTemplate.replace('__ID__', d.id);
            document.getElementById('usVehicle').textContent = d.plate + ' (' + d.type + ')';
            document.getElementById('usDriver').textContent = d.driver;
            const statusSelect = document.querySelector('#updateStatusForm select[name=status]');
            if (d.status !== 'Dispatched') statusSelect.value = d.status;
            const badge = document.getElementById('usStatus');
            badge.classList.remove(...badgeClasses);
            badge.classList.add('status-badge', d.badge);
            badge.textContent = showStatus(d.status);
        });
    </script>
@endsection
