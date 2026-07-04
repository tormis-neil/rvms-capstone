@extends('layouts.app')

@section('title', 'Drivers')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="fw-bold mb-0" style="color: var(--primary);">Drivers</h3>
        <button class="btn btn-navy" data-bs-toggle="modal" data-bs-target="#addDriverModal">
            <i class="bi bi-plus-lg me-1"></i> Add Driver
        </button>
    </div>

    {{-- Access requests (pending self-registrations, FR-03) --}}
    @if ($pending->isNotEmpty())
        <div class="card card-stat mb-4 border-start" style="border-left: 4px solid var(--accent) !important;">
            <div class="card-body">
                <h6 class="fw-bold mb-3"><i class="bi bi-person-plus me-2"></i>Access Requests ({{ $pending->count() }})</h6>
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead class="table-light">
                            <tr><th>Name</th><th>Email</th><th>License No.</th><th class="text-end">Action</th></tr>
                        </thead>
                        <tbody>
                            @foreach ($pending as $driver)
                                <tr>
                                    <td class="fw-semibold">{{ $driver->name }}</td>
                                    <td>{{ $driver->email }}</td>
                                    <td>{{ $driver->license_number ?? '—' }}</td>
                                    <td class="text-end">
                                        <form method="POST" action="{{ route('drivers.approve', $driver) }}" class="d-inline">
                                            @csrf @method('PATCH')
                                            <button class="btn btn-sm btn-success">Approve</button>
                                        </form>
                                        <form method="POST" action="{{ route('drivers.reject', $driver) }}" class="d-inline">
                                            @csrf @method('PATCH')
                                            <button class="btn btn-sm btn-outline-danger">Reject</button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif

    <div class="card card-stat">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>License No.</th>
                        <th>License Expiry</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($drivers as $driver)
                        <tr>
                            <td class="fw-semibold">{{ $driver->name }}</td>
                            <td>{{ $driver->email }}</td>
                            <td>{{ $driver->license_number ?? '—' }}</td>
                            <td>{{ $driver->license_expiry_date?->format('M j, Y') ?? '—' }}</td>
                            <td>
                                <span class="badge {{ $driver->status === 'active' ? 'badge-reviewed' : 'badge-not-operational' }}">
                                    {{ ucfirst($driver->status) }}
                                </span>
                            </td>
                            <td class="text-end">
                                <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#editDriver{{ $driver->id }}">Edit</button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-secondary py-4">No drivers yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">{{ $drivers->links() }}</div>

    {{-- Add Driver modal --}}
    <div class="modal fade" id="addDriverModal" tabindex="-1">
        <div class="modal-dialog">
            <form method="POST" action="{{ route('drivers.store') }}" class="modal-content">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Add Driver</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    @include('partials.driver-fields', ['driver' => null, 'requirePassword' => true])
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-navy">Save Driver</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Per-row Edit modals --}}
    @foreach ($drivers as $driver)
        <div class="modal fade" id="editDriver{{ $driver->id }}" tabindex="-1">
            <div class="modal-dialog">
                <form method="POST" action="{{ route('drivers.update', $driver) }}" class="modal-content">
                    @csrf @method('PUT')
                    <div class="modal-header">
                        <h5 class="modal-title">Edit {{ $driver->name }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        @include('partials.driver-fields', ['driver' => $driver, 'requirePassword' => false])
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-navy">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    @endforeach
@endsection
