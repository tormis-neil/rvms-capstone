{{--
    Shared delete confirmation (FR-05 / FR-06, extended 2026-08).

    One dialog for both Vehicles and Drivers, so a destructive action always
    looks the same and always says the same two things: exactly WHAT is being
    removed, and that the record is recoverable. The second matters more than it
    sounds — an administrator who believes "Delete" is permanent will hesitate
    over a routine tidy-up, and one who believes it is permanent and presses it
    anyway will panic. Saying "you can restore it" up front is what makes the
    button usable.

    Danger flow, so `bg-danger` header per the prototype's modal conventions
    (Non-Negotiable Rule 9), matching the Review & Assess dialog.

    Usage: include once per page, then call
        confirmDelete({ what, detail, note, action })
    where `action` is the form URL. The server still enforces the active
    dispatch rule regardless of this UI, and answers with a 422 if it is broken.
--}}
<div class="modal fade" id="deleteConfirmModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title fw-bold" id="dcTitle">Delete record?</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="fw-bold fs-6" id="dcWhat">—</div>
                <div class="small text-secondary" id="dcDetail"></div>
                <div class="alert alert-light border mt-3 mb-0 small text-secondary" id="dcNote"></div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
                <form method="POST" id="dcForm" class="d-inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger fw-medium">Delete</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    // DOMContentLoaded so the partial is safe wherever it is included, and so
    // `bootstrap` is defined by the time we construct the modal (Rule 10).
    document.addEventListener('DOMContentLoaded', function () {
        const el = document.getElementById('deleteConfirmModal');
        const modal = new bootstrap.Modal(el);

        window.confirmDelete = function ({ what, detail, note, action }) {
            document.getElementById('dcWhat').textContent = what || '—';
            document.getElementById('dcDetail').textContent = detail || '';
            document.getElementById('dcNote').textContent = note
                || 'Its history is kept, and you can restore it from Deleted Records below.';
            document.getElementById('dcForm').setAttribute('action', action);
            modal.show();
        };
    });
</script>
