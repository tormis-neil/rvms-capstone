package com.example.rvms.data

import com.example.rvms.data.remote.ApiClient
import com.example.rvms.data.remote.ApiService
import kotlinx.coroutines.test.runTest
import okhttp3.MediaType.Companion.toMediaType
import okhttp3.mockwebserver.MockResponse
import okhttp3.mockwebserver.MockWebServer
import org.junit.After
import org.junit.Assert.assertEquals
import org.junit.Assert.assertNull
import org.junit.Assert.assertTrue
import org.junit.Before
import org.junit.Test
import retrofit2.Retrofit
import retrofit2.converter.kotlinx.serialization.asConverterFactory

/**
 * Proves AuthRepository maps the API's status-code contract to result types:
 * login 200 (stores token) · 422 bad credentials · 403 pending reason;
 * register 201 success · 422 validation (duplicate email).
 */
class AuthRepositoryTest {

    private lateinit var server: MockWebServer
    private lateinit var tokenStore: FakeTokenStore
    private lateinit var repo: AuthRepository

    /** In-memory TokenStorage so the session layer runs without Android DataStore. */
    private class FakeTokenStore : TokenStorage {
        override var cachedToken: String? = null
        override suspend fun prime() {}
        override suspend fun save(token: String) { cachedToken = token }
        override suspend fun read(): String? = cachedToken
        override suspend fun clear() { cachedToken = null }
    }

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
        val session = SessionManager(api, tokenStore)
        repo = AuthRepository(api, session)
    }

    @After
    fun tearDown() {
        server.shutdown()
    }

    @Test
    fun `login success stores the token and returns the user`() = runTest {
        server.enqueue(
            MockResponse().setResponseCode(200).setBody(
                """
                {"token":"42|TOK","user":{"id":5,"agency_id":1,"role":"driver",
                 "name":"Ramon","email":"r@rvms.local","status":"active"}}
                """.trimIndent(),
            ),
        )

        val result = repo.login("r@rvms.local", "password")

        assertTrue(result is LoginResult.Success)
        assertEquals("Ramon", (result as LoginResult.Success).user.name)
        assertEquals("42|TOK", tokenStore.cachedToken)
    }

    @Test
    fun `login with bad credentials surfaces the 422 validation message`() = runTest {
        server.enqueue(
            MockResponse().setResponseCode(422).setBody(
                """
                {"message":"The given data was invalid.",
                 "errors":{"email":["These credentials do not match our records."]}}
                """.trimIndent(),
            ),
        )

        val result = repo.login("r@rvms.local", "wrong")

        assertTrue(result is LoginResult.Error)
        assertEquals(
            "These credentials do not match our records.",
            (result as LoginResult.Error).message,
        )
        assertNull(tokenStore.cachedToken)
    }

    @Test
    fun `login on a pending account surfaces the 403 reason`() = runTest {
        server.enqueue(
            MockResponse().setResponseCode(403).setBody(
                """{"message":"Your account is pending approval by your agency administrator."}""",
            ),
        )

        val result = repo.login("pending@rvms.local", "password")

        assertTrue(result is LoginResult.Error)
        assertEquals(
            "Your account is pending approval by your agency administrator.",
            (result as LoginResult.Error).message,
        )
    }

    @Test
    fun `register success returns Success`() = runTest {
        server.enqueue(
            MockResponse().setResponseCode(201).setBody(
                """
                {"message":"Registration submitted.","user":{"id":9,"agency_id":1,
                 "role":"driver","name":"New Driver","email":"n@rvms.local","status":"pending"}}
                """.trimIndent(),
            ),
        )

        val result = repo.register(1, "New Driver", "n@rvms.local", "password", "password")

        assertTrue(result is RegisterResult.Success)
    }

    @Test
    fun `register with a taken email surfaces the 422 validation message`() = runTest {
        server.enqueue(
            MockResponse().setResponseCode(422).setBody(
                """
                {"message":"The given data was invalid.",
                 "errors":{"email":["An account with this email address already exists."]}}
                """.trimIndent(),
            ),
        )

        val result = repo.register(1, "New Driver", "dupe@rvms.local", "password", "password")

        assertTrue(result is RegisterResult.Error)
        assertEquals(
            "An account with this email address already exists.",
            (result as RegisterResult.Error).message,
        )
    }
}
