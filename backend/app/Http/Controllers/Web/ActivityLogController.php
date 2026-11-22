<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Admin System Monitor page (IAS 2 activity logging & SIEM visibility).
 *
 * Read-only by design: exposes normalized authentication and vehicle status
 * events scoped to the administrator's own agency plus unscoped external probe
 * attempts (agency_id = null).
 */
class ActivityLogController extends Controller
{
    public function index(Request $request): View
    {
        $agencyId = $request->user()->agency_id;

        $eventsToday = ActivityLog::query()
            ->visibleToAgency($agencyId)
            ->monitoredTypes()
            ->where('created_at', '>=', now()->startOfDay())
            ->count();

        $byType = ActivityLog::query()
            ->visibleToAgency($agencyId)
            ->monitoredTypes()
            ->selectRaw('event_type, COUNT(*) as total')
            ->groupBy('event_type')
            ->pluck('total', 'event_type');

        $summary = [
            'events_today' => $eventsToday,
            'successful_logins' => (int) $byType->get(ActivityLog::TYPE_LOGIN_SUCCESS, 0),
            'failed_logins' => (int) $byType->get(ActivityLog::TYPE_LOGIN_FAILED, 0),
            'vehicle_status_updates' => (int) $byType->get(ActivityLog::TYPE_VEHICLE_STATUS_UPDATED, 0),
        ];

        $search = trim((string) $request->input('search', ''));
        $eventType = (string) $request->input('event_type', '');
        $severity = (string) $request->input('severity', '');
        $source = (string) $request->input('source', '');

        $logs = ActivityLog::query()
            ->visibleToAgency($agencyId)
            ->monitoredTypes()
            ->when($search !== '', function ($query) use ($search) {
                $term = '%'.$search.'%';
                $query->where(fn ($q) => $q
                    ->where('actor_name', 'like', $term)
                    ->orWhere('actor_email', 'like', $term)
                    ->orWhere('ip_address', 'like', $term)
                    ->orWhere('target_label', 'like', $term)
                    ->orWhere('description', 'like', $term));
            })
            ->when($eventType !== '' && array_key_exists($eventType, ActivityLog::TYPES), fn ($q) => $q->where('event_type', $eventType))
            ->when($severity !== '' && array_key_exists($severity, ActivityLog::SEVERITIES), fn ($q) => $q->where('severity', $severity))
            ->when($source !== '' && in_array($source, ActivityLog::SOURCES, true), fn ($q) => $q->where('source', $source))
            ->latest('created_at')
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return view('activity-logs', [
            'logs' => $logs,
            'summary' => $summary,
            'eventTypes' => ActivityLog::TYPES,
            'severities' => ActivityLog::SEVERITIES,
            'sources' => ActivityLog::SOURCES,
        ]);
    }
}
