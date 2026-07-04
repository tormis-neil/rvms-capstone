{{-- Shared driver form fields; $driver may be null (add) or a model (edit). --}}
<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label fw-semibold">Full Name</label>
        <input type="text" name="name" class="form-control" value="{{ old('name', $driver?->name) }}" required>
    </div>
    <div class="col-md-6">
        <label class="form-label fw-semibold">Email</label>
        <input type="email" name="email" class="form-control" value="{{ old('email', $driver?->email) }}" required>
    </div>
    <div class="col-md-6">
        <label class="form-label fw-semibold">License Number</label>
        <input type="text" name="license_number" class="form-control" value="{{ old('license_number', $driver?->license_number) }}">
    </div>
    <div class="col-md-6">
        <label class="form-label fw-semibold">License Expiry Date</label>
        <input type="date" name="license_expiry_date" class="form-control" value="{{ old('license_expiry_date', $driver?->license_expiry_date?->toDateString()) }}">
    </div>
    <div class="col-12">
        <label class="form-label fw-semibold">Password {{ $requirePassword ? '' : '(leave blank to keep current)' }}</label>
        <input type="password" name="password" class="form-control" @if($requirePassword) required @endif minlength="8">
    </div>
</div>
