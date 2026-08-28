#!/usr/bin/env bash
#
# RVMS container entrypoint (2026-08-28).
#
# WHY THIS FILE EXISTS. PHP's upload limits have to be raised for FR-11 (a 5 MB
# damage photo) and FR-13/FR-14 (a supporting document), and PHP enforces them
# before Laravel is reached, so a container on the 2M default discards the file
# with no error the app can explain.
#
# The obvious way to raise them — PHP_INI_SCAN_DIR pointing at deploy/php.ini —
# does not work on this image, and cost a day proving it. Setting that variable
# REPLACES PHP's default scan directory rather than adding to it, even with the
# leading colon that PHP documents as "append". Every dynamically loaded
# extension unloads: no pdo_mysql, so no database; no dom or tokenizer, so
# Laravel dies before it boots. The build log printed the module list both ways
# and the difference was 12 extensions against 45.
#
# Copying Nix's extension .ini files alongside ours at BUILD time does work —
# and then disappears, because Nixpacks is a multi-stage build that re-copies
# the source over /app after the install phase. The files were provably there
# during the build and provably gone at runtime.
#
# So the assembly happens HERE, in the running container, after every COPY has
# finished and where nothing can overwrite it. We read PHP's real scan directory
# (with the variable unset, which is the only way to see it), copy those .ini
# files into a writable directory together with ours, and point PHP at that.
#
# THE FALLBACK IS THE POINT. If any of that fails, the variable is unset and PHP
# starts with its own defaults — a working system with a low upload limit rather
# than a crash loop. `rvms:doctor` reports the limit, so a degraded container
# announces itself instead of hiding. An upload limit is never worth the app.
set -uo pipefail

ROLE="${1:-web}"

# --- assemble a scan directory that has the extensions AND our limits --------
unset PHP_INI_SCAN_DIR
RUNTIME_INI=/tmp/php-ini

NIX_INI="$(php -i 2>/dev/null | sed -n 's/^Scan this dir for additional .ini files => //p' | head -1)"

if [ -n "${NIX_INI}" ] && [ -d "${NIX_INI}" ]; then
    mkdir -p "${RUNTIME_INI}"
    cp "${NIX_INI}"/*.ini "${RUNTIME_INI}"/ 2>/dev/null

    # zz- prefix so our settings are scanned last and win any overlap.
    if [ -f /app/deploy/php.ini ]; then
        cp /app/deploy/php.ini "${RUNTIME_INI}/zz-rvms-uploads.ini"
    fi

    export PHP_INI_SCAN_DIR="${RUNTIME_INI}"
fi

# --- prove it, and fall back rather than crash ------------------------------
if ! php -m | grep -qw pdo_mysql; then
    echo "WARNING: ${RUNTIME_INI} did not load pdo_mysql — falling back to PHP's defaults."
    echo "WARNING: the app will run, but the upload limit will be low. Run rvms:doctor."
    unset PHP_INI_SCAN_DIR
fi

echo "RVMS start (${ROLE}): upload_max_filesize=$(php -r 'echo ini_get("upload_max_filesize");')" \
     "post_max_size=$(php -r 'echo ini_get("post_max_size");')" \
     "pdo_mysql=$(php -m | grep -qw pdo_mysql && echo yes || echo NO)"

# --- run the role -----------------------------------------------------------
case "${ROLE}" in
    scheduler)
        # A clock. No migrations: the web service owns the schema, and two
        # services migrating the same database at once is a race.
        exec php artisan schedule:work
        ;;
    web)
        # Migrations must succeed. storage:link may fail harmlessly when the
        # link already exists, which it will on a volume, hence the guard.
        php artisan migrate --force || exit 1
        php artisan storage:link || true
        exec php artisan serve --host=0.0.0.0 --port="${PORT:-8000}"
        ;;
    *)
        echo "FATAL: unknown role '${ROLE}'. Use 'web' or 'scheduler'."
        exit 1
        ;;
esac
