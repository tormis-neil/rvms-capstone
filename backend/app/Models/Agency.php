<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Agency extends Model
{
    protected $fillable = [
        'code',
        'name',
        'location',
        'contact_number',
        'email',
        'logo_path',
        'license_expiry_warning_days',
    ];

    protected function casts(): array
    {
        return [
            'license_expiry_warning_days' => 'integer',
        ];
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function drivers(): HasMany
    {
        return $this->users()->where('role', User::ROLE_DRIVER);
    }

    public function admins(): HasMany
    {
        return $this->users()->where('role', User::ROLE_ADMIN);
    }
}
