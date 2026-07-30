@extends('layouts.app')

{{-- BLOCK B: the prototype's markup (web/pages/dashboard.html) with the
     hardcoded demo numbers replaced by live, agency-scoped counts (FR-19).
     Every figure comes from App\Services\DashboardSummary, which the API's
     /dashboard/summary reads too, so the page and the API cannot disagree.
     Quick Action tiles keep the prototype's wording and icons; only their
     .html hrefs become named routes. --}}
@section('content')
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h3 class="fw-bold mb-0" style="color: var(--primary);">Fleet Overview</h3>
                    <p class="text-secondary mb-0">Today: {{ now()->format('F j, Y') }}</p>
                </div>

                <!-- Overview (8 metrics — Plan §8 Dashboard Monitoring) -->
                <h5 class="fw-bold mb-3">Overview</h5>
                <div class="row row-cols-1 row-cols-md-4 g-4 mb-5">
                    <div class="col">
                        <div class="card card-stat h-100 p-3" style="border-left: 4px solid var(--primary);">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <p class="text-secondary small fw-semibold mb-1">TOTAL VEHICLES</p>
                                    <h2 class="fw-bold mb-0 js-metric-total">{{ $metrics['total_vehicles'] }}</h2>
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
                                    <h2 class="fw-bold mb-0 js-metric-operational">{{ $metrics['operational'] }}</h2>
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
                                    <h2 class="fw-bold mb-0 js-metric-dispatched">{{ $metrics['dispatched'] }}</h2>
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
                                    <h2 class="fw-bold mb-0 js-metric-underpm">{{ $metrics['under_pm'] }}</h2>
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
                                    <h2 class="fw-bold mb-0 js-metric-notop">{{ $metrics['not_operational'] }}</h2>
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
                                    <h2 class="fw-bold mb-0 js-metric-drivers">{{ $metrics['total_drivers'] }}</h2>
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
                                    <h2 class="fw-bold mb-0 js-metric-expiring">{{ $metrics['expiring_licenses'] }}</h2>
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
                                    <h2 class="fw-bold mb-0 js-metric-damage">{{ $metrics['pending_damage_reports'] }}</h2>
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
                        <a href="{{ route('vehicles') }}" class="card card-stat h-100 p-3 text-center text-decoration-none">
                            <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-inline-flex justify-content-center align-items-center mx-auto mb-2" style="width: 44px; height: 44px;">
                                <i class="bi bi-truck fs-5"></i>
                            </div>
                            <div class="small fw-semibold text-dark">Add Vehicle</div>
                        </a>
                    </div>
                    <div class="col">
                        <a href="{{ route('drivers') }}" class="card card-stat h-100 p-3 text-center text-decoration-none">
                            <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-inline-flex justify-content-center align-items-center mx-auto mb-2" style="width: 44px; height: 44px;">
                                <i class="bi bi-person-plus fs-5"></i>
                            </div>
                            <div class="small fw-semibold text-dark">Add Driver</div>
                        </a>
                    </div>
                    <div class="col">
                        <a href="{{ route('inspections') }}" class="card card-stat h-100 p-3 text-center text-decoration-none">
                            <div class="bg-danger bg-opacity-10 text-danger rounded-circle d-inline-flex justify-content-center align-items-center mx-auto mb-2" style="width: 44px; height: 44px;">
                                <i class="bi bi-clipboard-check fs-5"></i>
                            </div>
                            <div class="small fw-semibold text-dark">Review Reports</div>
                        </a>
                    </div>
                    <div class="col">
                        <a href="{{ route('pm') }}" class="card card-stat h-100 p-3 text-center text-decoration-none">
                            <div class="bg-warning bg-opacity-10 text-warning rounded-circle d-inline-flex justify-content-center align-items-center mx-auto mb-2" style="width: 44px; height: 44px;">
                                <i class="bi bi-wrench-adjustable fs-5"></i>
                            </div>
                            <div class="small fw-semibold text-dark">Schedule PM</div>
                        </a>
                    </div>
                    <div class="col">
                        <a href="{{ route('dispatch') }}" class="card card-stat h-100 p-3 text-center text-decoration-none">
                            <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-inline-flex justify-content-center align-items-center mx-auto mb-2" style="width: 44px; height: 44px;">
                                <i class="bi bi-send fs-5"></i>
                            </div>
                            <div class="small fw-semibold text-dark">New Dispatch</div>
                        </a>
                    </div>
                    <div class="col">
                        <a href="{{ route('reports') }}" class="card card-stat h-100 p-3 text-center text-decoration-none">
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
                                <span class="badge bg-danger rounded-pill js-action-pending-count">{{ $pendingReviewCount }} New</span>
                            </div>
                            <div class="list-group list-group-flush js-action-pending">
                                {{-- Pending damage reports and pending inspections, newest first
                                     (FR-10, FR-12). The pill above shows the TRUE total while this
                                     list shows the most recent few — the rows link into the module
                                     page, which is where the full list lives. --}}
                                @forelse ($pendingReviews as $item)
                                <a href="{{ route('inspections') }}" class="list-group-item list-group-item-action py-3">
                                    <div class="d-flex w-100 justify-content-between">
                                        <h6 class="mb-1 fw-bold">{{ $item['vehicle'] }}</h6>
                                        <small class="text-secondary">{{ $item['when'] }}</small>
                                    </div>
                                    <p class="mb-1 small">{{ $item['detail'] }}</p>
                                    <small class="text-danger fw-semibold">Action: Needs Review</small>
                                </a>
                                @empty
                                <div class="list-group-item py-4 text-center text-secondary small">Nothing is waiting for review.</div>
                                @endforelse
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="card border-0 shadow-sm rounded-3">
                            <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                                <h6 class="mb-0 fw-bold"><i class="bi bi-person-badge text-warning me-2"></i>Expiring Licenses</h6>
                                <span class="badge bg-warning text-dark rounded-pill js-action-licenses-count">{{ $expiringLicenseCount }} {{ Str::plural('Warning', $expiringLicenseCount) }}</span>
                            </div>
                            <div class="list-group list-group-flush js-action-licenses">
                                {{-- Expiring AND already-expired licences (FR-08), soonest first so
                                     the expired ones lead. Wider than the EXPIRING LICENSES card
                                     above, which counts only the warning window so that it matches
                                     the Drivers page's card of the same name. --}}
                                @forelse ($expiringLicenses as $license)
                                <a href="{{ route('drivers') }}" class="list-group-item list-group-item-action py-3">
                                    <div class="d-flex w-100 justify-content-between">
                                        <h6 class="mb-1 fw-bold">{{ $license['name'] }}</h6>
                                        <span class="badge {{ $license['status'] === 'Expired' ? 'bg-danger' : 'bg-warning text-dark' }}">{{ $license['status'] }}</span>
                                    </div>
                                    <p class="mb-1 small">License: {{ $license['license_number'] ?: '—' }}</p>
                                    <small class="text-secondary fw-semibold">{{ $license['detail'] }}</small>
                                </a>
                                @empty
                                <div class="list-group-item py-4 text-center text-secondary small">No licences need attention.</div>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>

@endsection
