@extends('layouts.app')

@section('title', 'RVMS - Notifications')

{{-- BLOCK A: copied VERBATIM from the prototype (web/pages/notifications.html).
     That file's body is an empty <div id="notif-groups"></div> filled at runtime by
     agency.js renderNotificationsPage(); the markup below is that generator's exact
     output for its own BFP demo data, so the page renders identically without JS.
     Hardcoded rows and dead links are the prototype's own — wired live in Block B. --}}
@section('content')
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h3 class="fw-bold mb-0" style="color: var(--primary);">Notifications</h3>
                        <p class="text-secondary mb-0">All alerts and submissions for your agency</p>
                    </div>
                    <button class="btn btn-light border fw-medium px-4 py-2 rounded-3">
                        <i class="bi bi-check2-all me-2"></i>Mark All as Read
                    </button>
                </div>
                <h6 class="fw-bold text-secondary text-uppercase small mb-3">Today</h6>
                <div class="card border-0 shadow-sm rounded-3 overflow-hidden mb-4"><div class="list-group list-group-flush">
                    <a href="inspections-damage.html" class="list-group-item list-group-item-action d-flex align-items-start gap-3 py-3">
                        <span class="notif-icon rounded-circle bg-danger bg-opacity-10 text-danger d-inline-flex justify-content-center align-items-center mt-1"><i class="bi bi-exclamation-triangle"></i></span>
                        <span class="flex-grow-1"><span class="fw-bold d-block">New Damage Report Submitted</span><span class="small text-secondary d-block">ABC-1234 — Cracked side mirror (driver side).</span></span>
                        <span class="small text-secondary text-nowrap">8:10 AM</span>
                    </a>
                    <a href="inspections-damage.html" class="list-group-item list-group-item-action d-flex align-items-start gap-3 py-3">
                        <span class="notif-icon rounded-circle bg-primary bg-opacity-10 text-primary d-inline-flex justify-content-center align-items-center mt-1"><i class="bi bi-clipboard-check"></i></span>
                        <span class="flex-grow-1"><span class="fw-bold d-block">Daily Inspection Submitted</span><span class="small text-secondary d-block">BCD-2310 — 1 item flagged: low tire pressure.</span></span>
                        <span class="small text-secondary text-nowrap">7:05 AM</span>
                    </a>
                    <a href="inspections-damage.html" class="list-group-item list-group-item-action d-flex align-items-start gap-3 py-3">
                        <span class="notif-icon rounded-circle bg-primary bg-opacity-10 text-primary d-inline-flex justify-content-center align-items-center mt-1"><i class="bi bi-clipboard-check"></i></span>
                        <span class="flex-grow-1"><span class="fw-bold d-block">Daily Inspection Submitted</span><span class="small text-secondary d-block">GHI-6754 — All items OK.</span></span>
                        <span class="small text-secondary text-nowrap">6:50 AM</span>
                    </a>
                </div></div>
                <h6 class="fw-bold text-secondary text-uppercase small mb-3">Yesterday</h6>
                <div class="card border-0 shadow-sm rounded-3 overflow-hidden mb-4"><div class="list-group list-group-flush">
                    <a href="drivers.html" class="list-group-item list-group-item-action d-flex align-items-start gap-3 py-3">
                        <span class="notif-icon rounded-circle bg-warning bg-opacity-10 text-warning d-inline-flex justify-content-center align-items-center mt-1"><i class="bi bi-person-badge"></i></span>
                        <span class="flex-grow-1"><span class="fw-bold d-block">License Expiring Soon</span><span class="small text-secondary d-block">Ricardo Bautista — license expires Jul 8, 2026.</span></span>
                        <span class="small text-secondary text-nowrap">9:00 AM</span>
                    </a>
                    <a href="pm.html" class="list-group-item list-group-item-action d-flex align-items-start gap-3 py-3">
                        <span class="notif-icon rounded-circle bg-warning bg-opacity-10 text-warning d-inline-flex justify-content-center align-items-center mt-1"><i class="bi bi-wrench-adjustable"></i></span>
                        <span class="flex-grow-1"><span class="fw-bold d-block">PM Due</span><span class="small text-secondary d-block">EFG-4532 — engine service has reached its due mileage.</span></span>
                        <span class="small text-secondary text-nowrap">8:00 AM</span>
                    </a>
                </div></div>
                <h6 class="fw-bold text-secondary text-uppercase small mb-3">Earlier</h6>
                <div class="card border-0 shadow-sm rounded-3 overflow-hidden mb-4"><div class="list-group list-group-flush">
                    <a href="drivers.html" class="list-group-item list-group-item-action d-flex align-items-start gap-3 py-3">
                        <span class="notif-icon rounded-circle bg-danger bg-opacity-10 text-danger d-inline-flex justify-content-center align-items-center mt-1"><i class="bi bi-person-badge"></i></span>
                        <span class="flex-grow-1"><span class="fw-bold d-block">License Expired</span><span class="small text-secondary d-block">Ramon Cruz — license expired on May 28, 2026.</span></span>
                        <span class="small text-secondary text-nowrap">May 28</span>
                    </a>
                </div></div>
@endsection
