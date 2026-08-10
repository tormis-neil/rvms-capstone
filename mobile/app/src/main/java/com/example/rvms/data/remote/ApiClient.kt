package com.example.rvms.data.remote

import kotlinx.serialization.json.Json
import okhttp3.MediaType.Companion.toMediaType
import okhttp3.OkHttpClient
import okhttp3.logging.HttpLoggingInterceptor
import retrofit2.Retrofit
import retrofit2.converter.kotlinx.serialization.asConverterFactory

/**
 * Builds the one [ApiService] the whole app uses.
 *
 * The base URL below is a PLACEHOLDER, not the address the app talks to.
 * Retrofit needs one at build time; [ServerUrlInterceptor] rewrites the scheme,
 * host and port on every request from whatever
 * [com.example.rvms.data.ServerUrlStore] currently holds. That is what lets one
 * APK serve a deployed site, a laptop on a hotspot, and a laptop on a USB cable
 * without being rebuilt (2026-08).
 *
 * The JSON config ignores unknown keys (the API may add fields) and omits
 * nulls when encoding request bodies.
 */
object ApiClient {

    /**
     * Placeholder only — the host and port are replaced per request. The PATH
     * is real and is what every endpoint hangs off, so it must stay in step
     * with ServerUrlStore.API_PREFIX.
     */
    const val BASE_URL = "http://127.0.0.1:8000/api/v1/"

    val json: Json = Json {
        ignoreUnknownKeys = true
        explicitNulls = false
    }

    /**
     * @param tokenProvider returns the current bearer token, or null before sign-in.
     * @param originProvider returns the server origin to send this request to.
     */
    fun create(
        tokenProvider: () -> String?,
        originProvider: () -> String = { "http://127.0.0.1:8000" },
    ): ApiService {
        val logging = HttpLoggingInterceptor().apply {
            level = HttpLoggingInterceptor.Level.BODY
            // Level.BODY prints request headers too, and one of them is
            // `Authorization: Bearer <token>` — a session credential readable
            // by anyone with adb access to the handset. Redacted always, not
            // only in release builds, because a demo device is exactly where a
            // curious onlooker plugs in a cable (security audit R10.2).
            redactHeader("Authorization")
        }

        val client = OkHttpClient.Builder()
            // Address first: everything after it should see the real target.
            .addInterceptor(ServerUrlInterceptor(originProvider))
            .addInterceptor(AuthInterceptor(tokenProvider))
            .addInterceptor(logging)
            .build()

        val contentType = "application/json".toMediaType()

        return Retrofit.Builder()
            .baseUrl(BASE_URL)
            .client(client)
            .addConverterFactory(json.asConverterFactory(contentType))
            .build()
            .create(ApiService::class.java)
    }
}
