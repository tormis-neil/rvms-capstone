<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RVMS - @yield('title', 'Dashboard')</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('assets/img/rvms-logo.svg') }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="{{ asset('assets/css/style.css') }}" rel="stylesheet">
    @stack('styles')
</head>
<body>
    @php($agency = auth()->user()->agency)
    <div class="wrapper">
        <!-- Sidebar -->
        <nav class="sidebar">
            <div class="sidebar-header">
                @if ($agency->logo_path)
                    <img src="{{ asset($agency->logo_path) }}" alt="{{ $agency->code }}" class="agency-logo-img">
                @else
                    <img src="{{ asset('assets/img/rvms-logo.svg') }}" alt="RVMS" class="sidebar-logo-img">
                @endif
                <div>
                    <div class="fw-bold fs-5">RVMS</div>
                    <div class="small text-white-50">Admin Portal</div>
                </div>
            </div>

            <div class="sidebar-nav">
                <div class="px-4 pb-2 small text-white-50 text-uppercase fw-semibold">Overview</div>
                <a href="{{ route('dashboard') }}" class="nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                    <i class="bi bi-grid-1x2-fill"></i> Dashboard
                </a>

                <div class="px-4 pt-3 pb-2 small text-white-50 text-uppercase fw-semibold">Maintenance</div>
                {{-- Module pages ship with their phases (3–5). --}}
                <a href="#" class="nav-item disabled opacity-50" title="Available in a later phase">
                    <i class="bi bi-exclamation-triangle"></i> Inspections &amp; Damage
                </a>
                <a href="#" class="nav-item disabled opacity-50" title="Available in a later phase">
                    <i class="bi bi-wrench-adjustable-circle"></i> PM Schedules
                </a>
                <a href="#" class="nav-item disabled opacity-50" title="Available in a later phase">
                    <i class="bi bi-tools"></i> Repair Logs
                </a>

                <div class="px-4 pt-3 pb-2 small text-white-50 text-uppercase fw-semibold">Operations</div>
                <a href="#" class="nav-item disabled opacity-50" title="Available in a later phase">
                    <i class="bi bi-send-check"></i> Dispatch Logs
                </a>
                <a href="#" class="nav-item disabled opacity-50" title="Available in a later phase">
                    <i class="bi bi-file-earmark-bar-graph"></i> Reports
                </a>

                <div class="px-4 pt-3 pb-2 small text-white-50 text-uppercase fw-semibold">Fleets</div>
                <a href="#" class="nav-item disabled opacity-50" title="Available in a later phase">
                    <i class="bi bi-truck"></i> Vehicles
                </a>
                <a href="#" class="nav-item disabled opacity-50" title="Available in a later phase">
                    <i class="bi bi-person-badge"></i> Drivers
                </a>

                <div class="px-4 pt-3 pb-2 small text-white-50 text-uppercase fw-semibold">Settings</div>
                <a href="#" class="nav-item disabled opacity-50" title="Available in a later phase">
                    <i class="bi bi-person-gear"></i> Agency Profile
                </a>
            </div>
        </nav>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Topbar -->
            <header class="topbar">
                <div class="d-flex align-items-center gap-3">
                    <span class="badge bg-light text-dark border px-3 py-2 fs-6 d-flex align-items-center gap-2">
                        @if ($agency->logo_path)
                            <img src="{{ asset($agency->logo_path) }}" alt="" class="agency-badge-logo">
                        @endif
                        {{ $agency->name }}
                    </span>
                </div>

                <div class="d-flex align-items-center gap-3">
                    <div class="dropdown">
                        <a href="#" class="d-flex align-items-center text-decoration-none text-dark dropdown-toggle" data-bs-toggle="dropdown">
                            <div class="bg-secondary text-white rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 32px; height: 32px;">
                                {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                            </div>
                            <span class="fw-medium">{{ auth()->user()->name }}</span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0">
                            <li>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="dropdown-item text-danger">
                                        <i class="bi bi-box-arrow-right me-2"></i>Sign Out
                                    </button>
                                </form>
                            </li>
                        </ul>
                    </div>
                </div>
            </header>

            <!-- Content Body -->
            <div class="content-body">
                @yield('content')
            </div>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    @stack('scripts')
</body>
</html>
