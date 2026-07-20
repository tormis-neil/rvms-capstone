@extends('layouts.app')

@section('title', 'RVMS - Inspections & Damage')

@section('content')
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h3 class="fw-bold mb-0" style="color: var(--primary);">Inspections & Damage</h3>
                        <p class="text-secondary mb-0">Monitor daily BLOWBAGETS and review defect reports</p>
                    </div>
                </div>

                <!-- Daily Inspections Section -->
                <div class="d-flex align-items-center gap-2 mb-3">
                    <h5 class="fw-bold mb-0"><i class="bi bi-card-checklist me-2"></i>Daily BLOWBAGETS Inspections</h5>
                    <span class="badge badge-pending rounded-pill px-3 py-2 js-insp-pending">2 Pending Review</span>
                </div>
                <div class="card border-0 shadow-sm rounded-3 overflow-hidden mb-5">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="py-3 text-secondary fw-semibold small">DATE SUBMITTED</th>
                                    <th class="py-3 text-secondary fw-semibold small">VEHICLE & DRIVER</th>
                                    <th class="py-3 text-secondary fw-semibold small">RESULT</th>
                                    <th class="py-3 text-secondary fw-semibold small">REMARKS</th>
                                    <th class="py-3 text-secondary fw-semibold small">REVIEW STATUS</th>
                                    <th class="py-3 text-secondary fw-semibold small text-end">ACTIONS</th>
                                </tr>
                            </thead>
                            <tbody id="rows-inspections">
                                <tr>
                                    <td>
                                        <div class="fw-bold text-dark">Today</div>
                                        <div class="small text-secondary">07:30 AM</div>
                                    </td>
                                    <td>
                                        <div class="fw-bold text-dark">ABC-1234</div>
                                        <div class="small text-secondary">Juan Dela Cruz</div>
                                    </td>
                                    <td><span class="badge badge-operational px-3 py-2 rounded-pill">All OK</span></td>
                                    <td class="text-secondary"><em class="small">None</em></td>
                                    <td><span class="badge badge-pending px-3 py-2 rounded-pill">Pending</span></td>
                                    <td class="text-end">
                                        <button class="btn btn-sm btn-light border" data-bs-toggle="modal" data-bs-target="#viewChecklistModal">View Checklist</button>
                                        <button class="btn btn-sm bg-navy text-white fw-medium" data-bs-toggle="modal" data-bs-target="#reviewInspectionModal">Review</button>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <div class="fw-bold text-dark">Today</div>
                                        <div class="small text-secondary">07:05 AM</div>
                                    </td>
                                    <td>
                                        <div class="fw-bold text-dark">BCD-2310</div>
                                        <div class="small text-secondary">Ricardo Bautista</div>
                                    </td>
                                    <td><span class="badge badge-not-operational px-3 py-2 rounded-pill">Has Issue</span></td>
                                    <td class="text-secondary">Low tire pressure (front right)</td>
                                    <td><span class="badge badge-pending px-3 py-2 rounded-pill">Pending</span></td>
                                    <td class="text-end">
                                        <button class="btn btn-sm btn-light border" data-bs-toggle="modal" data-bs-target="#viewChecklistModal">View Checklist</button>
                                        <button class="btn btn-sm bg-navy text-white fw-medium" data-bs-toggle="modal" data-bs-target="#reviewInspectionModal">Review</button>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <div class="fw-bold text-dark">Today</div>
                                        <div class="small text-secondary">06:50 AM</div>
                                    </td>
                                    <td>
                                        <div class="fw-bold text-dark">GHI-6754</div>
                                        <div class="small text-secondary">Felipe Ramos</div>
                                    </td>
                                    <td><span class="badge badge-operational px-3 py-2 rounded-pill">All OK</span></td>
                                    <td class="text-secondary"><em class="small">None</em></td>
                                    <td><span class="badge badge-reviewed px-3 py-2 rounded-pill">Reviewed</span></td>
                                    <td class="text-end">
                                        <button class="btn btn-sm btn-light border" data-bs-toggle="modal" data-bs-target="#viewChecklistModal">View Checklist</button>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <div class="fw-bold text-dark">Yesterday</div>
                                        <div class="small text-secondary">07:15 AM</div>
                                    </td>
                                    <td>
                                        <div class="fw-bold text-dark">ABC-1234</div>
                                        <div class="small text-secondary">Juan Dela Cruz</div>
                                    </td>
                                    <td><span class="badge badge-not-operational px-3 py-2 rounded-pill">Has Issue</span></td>
                                    <td class="text-secondary">Brakes — unusual noise during braking</td>
                                    <td><span class="badge badge-reviewed px-3 py-2 rounded-pill">Reviewed</span></td>
                                    <td class="text-end">
                                        <button class="btn btn-sm btn-light border" data-bs-toggle="modal" data-bs-target="#viewChecklistModal">View Checklist</button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Frequently Reported Issues (aggregated from inspections & damage) -->
                <div class="d-flex align-items-center gap-2 mb-3">
                    <h5 class="fw-bold mb-0"><i class="bi bi-graph-up-arrow me-2"></i>Frequently Reported Issues</h5>
                    <span class="badge bg-light text-dark border rounded-pill px-3 py-2">This agency</span>
                </div>
                <div class="card border-0 shadow-sm rounded-3 mb-5">
                    <div class="card-body p-4">
                        <p class="text-secondary small mb-3">Recurring vehicle issues across recent BLOWBAGETS inspections and damage reports, ranked by how often they appear. Use this to spot fleet-wide maintenance patterns.</p>
                        <div id="freq-issues"></div>
                    </div>
                </div>

@endsection

@section('modals')
    <!-- View Checklist Modal -->
    <div class="modal fade" id="viewChecklistModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-light">
                    <h5 class="modal-title fw-bold">BLOWBAGETS Checklist</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row mb-4">
                        <div class="col-sm-6">
                            <p class="mb-1 text-secondary small">Vehicle</p>
                            <h6 class="fw-bold">ABC-1234 (Fire Truck)</h6>
                        </div>
                        <div class="col-sm-6 text-sm-end">
                            <p class="mb-1 text-secondary small">Submitted By</p>
                            <h6 class="fw-bold">Juan Dela Cruz</h6>
                        </div>
                    </div>
                    <h6 class="fw-bold border-bottom pb-2 mb-3">Standard Items (12)</h6>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6"><i class="bi bi-check-circle-fill text-success me-2"></i> Battery</div>
                        <div class="col-md-6"><i class="bi bi-check-circle-fill text-success me-2"></i> Lights</div>
                        <div class="col-md-6"><i class="bi bi-check-circle-fill text-success me-2"></i> Oil</div>
                        <div class="col-md-6"><i class="bi bi-check-circle-fill text-success me-2"></i> Water</div>
                        <div class="col-md-6"><i class="bi bi-check-circle-fill text-success me-2"></i> Brakes</div>
                        <div class="col-md-6"><i class="bi bi-check-circle-fill text-success me-2"></i> Air</div>
                        <div class="col-md-6"><i class="bi bi-check-circle-fill text-success me-2"></i> Gas</div>
                        <div class="col-md-6"><i class="bi bi-check-circle-fill text-success me-2"></i> Engine</div>
                        <div class="col-md-6"><i class="bi bi-check-circle-fill text-success me-2"></i> Tires</div>
                        <div class="col-md-6"><i class="bi bi-check-circle-fill text-success me-2"></i> Power Steering</div>
                        <div class="col-md-6"><i class="bi bi-check-circle-fill text-success me-2"></i> Horn/Siren</div>
                        <div class="col-md-6"><i class="bi bi-check-circle-fill text-success me-2"></i> Directional Signals</div>
                    </div>
                    <div id="checklist-extra">
                    <h6 class="fw-bold border-bottom pb-2 mb-3 text-warning">BFP Additional Items (2)</h6>
                    <div class="row g-3">
                        <div class="col-md-6"><i class="bi bi-check-circle-fill text-success me-2"></i> Hydraulic System</div>
                        <div class="col-md-6"><i class="bi bi-check-circle-fill text-success me-2"></i> Fire Pump</div>
                    </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Review Inspection Modal -->
    <div class="modal fade" id="reviewInspectionModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-navy text-white">
                    <h5 class="modal-title fw-bold">Review Inspection</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="d-flex justify-content-between align-items-center bg-light rounded-3 p-3 mb-3">
                        <div>
                            <div class="fw-bold" id="riVehicle">—</div>
                            <div class="small text-secondary"><span id="riDriver">—</span> &middot; <span id="riWhen">—</span></div>
                        </div>
                        <span class="badge px-3 py-2 rounded-pill" id="riResultBadge">—</span>
                    </div>
                    <div class="border rounded-3 p-3 mb-4">
                        <div class="small text-secondary fw-semibold mb-1">Driver's Submission</div>
                        <div class="fw-medium" id="riRemarks">—</div>
                    </div>
                    <p class="text-secondary small mb-4">Evaluate the findings, update the vehicle's operational status if action is required, and mark the inspection as Reviewed.</p>
                    <form>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Vehicle Status</label>
                            <select class="form-select">
                                <option>Leave as Operational (no action needed)</option>
                                <option>Not Operational (unsafe for deployment)</option>
                                <option>Under Preventive Maintenance (maintenance required)</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Admin Remarks (Optional)</label>
                            <textarea class="form-control" rows="3" placeholder="Enter review notes..."></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn bg-navy text-white" data-bs-dismiss="modal">Mark Reviewed & Update Status</button>
                </div>
            </div>
        </div>
    </div>
@endsection
