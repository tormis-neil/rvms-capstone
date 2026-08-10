package com.example.rvms.data.remote

import okhttp3.HttpUrl.Companion.toHttpUrlOrNull
import okhttp3.Interceptor
import okhttp3.Response

/**
 * Redirects every call to whichever server the app is currently pointed at
 * (2026-08).
 *
 * Retrofit needs a base URL at build time, so it is given a placeholder and
 * this rewrites the scheme, host and port on the way out. Doing it here rather
 * than rebuilding Retrofit means the address can change at runtime without
 * re-creating the HTTP stack, the repositories, or the session — one
 * [ApiService] instance for the life of the app, pointed wherever it needs to
 * be.
 *
 * The path is untouched: it is always `/api/v1/…`, because ServerUrlStore keeps
 * only the origin.
 *
 * @param originProvider the current origin, read fresh on every request so a
 *        change takes effect on the next call rather than the next launch.
 */
class ServerUrlInterceptor(private val originProvider: () -> String) : Interceptor {

    override fun intercept(chain: Interceptor.Chain): Response {
        val request = chain.request()
        val origin = originProvider().toHttpUrlOrNull()
            // An unparseable origin should not take the app down — the stored
            // value was validated on the way in, so this is belt and braces.
            ?: return chain.proceed(request)

        val rewritten = request.url.newBuilder()
            .scheme(origin.scheme)
            .host(origin.host)
            .port(origin.port)
            .build()

        return chain.proceed(request.newBuilder().url(rewritten).build())
    }
}
