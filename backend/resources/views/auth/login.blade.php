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
    <style>
        .auth-wrapper {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: var(--bg);
            padding: 2rem 1rem;
        }
        .auth-card {
            background: var(--surface);
            padding: 2.5rem;
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            width: 100%;
            max-width: 420px;
        }
        .auth-logo {
            width: 64px;
            height: 64px;
            margin: 0 auto 1rem;
        }
        .auth-logo .logo-text {
            font-size: 1.25rem;
        }
        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 0.25rem rgba(27, 47, 114, 0.25);
        }
        .input-group-text {
            background-color: var(--bg);
            color: var(--text-secondary);
        }
        .btn-navy {
            background-color: var(--primary);
            color: white;
            font-weight: 600;
        }
        .btn-navy:hover {
            background-color: var(--primary-dark);
            color: white;
        }
        .text-navy {
            color: var(--primary);
        }
        .demo-divider {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            color: var(--text-secondary);
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .demo-divider::before,
        .demo-divider::after {
            content: "";
            flex: 1;
            height: 1px;
            background-color: var(--border);
        }
        .agency-chip {
            border: 1px solid var(--border);
            border-radius: 8px;
            background: var(--surface);
            padding: 0.5rem 0.25rem;
            font-size: 0.75rem;
            font-weight: 600;
            color: var(--text-primary);
            text-decoration: none;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.25rem;
            transition: all 0.15s;
        }
        .agency-chip i {
            font-size: 1.1rem;
            color: var(--primary);
        }
        .agency-chip:hover {
            border-color: var(--primary);
            background-color: rgba(27, 47, 114, 0.04);
            color: var(--primary);
        }
    </style>
</head>
<body>
    <div class="auth-wrapper">
        <div class="auth-card">
            <!-- Logo -->
            <img src="assets/img/rvms-logo.svg" alt="RVMS" width="84" height="84" class="d-block mx-auto mb-3">

            <!-- Title -->
            <h4 class="text-center text-navy fw-bold mb-1">Rescue Vehicle<br>Management System</h4>
            <p class="text-center text-secondary small mb-4">Sign in to your agency admin account</p>

            <!-- Form (wired to the web session login route — R1 Block B) -->
            <form action="{{ route('login.attempt') }}" method="POST">
                @csrf
                <div class="mb-3">
                    <label for="email" class="form-label text-secondary small fw-semibold">Email</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                        <input type="email" name="email" value="{{ old('email') }}" class="form-control py-2" id="email" placeholder="admin@agency.gov.ph" required>
                    </div>
                    {{-- Validation feedback — documented addition (no error state in the prototype) --}}
                    @error('email')
                        <div class="small text-danger mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-2">
                    <label for="password" class="form-label text-secondary small fw-semibold">Password</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-lock"></i></span>
                        <input type="password" name="password" class="form-control py-2" id="password" placeholder="Enter your password" required>
                        <button type="button" class="btn btn-light border" id="togglePassword" tabindex="-1" aria-label="Show password">
                            <i class="bi bi-eye" id="togglePasswordIcon"></i>
                        </button>
                    </div>
                    @error('password')
                        <div class="small text-danger mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <div class="d-flex justify-content-end mb-4">
                    {{-- Opens the contact-your-administrator modal (plan R1.5) instead of a dead link --}}
                    <a href="#" class="small text-navy text-decoration-none fw-semibold" data-bs-toggle="modal" data-bs-target="#forgotPasswordModal">Forgot password?</a>
                </div>

                <button type="submit" class="btn btn-navy w-100 py-2 mb-4">Sign In</button>

                <p class="text-center text-secondary small mb-4" style="font-size: 0.75rem;">
                    Agency administrator accounts are provisioned by the system.
                    Contact your system administrator for access.
                </p>
            </form>

            {{-- The prototype's "Prototype Demo — View As" agency chips are omitted here:
                 the prototype itself labels them a demo-only aid, and the live system
                 identifies the agency from the account (documented omission, plan R1.5). --}}
        </div>
    </div>

    <!-- Forgot Password — contact-your-administrator modal (documented addition, plan R1.5) -->
    <div class="modal fade" id="forgotPasswordModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-light">
                    <h6 class="modal-title fw-bold"><i class="bi bi-key me-2"></i>Forgot Password</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="small mb-2">
                        Password resets are handled by your system administrator.
                    </p>
                    <p class="small text-secondary mb-0">
                        Please contact your agency's system administrator to have your
                        password reset. You will receive your new sign-in credentials
                        directly from them.
                    </p>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS is required for the modal (the prototype login page had no modal) -->
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
