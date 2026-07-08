<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'RVMS - Dashboard')</title>
    <link rel="icon" type="image/svg+xml" href="../assets/img/rvms-logo.svg">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>
    <div class="wrapper">
        <!-- Sidebar -->
        <nav class="sidebar">
            <div class="sidebar-header">
                <img src="../assets/img/rvms-logo.svg" alt="RVMS" class="sidebar-logo-img">
                <div>
                    <div class="fw-bold fs-5">RVMS</div>
                    <div class="small text-white-50">Admin Portal</div>
                </div>
            </div>
            
            <div class="sidebar-nav">
                <div class="px-4 pb-2 small text-white-50 text-uppercase fw-semibold">Overview</div>
                <a href="dashboard.html" class="nav-item active">
                    <i class="bi bi-grid-1x2-fill"></i> Dashboard
                </a>
                
                <div class="px-4 pt-3 pb-2 small text-white-50 text-uppercase fw-semibold">Maintenance</div>
                <a href="inspections-damage.html" class="nav-item">
                    <i class="bi bi-exclamation-triangle"></i> Inspections & Damage
                </a>
                <a href="pm.html" class="nav-item">
                    <i class="bi bi-wrench-adjustable-circle"></i> PM Schedules
                </a>
                <a href="repairs.html" class="nav-item">
                    <i class="bi bi-tools"></i> Repair Logs
                </a>

                <div class="px-4 pt-3 pb-2 small text-white-50 text-uppercase fw-semibold">Operations</div>
                <a href="dispatch.html" class="nav-item">
                    <i class="bi bi-send-check"></i> Dispatch Logs
                </a>
                <a href="reports.html" class="nav-item">
                    <i class="bi bi-file-earmark-bar-graph"></i> Reports
                </a>
                
                <div class="px-4 pt-3 pb-2 small text-white-50 text-uppercase fw-semibold">Fleets</div>
                <a href="vehicles.html" class="nav-item">
                    <i class="bi bi-truck"></i> Vehicles
                </a>
                <a href="drivers.html" class="nav-item">
                    <i class="bi bi-person-badge"></i> Drivers
                </a>

                <div class="px-4 pt-3 pb-2 small text-white-50 text-uppercase fw-semibold">Settings</div>
                <a href="profile.html" class="nav-item">
                    <i class="bi bi-person-gear"></i> Agency Profile
                </a>
            </div>
        </nav>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Topbar -->
            <header class="topbar">
                <div class="d-flex align-items-center gap-3">
                    <span class="badge bg-light text-dark border px-3 py-2 fs-6 js-agency-badge">Bureau of Fire Protection</span>
                </div>
                
                <div class="d-flex align-items-center gap-3">
                    <div class="dropdown me-2">
                        <div class="position-relative" role="button" data-bs-toggle="dropdown" aria-label="Notifications">
                            <i class="bi bi-bell fs-5 text-secondary"></i>
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger js-bell-count" style="font-size: 0.6rem;">5</span>
                        </div>
                        <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 p-0 notif-dropdown js-bell-list">
                            <li class="d-flex justify-content-between align-items-center px-3 py-2 border-bottom">
                                <h6 class="mb-0 fw-bold small">Notifications</h6>
                                <span class="badge bg-danger rounded-pill">5 new</span>
                            </li>
                            <li><a class="dropdown-item notif-item d-flex align-items-start gap-2 py-2 border-bottom" href="inspections-damage.html">
                                <span class="notif-icon rounded-circle bg-danger bg-opacity-10 text-danger d-inline-flex justify-content-center align-items-center"><i class="bi bi-exclamation-triangle"></i></span>
                                <span>
                                    <span class="small fw-bold d-block">New Damage Report Submitted</span>
                                    <span class="small text-secondary d-block">ABC-1234 &mdash; Cracked side mirror (driver side).</span>
                                    <span class="text-secondary d-block" style="font-size: 0.7rem;">Today, 8:10 AM</span>
                                </span>
                            </a></li>
                            <li><a class="dropdown-item notif-item d-flex align-items-start gap-2 py-2 border-bottom" href="inspections-damage.html">
                                <span class="notif-icon rounded-circle bg-primary bg-opacity-10 text-primary d-inline-flex justify-content-center align-items-center"><i class="bi bi-clipboard-check"></i></span>
                                <span>
                                    <span class="small fw-bold d-block">Daily Inspection Submitted</span>
                                    <span class="small text-secondary d-block">BCD-2310 &mdash; 1 item flagged: low tire pressure.</span>
                                    <span class="text-secondary d-block" style="font-size: 0.7rem;">Today, 7:05 AM</span>
                                </span>
                            </a></li>
                            <li><a class="dropdown-item notif-item d-flex align-items-start gap-2 py-2 border-bottom" href="inspections-damage.html">
                                <span class="notif-icon rounded-circle bg-primary bg-opacity-10 text-primary d-inline-flex justify-content-center align-items-center"><i class="bi bi-clipboard-check"></i></span>
                                <span>
                                    <span class="small fw-bold d-block">Daily Inspection Submitted</span>
                                    <span class="small text-secondary d-block">GHI-6754 &mdash; All items OK.</span>
                                    <span class="text-secondary d-block" style="font-size: 0.7rem;">Today, 6:50 AM</span>
                                </span>
                            </a></li>
                            <li><a class="dropdown-item notif-item d-flex align-items-start gap-2 py-2 border-bottom" href="drivers.html">
                                <span class="notif-icon rounded-circle bg-warning bg-opacity-10 text-warning d-inline-flex justify-content-center align-items-center"><i class="bi bi-person-badge"></i></span>
                                <span>
                                    <span class="small fw-bold d-block">License Expiring Soon</span>
                                    <span class="small text-secondary d-block">Ricardo Bautista &mdash; expires Jul 8, 2026.</span>
                                    <span class="text-secondary d-block" style="font-size: 0.7rem;">Yesterday, 9:00 AM</span>
                                </span>
                            </a></li>
                            <li><a class="dropdown-item notif-item d-flex align-items-start gap-2 py-2 border-bottom" href="pm.html">
                                <span class="notif-icon rounded-circle bg-warning bg-opacity-10 text-warning d-inline-flex justify-content-center align-items-center"><i class="bi bi-wrench-adjustable"></i></span>
                                <span>
                                    <span class="small fw-bold d-block">PM Due</span>
                                    <span class="small text-secondary d-block">EFG-4532 &mdash; engine service has reached its due mileage.</span>
                                    <span class="text-secondary d-block" style="font-size: 0.7rem;">Yesterday, 8:00 AM</span>
                                </span>
                            </a></li>
                            <li><a class="dropdown-item text-center small text-primary fw-semibold py-2" href="notifications.html">View All Notifications</a></li>
                        </ul>
                    </div>
                    <div class="dropdown">
                        <a href="#" class="d-flex align-items-center text-decoration-none text-dark dropdown-toggle" data-bs-toggle="dropdown">
                            <div class="bg-secondary text-white rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 32px; height: 32px;">
                                A
                            </div>
                            <span class="fw-medium">Admin User</span>
                        </a>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0">
                        <li><a class="dropdown-item" href="profile.html"><i class="bi bi-person me-2"></i>Profile</a></li>
                        <li><a class="dropdown-item text-danger" href="../login.html"><i class="bi bi-box-arrow-right me-2"></i>Sign Out</a></li>
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
    <script src="../assets/js/agency.js"></script>
</body>
</html>
