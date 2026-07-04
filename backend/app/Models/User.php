<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    public const ROLE_ADMIN = 'admin';
    public const ROLE_DRIVER = 'driver';

    public const STATUS_PENDING = 'pending';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_REJECTED = 'rejected';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'agency_id',
        'role',
        'name',
        'email',
        'password',
        'status',
        'license_number',
        'license_expiry_date',
        'fcm_token',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'fcm_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'license_expiry_date' => 'date',
            'password' => 'hashed',
        ];
    }

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }

    /** Only driver accounts. */
    public function scopeDrivers(Builder $query): Builder
    {
        return $query->where('role', self::ROLE_DRIVER);
    }

    /**
     * Licenses already past their expiry date (FR-08).
     */
    public function scopeExpired(Builder $query): Builder
    {
        return $query
            ->whereNotNull('license_expiry_date')
            ->whereDate('license_expiry_date', '<', Carbon::today()->toDateString());
    }

    /**
     * Licenses expiring within `$withinDays` days from today, not yet expired
     * (FR-08). The window comes from the agency's configurable
     * `license_expiry_warning_days` column, passed in by the caller — kept in
     * PHP so it works identically on MySQL and the SQLite test database.
     */
    public function scopeExpiringSoon(Builder $query, int $withinDays): Builder
    {
        $today = Carbon::today();

        return $query
            ->whereNotNull('license_expiry_date')
            ->whereDate('license_expiry_date', '>=', $today->toDateString())
            ->whereDate('license_expiry_date', '<=', $today->copy()->addDays($withinDays)->toDateString());
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isDriver(): bool
    {
        return $this->role === self::ROLE_DRIVER;
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }
}
