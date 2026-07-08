<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RVMS - Sign In</title>
    <link rel="icon" type="image/svg+xml" href="assets/img/rvms-logo.svg">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="assets/css/style.css" rel="stylesheet">
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

            <!-- Form -->
            <form action="pages/dashboard.html" method="GET">
                <div class="mb-3">
                    <label for="email" class="form-label text-secondary small fw-semibold">Email</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                        <input type="email" class="form-control py-2" id="email" placeholder="admin@agency.gov.ph" required>
                    </div>
                </div>

                <div class="mb-2">
                    <label for="password" class="form-label text-secondary small fw-semibold">Password</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-lock"></i></span>
                        <input type="password" class="form-control py-2" id="password" placeholder="Enter your password" required>
                        <button type="button" class="btn btn-light border" id="togglePassword" tabindex="-1" aria-label="Show password">
                            <i class="bi bi-eye" id="togglePasswordIcon"></i>
                        </button>
                    </div>
                </div>

                <div class="d-flex justify-content-end mb-4">
                    <a href="#" class="small text-navy text-decoration-none fw-semibold">Forgot password?</a>
                </div>

                <button type="submit" class="btn btn-navy w-100 py-2 mb-4">Sign In</button>

                <p class="text-center text-secondary small mb-4" style="font-size: 0.75rem;">
                    Agency administrator accounts are provisioned by the system.
                    Contact your system administrator for access.
                </p>
            </form>

            <!-- Prototype demo quick access (agency is bound to the account; this switcher is a demo aid only) -->
            <div class="demo-divider mb-3">Prototype Demo — View As</div>
            <div class="row g-2">
                <div class="col-3">
                    <a href="pages/dashboard.html?agency=BFP" class="agency-chip w-100">
                        <i class="bi bi-fire"></i>BFP
                    </a>
                </div>
                <div class="col-3">
                    <a href="pages/dashboard.html?agency=PNP" class="agency-chip w-100">
                        <i class="bi bi-shield-shaded"></i>PNP
                    </a>
                </div>
                <div class="col-3">
                    <a href="pages/dashboard.html?agency=CDRRMO" class="agency-chip w-100">
                        <i class="bi bi-life-preserver"></i>CDRRMO
                    </a>
                </div>
                <div class="col-3">
                    <a href="pages/dashboard.html?agency=CHO" class="agency-chip w-100">
                        <i class="bi bi-heart-pulse"></i>CHO
                    </a>
                </div>
            </div>
            <p class="text-center text-secondary mt-2 mb-0" style="font-size: 0.7rem;">
                In the live system, your agency is identified automatically from your account.
            </p>
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
