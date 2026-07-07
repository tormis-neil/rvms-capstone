{{-- Shared driver form fields (prototype layout); $driver may be null (add) or a model (edit). --}}
<div class="mb-3">
    <label class="form-label fw-semibold">Full Name</label>
    <input type="text" name="name" class="form-control" placeholder="First Last" value="{{ old('name', $driver?->name) }}" required>
</div>
<div class="mb-3">
    <label class="form-label fw-semibold">Email</label>
    <input type="email" name="email" class="form-control" placeholder="e.g. juan.delacruz@bfp.gov.ph" value="{{ old('email', $driver?->email) }}" required>
    <div class="form-text">Used as the driver's sign-in account for the mobile app.</div>
</div>
<div class="row g-3 mb-3">
    <div class="col-md-6">
        <label class="form-label fw-semibold">Password {{ $requirePassword ? '' : '(leave blank to keep)' }}</label>
        <input type="password" name="password" class="form-control" placeholder="{{ $requirePassword ? 'Set a password' : 'New password' }}" @if($requirePassword) required @endif minlength="8">
    </div>
    <div class="col-md-6">
        <label class="form-label fw-semibold">Confirm Password</label>
        <input type="password" name="password_confirmation" class="form-control" placeholder="Re-enter password" @if($requirePassword) required @endif>
    </div>
</div>
<div class="mb-3">
    <label class="form-label fw-semibold">License Number</label>
    <input type="text" name="license_number" class="form-control" placeholder="e.g. N01-12-345678" value="{{ old('license_number', $driver?->license_number) }}">
</div>
<div class="mb-3">
    <label class="form-label fw-semibold">License Expiry Date</label>
    <input type="date" name="license_expiry_date" class="form-control" value="{{ old('license_expiry_date', $driver?->license_expiry_date?->toDateString()) }}">
</div>
<div class="mb-0">
    <label class="form-label fw-semibold">Assign Vehicle (Optional)</label>
    @if ($driver && $driver->assignedVehicles->isNotEmpty())
        <div class="small text-secondary mb-1">
            Currently assigned: {{ $driver->assignedVehicles->map(fn ($v) => $v->plate_number)->join(', ') }}
        </div>
    @endif
    <select name="vehicle_id" class="form-select">
        <option value="">{{ $driver ? '— No change —' : 'Unassigned' }}</option>
        @foreach ($vehicles as $vehicle)
            <option value="{{ $vehicle->id }}"
                @disabled($vehicle->assigned_driver_id !== null)>
                {{ $vehicle->plate_number }} ({{ $vehicle->type }}){{ $vehicle->assigned_driver_id !== null ? ($vehicle->assigned_driver_id === $driver?->id ? ' — already theirs' : ' — assigned') : '' }}
            </option>
        @endforeach
    </select>
    <div class="form-text">A driver may hold more than one vehicle; each vehicle has one primary driver. To unassign or reassign a vehicle, use Edit on the Vehicles page.</div>
</div>
