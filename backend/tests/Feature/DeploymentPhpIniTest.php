<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * The deployed container's PHP upload limits (2026-08).
 *
 * PHP enforces upload_max_filesize BEFORE Laravel is reached, so a container
 * left on the usual 2M default silently discards a 5 MB damage photo (FR-11) or
 * repair receipt (FR-13/FR-14) — the request arrives with no file and no error
 * the app can explain. This container's stock default was measured at 2M, so
 * the deployment needs its own ini file.
 *
 * These assertions are about two files nothing else tests: they are not loaded
 * at runtime here, they are read as text. Both were verified in a container
 * before being written, and both have a failure mode that only shows up on the
 * deployment machine — which is precisely why they are guarded here rather than
 * trusted to stay correct.
 */
class DeploymentPhpIniTest extends TestCase
{
    private function iniFile(): string
    {
        $path = base_path('deploy/php.ini');

        $this->assertFileExists($path,
            'deploy/php.ini is what raises the deployed upload limit; without it rvms:doctor fails on Railway.');

        return (string) file_get_contents($path);
    }

    private function nixpacks(): string
    {
        $path = base_path('nixpacks.toml');

        $this->assertFileExists($path, 'nixpacks.toml is what Railway builds from.');

        return (string) file_get_contents($path);
    }

    /** The limits must clear the 5 MB the upload rules allow (max:5120). */
    public function test_the_ini_file_raises_both_upload_limits_past_the_apps_own_rule(): void
    {
        $ini = parse_ini_string($this->iniFile());

        $this->assertIsArray($ini, 'deploy/php.ini is not valid ini syntax — PHP would ignore it silently.');

        $upload = $this->megabytes($ini['upload_max_filesize'] ?? '');
        $post = $this->megabytes($ini['post_max_size'] ?? '');

        $this->assertGreaterThanOrEqual(5, $upload,
            'upload_max_filesize must be at least the 5M the forms accept (max:5120).');

        $this->assertGreaterThan($upload, $post,
            'post_max_size must exceed upload_max_filesize — a request carries the file PLUS its form '
            .'fields, so equal values still fail an upload sitting exactly at the limit.');
    }

    private function startScript(): string
    {
        $path = base_path('deploy/start.sh');

        $this->assertFileExists($path,
            'deploy/start.sh is the container entrypoint; nixpacks.toml runs it for both services.');

        return (string) file_get_contents($path);
    }

    /**
     * THE ONE THAT MATTERS — and it has now been wrong twice, so read the
     * history before changing it.
     *
     * It first required PHP_INI_SCAN_DIR to carry a LEADING COLON, on the
     * grounds that `:/app/deploy` appends to PHP's default scan directory while
     * `/app/deploy` replaces it. The build log of 2026-08-28 printed the module
     * list both ways and disproved it: 12 extensions with the variable set,
     * 45 without, colon included. The colon does nothing on this image.
     *
     * It was then rewritten to require the build to copy Nix's extension .ini
     * files into /app/deploy. That fixed the build and changed nothing at
     * runtime — Nixpacks is a multi-stage build that re-copies the source over
     * /app after the install phase, so the files were provably present during
     * the build and provably absent in the running container.
     *
     * What holds is this: the variable is set NOWHERE at build time, and
     * deploy/start.sh assembles the scan directory inside the running container.
     * Both failures came from configuring PHP where the configuration could not
     * survive to where PHP actually runs.
     *
     * The fallback is asserted too, and it is not a detail. If the assembly
     * fails the script unsets the variable and PHP starts on its own defaults —
     * a working system with a low upload limit, which rvms:doctor then reports.
     * Both of the earlier attempts crash-looped instead. An upload limit is
     * never worth the app.
     */
    public function test_php_is_configured_at_runtime_not_at_build_time(): void
    {
        $toml = $this->nixpacks();

        // Comments are stripped first: this file explains at length WHY the
        // variable is not set here, and that prose must not fail the check.
        $settings = implode("\n", array_filter(
            array_map('trim', explode("\n", $toml)),
            fn (string $line): bool => $line !== '' && ! str_starts_with($line, '#')
        ));

        $this->assertStringNotContainsString('PHP_INI_SCAN_DIR', $settings,
            'PHP_INI_SCAN_DIR must NOT be set in nixpacks.toml. Nixpacks bakes [variables] into the '
            .'Dockerfile as ENV, so it applies during the build too — and setting it there unloads '
            .'every dynamically loaded extension, which stops Composer from running at all. '
            .'The scan directory is assembled at runtime by deploy/start.sh instead.');

        $this->assertStringContainsString('bash deploy/start.sh web', $toml,
            'The start command must run deploy/start.sh, which configures PHP before starting the '
            .'server. Calling `php artisan serve` directly skips that and loses the upload limits.');

        $script = $this->startScript();

        $this->assertStringContainsString('unset PHP_INI_SCAN_DIR', $script,
            'The script must unset the variable before reading PHP\'s real scan directory — with it '
            .'set, `php -i` reports our value back and the true directory can never be found.');

        $this->assertStringContainsString('Scan this dir for additional .ini files', $script,
            'The script must read PHP\'s own scan directory and copy those .ini files alongside '
            .'ours. Without them, pointing PHP at our directory unloads pdo_mysql, dom and '
            .'tokenizer, and the container crash-loops on boot.');

        $this->assertMatchesRegularExpression('/grep -qw pdo_mysql[\s\S]*unset PHP_INI_SCAN_DIR/', $script,
            'The script must VERIFY pdo_mysql loaded and FALL BACK to PHP\'s defaults if not. '
            .'A container that cannot reach its database must still start and say so, rather than '
            .'crash-loop — that is what both earlier attempts did.');

        foreach (['web', 'scheduler'] as $role) {
            $this->assertStringContainsString($role, $script,
                "The script must handle the '{$role}' role. Both Railway services run this same "
                .'file, so both get the same PHP configuration; a second entrypoint would drift.');
        }

        $this->assertStringContainsString('artisan migrate --force', $script,
            'The web role must run migrations on boot — a fresh database gets its tables and a '
            .'redeploy picks up new ones.');

        $this->assertStringNotContainsString('migrate', substr($script, strpos($script, 'scheduler)'), 200),
            'The scheduler must NOT migrate. Two services migrating the same database at the same '
            .'moment is a race with no upside; the web service owns the schema.');
    }

    /** php.ini shorthand — "8M", "512K", "1G" — as megabytes. */
    private function megabytes(string $value): float
    {
        $value = trim($value);
        $number = (float) $value;

        return match (strtoupper(substr($value, -1))) {
            'G' => $number * 1024,
            'M' => $number,
            'K' => $number / 1024,
            default => $number / 1048576,
        };
    }
}
