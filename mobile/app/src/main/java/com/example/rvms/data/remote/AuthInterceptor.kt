package com.example.rvms.data.remote

import okhttp3.Interceptor
import okhttp3.Response

/**
 * Attaches `Authorization: Bearer <token>` to every request when a token is
 * available (NFR-02). The token is supplied lazily by [tokenProvider] — wired
 * to TokenStore.cachedToken in ApiClient — so each request always sends the
 * current token, and requests made before sign-in (login/register) simply go
 * out without the header.
 *
 * A `@GET`/`@POST` may already carry an Accept header; we always set
 * `Accept: application/json` so Laravel returns JSON errors (422/403) rather
 * than an HTML error page.
 */
class AuthInterceptor(
    private val tokenProvider: () -> String?,
) : Interceptor {

    override fun intercept(chain: Interceptor.Chain): Response {
        val builder = chain.request().newBuilder()
            .header("Accept", "application/json")

        tokenProvider()?.let { token ->
            builder.header("Authorization", "Bearer $token")
        }

        return chain.proceed(builder.build())
    }
}
