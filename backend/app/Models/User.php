<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
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

    /** Vehicle(s) this driver is the primary driver of — may be more than one. */
    public function vehicles(): HasMany
    {
        return $this->hasMany(Vehicle::class, 'assigned_driver_id');
    }

    /** Only driver accounts. */
    public function scopeDrivers(Builder $query): Builder
    {
        return $query->where('role', self::ROLE_DRIVER);
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

    /**
     * License status against the driver's agency configurable threshold
     * (FR-08): Expired / Expiring Soon / Valid. Null when no license on file.
     */
    public function licenseStatus(): ?string
    {
        if ($this->license_expiry_date === null) {
            return null;
        }

        $today = Carbon::today();

        if ($this->license_expiry_date->lt($today)) {
            return 'Expired';
        }

        $warningDays = $this->agency?->license_expiry_warning_days ?? 30;

        if ($this->license_expiry_date->lte($today->copy()->addDays($warningDays))) {
            return 'Expiring Soon';
        }

        return 'Valid';
    }
}
