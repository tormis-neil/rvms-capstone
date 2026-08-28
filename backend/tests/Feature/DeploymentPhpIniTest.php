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
     * THE ONE THAT MATTERS — and it now asserts the OPPOSITE of what it did.
     *
     * This test used to require a LEADING COLON, on the stated grounds that
     * `:/app/deploy` appends to PHP's default scan directory while
     * `/app/deploy` replaces it. The build log of 2026-08-28 disproved that.
     * The install phase printed the module list both ways:
     *
     *   WITH PHP_INI_SCAN_DIR: Core date hash json libxml pcre Phar random
     *                          Reflection SPL standard xml
     *   WITHOUT:               … ctype curl dom fileinfo iconv mbstring
     *                          openssl pdo_mysql tokenizer zip … (and 30 more)
     *
     * The colon was present. The twelve that survived are the ones PHP compiles
     * in statically; every other extension unloaded anyway. It broke the build
     * (Composer could not start without mbstring) and then the runtime (the
     * container crash-looped on `Class "DOMDocument" not found`).
     *
     * So the guarantee has moved. It is no longer "the value starts with a
     * colon" — that never held. It is "/app/deploy is SELF-SUFFICIENT": the
     * build copies Nix's own extension .ini files into it, so scanning that one
     * directory loads the extensions AND our upload limits.
     *
     * Three things are asserted, because all three are load-bearing and each
     * fails silently on its own:
     *   1. the variable points at /app/deploy;
     *   2. the install phase copies the extension inis in;
     *   3. the install phase asserts they load, so a container that cannot load
     *      its extensions fails the BUILD instead of reaching a deploy.
     *
     * The third is the one that would have caught this in August. Both earlier
     * failures shipped a green build.
     */
    public function test_the_scan_dir_is_self_sufficient(): void
    {
        $toml = $this->nixpacks();

        preg_match('/PHP_INI_SCAN_DIR\s*=\s*"([^"]*)"/', $toml, $m);

        $this->assertNotEmpty($m,
            'PHP_INI_SCAN_DIR must be set in nixpacks.toml — it is what loads deploy/php.ini, '
            .'which raises the upload limit past the 5M the forms accept.');

        $this->assertSame('/app/deploy', $m[1],
            'The path must be exactly /app/deploy. Root Directory is `backend`, so backend/deploy '
            .'becomes /app/deploy in the container. A LEADING COLON does NOT append to PHP\'s '
            .'defaults on this image — it was tried, and every extension unloaded regardless '
            .'(build log, 2026-08-28). The directory is made self-sufficient instead.');

        $this->assertMatchesRegularExpression(
            '/Scan this dir for additional \.ini files.*cp -v.*\/app\/deploy\//s',
            $toml,
            'The install phase must copy Nix\'s extension .ini files into /app/deploy. Without '
            .'that copy, pointing the scan directory there unloads pdo_mysql, mbstring, dom and '
            .'tokenizer — no database, and a container that crash-loops on boot.');

        foreach (['pdo_mysql', 'mbstring', 'dom', 'tokenizer'] as $extension) {
            $this->assertStringContainsString($extension, $toml,
                "The install phase must assert {$extension} loads from /app/deploy. This assertion "
                .'is what stops a container that cannot load its extensions from reaching a deploy — '
                .'which happened twice on 2026-08-28, both times behind a green build.');
        }

        $this->assertStringContainsString('exit 1', $toml,
            'The extension check must FAIL the build, not merely print a warning. A diagnostic that '
            .'nobody reads is how both August failures got as far as a running container.');
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
