<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * Append-only system monitoring record for security and operational auditing.
 *
 * Deliberately does NOT use BelongsToAgency / AgencyScope: failed login and
 * lockout attempts for unrecognized emails have agency_id = null, and hiding
 * them via a global scope would blind administrators to external probing.
 */
class ActivityLog extends Model
{
    public const UPDATED_AT = null;

    public const TYPE_LOGIN_SUCCESS = 'login_success';

    public const TYPE_LOGIN_FAILED = 'login_failed';

    public const TYPE_LOGIN_LOCKOUT = 'login_lockout';

    public const TYPE_LOGOUT = 'logout';

    public const TYPE_VEHICLE_STATUS_UPDATED = 'vehicle_status_updated';

    /** @var array<string, string> */
    public const TYPES = [
        self::TYPE_LOGIN_SUCCESS => 'Successful Login',
        self::TYPE_LOGIN_FAILED => 'Failed Login',
        self::TYPE_LOGIN_LOCKOUT => 'Login Lockout',
        self::TYPE_LOGOUT => 'Logout',
        self::TYPE_VEHICLE_STATUS_UPDATED => 'Vehicle Status Updated',
    ];

    public const SEVERITY_INFO = 'info';

    public const SEVERITY_WARNING = 'warning';

    public const SEVERITY_HIGH = 'high';

    /** @var array<string, string> */
    public const SEVERITIES = [
        self::SEVERITY_INFO => 'Info',
        self::SEVERITY_WARNING => 'Warning',
        self::SEVERITY_HIGH => 'High',
    ];

    public const SOURCE_WEB = 'Web Portal';

    public const SOURCE_MOBILE_API = 'Mobile API';

    public const SOURCE_SYSTEM = 'System';

    /** @var list<string> */
    public const SOURCES = [
        self::SOURCE_WEB,
        self::SOURCE_MOBILE_API,
        self::SOURCE_SYSTEM,
    ];

    protected $fillable = [
        'agency_id',
        'user_id',
        'actor_name',
        'actor_email',
        'actor_role',
        'event_type',
        'severity',
        'source',
        'ip_address',
        'user_agent',
        'target_label',
        'description',
        'metadata',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Restrict visible monitoring rows to the administrator's own agency
     * plus unscoped external probe events (unrecognized-email attempts).
     *
     * @param  Builder<ActivityLog>  $query
     * @return Builder<ActivityLog>
     */
    public function scopeVisibleToAgency(Builder $query, int $agencyId): Builder
    {
        return $query->where(fn (Builder $q) => $q
            ->where('agency_id', $agencyId)
            ->orWhereNull('agency_id'));
    }

    public function eventLabel(): string
    {
        return self::TYPES[$this->event_type] ?? Str::headline((string) $this->event_type);
    }

    public function eventBadgeClass(): string
    {
        return match ($this->event_type) {
            self::TYPE_LOGIN_SUCCESS => 'bg-success-subtle text-success border border-success-subtle',
            self::TYPE_LOGIN_FAILED => 'bg-warning-subtle text-warning-emphasis border border-warning-subtle',
            self::TYPE_LOGIN_LOCKOUT => 'bg-danger-subtle text-danger border border-danger-subtle',
            self::TYPE_LOGOUT => 'bg-secondary-subtle text-secondary border border-secondary-subtle',
            self::TYPE_VEHICLE_STATUS_UPDATED => 'bg-primary-subtle text-primary border border-primary-subtle',
            default => 'bg-light text-dark border',
        };
    }

    public function eventIcon(): string
    {
        return match ($this->event_type) {
            self::TYPE_LOGIN_SUCCESS => 'bi-box-arrow-in-right',
            self::TYPE_LOGIN_FAILED => 'bi-shield-exclamation',
            self::TYPE_LOGIN_LOCKOUT => 'bi-shield-lock-fill',
            self::TYPE_LOGOUT => 'bi-box-arrow-right',
            self::TYPE_VEHICLE_STATUS_UPDATED => 'bi-truck',
            default => 'bi-activity',
        };
    }

    public function severityLabel(): string
    {
        return self::SEVERITIES[$this->severity] ?? ucfirst((string) $this->severity);
    }

    public function severityBadgeClass(): string
    {
        return match ($this->severity) {
            self::SEVERITY_HIGH => 'bg-danger text-white',
            self::SEVERITY_WARNING => 'bg-warning text-dark',
            default => 'bg-info-subtle text-info-emphasis border border-info-subtle',
        };
    }

    public function timestampLabel(): string
    {
        if ($this->created_at === null) {
            return '—';
        }

        return $this->created_at
            ->timezone((string) config('app.timezone', 'Asia/Manila'))
            ->format('M j, Y · g:i:s A');
    }

    public function isUnrecognizedAccount(): bool
    {
        return $this->agency_id === null && $this->user_id === null;
    }
}
