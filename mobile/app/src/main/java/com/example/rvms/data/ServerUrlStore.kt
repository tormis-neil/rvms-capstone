package com.example.rvms.data

import android.content.Context
import androidx.datastore.preferences.core.edit
import androidx.datastore.preferences.core.stringPreferencesKey
import androidx.datastore.preferences.preferencesDataStore
import kotlinx.coroutines.flow.firstOrNull
import kotlinx.coroutines.flow.map
import okhttp3.HttpUrl.Companion.toHttpUrlOrNull

/** App-wide DataStore for the server address. */
private val Context.serverDataStore by preferencesDataStore(name = "rvms_server")

/**
 * Where the app looks for the RVMS server (2026-08).
 *
 * The address used to be a compile-time constant, which meant the APK could
 * only ever reach one place: `127.0.0.1:8000`, the laptop at the other end of a
 * USB cable. Pointing it at a deployed site or at a laptop on a hotspot needed
 * a rebuild in Android Studio — the one tool that stops being available when
 * the borrowed laptop goes back. So the fallback for a failed demo was "push a
 * commit and wait for CI to build a new APK", in front of the room.
 *
 * Storing it instead means one APK covers all three demo tiers and the switch
 * is typing an address:
 *
 *   deployed  https://rvms-production.up.railway.app
 *   hotspot   http://192.168.1.15:8000
 *   USB cable http://127.0.0.1:8000
 *
 * Only the ORIGIN is stored — scheme, host, port. The `/api/v1/` prefix stays a
 * constant the interceptor applies, so nobody has to type it and nobody can
 * mistype it. That does assume the server sits at the domain root, which is
 * what Railway, Render and a plain `php artisan serve` all give you; a
 * deployment under a subdirectory would need the prefix made configurable too.
 *
 * Same shape as [TokenStore], and for the same reason: DataStore is
 * asynchronous but the OkHttp interceptor runs on a blocking network thread and
 * needs the value synchronously, so it is mirrored into [cachedOrigin].
 */
class ServerUrlStore(private val context: Context) {

    @Volatile
    var cachedOrigin: String = DEFAULT_ORIGIN
        private set

    /** Load the saved address into memory (call once at startup). */
    suspend fun prime() {
        cachedOrigin = read() ?: DEFAULT_ORIGIN
    }

    /**
     * Persist a new address.
     *
     * @return the normalised value that was stored, or null when the input
     *         could not be understood — in which case nothing is saved and the
     *         previous address stays in use.
     */
    suspend fun save(input: String): String? {
        val origin = normalize(input) ?: return null

        cachedOrigin = origin
        context.serverDataStore.edit { prefs -> prefs[KEY_ORIGIN] = origin }

        return origin
    }

    suspend fun read(): String? =
        context.serverDataStore.data
            .map { prefs -> prefs[KEY_ORIGIN] }
            .firstOrNull()

    /** Back to the built-in default. */
    suspend fun reset() {
        cachedOrigin = DEFAULT_ORIGIN
        context.serverDataStore.edit { prefs -> prefs.remove(KEY_ORIGIN) }
    }

    companion object {
        /**
         * The USB-cable address, because that is the tier that needs no setup
         * at all — a fresh install works over a cable with nothing typed.
         */
        const val DEFAULT_ORIGIN = "http://127.0.0.1:8000"

        /** Applied by the interceptor; never typed by a user. */
        const val API_PREFIX = "/api/v1/"

        /**
         * Turn what somebody actually typed into an origin, or null if it
         * cannot be salvaged.
         *
         * Forgiving on purpose — this gets typed on a phone keyboard, in a
         * hurry, in front of people:
         *
         *   192.168.1.15:8000              -> http://192.168.1.15:8000
         *   http://192.168.1.15:8000/      -> http://192.168.1.15:8000
         *   192.168.1.15:8000/api/v1       -> http://192.168.1.15:8000
         *   https://rvms.up.railway.app/   -> https://rvms.up.railway.app
         *
         * A bare host with no scheme becomes http, not https: every address
         * typed without one here is a local laptop, and guessing https would
         * fail in a way that looks like the server is down.
         */
        fun normalize(input: String): String? {
            var text = input.trim()
            if (text.isEmpty()) return null

            if (!text.startsWith("http://") && !text.startsWith("https://")) {
                text = "http://$text"
            }

            // Drop a pasted API path so it cannot be applied twice.
            text = text.trimEnd('/')
            if (text.endsWith("/api/v1")) {
                text = text.removeSuffix("/api/v1")
            }
            text = text.trimEnd('/')

            val url = text.toHttpUrlOrNull() ?: return null
            if (url.host.isBlank()) return null

            // Rebuild from the parsed pieces so anything after the authority —
            // a stray path, query or fragment — is discarded rather than
            // silently prefixed onto every request.
            val port = if (url.port == defaultPortFor(url.scheme)) "" else ":${url.port}"

            return "${url.scheme}://${url.host}$port"
        }

        private fun defaultPortFor(scheme: String): Int = if (scheme == "https") 443 else 80

        val KEY_ORIGIN = stringPreferencesKey("server_origin")
    }
}
