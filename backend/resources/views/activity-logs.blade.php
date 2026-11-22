@extends('layouts.app')

@section('title', 'RVMS - System Monitor')

@section('content')
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h3 class="fw-bold mb-0" style="color: var(--primary);">System Monitor</h3>
                        <p class="text-secondary mb-0">Security authentication events and operational vehicle status activity (Asia/Manila)</p>
                    </div>
                    <a href="{{ request()->fullUrl() }}" class="btn btn-navy text-white fw-medium px-4 py-2 bg-navy rounded-3">
                        <i class="bi bi-arrow-clockwise me-2"></i>Refresh
                    </a>
                </div>

                <!-- Summary Metrics -->
                <div class="row row-cols-1 row-cols-sm-2 row-cols-lg-4 g-3 mb-4">
                    <div class="col">
                        <div class="card card-stat h-100 p-3" style="border-left: 4px solid var(--primary);">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <p class="text-secondary small fw-semibold mb-1">EVENTS TODAY</p>
                                    <h2 class="fw-bold mb-0">{{ $summary['events_today'] }}</h2>
                                </div>
                                <div class="bg-primary bg-opacity-10 text-primary rounded p-2">
                                    <i class="bi bi-activity fs-4"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col">
                        <div class="card card-stat h-100 p-3" style="border-left: 4px solid #16A34A;">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <p class="text-secondary small fw-semibold mb-1">SUCCESSFUL LOGINS</p>
                                    <h2 class="fw-bold mb-0">{{ $summary['successful_logins'] }}</h2>
                                </div>
                                <div class="rounded p-2" style="background-color: rgba(22, 163, 74, 0.1); color: #16A34A;">
                                    <i class="bi bi-box-arrow-in-right fs-4"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col">
                        <div class="card card-stat h-100 p-3" style="border-left: 4px solid #DC2626;">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <p class="text-secondary small fw-semibold mb-1">FAILED LOGINS</p>
                                    <h2 class="fw-bold mb-0">{{ $summary['failed_logins'] }}</h2>
                                </div>
                                <div class="rounded p-2" style="background-color: rgba(220, 38, 38, 0.1); color: #DC2626;">
                                    <i class="bi bi-shield-exclamation fs-4"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col">
                        <div class="card card-stat h-100 p-3" style="border-left: 4px solid #D97706;">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <p class="text-secondary small fw-semibold mb-1">VEHICLE STATUS UPDATES</p>
                                    <h2 class="fw-bold mb-0">{{ $summary['vehicle_status_updates'] }}</h2>
                                </div>
                                <div class="rounded p-2" style="background-color: rgba(217, 119, 6, 0.1); color: #D97706;">
                                    <i class="bi bi-truck fs-4"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filters -->
                <div class="card border-0 shadow-sm rounded-3 mb-4">
                    <div class="card-body p-3">
                        <form method="GET" action="{{ route('activity-logs') }}">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="Search user, email, IP, plate, details...">
                            </div>
                            <div class="col-md-2">
                                <select class="form-select" name="event_type">
                                    <option value="">All Event Types</option>
                                    @foreach ($eventTypes as $code => $label)
                                    <option value="{{ $code }}" @selected(request('event_type') === $code)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <select class="form-select" name="severity">
                                    <option value="">All Severities</option>
                                    @foreach ($severities as $code => $label)
                                    <option value="{{ $code }}" @selected(request('severity') === $code)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <select class="form-select" name="source">
                                    <option value="">All Sources</option>
                                    @foreach ($sources as $sourceOption)
                                    <option value="{{ $sourceOption }}" @selected(request('source') === $sourceOption)>{{ $sourceOption }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2 d-flex gap-2">
                                <button type="submit" class="btn btn-outline-secondary w-100">Filter</button>
                                @if (request()->filled('search') || request()->filled('event_type') || request()->filled('severity') || request()->filled('source'))
                                <a href="{{ route('activity-logs') }}" class="btn btn-light border">Reset</a>
                                @endif
                            </div>
                        </div>
                        </form>
                    </div>
                </div>

                <!-- Activity Event Table -->
                <div class="card border-0 shadow-sm rounded-3 overflow-hidden">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="py-3 text-secondary fw-semibold small">DATE &amp; TIME</th>
                                    <th class="py-3 text-secondary fw-semibold small">EVENT TYPE</th>
                                    <th class="py-3 text-secondary fw-semibold small">SEVERITY</th>
                                    <th class="py-3 text-secondary fw-semibold small">USER / ACTOR</th>
                                    <th class="py-3 text-secondary fw-semibold small">SOURCE &amp; IP</th>
                                    <th class="py-3 text-secondary fw-semibold small">DETAILS</th>
                                    <th class="py-3 text-secondary fw-semibold small text-end">ACTIONS</th>
                                </tr>
                            </thead>
                            <tbody id="rows-activity-logs">
                                @forelse ($logs as $log)
                                @php
                                    $metadataJson = $log->metadata
                                        ? json_encode($log->metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
                                        : '';
                                    $accountStatus = $log->isUnrecognizedAccount()
                                        ? 'Unrecognized Account'
                                        : ucfirst((string) ($log->actor_role ?: 'system'));
                                @endphp
                                <tr data-id="{{ $log->id }}"
                                    data-timestamp="{{ $log->timestampLabel() }}"
                                    data-event-label="{{ $log->eventLabel() }}"
                                    data-event-type="{{ $log->event_type }}"
                                    data-severity-label="{{ $log->severityLabel() }}"
                                    data-actor-name="{{ $log->isUnrecognizedAccount() ? 'Unrecognized Account' : ($log->actor_name ?? 'System') }}"
                                    data-actor-email="{{ $log->actor_email ?? '—' }}"
                                    data-actor-role="{{ ucfirst((string) ($log->actor_role ?? 'unknown')) }}"
                                    data-account-status="{{ $accountStatus }}"
                                    data-source="{{ $log->source }}"
                                    data-ip="{{ $log->ip_address ?? '—' }}"
                                    data-target="{{ $log->target_label ?? '—' }}"
                                    data-description="{{ $log->description }}"
                                    data-user-agent="{{ $log->user_agent ?? '—' }}"
                                    data-metadata="{{ $metadataJson }}">
                                    <td>
                                        <div class="fw-medium text-nowrap">{{ $log->timestampLabel() }}</div>
                                        <div class="small text-secondary">{{ $log->created_at?->diffForHumans() ?? '—' }}</div>
                                    </td>
                                    <td>
                                        <span class="badge status-badge {{ $log->eventBadgeClass() }} px-3 py-2 rounded-pill">
                                            <i class="bi {{ $log->eventIcon() }} me-1"></i>{{ $log->eventLabel() }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge status-badge {{ $log->severityBadgeClass() }} px-3 py-2 rounded-pill">{{ $log->severityLabel() }}</span>
                                    </td>
                                    <td>
                                        @if ($log->isUnrecognizedAccount())
                                        <div class="fw-semibold text-danger">Unrecognized Account</div>
                                        <div class="small text-secondary">{{ $log->actor_email ?? '—' }}</div>
                                        @else
                                        <div class="fw-semibold">{{ $log->actor_name ?? 'System' }}</div>
                                        <div class="small text-secondary">{{ $log->actor_email ?? '—' }} · {{ ucfirst((string) ($log->actor_role ?? 'system')) }}</div>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="fw-medium">{{ $log->source }}</div>
                                        <div class="small text-secondary font-monospace">{{ $log->ip_address ?? '—' }}</div>
                                    </td>
                                    <td>
                                        <div>{{ $log->description }}</div>
                                        @if ($log->target_label)
                                        <div class="small text-secondary">Target: {{ $log->target_label }}</div>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <div class="d-flex gap-2 justify-content-end">
                                            <button type="button" class="btn btn-sm btn-light border js-view-log" title="View Event Details" data-bs-toggle="modal" data-bs-target="#viewActivityLogModal">
                                                <i class="bi bi-eye"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="7" class="text-center text-secondary py-4">No activity events recorded yet.</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @include('partials.table-footer', ['paginator' => $logs, 'label' => 'events'])
                </div>
@endsection

@section('modals')
    <!-- Normalized Event Details Modal -->
    <div class="modal fade" id="viewActivityLogModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content border-0 rounded-3">
                <div class="modal-header bg-navy text-white">
                    <h5 class="modal-title fw-bold"><i class="bi bi-shield-check me-2"></i>Normalized Event Details</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <div class="small text-secondary fw-semibold text-uppercase">Event Type</div>
                            <div class="fw-bold" id="alEventLabel">—</div>
                        </div>
                        <div class="col-md-3">
                            <div class="small text-secondary fw-semibold text-uppercase">Event Code</div>
                            <div class="font-monospace small" id="alEventType">—</div>
                        </div>
                        <div class="col-md-3">
                            <div class="small text-secondary fw-semibold text-uppercase">Severity</div>
                            <div class="fw-semibold" id="alSeverityLabel">—</div>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <div class="small text-secondary fw-semibold text-uppercase">Date &amp; Time (Asia/Manila)</div>
                            <div class="fw-medium" id="alTimestamp">—</div>
                        </div>
                        <div class="col-md-6">
                            <div class="small text-secondary fw-semibold text-uppercase">Target</div>
                            <div class="fw-medium" id="alTarget">—</div>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <div class="small text-secondary fw-semibold text-uppercase">Actor Name</div>
                            <div class="fw-medium" id="alActorName">—</div>
                        </div>
                        <div class="col-md-4">
                            <div class="small text-secondary fw-semibold text-uppercase">Email / Identifier</div>
                            <div class="fw-medium text-break" id="alActorEmail">—</div>
                        </div>
                        <div class="col-md-4">
                            <div class="small text-secondary fw-semibold text-uppercase">Role / Account Status</div>
                            <div class="fw-medium" id="alAccountStatus">—</div>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <div class="small text-secondary fw-semibold text-uppercase">Source Channel</div>
                            <div class="fw-medium" id="alSource">—</div>
                        </div>
                        <div class="col-md-6">
                            <div class="small text-secondary fw-semibold text-uppercase">IP Address</div>
                            <div class="font-monospace" id="alIp">—</div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="small text-secondary fw-semibold text-uppercase">Description</div>
                        <div id="alDescription">—</div>
                    </div>

                    <div class="mb-3">
                        <div class="small text-secondary fw-semibold text-uppercase">User-Agent</div>
                        <div class="small text-secondary text-break font-monospace" id="alUserAgent">—</div>
                    </div>

                    <div>
                        <div class="small text-secondary fw-semibold text-uppercase mb-1">Normalized Metadata (JSON)</div>
                        <pre id="alMetadata" class="activity-metadata-pre bg-light border rounded-3 p-3 small mb-0">None</pre>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light border fw-medium px-4" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const modal = document.getElementById('viewActivityLogModal');
    if (!modal) {
        return;
    }

    const fields = {
        alTimestamp: 'timestamp',
        alEventLabel: 'eventLabel',
        alEventType: 'eventType',
        alSeverityLabel: 'severityLabel',
        alActorName: 'actorName',
        alActorEmail: 'actorEmail',
        alAccountStatus: 'accountStatus',
        alSource: 'source',
        alIp: 'ip',
        alTarget: 'target',
        alDescription: 'description',
        alUserAgent: 'userAgent',
    };

    modal.addEventListener('show.bs.modal', (event) => {
        const trigger = event.relatedTarget;
        const row = trigger ? trigger.closest('tr') : null;
        if (!row) {
            return;
        }

        const data = row.dataset;
        Object.entries(fields).forEach(([elementId, key]) => {
            const el = document.getElementById(elementId);
            if (el) {
                el.textContent = data[key] || '—';
            }
        });

        const metaEl = document.getElementById('alMetadata');
        if (metaEl) {
            metaEl.textContent = data.metadata || 'None';
        }
    });
});
</script>
@endsection
