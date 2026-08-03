package com.example.rvms.data

import com.example.rvms.data.remote.ApiClient
import com.example.rvms.data.remote.ApiService
import kotlinx.coroutines.test.runTest
import okhttp3.MediaType.Companion.toMediaType
import okhttp3.mockwebserver.MockResponse
import okhttp3.mockwebserver.MockWebServer
import org.junit.After
import org.junit.Assert.assertEquals
import org.junit.Assert.assertFalse
import org.junit.Assert.assertNull
import org.junit.Assert.assertTrue
import org.junit.Before
import org.junit.Test
import retrofit2.Retrofit
import retrofit2.converter.kotlinx.serialization.asConverterFactory

/**
 * The R10 sub-task 1 crash, pinned.
 *
 * `loadMe()` was the one network call in the app with no catch, and it runs
 * unattended from three places: Splash's bootstrap() at cold start, and the
 * resume refresh on Home and Profile. A driver opening the app in a dead spot
 * got an uncaught coroutine exception — a crash, not an error state.
 *
 * The second half of the contract matters as much as not crashing: a network
 * failure must leave the CACHED session alone. Being briefly offline is no
 * reason to forget who is signed in; only a 401 — the server actively
 * rejecting the token — may clear it.
 */
class SessionManagerOfflineTest {

    private lateinit var server: MockWebServer
    private lateinit var tokenStore: FakeTokenStore
    private lateinit var session: SessionManager

    private class FakeTokenStore : TokenStorage {
        override var cachedToken: String? = "42|TOK"
        override suspend fun prime() {}
        override suspend fun save(token: String) { cachedToken = token }
        override suspend fun read(): String? = cachedToken
        override suspend fun clear() { cachedToken = null }
    }

    private fun userBody(name: String) =
        """
        {"user":{"id":5,"agency_id":1,"role":"driver",
         "name":"$name","email":"r@rvms.local","status":"active"}}
        """.trimIndent()

    @Before
    fun setUp() {
        server = MockWebServer()
        server.start()

        val api = Retrofit.Builder()
            .baseUrl(server.url("/"))
            .addConverterFactory(
                ApiClient.json.asConverterFactory("application/json".toMediaType()),
            )
            .build()
            .create(ApiService::class.java)

        tokenStore = FakeTokenStore()
        session = SessionManager(api, tokenStore)
    }

    @After
    fun tearDown() {
        // Some tests shut the server down themselves; a second call is a no-op.
        runCatching { server.shutdown() }
    }

    /** The crash: cold start with no signal must return false, never throw. */
    @Test
    fun `loadMe with an unreachable server returns false instead of throwing`() = runTest {
        server.shutdown()

        assertFalse(session.loadMe())
    }

    @Test
    fun `bootstrap with an unreachable server returns false instead of throwing`() = runTest {
        server.shutdown()

        assertFalse(session.bootstrap())
    }

    /** Briefly offline is not signed out: token and cached user both survive. */
    @Test
    fun `a network failure keeps the token and the cached user`() = runTest {
        server.enqueue(MockResponse().setResponseCode(200).setBody(userBody("Ramon")))
        assertTrue(session.loadMe())

        server.shutdown()
        assertFalse(session.loadMe())

        assertEquals("42|TOK", tokenStore.cachedToken)
        assertEquals("Ramon", session.currentUser.value?.name)
    }

    /** A 5xx is the server having a bad day, not this token being invalid. */
    @Test
    fun `a server error keeps the session too`() = runTest {
        server.enqueue(MockResponse().setResponseCode(200).setBody(userBody("Ramon")))
        session.loadMe()

        server.enqueue(MockResponse().setResponseCode(503))
        assertFalse(session.loadMe())

        assertEquals("42|TOK", tokenStore.cachedToken)
        assertEquals("Ramon", session.currentUser.value?.name)
    }

    /** Only the server actively rejecting the token ends the session. */
    @Test
    fun `a 401 clears the token and the cached user`() = runTest {
        server.enqueue(MockResponse().setResponseCode(200).setBody(userBody("Ramon")))
        session.loadMe()

        server.enqueue(MockResponse().setResponseCode(401))
        assertFalse(session.loadMe())

        assertNull(tokenStore.cachedToken)
        assertNull(session.currentUser.value)
    }
}
