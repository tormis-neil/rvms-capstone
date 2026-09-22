{{-- Password reveal toggle (NFR-03 usability, 2026-09 — CHO adviser request).

     Lets an admin (and the driver on mobile, wired separately) reveal what they
     are typing into a password field, cutting typo-driven lockouts. Delegated
     from document, so it covers every password field on the page — including
     those inside modals rendered after this script runs. Plain JS with no
     Bootstrap dependency; wrapped in DOMContentLoaded per the layout's script
     rules (CLAUDE.md rule 10). A [data-password-toggle] button reveals the
     password input that shares its .input-group. --}}
<script>
    document.addEventListener('DOMContentLoaded', function () {
        document.addEventListener('click', function (e) {
            const btn = e.target.closest('[data-password-toggle]');
            if (! btn) return;

            const group = btn.closest('.input-group');
            const input = group ? group.querySelector('input') : null;
            if (! input) return;

            const reveal = input.type === 'password';
            input.type = reveal ? 'text' : 'password';

            const icon = btn.querySelector('i');
            if (icon) icon.className = reveal ? 'bi bi-eye-slash' : 'bi bi-eye';
            btn.setAttribute('aria-label', reveal ? 'Hide password' : 'Show password');
        });
    });
</script>
