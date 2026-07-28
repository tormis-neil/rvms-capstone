<?php

namespace App\Models;

use App\Models\Concerns\BelongsToAgency;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * In-app notification addressed to one user (FR-21). Agency-scoped.
 *
 * The presentation map below is the prototype's own NOTIF table from
 * `web/assets/js/agency.js` (icon, tone, link per kind), extended for the two
 * types the prototype has no kind for: New_Access_Request (approval was added
 * to scope after the prototype was built, FR-03) and Vehicle_Status_Update
 * (driver-facing, so it never appears on the admin bell).
 */
class Notification extends Model
{
    use BelongsToAgency;
    use HasFactory;

    /** Driver-facing (FR-21). */
    public const TYPE_PM_REMINDER = 'PM_Reminder';

    public const TYPE_VEHICLE_STATUS_UPDATE = 'Vehicle_Status_Update';

    /** Admin-facing (FR-21, FR-03, FR-09). */
    public const TYPE_NEW_DAMAGE_REPORT = 'New_Damage_Report';

    /**
     * Raised only when a submitted inspection reports one or more items as Has
     * Issue — never on an all-OK submission. A driver submits daily per vehicle,
     * so alerting on every submission would bury the urgent notifications.
     */
    public const TYPE_INSPECTION_FLAGGED = 'Inspection_Flagged';

    public const TYPE_LICENSE_EXPIRING = 'License_Expiring';

    public const TYPE_LICENSE_EXPIRED = 'License_Expired';

    public const TYPE_PM_DUE_SOON = 'PM_Due_Soon';

    public const TYPE_PM_DUE = 'PM_Due';

    public const TYPE_NEW_ACCESS_REQUEST = 'New_Access_Request';

    public const TYPES = [
        self::TYPE_PM_REMINDER,
        self::TYPE_VEHICLE_STATUS_UPDATE,
        self::TYPE_NEW_DAMAGE_REPORT,
        self::TYPE_INSPECTION_FLAGGED,
        self::TYPE_LICENSE_EXPIRING,
        self::TYPE_LICENSE_EXPIRED,
        self::TYPE_PM_DUE_SOON,
        self::TYPE_PM_DUE,
        self::TYPE_NEW_ACCESS_REQUEST,
    ];

    /**
     * icon/tone/route per type — the prototype's NOTIF map (agency.js).
     *
     * @var array<string, array{icon: string, tone: string, route: string}>
     */
    public const PRESENTATION = [
        self::TYPE_NEW_DAMAGE_REPORT => ['icon' => 'bi-exclamation-triangle', 'tone' => 'danger', 'route' => 'inspections'],
        // The prototype's inspection icon, but `warning` rather than its
        // `primary`: unlike a routine submission, a flagged one is actionable.
        self::TYPE_INSPECTION_FLAGGED => ['icon' => 'bi-clipboard-check', 'tone' => 'warning', 'route' => 'inspections'],
        self::TYPE_LICENSE_EXPIRING => ['icon' => 'bi-person-badge', 'tone' => 'warning', 'route' => 'drivers'],
        self::TYPE_LICENSE_EXPIRED => ['icon' => 'bi-person-badge', 'tone' => 'danger', 'route' => 'drivers'],
        self::TYPE_PM_DUE_SOON => ['icon' => 'bi-wrench-adjustable', 'tone' => 'warning', 'route' => 'pm'],
        self::TYPE_PM_DUE => ['icon' => 'bi-wrench-adjustable', 'tone' => 'danger', 'route' => 'pm'],
        self::TYPE_PM_REMINDER => ['icon' => 'bi-wrench-adjustable', 'tone' => 'warning', 'route' => 'pm'],
        self::TYPE_NEW_ACCESS_REQUEST => ['icon' => 'bi-person-plus', 'tone' => 'primary', 'route' => 'drivers'],
        self::TYPE_VEHICLE_STATUS_UPDATE => ['icon' => 'bi-truck', 'tone' => 'primary', 'route' => 'vehicles'],
    ];

    protected $fillable = [
        'agency_id',
        'user_id',
        'type',
        'title',
        'message',
        'data',
        'is_read',
        'read_at',
    ];

    protected function casts(): array
    {
        return [
            'data' => 'array',
            'is_read' => 'boolean',
            'read_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @param  Builder<Notification>  $query */
    public function scopeUnread(Builder $query): Builder
    {
        return $query->where('is_read', false);
    }

    /** Only ever this user's own notifications (FR-21). */
    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    public function icon(): string
    {
        return self::PRESENTATION[$this->type]['icon'] ?? 'bi-bell';
    }

    public function tone(): string
    {
        return self::PRESENTATION[$this->type]['tone'] ?? 'secondary';
    }

    /** Where clicking the notification takes the admin. */
    public function link(): string
    {
        $route = self::PRESENTATION[$this->type]['route'] ?? 'dashboard';

        return route($route);
    }

    /**
     * The prototype groups the page under Today / Yesterday / Earlier
     * (agency.js renderNotificationsPage).
     */
    public function group(): string
    {
        if ($this->created_at?->isToday()) {
            return 'Today';
        }

        if ($this->created_at?->isYesterday()) {
            return 'Yesterday';
        }

        return 'Earlier';
    }

    /** "8:10 AM" for today/yesterday, "May 28" for anything older. */
    public function timeLabel(): string
    {
        if ($this->group() === 'Earlier') {
            return $this->created_at?->format('M j') ?? '—';
        }

        return $this->created_at?->format('g:i A') ?? '—';
    }
}
