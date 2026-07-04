{{-- Shared vehicle form fields; $vehicle may be null (add) or a model (edit). --}}
<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label fw-semibold">Plate Number</label>
        <input type="text" name="plate_number" class="form-control" value="{{ old('plate_number', $vehicle?->plate_number) }}" required>
    </div>
    <div class="col-md-6">
        <label class="form-label fw-semibold">Type</label>
        <input type="text" name="type" class="form-control" placeholder="Fire Truck, Ambulance…" value="{{ old('type', $vehicle?->type) }}" required>
    </div>
    <div class="col-md-6">
        <label class="form-label fw-semibold">Make</label>
        <input type="text" name="make" class="form-control" value="{{ old('make', $vehicle?->make) }}" required>
    </div>
    <div class="col-md-6">
        <label class="form-label fw-semibold">Model</label>
        <input type="text" name="model" class="form-control" value="{{ old('model', $vehicle?->model) }}" required>
    </div>
    <div class="col-md-6">
        <label class="form-label fw-semibold">Engine Number</label>
        <input type="text" name="engine_number" class="form-control" value="{{ old('engine_number', $vehicle?->engine_number) }}">
    </div>
    <div class="col-md-6">
        <label class="form-label fw-semibold">Chassis Number</label>
        <input type="text" name="chassis_number" class="form-control" value="{{ old('chassis_number', $vehicle?->chassis_number) }}">
    </div>
    <div class="col-md-6">
        <label class="form-label fw-semibold">Current Mileage (km)</label>
        <input type="number" name="current_mileage" min="0" class="form-control" value="{{ old('current_mileage', $vehicle?->current_mileage ?? 0) }}" required>
    </div>
    <div class="col-md-6">
        <label class="form-label fw-semibold">Assigned Driver</label>
        <select name="assigned_driver_id" class="form-select">
            <option value="">— Unassigned —</option>
            @foreach ($drivers as $driver)
                <option value="{{ $driver->id }}" @selected((int) old('assigned_driver_id', $vehicle?->assigned_driver_id) === $driver->id)>{{ $driver->name }}</option>
            @endforeach
        </select>
    </div>
</div>
