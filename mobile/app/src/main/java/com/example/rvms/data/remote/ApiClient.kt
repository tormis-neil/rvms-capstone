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
 * Base URL is a single constant: the lead's real-phone-over-USB setup reaches
 * the laptop's Laravel server through `adb reverse tcp:8000 tcp:8000`, so the
 * phone's own 127.0.0.1:8000 points at the server. An emulator would instead
 * use 10.0.2.2 (its alias for the host) — switch the one line below.
 *
 * The JSON config ignores unknown keys (the API may add fields) and omits
 * nulls when encoding request bodies.
 */
object ApiClient {

    // Real phone via USB (adb reverse). Emulator: use "http://10.0.2.2:8000/api/v1/".
    const val BASE_URL = "http://127.0.0.1:8000/api/v1/"

    val json: Json = Json {
        ignoreUnknownKeys = true
        explicitNulls = false
    }

    /**
     * @param tokenProvider returns the current bearer token (TokenStore.cachedToken),
     *        or null before sign-in.
     */
    fun create(tokenProvider: () -> String?): ApiService {
        val logging = HttpLoggingInterceptor().apply {
            level = HttpLoggingInterceptor.Level.BODY
        }

        val client = OkHttpClient.Builder()
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
