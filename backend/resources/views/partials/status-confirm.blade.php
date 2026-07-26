{{--
    Shared vehicle-status confirmation (design decision 9).

    One dialog, identical on every screen that can write the shared status
    (FR-18), so the admin always sees the same three facts before committing:
    the vehicle's CURRENT status, WHERE that status came from, and the NEW
    status being applied.

    Usage: include once per page, then call
        confirmStatusChange({ plate, type, current, origin, next, onConfirm })
    The server still enforces the active-dispatch rule regardless of this UI.
--}}
<div class="modal fade" id="statusConfirmModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-navy text-white">
                <h5 class="modal-title fw-bold" id="scTitle">Change vehicle status?</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="fw-bold fs-6" id="scVehicle">—</div>
                <hr>
                <div class="row g-3">
                    <div class="col-12">
                        <p class="mb-1 text-secondary small">Current status</p>
                        <h6 class="fw-bold mb-0" id="scCurrent">—</h6>
                        <div class="small text-secondary" id="scOrigin"></div>
                    </div>
                    <div class="col-12" id="scNextWrap">
                        <p class="mb-1 text-secondary small">New status</p>
                        <h6 class="fw-bold mb-0" id="scNext">—</h6>
                    </div>
                </div>
                <div class="alert alert-light border mt-3 mb-0 small text-secondary" id="scNote">
                    This status is shared across the whole system and the driver's mobile app.
                </div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-light border" data-bs-dismiss="modal" id="scCancel">Cancel</button>
                <button type="button" class="btn bg-navy text-white" id="scConfirm">Confirm change</button>
            </div>
        </div>
    </div>
</div>

<script>
    /*
     * confirmStatusChange({plate, type, current, origin, next, onConfirm})
     *
     * When the vehicle is on an active dispatch the dialog becomes a BLOCK: no
     * confirm button, and the admin is pointed at Dispatch Logs. Otherwise it is
     * a normal confirm that runs onConfirm().
     */
    (function () {
        const el = document.getElementById('statusConfirmModal');
        const modal = new bootstrap.Modal(el);
        const confirmBtn = document.getElementById('scConfirm');
        let pending = null;

        confirmBtn.addEventListener('click', () => {
            const run = pending;
            pending = null;
            modal.hide();
            if (run) run();
        });

        window.confirmStatusChange = function (opts) {
            const dispatched = opts.current === 'Dispatched';

            document.getElementById('scVehicle').textContent =
                opts.plate + (opts.type ? ' (' + opts.type + ')' : '');
            document.getElementById('scCurrent').textContent = opts.current || '—';
            document.getElementById('scOrigin').textContent = opts.origin || '';
            document.getElementById('scNext').textContent = opts.next || '—';

            const title = document.getElementById('scTitle');
            const header = title.closest('.modal-header');
            const note = document.getElementById('scNote');
            const nextWrap = document.getElementById('scNextWrap');
            const cancel = document.getElementById('scCancel');

            if (dispatched) {
                title.textContent = 'Status cannot be changed here';
                header.className = 'modal-header bg-danger text-white';
                nextWrap.style.display = 'none';
                note.textContent = 'A vehicle on an active dispatch keeps its Dispatched status '
                    + 'until the dispatch is closed, where you set its return status.';
                confirmBtn.textContent = 'Go to Dispatch Logs';
                confirmBtn.className = 'btn btn-danger';
                pending = () => { window.location = @json(route('dispatch')); };
                cancel.textContent = 'Cancel';
            } else {
                title.textContent = 'Change vehicle status?';
                header.className = 'modal-header bg-navy text-white';
                nextWrap.style.display = '';
                note.textContent = 'This status is shared across the whole system and the driver\'s mobile app.';
                confirmBtn.textContent = 'Confirm change';
                confirmBtn.className = 'btn bg-navy text-white';
                pending = opts.onConfirm || null;
                cancel.textContent = 'Cancel';
            }

            modal.show();
        };
    })();
</script>
