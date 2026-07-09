@extends('layouts.app')

{{-- R1 ships the dashboard SHELL (chrome) only. The content below is the
     prototype's static demo data, left verbatim on purpose — it goes live
     with real counts in Phase R8 (FR-19). --}}
@section('content')
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h3 class="fw-bold mb-0" style="color: var(--primary);">Fleet Overview</h3>
                    <p class="text-secondary mb-0">Today: June 8, 2026</p>
                </div>

                <!-- Overview (8 metrics — Plan §8 Dashboard Monitoring) -->
                <h5 class="fw-bold mb-3">Overview</h5>
                <div class="row row-cols-1 row-cols-md-4 g-4 mb-5">
                    <div class="col">
                        <div class="card card-stat h-100 p-3" style="border-left: 4px solid var(--primary);">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <p class="text-secondary small fw-semibold mb-1">TOTAL VEHICLES</p>
                                    <h2 class="fw-bold mb-0 js-metric-total">6</h2>
                                </div>
                                <div class="bg-primary bg-opacity-10 text-primary rounded p-2">
                                    <i class="bi bi-truck fs-4"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col">
                        <div class="card card-stat h-100 p-3" style="border-left: 4px solid var(--status-operational);">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <p class="text-secondary small fw-semibold mb-1">OPERATIONAL</p>
                                    <h2 class="fw-bold mb-0 js-metric-operational">3</h2>
                                </div>
                                <div class="bg-success bg-opacity-10 text-success rounded p-2">
                                    <i class="bi bi-check-circle-fill fs-4"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col">
                        <div class="card card-stat h-100 p-3" style="border-left: 4px solid var(--status-dispatched);">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <p class="text-secondary small fw-semibold mb-1">DISPATCHED</p>
                                    <h2 class="fw-bold mb-0 js-metric-dispatched">1</h2>
                                </div>
                                <div class="bg-primary bg-opacity-10 text-primary rounded p-2">
                                    <i class="bi bi-cursor-fill fs-4"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col">
                        <div class="card card-stat h-100 p-3" style="border-left: 4px solid var(--status-under-pm);">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <p class="text-secondary small fw-semibold mb-1">UNDER PREVENTIVE MAINTENANCE</p>
                                    <h2 class="fw-bold mb-0 js-metric-underpm">1</h2>
                                </div>
                                <div class="bg-warning bg-opacity-10 text-warning rounded p-2">
                                    <i class="bi bi-tools fs-4"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col">
                        <div class="card card-stat h-100 p-3" style="border-left: 4px solid var(--status-not-operational);">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <p class="text-secondary small fw-semibold mb-1">NOT OPERATIONAL</p>
                                    <h2 class="fw-bold mb-0 js-metric-notop">1</h2>
                                </div>
                                <div class="bg-danger bg-opacity-10 text-danger rounded p-2">
                                    <i class="bi bi-x-circle-fill fs-4"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col">
                        <div class="card card-stat h-100 p-3" style="border-left: 4px solid var(--primary);">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <p class="text-secondary small fw-semibold mb-1">TOTAL DRIVERS</p>
                                    <h2 class="fw-bold mb-0 js-metric-drivers">6</h2>
                                </div>
                                <div class="bg-primary bg-opacity-10 text-primary rounded p-2">
                                    <i class="bi bi-people-fill fs-4"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col">
                        <div class="card card-stat h-100 p-3" style="border-left: 4px solid var(--status-under-pm);">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <p class="text-secondary small fw-semibold mb-1">EXPIRING LICENSES</p>
                                    <h2 class="fw-bold mb-0 js-metric-expiring">2</h2>
                                </div>
                                <div class="bg-warning bg-opacity-10 text-warning rounded p-2">
                                    <i class="bi bi-person-badge fs-4"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col">
                        <div class="card card-stat h-100 p-3" style="border-left: 4px solid var(--status-not-operational);">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <p class="text-secondary small fw-semibold mb-1">PENDING DAMAGE REPORTS</p>
                                    <h2 class="fw-bold mb-0 js-metric-damage">2</h2>
                                </div>
                                <div class="bg-danger bg-opacity-10 text-danger rounded p-2">
                                    <i class="bi bi-exclamation-triangle-fill fs-4"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <h5 class="fw-bold mb-3">Quick Actions</h5>
                <div class="row row-cols-2 row-cols-md-3 row-cols-lg-6 g-3 mb-5">
                    <div class="col">
                        <a href="vehicles.html" class="card card-stat h-100 p-3 text-center text-decoration-none">
                            <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-inline-flex justify-content-center align-items-center mx-auto mb-2" style="width: 44px; height: 44px;">
                                <i class="bi bi-truck fs-5"></i>
                            </div>
                            <div class="small fw-semibold text-dark">Add Vehicle</div>
                        </a>
                    </div>
                    <div class="col">
                        <a href="drivers.html" class="card card-stat h-100 p-3 text-center text-decoration-none">
                            <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-inline-flex justify-content-center align-items-center mx-auto mb-2" style="width: 44px; height: 44px;">
                                <i class="bi bi-person-plus fs-5"></i>
                            </div>
                            <div class="small fw-semibold text-dark">Add Driver</div>
                        </a>
                    </div>
                    <div class="col">
                        <a href="inspections-damage.html" class="card card-stat h-100 p-3 text-center text-decoration-none">
                            <div class="bg-danger bg-opacity-10 text-danger rounded-circle d-inline-flex justify-content-center align-items-center mx-auto mb-2" style="width: 44px; height: 44px;">
                                <i class="bi bi-clipboard-check fs-5"></i>
                            </div>
                            <div class="small fw-semibold text-dark">Review Reports</div>
                        </a>
                    </div>
                    <div class="col">
                        <a href="pm.html" class="card card-stat h-100 p-3 text-center text-decoration-none">
                            <div class="bg-warning bg-opacity-10 text-warning rounded-circle d-inline-flex justify-content-center align-items-center mx-auto mb-2" style="width: 44px; height: 44px;">
                                <i class="bi bi-wrench-adjustable fs-5"></i>
                            </div>
                            <div class="small fw-semibold text-dark">Schedule PM</div>
                        </a>
                    </div>
                    <div class="col">
                        <a href="dispatch.html" class="card card-stat h-100 p-3 text-center text-decoration-none">
                            <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-inline-flex justify-content-center align-items-center mx-auto mb-2" style="width: 44px; height: 44px;">
                                <i class="bi bi-send fs-5"></i>
                            </div>
                            <div class="small fw-semibold text-dark">New Dispatch</div>
                        </a>
                    </div>
                    <div class="col">
                        <a href="reports.html" class="card card-stat h-100 p-3 text-center text-decoration-none">
                            <div class="bg-success bg-opacity-10 text-success rounded-circle d-inline-flex justify-content-center align-items-center mx-auto mb-2" style="width: 44px; height: 44px;">
                                <i class="bi bi-file-earmark-bar-graph fs-5"></i>
                            </div>
                            <div class="small fw-semibold text-dark">Generate Report</div>
                        </a>
                    </div>
                </div>

                <!-- Action Required Alerts -->
                <h5 class="fw-bold mb-3">Action Required</h5>
                <div class="row g-4">
                    <div class="col-md-6">
                        <div class="card border-0 shadow-sm rounded-3">
                            <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                                <h6 class="mb-0 fw-bold"><i class="bi bi-exclamation-triangle text-danger me-2"></i>Pending Inspections & Damage</h6>
                                <span class="badge bg-danger rounded-pill js-action-pending-count">3 New</span>
                            </div>
                            <div class="list-group list-group-flush js-action-pending">
                                <a href="inspections-damage.html" class="list-group-item list-group-item-action py-3">
                                    <div class="d-flex w-100 justify-content-between">
                                        <h6 class="mb-1 fw-bold">Fire Truck (ABC-1234)</h6>
                                        <small class="text-secondary">Today, 8:10 AM</small>
                                    </div>
                                    <p class="mb-1 small">Damage: Cracked side mirror (driver side)</p>
                                    <small class="text-danger fw-semibold">Action: Needs Review</small>
                                </a>
                                <a href="inspections-damage.html" class="list-group-item list-group-item-action py-3">
                                    <div class="d-flex w-100 justify-content-between">
                                        <h6 class="mb-1 fw-bold">Fire Truck (BCD-2310)</h6>
                                        <small class="text-secondary">Today, 7:05 AM</small>
                                    </div>
                                    <p class="mb-1 small">BLOWBAGETS issue: Low tire pressure (front right)</p>
                                    <small class="text-danger fw-semibold">Action: Needs Review</small>
                                </a>
                                <a href="inspections-damage.html" class="list-group-item list-group-item-action py-3">
                                    <div class="d-flex w-100 justify-content-between">
                                        <h6 class="mb-1 fw-bold">Service Vehicle (FGH-5643)</h6>
                                        <small class="text-secondary">Yesterday, 4:45 PM</small>
                                    </div>
                                    <p class="mb-1 small">Damage: Transmission slipping — unsafe to deploy</p>
                                    <small class="text-danger fw-semibold">Action: Needs Review</small>
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="card border-0 shadow-sm rounded-3">
                            <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                                <h6 class="mb-0 fw-bold"><i class="bi bi-person-badge text-warning me-2"></i>Expiring Licenses</h6>
                                <span class="badge bg-warning text-dark rounded-pill js-action-licenses-count">2 Warnings</span>
                            </div>
                            <div class="list-group list-group-flush js-action-licenses">
                                <a href="drivers.html" class="list-group-item list-group-item-action py-3">
                                    <div class="d-flex w-100 justify-content-between">
                                        <h6 class="mb-1 fw-bold">Ricardo Bautista</h6>
                                        <span class="badge bg-warning text-dark">Expiring Soon</span>
                                    </div>
                                    <p class="mb-1 small">License: N01-14-220815</p>
                                    <small class="text-secondary fw-semibold">Expiry: July 8, 2026 (30 days left)</small>
                                </a>
                                <a href="drivers.html" class="list-group-item list-group-item-action py-3">
                                    <div class="d-flex w-100 justify-content-between">
                                        <h6 class="mb-1 fw-bold">Ramon Cruz</h6>
                                        <span class="badge bg-danger">Expired</span>
                                    </div>
                                    <p class="mb-1 small">License: N01-09-778899</p>
                                    <small class="text-secondary fw-semibold">Expired: May 28, 2026 — renewal required</small>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

@endsection
