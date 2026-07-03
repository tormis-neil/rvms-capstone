@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="fw-bold mb-0" style="color: var(--primary);">Fleet Overview</h3>
        <p class="text-secondary mb-0">Today: {{ now()->format('F j, Y') }}</p>
    </div>

    <div class="card card-stat p-4">
        <div class="d-flex align-items-center gap-3">
            <div class="bg-primary bg-opacity-10 text-primary rounded p-3">
                <i class="bi bi-grid-1x2-fill fs-3"></i>
            </div>
            <div>
                <h5 class="fw-bold mb-1">Welcome, {{ auth()->user()->name }}</h5>
                <p class="text-secondary mb-0">
                    You are signed in to the {{ auth()->user()->agency->name }} ({{ auth()->user()->agency->code }})
                    admin dashboard. Fleet summary cards, action-required lists, and module pages
                    are delivered with their respective phases.
                </p>
            </div>
        </div>
    </div>
@endsection
