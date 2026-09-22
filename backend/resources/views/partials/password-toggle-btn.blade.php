{{-- Reveal ("eye") button for a password field (NFR-03 usability, 2026-09).
     Sits inside an .input-group next to a password input; the delegated handler
     in partials/password-reveal-script toggles that input's visibility. --}}
<button type="button" class="btn btn-light border" data-password-toggle tabindex="-1" aria-label="Show password"><i class="bi bi-eye"></i></button>
