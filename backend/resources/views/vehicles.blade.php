@extends('layouts.app')

@section('title', 'Vehicles')

@php
    use App\Models\Vehicle;
    $badge = [
        Vehicle::STATUS_OPERATIONAL => 'badge-operational',
        Vehicle::STATUS_DISPATCHED => 'badge-dispatched',
        Vehicle::STATUS_NOT_OPERATIONAL => 'badge-not-operational',
        Vehicle::STATUS_UNDER_PM => 'badge-pm',
    ];
    // Manual page sets 3 statuses; Dispatched is written by the Dispatch module (FR-15/FR-18).
    $manualStatuses = [Vehicle::STATUS_OPERATIONAL, Vehicle::STATUS_NOT_OPERATIONAL, Vehicle::STATUS_UNDER_PM];
@endphp

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold mb-0" style="color: var(--primary);">Vehicle Management</h3>
            <p class="text-secondary mb-0">Manage agency rescue vehicles</p>
        </div>
        <button class="btn btn-navy text-white fw-medium px-4 py-2 rounded-3" data-bs-toggle="modal" data-bs-target="#addVehicleModal">
            <i class="bi bi-plus-lg me-2"></i>Add Vehicle
        </button>
    </div>

    <!-- Filters -->
    <form method="GET" action="{{ route('vehicles.index') }}" class="card border-0 shadow-sm rounded-3 mb-4">
        <div class="card-body p-3">
            <div class="row g-3">
                <div class="col-md-4">
                    <input type="text" name="q" value="{{ request('q') }}" class="form-control" placeholder="Search plate no, make, model...">
                </div>
                <div class="col-md-3">
                    <select name="type" class="form-select">
                        <option value="">All Types</option>
                        @foreach ($types as $type)
                            <option value="{{ $type }}" @selected(request('type') === $type)>{{ $type }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <select name="status" class="form-select">
                        <option value="">All Statuses</option>
                        @foreach (Vehicle::STATUSES as $status)
                            <option value="{{ $status }}" @selected(request('status') === $status)>{{ $status }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-outline-secondary w-100">Filter</button>
                    @if (request()->hasAny(['q', 'type', 'status']))
                        <a href="{{ route('vehicles.index') }}" class="btn btn-light border" title="Clear filters"><i class="bi bi-x-lg"></i></a>
                    @endif
                </div>
            </div>
        </div>
    </form>

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
                <tbody>
                    @forelse ($vehicles as $vehicle)
                        <tr>
                            <td class="fw-bold">{{ $vehicle->plate_number }}</td>
                            <td>
                                <div class="fw-semibold">{{ $vehicle->type }}</div>
                                <div class="small text-secondary">{{ $vehicle->make }} {{ $vehicle->model }}</div>
                            </td>
                            <td>{{ $vehicle->assignedDriver?->name ?? 'Unassigned' }}</td>
                            <td>{{ number_format($vehicle->current_mileage) }} km</td>
                            <td>
                                <span class="badge status-badge {{ $badge[$vehicle->status] }} px-3 py-2 rounded-pill">{{ $vehicle->status }}</span>
                            </td>
                            <td class="text-end">
                                <button class="btn btn-sm btn-light border" title="View Details" data-bs-toggle="modal" data-bs-target="#viewVehicle{{ $vehicle->id }}"><i class="bi bi-eye"></i></button>
                                <button class="btn btn-sm btn-light border" title="Edit" data-bs-toggle="modal" data-bs-target="#editVehicle{{ $vehicle->id }}"><i class="bi bi-pencil"></i></button>
                                <button class="btn btn-sm btn-light border" title="Update Status" data-bs-toggle="modal" data-bs-target="#statusVehicle{{ $vehicle->id }}"><i class="bi bi-arrow-repeat"></i></button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-secondary py-4">No vehicles found. Click "Add Vehicle" to register one.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @include('partials.table-footer', ['paginator' => $vehicles, 'label' => 'vehicles'])
    </div>

    <!-- Add Vehicle Modal -->
    <div class="modal fade" id="addVehicleModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <form method="POST" action="{{ route('vehicles.store') }}" class="modal-content">
                @csrf
                <div class="modal-header bg-navy text-white">
                    <h5 class="modal-title fw-bold">Register New Vehicle</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    @include('partials.vehicle-fields', ['vehicle' => null, 'drivers' => $drivers, 'types' => $types])
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-navy text-white">Register Vehicle</button>
                </div>
            </form>
        </div>
    </div>

    @foreach ($vehicles as $vehicle)
        <!-- View Vehicle Modal -->
        <div class="modal fade" id="viewVehicle{{ $vehicle->id }}" tabindex="-1">
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
                            <h4 class="fw-bold mb-1">{{ $vehicle->plate_number }}</h4>
                            <span class="badge status-badge {{ $badge[$vehicle->status] }} px-3 py-1 rounded-pill">{{ $vehicle->status }}</span>
                        </div>

                        <ul class="list-group list-group-flush">
                            <li class="list-group-item d-flex justify-content-between align-items-center py-3">
                                <span class="text-secondary small fw-semibold">Vehicle Type</span>
                                <span class="fw-medium">{{ $vehicle->type }}</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center py-3">
                                <span class="text-secondary small fw-semibold">Make &amp; Model</span>
                                <span class="fw-medium">{{ $vehicle->make }} {{ $vehicle->model }}</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center py-3">
                                <span class="text-secondary small fw-semibold">Assigned Driver</span>
                                <span class="fw-medium">{{ $vehicle->assignedDriver?->name ?? 'Unassigned' }}</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center py-3">
                                <span class="text-secondary small fw-semibold">Current Mileage</span>
                                <span class="fw-medium">{{ number_format($vehicle->current_mileage) }} km</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center py-3">
                                <span class="text-secondary small fw-semibold">Engine No.</span>
                                <span class="fw-medium text-uppercase">{{ $vehicle->engine_number ?? '—' }}</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center py-3">
                                <span class="text-secondary small fw-semibold">Chassis No.</span>
                                <span class="fw-medium text-uppercase">{{ $vehicle->chassis_number ?? '—' }}</span>
                            </li>
                        </ul>
                    </div>
                    <div class="modal-footer border-0">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Edit Vehicle Modal -->
        <div class="modal fade" id="editVehicle{{ $vehicle->id }}" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <form method="POST" action="{{ route('vehicles.update', $vehicle) }}" class="modal-content">
                    @csrf @method('PUT')
                    <div class="modal-header bg-navy text-white">
                        <h5 class="modal-title fw-bold">Edit Vehicle Details</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body p-4">
                        @include('partials.vehicle-fields', ['vehicle' => $vehicle, 'drivers' => $drivers, 'types' => $types])
                    </div>
                    <div class="modal-footer border-0">
                        <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-navy text-white">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Update Vehicle Status Modal -->
        <div class="modal fade" id="statusVehicle{{ $vehicle->id }}" tabindex="-1">
            <div class="modal-dialog">
                <form method="POST" action="{{ route('vehicles.status', $vehicle) }}" class="modal-content">
                    @csrf @method('PATCH')
                    <div class="modal-header bg-navy text-white">
                        <h5 class="modal-title fw-bold">Update Vehicle Status</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="d-flex justify-content-between align-items-center bg-light rounded-3 p-3 mb-4">
                            <div>
                                <div class="fw-bold">{{ $vehicle->plate_number }} ({{ $vehicle->type }})</div>
                                <div class="small text-secondary">{{ $vehicle->assignedDriver?->name ?? 'Unassigned' }}</div>
                            </div>
                            <span class="badge status-badge {{ $badge[$vehicle->status] }} px-3 py-2 rounded-pill">{{ $vehicle->status }}</span>
                        </div>
                        <div class="mb-2">
                            <label class="form-label fw-semibold">New Operational Status</label>
                            <select name="status" class="form-select">
                                @foreach ($manualStatuses as $status)
                                    <option value="{{ $status }}" @selected($vehicle->status === $status)>{{ $status }}</option>
                                @endforeach
                            </select>
                            <div class="form-text">Dispatched status is set automatically by the Dispatch module and cannot be assigned here.</div>
                        </div>
                    </div>
                    <div class="modal-footer border-0">
                        <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-navy text-white">Update Status</button>
                    </div>
                </form>
            </div>
        </div>
    @endforeach
@endsection
