<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RVMS - Sign In</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('assets/img/rvms-logo.svg') }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="{{ asset('assets/css/style.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/css/admin.css') }}" rel="stylesheet">
</head>
<body>
    <div class="auth-wrapper">
        <div class="auth-card">
            <!-- Logo -->
            <img src="{{ asset('assets/img/rvms-logo.svg') }}" alt="RVMS" width="84" height="84" class="d-block mx-auto mb-3">

            <!-- Title -->
            <h4 class="text-center text-navy fw-bold mb-1">Rescue Vehicle<br>Management System</h4>
            <p class="text-center text-secondary small mb-4">Sign in to your agency admin account</p>

            @if ($errors->any())
                <div class="alert alert-danger py-2 small" role="alert">
                    {{ $errors->first() }}
                </div>
            @endif

            <!-- Form -->
            <form action="{{ route('login.attempt') }}" method="POST">
                @csrf
                <div class="mb-3">
                    <label for="email" class="form-label text-secondary small fw-semibold">Email</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                        <input type="email" class="form-control py-2 @error('email') is-invalid @enderror"
                               id="email" name="email" value="{{ old('email') }}"
                               placeholder="admin@agency.gov.ph" required autofocus>
                    </div>
                </div>

                <div class="mb-2">
                    <label for="password" class="form-label text-secondary small fw-semibold">Password</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-lock"></i></span>
                        <input type="password" class="form-control py-2" id="password" name="password"
                               placeholder="Enter your password" required>
                        <button type="button" class="btn btn-light border" id="togglePassword" tabindex="-1" aria-label="Show password">
                            <i class="bi bi-eye" id="togglePasswordIcon"></i>
                        </button>
                    </div>
                </div>

                <div class="d-flex justify-content-end mb-4">
                    <a href="#" class="small text-navy text-decoration-none fw-semibold"
                       data-bs-toggle="modal" data-bs-target="#forgotPasswordModal">Forgot password?</a>
                </div>

                <button type="submit" class="btn btn-navy w-100 py-2 mb-4">Sign In</button>

                <p class="text-center text-secondary small mb-0" style="font-size: 0.75rem;">
                    Agency administrator accounts are provisioned by the system.
                    Contact your system administrator for access.
                </p>
            </form>
        </div>
    </div>

    <!-- Forgot Password guidance (no self-service reset flow in scope) -->
    <div class="modal fade" id="forgotPasswordModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-light">
                    <h5 class="modal-title fw-bold">Forgot Password</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <p class="mb-0 text-secondary">
                        Please contact your system administrator to reset your agency
                        administrator password. Drivers can update their password from
                        the RVMS mobile app profile screen.
                    </p>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const passwordInput = document.getElementById('password');
        const toggleBtn = document.getElementById('togglePassword');
        const toggleIcon = document.getElementById('togglePasswordIcon');
        toggleBtn.addEventListener('click', () => {
            const hidden = passwordInput.type === 'password';
            passwordInput.type = hidden ? 'text' : 'password';
            toggleIcon.className = hidden ? 'bi bi-eye-slash' : 'bi bi-eye';
        });
    </script>
</body>
</html>
