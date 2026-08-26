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

    /**
     * THE ONE THAT MATTERS. `PHP_INI_SCAN_DIR=/app/deploy` REPLACES PHP's
     * default scan directory instead of adding to it, which unloads every
     * extension configured there — verified in a container, where the replace
     * form reported pdo_mysql as not loaded, i.e. no database at all. The
     * leading colon appends. Anyone "tidying" it away breaks the deployment in
     * a way whose error message says nothing about ini files.
     */
    public function test_the_scan_dir_appends_rather_than_replaces_phps_defaults(): void
    {
        $toml = $this->nixpacks();

        $this->assertMatchesRegularExpression(
            '/PHP_INI_SCAN_DIR\s*=\s*"(:[^"]*)"/',
            $toml,
            'PHP_INI_SCAN_DIR must be set in nixpacks.toml and its value MUST start with a colon. '
            .'Without the colon PHP stops reading its default configuration directory and pdo_mysql '
            .'unloads — the deployment then fails with a database connection error, not an upload one.'
        );

        preg_match('/PHP_INI_SCAN_DIR\s*=\s*"(:[^"]*)"/', $toml, $m);

        $this->assertSame(':/app/deploy', $m[1],
            'The path must point at the deploy folder as Railway mounts it: Root Directory is `backend`, '
            .'so backend/deploy becomes /app/deploy in the container.');
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
