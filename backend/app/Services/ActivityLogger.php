<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * Centralized, fail-safe recorder for security and operational activity logs.
 *
 * Normalizes events emitted from the Web Admin Portal, Mobile API, and
 * internal domain services while guaranteeing that credentials/tokens are never
 * stored and that a logging failure can never block core operations.
 */
class ActivityLogger
{
    /** @var list<string> */
    private const SENSITIVE_KEY_FRAGMENTS = [
        'password',
        'token',
        'secret',
        'authorization',
        'cookie',
    ];

    public function logLoginSuccess(Request $request, User $user, string $source): ?ActivityLog
    {
        $normalizedEmail = $this->normalizeEmail((string) $user->email);

        return $this->record([
            'agency_id' => $user->agency_id,
            'user_id' => $user->id,
            'actor_name' => $user->name,
            'actor_email' => $normalizedEmail,
            'actor_role' => $user->role,
            'event_type' => ActivityLog::TYPE_LOGIN_SUCCESS,
            'severity' => ActivityLog::SEVERITY_INFO,
            'source' => $source,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'target_label' => $normalizedEmail,
            'description' => "{$user->name} signed in via {$source}.",
            'metadata' => [
                'role' => $user->role,
                'source' => $source,
            ],
        ]);
    }

    public function logLoginFailed(
        Request $request,
        string $email,
        string $source,
        string $reason,
        ?User $matchedUser = null,
    ): ?ActivityLog {
        $actor = $this->resolveAuthActor($email, $matchedUser);
        $label = $actor['recognized'] ? $actor['actor_email'] : "unrecognized account {$actor['actor_email']}";

        return $this->record([
            'agency_id' => $actor['agency_id'],
            'user_id' => $actor['user_id'],
            'actor_name' => $actor['actor_name'],
            'actor_email' => $actor['actor_email'],
            'actor_role' => $actor['actor_role'],
            'event_type' => ActivityLog::TYPE_LOGIN_FAILED,
            'severity' => ActivityLog::SEVERITY_WARNING,
            'source' => $source,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'target_label' => $actor['actor_email'],
            'description' => "Failed {$source} login for {$label} ({$reason}).",
            'metadata' => [
                'reason' => $reason,
                'account_recognized' => $actor['recognized'],
                'source' => $source,
            ],
        ]);
    }

    public function logLogout(Request $request, User $user, string $source): ?ActivityLog
    {
        $normalizedEmail = $this->normalizeEmail((string) $user->email);

        return $this->record([
            'agency_id' => $user->agency_id,
            'user_id' => $user->id,
            'actor_name' => $user->name,
            'actor_email' => $normalizedEmail,
            'actor_role' => $user->role,
            'event_type' => ActivityLog::TYPE_LOGOUT,
            'severity' => ActivityLog::SEVERITY_INFO,
            'source' => $source,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'target_label' => $normalizedEmail,
            'description' => "{$user->name} signed out from {$source}.",
            'metadata' => [
                'role' => $user->role,
                'source' => $source,
            ],
        ]);
    }

    public function logVehicleStatusUpdated(
        Vehicle $vehicle,
        ?string $previousStatus,
        string $newStatus,
        string $moduleSource,
        ?User $actor = null,
        ?Request $request = null,
    ): ?ActivityLog {
        $resolvedActor = $actor ?? Auth::user();
        $actingUser = $resolvedActor instanceof User ? $resolvedActor : null;
        $req = $this->resolveActiveRequest($request);

        $source = match (true) {
            $req === null => ActivityLog::SOURCE_SYSTEM,
            $req->is('api/*') => ActivityLog::SOURCE_MOBILE_API,
            default => ActivityLog::SOURCE_WEB,
        };

        $severity = $newStatus === Vehicle::STATUS_NOT_OPERATIONAL
            ? ActivityLog::SEVERITY_WARNING
            : ActivityLog::SEVERITY_INFO;

        $fromLabel = $previousStatus ?? 'Unset';

        return $this->record([
            'agency_id' => $vehicle->agency_id,
            'user_id' => $actingUser?->id,
            'actor_name' => $actingUser?->name ?? 'System',
            'actor_email' => $actingUser?->email ? $this->normalizeEmail($actingUser->email) : null,
            'actor_role' => $actingUser?->role ?? 'system',
            'event_type' => ActivityLog::TYPE_VEHICLE_STATUS_UPDATED,
            'severity' => $severity,
            'source' => $source,
            'ip_address' => $req?->ip(),
            'user_agent' => $req?->userAgent(),
            'target_label' => "{$vehicle->plate_number} ({$vehicle->type})",
            'description' => "Changed {$vehicle->plate_number} status from {$fromLabel} to {$newStatus} via {$moduleSource}.",
            'metadata' => [
                'vehicle_id' => $vehicle->id,
                'plate_number' => $vehicle->plate_number,
                'vehicle_type' => $vehicle->type,
                'from_status' => $previousStatus,
                'to_status' => $newStatus,
                'module' => $moduleSource,
                'remarks' => $vehicle->remarks,
            ],
        ]);
    }

    /**
     * Recursively remove keys carrying sensitive credential fragments so secrets
     * can never be persisted inside JSON metadata.
     *
     * @param  array<array-key, mixed>  $data
     * @return array<array-key, mixed>
     */
    public static function sanitizeMetadata(array $data): array
    {
        $clean = [];

        foreach ($data as $key => $value) {
            if (is_string($key) && self::isSensitiveKey($key)) {
                continue;
            }

            $clean[$key] = is_array($value)
                ? self::sanitizeMetadata($value)
                : $value;
        }

        return $clean;
    }

    /**
     * Persist one normalized record safely; never throw into calling workflows.
     *
     * @param  array<string, mixed>  $attributes
     */
    private function record(array $attributes): ?ActivityLog
    {
        try {
            $attributes['actor_name'] = $this->truncate($attributes['actor_name'] ?? null, 255);
            $attributes['actor_email'] = $this->truncate($attributes['actor_email'] ?? null, 255);
            $attributes['target_label'] = $this->truncate($attributes['target_label'] ?? null, 255);
            $attributes['ip_address'] = $this->truncate($attributes['ip_address'] ?? null, 45);
            $attributes['user_agent'] = $this->truncate($attributes['user_agent'] ?? null, 500);
            $attributes['description'] = $this->truncate($attributes['description'] ?? '', 500) ?? '';

            if (isset($attributes['metadata']) && is_array($attributes['metadata'])) {
                $attributes['metadata'] = self::sanitizeMetadata($attributes['metadata']);
            }

            return ActivityLog::query()->create($attributes);
        } catch (Throwable $e) {
            Log::warning('Failed to record activity log entry.', [
                'event_type' => $attributes['event_type'] ?? null,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Resolve actor identity for failed login attempts without leaking
     * account existence back to callers.
     *
     * @return array{agency_id: ?int, user_id: ?int, actor_name: ?string, actor_email: string, actor_role: string, recognized: bool}
     */
    private function resolveAuthActor(string $email, ?User $matchedUser = null): array
    {
        $normalizedEmail = $this->normalizeEmail($email);
        $user = $matchedUser ?? $this->findUserByEmail($email, $normalizedEmail);

        if ($user === null) {
            return [
                'agency_id' => null,
                'user_id' => null,
                'actor_name' => null,
                'actor_email' => $normalizedEmail,
                'actor_role' => 'unknown',
                'recognized' => false,
            ];
        }

        return [
            'agency_id' => $user->agency_id,
            'user_id' => $user->id,
            'actor_name' => $user->name,
            'actor_email' => $normalizedEmail,
            'actor_role' => $user->role,
            'recognized' => true,
        ];
    }

    private function findUserByEmail(string $rawEmail, string $normalizedEmail): ?User
    {
        $user = User::query()->where('email', $normalizedEmail)->first();

        if ($user !== null || $rawEmail === $normalizedEmail) {
            return $user;
        }

        return User::query()->where('email', trim($rawEmail))->first();
    }

    private function resolveActiveRequest(?Request $request): ?Request
    {
        if ($request !== null) {
            return $request;
        }

        if (! app()->bound('request')) {
            return null;
        }

        $candidate = request();

        // In PHPUnit HTTP tests runningInConsole() is true, so a matched route
        // distinguishes a simulated HTTP request from an Artisan/CLI invocation.
        if ($candidate instanceof Request && (! app()->runningInConsole() || $candidate->route() !== null)) {
            return $candidate;
        }

        return null;
    }

    private function normalizeEmail(string $email): string
    {
        return Str::lower(trim($email));
    }

    private function truncate(mixed $value, int $limit): ?string
    {
        if ($value === null) {
            return null;
        }

        $string = (string) $value;

        return Str::substr($string, 0, $limit);
    }

    private static function isSensitiveKey(string $key): bool
    {
        $lower = Str::lower($key);

        foreach (self::SENSITIVE_KEY_FRAGMENTS as $fragment) {
            if (str_contains($lower, $fragment)) {
                return true;
            }
        }

        return false;
    }
}
