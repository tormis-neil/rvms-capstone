@extends('layouts.app')

@section('title', 'RVMS - Profile')

{{-- BLOCK B: the prototype's markup (web/pages/profile.html) with the hardcoded
     demo values replaced by the signed-in administrator's own record and the
     form wired to PATCH /profile (FR-04).

     One documented deviation, per design decision 7: the prototype's Agency
     Name / Location / Contact inputs and its "Change Agency Logo" button are
     rendered READ-ONLY. No functional requirement backs editing agency
     information, so the fields keep the prototype's layout and still show the
     agency's details — they simply cannot be submitted. Dropping them would
     have changed the page; leaving them editable would have invented a
     feature. --}}
@section('content')
                <div class="mb-4">
                    <h3 class="fw-bold mb-0" style="color: var(--primary);">Agency Profile</h3>
                    <p class="text-secondary mb-0">Manage your agency details and admin account settings</p>
                </div>

                @include('partials.alerts')

                <div class="row g-4">
                    <div class="col-md-4">
                        <!-- Profile Card -->
                        <div class="card border-0 shadow-sm rounded-3 mb-4">
                            <div class="card-body p-4 text-center">
                                <div class="bg-light rounded-circle d-inline-flex justify-content-center align-items-center mb-3 js-agency-avatar overflow-hidden" style="width: 100px; height: 100px;">
                                    @if ($user->agency->logo_path)
                                    <img src="{{ asset($user->agency->logo_path) }}" alt="{{ $user->agency->code }} logo" style="max-width: 70%; max-height: 70%;">
                                    @else
                                    <i class="bi bi-building fs-1 text-secondary"></i>
                                    @endif
                                </div>
                                <h5 class="fw-bold mb-1 js-agency-name">{{ $user->agency->name }}</h5>
                                <p class="text-secondary small mb-3">Admin Portal</p>
                                {{-- Read-only, per design decision 7 — the agency's identity is not editable. --}}
                                <button class="btn btn-sm btn-outline-primary w-100 mb-2" disabled title="Agency details are managed outside the system">Change Agency Logo</button>
                                <hr class="my-4">
                                <div class="text-start">
                                    <h6 class="fw-bold mb-3">Admin Details</h6>
                                    <p class="mb-1 small"><i class="bi bi-person me-2 text-secondary"></i> {{ $user->name }}</p>
                                    <p class="mb-1 small"><i class="bi bi-envelope me-2 text-secondary"></i> <span class="js-agency-email">{{ $user->email }}</span></p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-8">
                        <!-- Settings Form -->
                        <div class="card border-0 shadow-sm rounded-3">
                            <div class="card-header bg-white border-bottom p-4">
                                <h5 class="fw-bold mb-0">General Settings</h5>
                            </div>
                            <div class="card-body p-4">
                                <form method="POST" action="{{ route('profile.update') }}" id="profileForm">
                                    @csrf
                                    @method('PATCH')
                                    {{-- Agency block: display-only (design decision 7). Rendered
                                         disabled so the browser never submits them. --}}
                                    <div class="row g-3 mb-4">
                                        <div class="col-md-12">
                                            <label class="form-label fw-semibold">Agency Name</label>
                                            <input type="text" class="form-control js-agency-name-input" value="{{ $user->agency->name }}" disabled>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold">Location / Region</label>
                                            <input type="text" class="form-control js-agency-location" value="{{ $user->agency->location }}" disabled>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold">Contact Number</label>
                                            <input type="text" class="form-control js-agency-contact" value="{{ $user->agency->contact_number }}" disabled>
                                        </div>
                                    </div>
                                    <h6 class="fw-bold mb-3 border-top pt-4">Admin Account</h6>
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold">Full Name</label>
                                            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $user->name) }}" required>
                                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold">Email Address</label>
                                            <input type="email" name="email" class="form-control js-agency-email @error('email') is-invalid @enderror" value="{{ old('email', $user->email) }}" required>
                                            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold">New Password</label>
                                            <input type="password" name="password" class="form-control @error('password') is-invalid @enderror" placeholder="Leave blank to keep current" autocomplete="new-password">
                                            @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold">Confirm Password</label>
                                            <input type="password" name="password_confirmation" class="form-control" placeholder="Confirm new password" autocomplete="new-password">
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-end mt-4">
                    <button type="submit" form="profileForm" class="btn btn-navy text-white fw-medium px-4 py-2 bg-navy rounded-3">
                        <i class="bi bi-floppy me-2"></i>Save Changes
                    </button>
                </div>

                {{-- Agency Administrators — documented addition (FR-04a, 2026-08).
                     NOT a management module: administrator accounts stay provisioned
                     (design decision 6), so there is no add, no edit and no delete
                     here. One action only, and it is the one that stops a forgotten
                     password locking an agency out of its own dashboard. Hidden for
                     a sole administrator, who has nobody to reset and uses
                     `php artisan rvms:reset-password` instead. --}}
                @if ($colleagues->isNotEmpty())
                <div class="card border-0 shadow-sm rounded-3 mt-4">
                    <div class="card-header bg-white border-bottom py-3">
                        <h6 class="fw-bold mb-0">Agency Administrators</h6>
                        <p class="small text-secondary mb-0">
                            If a colleague is locked out, set a new password for them here and tell them directly.
                        </p>
                    </div>
                    <ul class="list-group list-group-flush">
                        @foreach ($colleagues as $colleague)
                        <li class="list-group-item d-flex justify-content-between align-items-center py-3">
                            <div>
                                <div class="fw-bold text-dark">{{ $colleague->name }}</div>
                                <div class="small text-secondary">{{ $colleague->email }}</div>
                            </div>
                            <button type="button" class="btn btn-sm btn-light border js-reset-admin"
                                    data-name="{{ $colleague->name }}"
                                    data-action="{{ route('admins.password', $colleague) }}">
                                <i class="bi bi-key me-1"></i>Reset Password
                            </button>
                        </li>
                        @endforeach
                    </ul>
                </div>
                @endif

@endsection

@section('modals')
    {{-- Resetting a peer hands over an account with the same reach as your own,
         so this dialog asks for YOUR password first — unlike a driver reset,
         which is routine administration (FR-04a, 2026-08). --}}
    <div class="modal fade" id="resetAdminModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-navy text-white">
                    <h5 class="modal-title fw-bold">Reset Administrator Password</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="resetAdminForm">
                    @csrf
                    @method('PATCH')
                    <div class="modal-body p-4">
                        <div class="alert alert-light border small text-secondary">
                            Setting a new password for <span class="fw-bold" id="raName">—</span>.
                            Give it to them directly — the system does not send email.
                        </div>
                        <label class="form-label small fw-semibold">Your Current Password</label>
                        <input type="password" name="current_password" class="form-control mb-3" required
                               placeholder="Confirm it is you">
                        <label class="form-label small fw-semibold">Their New Password</label>
                        <input type="text" name="password" class="form-control" minlength="8" required
                               placeholder="At least 8 characters">
                        <div class="form-text">Shown as plain text so you can read it out accurately.</div>
                    </div>
                    <div class="modal-footer border-0">
                        <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn bg-navy text-white fw-medium">Reset Password</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.js-reset-admin').forEach(function (btn) {
            btn.addEventListener('click', function () {
                document.getElementById('raName').textContent = btn.dataset.name;
                document.getElementById('resetAdminForm').setAttribute('action', btn.dataset.action);
                new bootstrap.Modal(document.getElementById('resetAdminModal')).show();
            });
        });
    });
</script>
@endsection
