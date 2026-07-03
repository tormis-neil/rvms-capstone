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
    </style>
</head>
<body>
    <div class="auth-wrapper">
        <div class="auth-card">
            <img src="{{ asset('assets/img/rvms-logo.svg') }}" alt="RVMS" width="84" height="84" class="d-block mx-auto mb-3">

            <h4 class="text-center text-navy fw-bold mb-1">Rescue Vehicle<br>Management System</h4>
            <p class="text-center text-secondary small mb-4">Sign in to your agency admin account</p>

            @if ($errors->any())
                <div class="alert alert-danger py-2 small" role="alert">
                    {{ $errors->first() }}
                </div>
            @endif

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

                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="remember" name="remember" value="1">
                        <label class="form-check-label small text-secondary" for="remember">Remember me</label>
                    </div>
                </div>

                <button type="submit" class="btn btn-navy w-100 py-2 mb-4">Sign In</button>

                <p class="text-center text-secondary small mb-0" style="font-size: 0.75rem;">
                    Agency administrator accounts are provisioned by the system.
                    Contact your system administrator for access.
                </p>
            </form>
        </div>
    </div>

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
