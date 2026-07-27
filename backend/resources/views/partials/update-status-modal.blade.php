{{--
    Shared "Update Vehicle Status" modal (FR-18, design decision 9).

    FR-18 says the single shared status may be "updated from any applicable
    module", so every screen that DISPLAYS a vehicle status also offers this
    control — Vehicle Management, Repair Logs, PM Schedules, and reviewed
    Inspection/Damage rows. That removes the dead end where a vehicle was left
    Under Preventive Maintenance on a screen with no way to release it.

    Trigger it from a row button carrying these data attributes:
        data-vehicle-id, data-plate, data-type,
        data-vehicle-status, data-status-origin

    Submitting always routes through the shared confirmation first, and the
    server refuses the write outright while a dispatch is open.

    Requires partials.status-confirm to be included on the same page.
--}}
<div class="modal fade" id="updateStatusModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-navy text-white">
                <h5 class="modal-title fw-bold">Update Vehicle Status</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="sharedStatusForm" action="#">
            @csrf
            @method('PATCH')
            <div class="modal-body p-4">
                <div class="d-flex justify-content-between align-items-center bg-light rounded-3 p-3 mb-4">
                    <div>
                        <div class="fw-bold" id="usVehicle">—</div>
                        <div class="small text-secondary" id="usOrigin">—</div>
                    </div>
                    <span class="badge px-3 py-2 rounded-pill" id="usBadge">—</span>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">New Operational Status</label>
                    {{-- Only the three manual statuses; Dispatched is written by the
                         Dispatch module alone (FR-15/FR-18). --}}
                    <select class="form-select" name="status" id="usStatus">
                        @foreach (\App\Models\Vehicle::MANUAL_STATUSES as $status)
                        <option value="{{ $status }}">{{ $status }}</option>
                        @endforeach
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

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const template = @json(route('vehicles.status', ['vehicle' => '__ID__']));
        const el = document.getElementById('updateStatusModal');
        const form = document.getElementById('sharedStatusForm');
        const badgeClasses = @json(\App\Models\Vehicle::STATUS_BADGES);
        let row = {};

        el.addEventListener('show.bs.modal', event => {
            const tr = event.relatedTarget && event.relatedTarget.closest('tr');
            if (!tr) return;
            row = tr.dataset;

            form.action = template.replace('__ID__', row.vehicleId);
            document.getElementById('usVehicle').textContent =
                row.plate + (row.type ? ' (' + row.type + ')' : '');
            document.getElementById('usOrigin').textContent = row.statusOrigin || '';

            const badge = document.getElementById('usBadge');
            badge.textContent = row.vehicleStatus || '—';
            badge.className = 'badge px-3 py-2 rounded-pill ' + (badgeClasses[row.vehicleStatus] || 'bg-light text-dark border');

            // Start from the current status so a change is always deliberate.
            const select = document.getElementById('usStatus');
            const selectable = [...select.options].some(o => o.value === row.vehicleStatus);
            select.value = selectable ? row.vehicleStatus : 'Operational';
        });

        // Route every submission through the shared confirmation.
        form.addEventListener('submit', event => {
            if (form.dataset.confirmed === 'yes') return; // already approved
            event.preventDefault();

            const next = document.getElementById('usStatus').value;

            // No actual change and not dispatched — nothing to confirm.
            if (next === row.vehicleStatus && row.vehicleStatus !== 'Dispatched') {
                form.dataset.confirmed = 'yes';
                form.submit();
                return;
            }

            bootstrap.Modal.getInstance(el).hide();
            window.confirmStatusChange({
                plate: row.plate,
                type: row.type,
                current: row.vehicleStatus,
                origin: row.statusOrigin,
                next: next,
                onConfirm: () => { form.dataset.confirmed = 'yes'; form.submit(); },
            });
        });
    });
</script>
