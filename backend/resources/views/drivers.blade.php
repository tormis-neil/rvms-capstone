@extends('layouts.app')

@section('title', 'Drivers')

@php
    $licenseBadge = [
        'Valid' => 'bg-success bg-opacity-10 text-success',
        'Expiring Soon' => 'bg-warning bg-opacity-10 text-warning',
        'Expired' => 'bg-danger bg-opacity-10 text-danger',
    ];
@endphp

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold mb-0" style="color: var(--primary);">Driver Management</h3>
            <p class="text-secondary mb-0">Manage authorized drivers and monitor license expiry</p>
        </div>
        <button class="btn btn-navy text-white fw-medium px-4 py-2 rounded-3" data-bs-toggle="modal" data-bs-target="#addDriverModal">
            <i class="bi bi-plus-lg me-2"></i>Add Driver
        </button>
    </div>

    <!-- License Status Summary (FR-08) -->
    <div class="row row-cols-1 row-cols-md-3 g-4 mb-4">
        <div class="col">
            <div class="card card-stat h-100 p-3" style="border-left: 4px solid var(--status-operational);">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <p class="text-secondary small fw-semibold mb-1">VALID LICENSES</p>
                        <h2 class="fw-bold mb-0">{{ $validCount }}</h2>
                    </div>
                    <div class="bg-success bg-opacity-10 text-success rounded p-2">
                        <i class="bi bi-patch-check fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card card-stat h-100 p-3" style="border-left: 4px solid var(--status-under-pm);">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <p class="text-secondary small fw-semibold mb-1">EXPIRING SOON</p>
                        <h2 class="fw-bold mb-0">{{ $expiringSoonCount }}</h2>
                    </div>
                    <div class="bg-warning bg-opacity-10 text-warning rounded p-2">
                        <i class="bi bi-hourglass-split fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card card-stat h-100 p-3" style="border-left: 4px solid var(--status-not-operational);">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <p class="text-secondary small fw-semibold mb-1">EXPIRED</p>
                        <h2 class="fw-bold mb-0">{{ $expiredCount }}</h2>
                    </div>
                    <div class="bg-danger bg-opacity-10 text-danger rounded p-2">
                        <i class="bi bi-x-octagon fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Access requests (pending self-registrations, FR-03) — not in the
         prototype (approval flow was added to scope later); styled to match. --}}
    @if ($pending->isNotEmpty())
        <div class="d-flex align-items-center gap-2 mb-3">
            <h5 class="fw-bold mb-0"><i class="bi bi-person-plus me-2"></i>Access Requests</h5>
            <span class="badge badge-pending rounded-pill px-3 py-2">{{ $pending->count() }} Pending Approval</span>
        </div>
        <div class="card border-0 shadow-sm rounded-3 overflow-hidden mb-4">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="py-3 text-secondary fw-semibold small">DRIVER NAME</th>
                            <th class="py-3 text-secondary fw-semibold small">LICENSE NO.</th>
                            <th class="py-3 text-secondary fw-semibold small">REQUESTED</th>
                            <th class="py-3 text-secondary fw-semibold small text-end">ACTIONS</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($pending as $driver)
                            <tr>
                                <td>
                                    <div class="fw-bold">{{ $driver->name }}</div>
                                    <div class="small text-secondary">{{ $driver->email }}</div>
                                </td>
                                <td class="font-monospace text-secondary">{{ $driver->license_number ?? '—' }}</td>
                                <td>{{ $driver->created_at->format('M j, Y') }}</td>
                                <td class="text-end">
                                    <form method="POST" action="{{ route('drivers.approve', $driver) }}" class="d-inline">
                                        @csrf @method('PATCH')
                                        <button class="btn btn-sm btn-navy text-white fw-medium">Approve</button>
                                    </form>
                                    <form method="POST" action="{{ route('drivers.reject', $driver) }}" class="d-inline">
                                        @csrf @method('PATCH')
                                        <button class="btn btn-sm btn-light border text-danger">Reject</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <!-- Filters -->
    <form method="GET" action="{{ route('drivers.index') }}" class="card border-0 shadow-sm rounded-3 mb-4">
        <div class="card-body p-3">
            <div class="row g-3">
                <div class="col-md-5">
                    <input type="text" name="q" value="{{ request('q') }}" class="form-control" placeholder="Search driver name or license no...">
                </div>
                <div class="col-md-4">
                    <select name="license_status" class="form-select">
                        <option value="">License Status (All)</option>
                        @foreach (['Valid', 'Expiring Soon', 'Expired'] as $status)
                            <option value="{{ $status }}" @selected(request('license_status') === $status)>{{ $status }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-outline-secondary w-100">Filter</button>
                    @if (request()->hasAny(['q', 'license_status']))
                        <a href="{{ route('drivers.index') }}" class="btn btn-light border" title="Clear filters"><i class="bi bi-x-lg"></i></a>
                    @endif
                </div>
            </div>
        </div>
    </form>

    <!-- Driver Table -->
    <div class="card border-0 shadow-sm rounded-3 overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="py-3 text-secondary fw-semibold small">DRIVER NAME</th>
                        <th class="py-3 text-secondary fw-semibold small">LICENSE NO.</th>
                        <th class="py-3 text-secondary fw-semibold small">EXPIRY DATE</th>
                        <th class="py-3 text-secondary fw-semibold small">LICENSE STATUS</th>
                        <th class="py-3 text-secondary fw-semibold small">ASSIGNED VEHICLE</th>
                        <th class="py-3 text-secondary fw-semibold small text-end">ACTIONS</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($drivers as $driver)
                        @php($license = $driver->licenseStatus($warningDays))
                        <tr>
                            <td>
                                <div class="fw-bold">{{ $driver->name }}</div>
                                <div class="small text-secondary">{{ $driver->email }}</div>
                            </td>
                            <td class="font-monospace text-secondary">{{ $driver->license_number ?? '—' }}</td>
                            <td @class([
                                'text-warning fw-bold' => $license === 'Expiring Soon',
                                'text-danger fw-bold' => $license === 'Expired',
                            ])>{{ $driver->license_expiry_date?->format('M j, Y') ?? '—' }}</td>
                            <td>
                                @if ($license)
                                    <span class="badge {{ $licenseBadge[$license] }} px-3 py-2 rounded-pill">{{ $license }}</span>
                                @else
                                    <span class="badge bg-light text-secondary border px-3 py-2 rounded-pill">No License</span>
                                @endif
                            </td>
                            <td>
                                {{ $driver->assignedVehicle ? $driver->assignedVehicle->plate_number.' ('.$driver->assignedVehicle->type.')' : 'Unassigned' }}
                            </td>
                            <td class="text-end">
                                <button class="btn btn-sm btn-light border" title="View Details" data-bs-toggle="modal" data-bs-target="#viewDriver{{ $driver->id }}"><i class="bi bi-eye"></i></button>
                                <button class="btn btn-sm btn-light border" title="Edit" data-bs-toggle="modal" data-bs-target="#editDriver{{ $driver->id }}"><i class="bi bi-pencil"></i></button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-secondary py-4">No drivers found. Click "Add Driver" to register one.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @include('partials.table-footer', ['paginator' => $drivers, 'label' => 'drivers'])
    </div>

    <!-- Add Driver Modal -->
    <div class="modal fade" id="addDriverModal" tabindex="-1">
        <div class="modal-dialog">
            <form method="POST" action="{{ route('drivers.store') }}" class="modal-content">
                @csrf
                <div class="modal-header bg-navy text-white">
                    <h5 class="modal-title fw-bold">Register New Driver</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    @include('partials.driver-fields', ['driver' => null, 'vehicles' => $vehicles, 'requirePassword' => true])
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-navy text-white">Register Driver</button>
                </div>
            </form>
        </div>
    </div>

    @foreach ($drivers as $driver)
        @php($license = $driver->licenseStatus($warningDays))

        <!-- View Driver Modal -->
        <div class="modal fade" id="viewDriver{{ $driver->id }}" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header bg-light">
                        <h5 class="modal-title fw-bold">Driver Information</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="text-center mb-4">
                            <div class="bg-light rounded-circle d-inline-flex p-4 mb-2">
                                <i class="bi bi-person-badge fs-1 text-secondary"></i>
                            </div>
                            <h4 class="fw-bold mb-1">{{ $driver->name }}</h4>
                            @if ($license)
                                <span class="badge {{ $licenseBadge[$license] }} px-3 py-1 rounded-pill">{{ $license }}</span>
                            @endif
                        </div>

                        <ul class="list-group list-group-flush">
                            <li class="list-group-item d-flex justify-content-between align-items-center py-3">
                                <span class="text-secondary small fw-semibold">Email</span>
                                <span class="fw-medium">{{ $driver->email }}</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center py-3">
                                <span class="text-secondary small fw-semibold">License No.</span>
                                <span class="fw-medium font-monospace">{{ $driver->license_number ?? '—' }}</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center py-3">
                                <span class="text-secondary small fw-semibold">License Expiry</span>
                                <span class="fw-medium">{{ $driver->license_expiry_date?->format('M j, Y') ?? '—' }}</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center py-3">
                                <span class="text-secondary small fw-semibold">Assigned Vehicle</span>
                                <span class="fw-medium">{{ $driver->assignedVehicle ? $driver->assignedVehicle->plate_number.' ('.$driver->assignedVehicle->type.')' : 'Unassigned' }}</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center py-3">
                                <span class="text-secondary small fw-semibold">Account Status</span>
                                <span class="fw-medium">{{ ucfirst($driver->status) }}</span>
                            </li>
                        </ul>
                    </div>
                    <div class="modal-footer border-0">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Edit Driver Modal -->
        <div class="modal fade" id="editDriver{{ $driver->id }}" tabindex="-1">
            <div class="modal-dialog">
                <form method="POST" action="{{ route('drivers.update', $driver) }}" class="modal-content">
                    @csrf @method('PUT')
                    <div class="modal-header bg-navy text-white">
                        <h5 class="modal-title fw-bold">Edit Driver Details</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body p-4">
                        @include('partials.driver-fields', ['driver' => $driver, 'vehicles' => $vehicles, 'requirePassword' => false])
                    </div>
                    <div class="modal-footer border-0">
                        <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-navy text-white">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    @endforeach
@endsection
