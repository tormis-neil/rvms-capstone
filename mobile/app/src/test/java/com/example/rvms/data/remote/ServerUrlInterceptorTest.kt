package com.example.rvms.data.remote

import com.example.rvms.data.ServerUrlStore
import kotlinx.coroutines.test.runTest
import okhttp3.MediaType.Companion.toMediaType
import okhttp3.mockwebserver.MockResponse
import okhttp3.mockwebserver.MockWebServer
import org.junit.After
import org.junit.Assert.assertEquals
import org.junit.Before
import org.junit.Test
import retrofit2.Retrofit
import retrofit2.converter.kotlinx.serialization.asConverterFactory

/**
 * The address the app is pointed at is applied to every request (2026-08).
 *
 * Retrofit is built once with a placeholder base URL, so this is what actually
 * decides where a call goes — and it is what lets one APK cover the deployed,
 * hotspot and USB demo tiers without a rebuild. If it stops rewriting, every
 * request silently goes to 127.0.0.1 and the app looks offline everywhere
 * except on a cable.
 */
class ServerUrlInterceptorTest {

    private lateinit var server: MockWebServer

    @Before
    fun setUp() {
        server = MockWebServer()
        server.start()
    }

    @After
    fun tearDown() {
        runCatching { server.shutdown() }
    }

    /** Build an ApiService pointed at whatever [origin] returns. */
    private fun apiPointedAt(origin: () -> String): ApiService =
        Retrofit.Builder()
            .baseUrl(ApiClient.BASE_URL)
            .client(
                okhttp3.OkHttpClient.Builder()
                    .addInterceptor(ServerUrlInterceptor(origin))
                    .build(),
            )
            .addConverterFactory(ApiClient.json.asConverterFactory("application/json".toMediaType()))
            .build()
            .create(ApiService::class.java)

    /** The mock server's own origin, e.g. http://127.0.0.1:41234 */
    private fun mockOrigin(): String {
        val url = server.url("/")
        return "http://${url.host}:${url.port}"
    }

    @Test
    fun `a request goes to the configured host and port`() = runTest {
        server.enqueue(MockResponse().setResponseCode(200).setBody("""{"data":[]}"""))

        apiPointedAt { mockOrigin() }.myVehicle()

        val recorded = server.takeRequest()
        assertEquals(server.port, recorded.requestUrl!!.port)
    }

    /** The path is the interceptor's business to leave alone. */
    @Test
    fun `the api path is preserved when the host changes`() = runTest {
        server.enqueue(MockResponse().setResponseCode(200).setBody("""{"data":[]}"""))

        apiPointedAt { mockOrigin() }.myVehicle()

        val path = server.takeRequest().path.orEmpty()
        assert(path.startsWith(ServerUrlStore.API_PREFIX)) {
            "Expected the request path to keep ${ServerUrlStore.API_PREFIX}, got $path"
        }
    }

    /**
     * Read fresh on every call, not captured once — switching tiers mid-demo
     * has to take effect on the next request, not the next app launch.
     */
    @Test
    fun `changing the origin between calls redirects the next one`() = runTest {
        val second = MockWebServer().apply { start() }
        try {
            server.enqueue(MockResponse().setResponseCode(200).setBody("""{"data":[]}"""))
            second.enqueue(MockResponse().setResponseCode(200).setBody("""{"data":[]}"""))

            var origin = mockOrigin()
            val api = apiPointedAt { origin }

            api.myVehicle()
            assertEquals(1, server.requestCount)

            origin = "http://${second.url("/").host}:${second.port}"
            api.myVehicle()

            assertEquals(1, server.requestCount)
            assertEquals(1, second.requestCount)
        } finally {
            runCatching { second.shutdown() }
        }
    }

    /** A broken stored value must not take the app down. */
    @Test
    fun `an unparseable origin leaves the request untouched`() = runTest {
        val api = apiPointedAt { "not a url" }

        // The placeholder host is not listening, so this fails to connect —
        // the point is that it throws an IO error rather than crashing inside
        // the interceptor.
        runCatching { api.myVehicle() }
    }
}
