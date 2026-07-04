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
@endphp

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="fw-bold mb-0" style="color: var(--primary);">Vehicles</h3>
        <button class="btn btn-navy" data-bs-toggle="modal" data-bs-target="#addVehicleModal">
            <i class="bi bi-plus-lg me-1"></i> Add Vehicle
        </button>
    </div>

    <div class="card card-stat">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Plate No.</th>
                        <th>Type</th>
                        <th>Make / Model</th>
                        <th>Mileage (km)</th>
                        <th>Assigned Driver</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($vehicles as $vehicle)
                        <tr>
                            <td class="fw-semibold">{{ $vehicle->plate_number }}</td>
                            <td>{{ $vehicle->type }}</td>
                            <td>{{ $vehicle->make }} {{ $vehicle->model }}</td>
                            <td>{{ number_format($vehicle->current_mileage) }}</td>
                            <td>{{ $vehicle->assignedDriver?->name ?? '—' }}</td>
                            <td><span class="badge status-badge {{ $badge[$vehicle->status] }}">{{ $vehicle->status }}</span></td>
                            <td class="text-end">
                                <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#editVehicle{{ $vehicle->id }}">Edit</button>
                                <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#statusVehicle{{ $vehicle->id }}">Status</button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-secondary py-4">No vehicles yet. Click "Add Vehicle" to create one.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">{{ $vehicles->links() }}</div>

    {{-- Add Vehicle modal --}}
    <div class="modal fade" id="addVehicleModal" tabindex="-1">
        <div class="modal-dialog">
            <form method="POST" action="{{ route('vehicles.store') }}" class="modal-content">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Add Vehicle</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    @include('partials.vehicle-fields', ['vehicle' => null, 'drivers' => $drivers])
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-navy">Save Vehicle</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Per-row Edit + Status modals --}}
    @foreach ($vehicles as $vehicle)
        <div class="modal fade" id="editVehicle{{ $vehicle->id }}" tabindex="-1">
            <div class="modal-dialog">
                <form method="POST" action="{{ route('vehicles.update', $vehicle) }}" class="modal-content">
                    @csrf @method('PUT')
                    <div class="modal-header">
                        <h5 class="modal-title">Edit {{ $vehicle->plate_number }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        @include('partials.vehicle-fields', ['vehicle' => $vehicle, 'drivers' => $drivers])
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-navy">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="modal fade" id="statusVehicle{{ $vehicle->id }}" tabindex="-1">
            <div class="modal-dialog">
                <form method="POST" action="{{ route('vehicles.status', $vehicle) }}" class="modal-content">
                    @csrf @method('PATCH')
                    <div class="modal-header">
                        <h5 class="modal-title">Update Status — {{ $vehicle->plate_number }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <label class="form-label fw-semibold">Operational Status</label>
                        <select name="status" class="form-select">
                            @foreach (Vehicle::STATUSES as $status)
                                <option value="{{ $status }}" @selected($vehicle->status === $status)>{{ $status }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-navy">Update Status</button>
                    </div>
                </form>
            </div>
        </div>
    @endforeach
@endsection
