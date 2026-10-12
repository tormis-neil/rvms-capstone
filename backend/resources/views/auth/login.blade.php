<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RVMS - Rescue Vehicle Management System | Sign In</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('assets/img/rvms-logo.svg') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="{{ asset('assets/css/style.css') }}" rel="stylesheet">
    <style>
        :root {
            --brand-primary: #1B2F72;
            --brand-dark: #0D1B44;
            --surface-bg: #F8FAFC;
        }

        body {
            font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
            background-color: var(--surface-bg);
            color: #1E293B;
            min-height: 100vh;
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .auth-split-layout {
            display: flex;
            min-height: 100vh;
            width: 100%;
            overflow-x: hidden;
        }

        /* --- LEFT BRANDING PANEL --- */
        .brand-panel {
            flex: 1.15;
            background: linear-gradient(145deg, #0A1435 0%, #15265F 50%, #0F1E4A 100%);
            position: relative;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding: 3.5rem 4rem;
            color: #FFFFFF;
            overflow: hidden;
        }

        .brand-panel::before {
            content: "";
            position: absolute;
            top: -20%;
            right: -10%;
            width: 520px;
            height: 520px;
            background: radial-gradient(circle, rgba(37, 99, 235, 0.18) 0%, rgba(15, 30, 74, 0) 70%);
            border-radius: 50%;
            pointer-events: none;
        }

        .brand-panel::after {
            content: "";
            position: absolute;
            bottom: -15%;
            left: -10%;
            width: 420px;
            height: 420px;
            background: radial-gradient(circle, rgba(217, 119, 6, 0.1) 0%, rgba(15, 30, 74, 0) 70%);
            border-radius: 50%;
            pointer-events: none;
        }

        .brand-header {
            position: relative;
            z-index: 2;
        }

        .brand-identity-header {
            display: flex;
            align-items: center;
            gap: 1.25rem;
            margin-bottom: 1.5rem;
        }

        .brand-logo-img {
            width: 68px;
            height: 68px;
            filter: drop-shadow(0 4px 14px rgba(0, 0, 0, 0.35));
            flex-shrink: 0;
        }

        .brand-hero-title {
            font-size: 2.1rem;
            font-weight: 800;
            line-height: 1.2;
            letter-spacing: -0.5px;
            color: #FFFFFF;
            margin: 0;
        }

        .brand-hero-desc {
            font-size: 1.05rem;
            line-height: 1.65;
            color: #CBD5E1;
            max-width: 500px;
            margin-bottom: 2.5rem;
        }

        /* Agency Showcase */
        .agency-showcase-title {
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #94A3B8;
            margin-bottom: 1rem;
        }

        .agency-badges-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 0.85rem;
            max-width: 520px;
            margin-bottom: 2.25rem;
            position: relative;
            z-index: 2;
        }

        .agency-badge-card {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            padding: 0.65rem 0.85rem;
            border-radius: 12px;
            transition: all 0.2s ease;
        }

        .agency-badge-card:hover {
            background: rgba(255, 255, 255, 0.08);
            border-color: rgba(255, 255, 255, 0.2);
        }

        .agency-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid rgba(255, 255, 255, 0.8);
            background: #FFFFFF;
            flex-shrink: 0;
        }

        .agency-info-code {
            font-weight: 700;
            font-size: 0.85rem;
            color: #FFFFFF;
            line-height: 1.2;
        }

        .agency-info-name {
            font-size: 0.7rem;
            color: #94A3B8;
            line-height: 1.2;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 150px;
        }

        /* System Capability Pills */
        .system-pillars {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin-bottom: 1.5rem;
            position: relative;
            z-index: 2;
        }

        .pillar-pill {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            background: rgba(15, 23, 42, 0.45);
            border: 1px solid rgba(255, 255, 255, 0.08);
            padding: 0.35rem 0.75rem;
            border-radius: 8px;
            font-size: 0.75rem;
            color: #E2E8F0;
        }

        .pillar-pill i {
            color: #FBBF24;
            font-size: 0.85rem;
        }

        .brand-footer {
            position: relative;
            z-index: 2;
            padding-top: 1.5rem;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            font-size: 0.75rem;
            color: #94A3B8;
        }

        /* --- RIGHT AUTH FORM PANEL --- */
        .form-panel {
            flex: 0.95;
            background-color: #F1F5F9;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 3rem 2rem;
            position: relative;
        }

        .auth-card {
            background: #FFFFFF;
            border: 1px solid #E2E8F0;
            border-radius: 16px;
            box-shadow: 0 10px 25px -5px rgba(15, 23, 42, 0.07), 0 8px 10px -6px rgba(15, 23, 42, 0.04);
            padding: 2.75rem 2.5rem;
            width: 100%;
            max-width: 440px;
        }

        .auth-form-header {
            margin-bottom: 2rem;
            text-align: center;
        }

        .mobile-brand-logo {
            display: none;
            margin-bottom: 1.25rem;
        }

        .form-title {
            font-size: 1.75rem;
            font-weight: 800;
            color: #0F172A;
            letter-spacing: -0.5px;
            margin-bottom: 0.35rem;
        }

        .form-subtitle {
            font-size: 0.875rem;
            color: #64748B;
            margin-bottom: 0;
        }

        .form-label {
            font-size: 0.8125rem;
            font-weight: 600;
            color: #334155;
            margin-bottom: 0.4rem;
        }

        .input-group {
            border-radius: 10px;
            transition: all 0.2s;
        }

        .input-group:focus-within {
            box-shadow: 0 0 0 3.5px rgba(27, 47, 114, 0.14);
        }

        .input-group .form-control {
            border-left: none;
            font-size: 0.9rem;
            padding: 0.75rem 0.9rem;
            border-color: #E2E8F0;
            border-top-right-radius: 10px;
            border-bottom-right-radius: 10px;
        }

        .input-group .input-group-text {
            background-color: #F8FAFC;
            border-color: #E2E8F0;
            color: #64748B;
            border-top-left-radius: 10px;
            border-bottom-left-radius: 10px;
            padding-left: 1rem;
            padding-right: 0.75rem;
        }

        .input-group .form-control:focus {
            box-shadow: none;
            border-color: #CBD5E1;
        }

        .input-group:focus-within .input-group-text,
        .input-group:focus-within .form-control {
            border-color: var(--brand-primary);
        }

        .toggle-password-btn {
            border: 1px solid #E2E8F0;
            border-left: none;
            background: #FFFFFF;
            color: #64748B;
            border-top-right-radius: 10px !important;
            border-bottom-right-radius: 10px !important;
            padding-right: 0.9rem;
        }

        .toggle-password-btn:hover {
            color: var(--brand-primary);
            background: #F8FAFC;
        }

        .btn-brand-primary {
            background: linear-gradient(135deg, #1B2F72 0%, #112155 100%);
            color: #FFFFFF;
            font-weight: 700;
            font-size: 0.925rem;
            padding: 0.8rem;
            border-radius: 10px;
            border: none;
            box-shadow: 0 4px 12px rgba(27, 47, 114, 0.25);
            transition: all 0.2s ease;
        }

        .btn-brand-primary:hover {
            background: linear-gradient(135deg, #243D8F 0%, #162B6D 100%);
            color: #FFFFFF;
            box-shadow: 0 6px 16px rgba(27, 47, 114, 0.35);
            transform: translateY(-1px);
        }

        .btn-brand-primary:active {
            transform: translateY(0);
        }

        .auth-notice-card {
            background: #F1F5F9;
            border-radius: 8px;
            padding: 0.75rem 0.85rem;
            font-size: 0.75rem;
            color: #475569;
            display: flex;
            align-items: flex-start;
            gap: 0.5rem;
            margin-top: 1.75rem;
        }

        .auth-notice-card i {
            font-size: 0.9rem;
            color: #64748B;
            margin-top: 0.1rem;
        }

        /* Responsive Breakpoints */
        @media (max-width: 991.98px) {
            .auth-split-layout {
                flex-direction: column;
            }
            .brand-panel {
                padding: 2.5rem 1.75rem;
                flex: none;
            }
            .brand-hero-title {
                font-size: 1.65rem;
            }
            .agency-badges-grid {
                grid-template-columns: 1fr;
            }
            .form-panel {
                padding: 2.5rem 1.5rem;
                flex: none;
            }
            .mobile-brand-logo {
                display: block;
            }
        }
    </style>
</head>
<body>

<div class="auth-split-layout">
    
    <!-- LEFT SIDE: Project Name beside Logo, Tagline & Agency Showcase -->
    <div class="brand-panel">
        <div class="brand-header">
            
            <!-- Logo beside Project Title -->
            <div class="brand-identity-header">
                <img src="{{ asset('assets/img/rvms-logo.svg') }}" alt="RVMS Crest" class="brand-logo-img">
                <h1 class="brand-hero-title">
                    Rescue Vehicle<br>Management System
                </h1>
            </div>

            <!-- Grounded, simple, impactful tagline -->
            <p class="brand-hero-desc">
                Supporting vehicle readiness, standardized inspections, and maintenance monitoring for Calbayog City's emergency response agencies.
            </p>

            <!-- Participating Agencies Showcase -->
            <div class="agency-showcase-title">
                <i class="bi bi-shield-check me-1 text-warning"></i> Participating Emergency Response Agencies
            </div>

            <div class="agency-badges-grid">
                <!-- CDRRMO -->
                <div class="agency-badge-card">
                    <img src="{{ asset('assets/img/agency/cdrrmo-logo.jpg') }}" alt="CDRRMO" class="agency-avatar">
                    <div>
                        <div class="agency-info-code">CDRRMO</div>
                        <div class="agency-info-name">Disaster Risk Reduction Office</div>
                    </div>
                </div>

                <!-- BFP -->
                <div class="agency-badge-card">
                    <img src="{{ asset('assets/img/agency/bfp-logo.jpg') }}" alt="BFP" class="agency-avatar">
                    <div>
                        <div class="agency-info-code">BFP</div>
                        <div class="agency-info-name">Bureau of Fire Protection</div>
                    </div>
                </div>

                <!-- CHO -->
                <div class="agency-badge-card">
                    <img src="{{ asset('assets/img/agency/cho-logo.png') }}" alt="CHO" class="agency-avatar">
                    <div>
                        <div class="agency-info-code">CHO</div>
                        <div class="agency-info-name">City Health Office</div>
                    </div>
                </div>

                <!-- PNP -->
                <div class="agency-badge-card">
                    <img src="{{ asset('assets/img/agency/pnp-logo.jpg') }}" alt="PNP" class="agency-avatar">
                    <div>
                        <div class="agency-info-code">PNP</div>
                        <div class="agency-info-name">Philippine National Police</div>
                    </div>
                </div>
            </div>

            <div class="system-pillars">
                <div class="pillar-pill"><i class="bi bi-clipboard2-check"></i> BLOWBAGETS Inspection</div>
                <div class="pillar-pill"><i class="bi bi-tools"></i> Preventive Maintenance</div>
                <div class="pillar-pill"><i class="bi bi-geo-alt"></i> Mission Dispatches</div>
                <div class="pillar-pill"><i class="bi bi-bell"></i> Expiry & Defect Alerts</div>
            </div>
        </div>

        <div class="brand-footer">
            <span>Northwest Samar State University · Capstone Project</span>
        </div>
    </div>

    <!-- RIGHT SIDE: Clean Sign In Form Card -->
    <div class="form-panel">
        <div class="auth-card">
            
            <div class="auth-form-header">
                <div class="mobile-brand-logo text-center">
                    <img src="{{ asset('assets/img/rvms-logo.svg') }}" alt="RVMS" width="60" height="60" class="mb-2">
                    <h5 class="fw-bold text-dark">Rescue Vehicle Management System</h5>
                </div>
                <h2 class="form-title">Administrator Sign In</h2>
                <p class="form-subtitle">Sign in to your agency administrator account.</p>
            </div>

            <form action="{{ route('login.attempt') }}" method="POST">
                @csrf
                
                <div class="mb-3">
                    <label for="email" class="form-label">Email Address</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                        <input type="email" name="email" value="{{ old('email') }}" class="form-control" id="email" placeholder="admin@agency.gov.ph" required autocomplete="username">
                    </div>
                    @error('email')
                        <div class="small text-danger mt-1 fw-semibold"><i class="bi bi-exclamation-circle me-1"></i>{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <label for="password" class="form-label mb-0">Password</label>
                        <a href="#" class="small text-decoration-none fw-semibold" style="color: var(--brand-primary); font-size: 0.78rem;" data-bs-toggle="modal" data-bs-target="#forgotPasswordModal">
                            Forgot password?
                        </a>
                    </div>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-lock"></i></span>
                        <input type="password" name="password" class="form-control" id="password" placeholder="Enter your password" required autocomplete="current-password">
                        <button type="button" class="btn toggle-password-btn" id="togglePassword" tabindex="-1" aria-label="Toggle password visibility">
                            <i class="bi bi-eye" id="togglePasswordIcon"></i>
                        </button>
                    </div>
                    @error('password')
                        <div class="small text-danger mt-1 fw-semibold"><i class="bi bi-exclamation-circle me-1"></i>{{ $message }}</div>
                    @enderror
                </div>

                <button type="submit" class="btn btn-brand-primary w-100 mt-2 mb-3">
                    Sign In
                </button>

                <div class="auth-notice-card">
                    <i class="bi bi-shield-lock"></i>
                    <div>
                        Agency administrator accounts are provisioned by the system. Contact your system maintenance personnel for access.
                    </div>
                </div>
            </form>

        </div>
    </div>

</div>

<!-- Forgot Password Modal -->
<div class="modal fade" id="forgotPasswordModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header border-0 bg-light py-3 px-4">
                <div class="d-flex align-items-center gap-2">
                    <div class="p-2 bg-primary bg-opacity-10 text-primary rounded-circle">
                        <i class="bi bi-key-fill"></i>
                    </div>
                    <h6 class="modal-title fw-bold text-dark mb-0">Password Recovery Policy</h6>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <p class="small text-muted mb-3">
                    To maintain strict security across government fleet records, RVMS does not issue automatic password reset emails over public networks.
                </p>
                <div class="list-group list-group-flush small">
                    <div class="list-group-item px-0 py-2 border-0">
                        <span class="fw-bold text-dark d-block mb-1"><i class="bi bi-person-badge text-primary me-1"></i> Agency Administrators:</span>
                        <span class="text-muted">If you are currently signed in, you may update your password directly from your Profile settings. Locked-out administrators must contact the designated system maintenance personnel to restore access via server recovery command.</span>
                    </div>
                    <div class="list-group-item px-0 py-2 border-0">
                        <span class="fw-bold text-dark d-block mb-1"><i class="bi bi-truck text-success me-1"></i> Authorized Drivers:</span>
                        <span class="text-muted">Drivers can request a password reset directly from their assigned Agency Administrator via the web dashboard's Drivers management module.</span>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0 bg-light py-2 px-4">
                <button type="button" class="btn btn-secondary btn-sm px-3 rounded-pill" data-bs-dismiss="modal">Close</button>
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
        const isHidden = passwordInput.type === 'password';
        passwordInput.type = isHidden ? 'text' : 'password';
        toggleIcon.className = isHidden ? 'bi bi-eye-slash' : 'bi bi-eye';
    });
</script>

</body>
</html>
