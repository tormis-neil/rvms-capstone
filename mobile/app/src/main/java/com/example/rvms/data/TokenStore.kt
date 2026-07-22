package com.example.rvms.data

import android.content.Context
import androidx.datastore.preferences.core.edit
import androidx.datastore.preferences.core.stringPreferencesKey
import androidx.datastore.preferences.preferencesDataStore
import kotlinx.coroutines.flow.firstOrNull
import kotlinx.coroutines.flow.map

/** App-wide DataStore for auth. */
private val Context.authDataStore by preferencesDataStore(name = "rvms_auth")

/**
 * Persists the Sanctum bearer token across app launches (replaces the
 * prototype's fake in-memory auth).
 *
 * DataStore is asynchronous, but the OkHttp auth interceptor runs on a
 * blocking network thread and needs the token *synchronously*. So the token is
 * also mirrored into an in-memory [cachedToken] that the interceptor reads
 * directly; the cache is kept in sync on every save/clear and primed once at
 * startup via [prime]. This is the standard pattern for a DataStore-backed
 * token feeding an interceptor.
 */
class TokenStore(private val context: Context) {

    @Volatile
    var cachedToken: String? = null
        private set

    /** Load the persisted token into the in-memory cache (call once at startup). */
    suspend fun prime() {
        cachedToken = read()
    }

    /** Persist the token and update the in-memory cache. */
    suspend fun save(token: String) {
        cachedToken = token
        context.authDataStore.edit { prefs -> prefs[KEY_TOKEN] = token }
    }

    /** Read the persisted token (null if none). */
    suspend fun read(): String? =
        context.authDataStore.data
            .map { prefs -> prefs[KEY_TOKEN] }
            .firstOrNull()

    /** Clear the token on sign-out. */
    suspend fun clear() {
        cachedToken = null
        context.authDataStore.edit { prefs -> prefs.remove(KEY_TOKEN) }
    }

    private companion object {
        val KEY_TOKEN = stringPreferencesKey("bearer_token")
    }
}
