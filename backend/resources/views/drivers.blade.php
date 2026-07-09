@extends('layouts.app')

@section('title', 'RVMS - Drivers')

@section('content')
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h3 class="fw-bold mb-0" style="color: var(--primary);">Driver Management</h3>
                        <p class="text-secondary mb-0">Manage authorized drivers and monitor license expiry</p>
                    </div>
                    <button class="btn btn-navy text-white fw-medium px-4 py-2 bg-navy rounded-3" data-bs-toggle="modal" data-bs-target="#addDriverModal">
                        <i class="bi bi-plus-lg me-2"></i>Add Driver
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

                <!-- License Status Summary -->
                <div class="row row-cols-1 row-cols-md-3 g-4 mb-4">
                    <div class="col">
                        <div class="card card-stat h-100 p-3" style="border-left: 4px solid var(--status-operational);">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <p class="text-secondary small fw-semibold mb-1">VALID LICENSES</p>
                                    <h2 class="fw-bold mb-0 js-lic-valid">{{ $licenseCounts['Valid'] }}</h2>
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
                                    <h2 class="fw-bold mb-0 js-lic-soon">{{ $licenseCounts['Expiring Soon'] }}</h2>
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
                                    <h2 class="fw-bold mb-0 js-lic-expired">{{ $licenseCounts['Expired'] }}</h2>
                                </div>
                                <div class="bg-danger bg-opacity-10 text-danger rounded p-2">
                                    <i class="bi bi-x-octagon fs-4"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Access Requests — documented addition (FR-03): pending self-registrations are
                     NOT in the prototype (approval was added to scope later). Built with the
                     prototype's own card/table/badge/button conventions. --}}
                @if ($pendingDrivers->isNotEmpty())
                <div class="card border-0 shadow-sm rounded-3 mb-4">
                    <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                        <h6 class="mb-0 fw-bold"><i class="bi bi-person-plus text-primary me-2"></i>Access Requests</h6>
                        <span class="badge bg-primary rounded-pill">{{ $pendingDrivers->count() }} Pending</span>
                    </div>
                    <div class="list-group list-group-flush">
                        @foreach ($pendingDrivers as $pending)
                        <div class="list-group-item py-3 d-flex justify-content-between align-items-center">
                            <div>
                                <div class="fw-bold">{{ $pending->name }}</div>
                                <div class="small text-secondary">{{ $pending->email }}</div>
                                @if ($pending->license_number)
                                <div class="small text-secondary font-monospace">{{ $pending->license_number }}</div>
                                @endif
                            </div>
                            <div class="d-flex gap-2">
                                <form method="POST" action="{{ route('drivers.approve', $pending) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="btn btn-sm btn-navy bg-navy text-white">Approve</button>
                                </form>
                                <form method="POST" action="{{ route('drivers.reject', $pending) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">Reject</button>
                                </form>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif

                <!-- Filters (live server-side search/license-status) -->
                <div class="card border-0 shadow-sm rounded-3 mb-4">
                    <div class="card-body p-3">
                        <form method="GET" action="{{ route('drivers') }}">
                        <div class="row g-3">
                            <div class="col-md-5">
                                <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="Search driver name or license no...">
                            </div>
                            <div class="col-md-4">
                                <select class="form-select" name="license_status">
                                    <option value="">License Status (All)</option>
                                    <option value="Valid" @selected(request('license_status') === 'Valid')>Valid</option>
                                    <option value="Expiring Soon" @selected(request('license_status') === 'Expiring Soon')>Expiring Soon</option>
                                    <option value="Expired" @selected(request('license_status') === 'Expired')>Expired</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <button class="btn btn-outline-secondary w-100">Filter</button>
                            </div>
                        </div>
                        </form>
                    </div>
                </div>

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
                            <tbody id="rows-drivers">
                                {{-- Live rows — matches the prototype's own renderDrivers() markup, incl.
                                     the 3rd "Update License" action button that agency.js paints (not in
                                     the static HTML — confirmed via a live pixel-diff against the prototype). --}}
                                @forelse ($drivers as $driver)
                                @php $licenseStatus = $driver->licenseStatus(); @endphp
                                <tr data-id="{{ $driver->id }}"
                                    data-name="{{ $driver->name }}"
                                    data-email="{{ $driver->email }}"
                                    data-license="{{ $driver->license_number }}"
                                    data-expiry="{{ $driver->license_expiry_date?->toDateString() }}"
                                    data-expiry-label="{{ $driver->license_expiry_date ? \Illuminate\Support\Carbon::parse($driver->license_expiry_date)->format('M j, Y') : '—' }}"
                                    data-status="{{ $licenseStatus }}"
                                    data-vehicle-ids="{{ $driver->vehicles->pluck('id')->implode(',') }}"
                                    data-vehicle="{{ $driver->vehicles->isNotEmpty() ? $driver->vehicles->map(fn ($v) => "{$v->plate_number} ({$v->type})")->implode(', ') : 'Unassigned' }}">
                                    <td>
                                        <div class="fw-bold">{{ $driver->name }}</div>
                                        <div class="small text-secondary">{{ $driver->email }}</div>
                                    </td>
                                    <td class="font-monospace text-secondary">{{ $driver->license_number ?? '—' }}</td>
                                    <td class="{{ $licenseStatus === 'Expiring Soon' ? 'text-warning fw-bold' : ($licenseStatus === 'Expired' ? 'text-danger fw-bold' : '') }}">
                                        {{ $driver->license_expiry_date ? \Illuminate\Support\Carbon::parse($driver->license_expiry_date)->format('M j, Y') : '—' }}
                                    </td>
                                    <td>
                                        @if ($licenseStatus)
                                        <span class="badge bg-{{ ['Valid' => 'success', 'Expiring Soon' => 'warning', 'Expired' => 'danger'][$licenseStatus] }} bg-opacity-10 text-{{ ['Valid' => 'success', 'Expiring Soon' => 'warning', 'Expired' => 'danger'][$licenseStatus] }} px-3 py-2 rounded-pill">{{ $licenseStatus }}</span>
                                        @else
                                        <span class="badge bg-secondary bg-opacity-10 text-secondary px-3 py-2 rounded-pill">No License</span>
                                        @endif
                                    </td>
                                    <td>{{ $driver->vehicles->isNotEmpty() ? $driver->vehicles->map(fn ($v) => "{$v->plate_number} ({$v->type})")->implode(', ') : 'Unassigned' }}</td>
                                    <td class="text-end">
                                        <button class="btn btn-sm btn-light border" title="View Details" data-bs-toggle="modal" data-bs-target="#viewDriverModal"><i class="bi bi-eye"></i></button>
                                        <button class="btn btn-sm btn-light border" title="Edit" data-bs-toggle="modal" data-bs-target="#editDriverModal"><i class="bi bi-pencil"></i></button>
                                        <button class="btn btn-sm btn-light border" title="Update License" data-bs-toggle="modal" data-bs-target="#updateLicenseModal"><i class="bi bi-arrow-clockwise"></i></button>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="6" class="text-center text-secondary py-4">No drivers found.</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @include('partials.table-footer', ['paginator' => $drivers, 'label' => 'drivers'])
                </div>

@endsection

@section('modals')
    <!-- Add Driver Modal -->
    <div class="modal fade" id="addDriverModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-navy text-white">
                    <h5 class="modal-title fw-bold">Register New Driver</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                {{-- Live form — posts to drivers.store, always active immediately (FR-06) --}}
                <form method="POST" action="{{ route('drivers.store') }}">
                @csrf
                <div class="modal-body p-4">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Full Name</label>
                            <input type="text" name="name" value="{{ old('name') }}" class="form-control" placeholder="First Last" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Email</label>
                            <input type="email" name="email" value="{{ old('email') }}" class="form-control" placeholder="e.g. juan.delacruz@bfp.gov.ph" required>
                            <div class="form-text">Used as the driver's sign-in account for the mobile app.</div>
                        </div>
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Password</label>
                                <input type="password" name="password" class="form-control" placeholder="Set a password" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Confirm Password</label>
                                <input type="password" name="password_confirmation" class="form-control" placeholder="Re-enter password" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">License Number</label>
                            <input type="text" name="license_number" value="{{ old('license_number') }}" class="form-control" placeholder="e.g. N01-12-345678">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">License Expiry Date</label>
                            <input type="date" name="license_expiry_date" value="{{ old('license_expiry_date') }}" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Assign Vehicle (Optional)</label>
                            <select class="form-select" name="assigned_vehicle_id">
                                <option value="">Unassigned</option>
                                {{-- Only currently-unassigned vehicles — never steals another driver's vehicle --}}
                                @foreach ($availableVehicles as $vehicle)
                                <option value="{{ $vehicle->id }}" @selected(old('assigned_vehicle_id') == $vehicle->id)>{{ $vehicle->plate_number }} ({{ $vehicle->type }})</option>
                                @endforeach
                            </select>
                        </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-navy bg-navy text-white">Register Driver</button>
                </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Driver Modal -->
    <div class="modal fade" id="editDriverModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-navy text-white">
                    <h5 class="modal-title fw-bold">Edit Driver Details</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                {{-- Live form — action is set per-row by the page script (drivers.update) --}}
                <form method="POST" id="editDriverForm" action="#">
                @csrf
                @method('PUT')
                <div class="modal-body p-4">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Full Name</label>
                            <input type="text" name="name" class="form-control" id="edName" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Email</label>
                            <input type="email" name="email" class="form-control" id="edEmail" required>
                            <div class="form-text">Used as the driver's sign-in account for the mobile app.</div>
                        </div>
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">New Password</label>
                                <input type="password" name="password" class="form-control" placeholder="Leave blank to keep current password">
                            </div>
                            {{-- Confirm field added — documented deviation. The prototype's Edit
                                 modal had a single password field with no confirmation, but the
                                 'confirmed' validation rule (matching the Add Driver modal) needs it. --}}
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Confirm New Password</label>
                                <input type="password" name="password_confirmation" class="form-control" placeholder="Re-enter new password">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">License Number</label>
                            <input type="text" name="license_number" class="form-control" id="edLicense">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">License Expiry Date</label>
                            <input type="date" name="license_expiry_date" class="form-control" id="edExpiry">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Assign Vehicle</label>
                            {{-- First option reads "No change" (not "Unassigned") — approved plan
                                 R2 Day 4 sub-task 13 — so editing other fields never accidentally
                                 clears an assignment. Selecting a vehicle can only ever pick one
                                 that is unassigned or already this driver's own. --}}
                            <select class="form-select" name="assigned_vehicle_id" id="edVehicle">
                                <option value="">No change</option>
                                @foreach ($availableVehicles as $vehicle)
                                <option value="{{ $vehicle->id }}">{{ $vehicle->plate_number }} ({{ $vehicle->type }})</option>
                                @endforeach
                            </select>
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

    <!-- Update License Modal -->
    <div class="modal fade" id="updateLicenseModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-navy text-white">
                    <h5 class="modal-title fw-bold">Update Driver License</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                {{-- Live form — action is set per-row by the page script (drivers.license, FR-08) --}}
                <form method="POST" id="updateLicenseForm" action="#">
                @csrf
                @method('PATCH')
                <div class="modal-body p-4">
                    <div class="d-flex justify-content-between align-items-center bg-light rounded-3 p-3 mb-4">
                        <div>
                            <div class="fw-bold" id="ulName">Driver Name</div>
                            <div class="small text-secondary">License <span class="font-monospace" id="ulLicense">—</span></div>
                        </div>
                        <span class="badge bg-success bg-opacity-10 text-success px-3 py-2 rounded-pill" id="ulCurrentBadge">Valid</span>
                    </div>
                    <p class="text-secondary small mb-4">After the driver renews their license, enter the new expiry date. The license status updates automatically based on the date.</p>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Current Expiry Date</label>
                            <input type="text" class="form-control bg-light" id="ulCurrentExpiry" value="—" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">New Expiry Date</label>
                            <input type="date" class="form-control" name="license_expiry_date" id="ulNewExpiry" required>
                            <div class="mt-2 d-flex gap-2">
                                <button type="button" class="btn btn-sm btn-light border" id="ulPlus5">+5 years</button>
                                <button type="button" class="btn btn-sm btn-light border" id="ulPlus10">+10 years</button>
                            </div>
                        </div>
                        <div class="alert alert-light border d-flex align-items-center mb-0">
                            <i class="bi bi-info-circle text-primary me-2"></i>
                            <div class="small">Resulting status: <span class="fw-bold" id="ulResult">—</span></div>
                        </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-navy bg-navy text-white">Mark as Renewed</button>
                </div>
                </form>
            </div>
        </div>
    </div>

    <!-- View Driver Modal -->
    <div class="modal fade" id="viewDriverModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-light">
                    <h5 class="modal-title fw-bold">Driver Information</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="text-center mb-4">
                        <div class="bg-light rounded-circle d-inline-flex justify-content-center align-items-center p-3 mb-2" style="width: 80px; height: 80px;">
                            <i class="bi bi-person fs-1 text-secondary"></i>
                        </div>
                        <h4 class="fw-bold mb-1" id="vdName">Juan Dela Cruz</h4>
                        <span class="badge bg-success bg-opacity-10 text-success px-3 py-1 rounded-pill" id="vdStatusBadge">License Valid</span>
                    </div>
                    
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between align-items-center py-3">
                            <span class="text-secondary small fw-semibold">Email</span>
                            <span class="fw-medium" id="vdEmail">juan.delacruz@bfp.gov.ph</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center py-3">
                            <span class="text-secondary small fw-semibold">License No.</span>
                            <span class="fw-medium font-monospace" id="vdLicense">N01-12-345678</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center py-3">
                            <span class="text-secondary small fw-semibold">Expiry Date</span>
                            <span class="fw-medium" id="vdExpiry">Dec 15, 2027</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center py-3">
                            <span class="text-secondary small fw-semibold">Assigned Vehicle</span>
                            <span class="fw-medium" id="vdVehicle">ABC-1234 (Fire Truck)</span>
                        </li>
                    </ul>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        // Live wiring: modal population from the clicked row's data attributes,
        // mirroring the prototype's own agency.js driver-modal behavior.
        const LIC_TONE = { 'Valid': 'success', 'Expiring Soon': 'warning', 'Expired': 'danger' };
        const vehicleLabels = @json($vehicleLabels);
        const licenseWarningDays = {{ auth()->user()->agency->license_expiry_warning_days }};

        function rowData(event) {
            const row = event.relatedTarget && event.relatedTarget.closest('tr');
            return row ? row.dataset : null;
        }

        // View Driver modal
        document.getElementById('viewDriverModal').addEventListener('show.bs.modal', event => {
            const d = rowData(event);
            if (!d) return;
            document.getElementById('vdName').textContent = d.name;
            document.getElementById('vdEmail').textContent = d.email;
            document.getElementById('vdLicense').textContent = d.license || '—';
            document.getElementById('vdExpiry').textContent = d.expiryLabel;
            document.getElementById('vdVehicle').textContent = d.vehicle;
            const badge = document.getElementById('vdStatusBadge');
            const tone = LIC_TONE[d.status] || 'secondary';
            badge.className = 'badge bg-' + tone + ' bg-opacity-10 text-' + tone + ' px-3 py-1 rounded-pill';
            badge.textContent = d.status ? ('License ' + d.status) : 'No License';
        });

        // Edit Driver modal
        const editActionTemplate = @json(route('drivers.update', ['driver' => '__ID__']));
        document.getElementById('editDriverModal').addEventListener('show.bs.modal', event => {
            const d = rowData(event);
            if (!d) return;
            document.getElementById('editDriverForm').action = editActionTemplate.replace('__ID__', d.id);
            document.getElementById('edName').value = d.name;
            document.getElementById('edEmail').value = d.email;
            document.getElementById('edLicense').value = d.license || '';
            document.getElementById('edExpiry').value = d.expiry || '';

            // Inject this driver's own current vehicle(s) as extra options (excluded
            // from the base "available" list) so they're visible/selectable here too.
            const select = document.getElementById('edVehicle');
            select.querySelectorAll('option[data-own]').forEach(o => o.remove());
            const ids = (d.vehicleIds || '').split(',').filter(Boolean);
            ids.forEach((id, index) => {
                const opt = document.createElement('option');
                opt.value = id;
                opt.dataset.own = 'true';
                opt.textContent = vehicleLabels[id] || ('Vehicle #' + id);
                select.appendChild(opt);
                if (index === 0) opt.selected = true;
            });
            if (ids.length === 0) select.value = '';
        });

        // Update License modal
        const licenseActionTemplate = @json(route('drivers.license', ['driver' => '__ID__']));
        const ulNewExpiry = document.getElementById('ulNewExpiry');
        const ulResult = document.getElementById('ulResult');

        function computeStatus(dateStr) {
            if (!dateStr) return null;
            const days = (new Date(dateStr) - new Date()) / 86400000;
            return days < 0 ? 'Expired' : days <= licenseWarningDays ? 'Expiring Soon' : 'Valid';
        }

        function showResult() {
            const status = computeStatus(ulNewExpiry.value);
            if (!status) { ulResult.textContent = '—'; ulResult.className = 'fw-bold'; return; }
            ulResult.textContent = status;
            ulResult.className = 'fw-bold text-' + LIC_TONE[status];
        }

        document.getElementById('updateLicenseModal').addEventListener('show.bs.modal', event => {
            const d = rowData(event);
            if (!d) return;
            document.getElementById('updateLicenseForm').action = licenseActionTemplate.replace('__ID__', d.id);
            document.getElementById('ulName').textContent = d.name;
            document.getElementById('ulLicense').textContent = d.license || '—';
            document.getElementById('ulCurrentExpiry').value = d.expiryLabel;
            const badge = document.getElementById('ulCurrentBadge');
            const tone = LIC_TONE[d.status] || 'secondary';
            badge.className = 'badge bg-' + tone + ' bg-opacity-10 text-' + tone + ' px-3 py-2 rounded-pill';
            badge.textContent = d.status || 'No License';
            ulNewExpiry.value = '';
            showResult();
        });
        ulNewExpiry.addEventListener('input', showResult);

        function addYears(years) {
            const dt = new Date();
            dt.setFullYear(dt.getFullYear() + years);
            ulNewExpiry.value = dt.toISOString().slice(0, 10);
            showResult();
        }
        document.getElementById('ulPlus5').addEventListener('click', () => addYears(5));
        document.getElementById('ulPlus10').addEventListener('click', () => addYears(10));
    </script>
@endsection
