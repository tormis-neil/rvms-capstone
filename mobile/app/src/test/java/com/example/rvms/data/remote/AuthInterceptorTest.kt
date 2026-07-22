package com.example.rvms.data.remote

import okhttp3.OkHttpClient
import okhttp3.Request
import okhttp3.mockwebserver.MockResponse
import okhttp3.mockwebserver.MockWebServer
import org.junit.After
import org.junit.Assert.assertEquals
import org.junit.Assert.assertNull
import org.junit.Before
import org.junit.Test

/**
 * Proves the auth interceptor attaches `Authorization: Bearer <token>` when a
 * token is available (and only then), and always negotiates JSON.
 */
class AuthInterceptorTest {

    private lateinit var server: MockWebServer

    @Before
    fun setUp() {
        server = MockWebServer()
        server.start()
    }

    @After
    fun tearDown() {
        server.shutdown()
    }

    @Test
    fun `attaches bearer token and accept header when a token is present`() {
        server.enqueue(MockResponse().setBody("{}"))
        val client = OkHttpClient.Builder()
            .addInterceptor(AuthInterceptor { "tok123" })
            .build()

        client.newCall(Request.Builder().url(server.url("/me")).build()).execute()

        val recorded = server.takeRequest()
        assertEquals("Bearer tok123", recorded.getHeader("Authorization"))
        assertEquals("application/json", recorded.getHeader("Accept"))
    }

    @Test
    fun `omits authorization header before sign-in when token is null`() {
        server.enqueue(MockResponse().setBody("{}"))
        val client = OkHttpClient.Builder()
            .addInterceptor(AuthInterceptor { null })
            .build()

        client.newCall(Request.Builder().url(server.url("/login")).build()).execute()

        val recorded = server.takeRequest()
        assertNull(recorded.getHeader("Authorization"))
        assertEquals("application/json", recorded.getHeader("Accept"))
    }
}
