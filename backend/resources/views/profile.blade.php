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

                {{-- Agency Administrators — documented addition (design decision 6
                     revised, 2026-09). An administrator can provision another
                     administrator for their OWN agency in-app, instead of only via
                     the rvms:create-admin server command. Confirms the actor's own
                     password and notifies the agency's existing admins. No prototype
                     page backs this; it reuses the prototype's card + modal conventions. --}}
                <div class="card border-0 shadow-sm rounded-3 mt-5">
                    <div class="card-header bg-white border-bottom p-4 d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="fw-bold mb-0">Agency Administrators</h5>
                            <p class="text-secondary small mb-0">Administrators can view and manage every record in your agency.</p>
                        </div>
                        <button class="btn btn-navy text-white fw-medium px-3 py-2 bg-navy rounded-3" data-bs-toggle="modal" data-bs-target="#createAdminModal">
                            <i class="bi bi-person-plus me-2"></i>Create Administrator
                        </button>
                    </div>
                    <div class="card-body p-0">
                        <ul class="list-group list-group-flush">
                            @foreach ($admins as $admin)
                            <li class="list-group-item d-flex justify-content-between align-items-center px-4 py-3">
                                <div>
                                    <span class="fw-semibold">{{ $admin->name }}</span>
                                    @if ($admin->id === $user->id)
                                    <span class="badge bg-light text-dark border rounded-pill ms-2">You</span>
                                    @endif
                                    <div class="text-secondary small">{{ $admin->email }}</div>
                                </div>
                                <i class="bi bi-person-badge text-secondary"></i>
                            </li>
                            @endforeach
                        </ul>
                    </div>
                </div>

@endsection

@section('modals')
    {{-- Create Administrator (design decision 6 revised, 2026-09). Fields are
         prefixed admin_* so a validation failure never cross-populates the
         profile form above. The modal auto-opens on error (see script). --}}
    <div class="modal fade" id="createAdminModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-navy text-white">
                    <h5 class="modal-title fw-bold">Create Administrator</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="{{ route('profile.administrators.store') }}">
                    @csrf
                    <div class="modal-body p-4">
                        <p class="text-secondary small mb-3">The new administrator belongs to your agency ({{ $user->agency->name }}) and can sign in immediately. Give them the password directly.</p>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Full Name</label>
                            <input type="text" name="admin_name" class="form-control @error('admin_name') is-invalid @enderror" value="{{ old('admin_name') }}" required>
                            @error('admin_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Email Address</label>
                            <input type="email" name="admin_email" class="form-control @error('admin_email') is-invalid @enderror" value="{{ old('admin_email') }}" required>
                            @error('admin_email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Password</label>
                                <input type="password" name="admin_password" class="form-control @error('admin_password') is-invalid @enderror" minlength="8" required autocomplete="new-password">
                                @error('admin_password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Confirm Password</label>
                                <input type="password" name="admin_password_confirmation" class="form-control" minlength="8" required autocomplete="new-password">
                            </div>
                        </div>
                        {{-- Confirm your own password before creating a peer with equal reach. --}}
                        <div class="mt-3 border-top pt-3">
                            <label class="form-label fw-semibold">Your Current Password</label>
                            <input type="password" name="current_password" class="form-control @error('current_password') is-invalid @enderror" required autocomplete="current-password">
                            @error('current_password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                    <div class="modal-footer border-0">
                        <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-navy text-white fw-medium bg-navy">Create Administrator</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Re-open the modal when a create-admin submission failed validation, so the
         messages are seen in context rather than silently behind a closed dialog. --}}
    @if ($errors->hasAny(['admin_name', 'admin_email', 'admin_password', 'current_password']))
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            new bootstrap.Modal(document.getElementById('createAdminModal')).show();
        });
    </script>
    @endif
@endsection
