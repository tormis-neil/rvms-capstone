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
import org.junit.Assert.assertTrue
import org.junit.Before
import org.junit.Test
import retrofit2.Retrofit
import retrofit2.converter.kotlinx.serialization.asConverterFactory

/**
 * Driver self-service profile edit (FR-04) — R9's mobile half.
 *
 * The behaviour worth pinning is what the app sends, not just what it does
 * with the reply: a blank password must be OMITTED from the request rather
 * than sent as an empty string, because the API's rule is `sometimes|min:8`
 * and an empty string would be rejected as too short — turning "I only wanted
 * to fix my name" into a validation error the driver cannot explain.
 */
class UpdateProfileTest {

    private lateinit var server: MockWebServer
    private lateinit var session: SessionManager
    private lateinit var repo: AuthRepository

    private class FakeTokenStore : TokenStorage {
        override var cachedToken: String? = "42|TOK"
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

        session = SessionManager(api, FakeTokenStore())
        repo = AuthRepository(api, session)
    }

    @After
    fun tearDown() {
        // Some tests shut the server down themselves; a second call is a no-op.
        runCatching { server.shutdown() }
    }

    private fun userBody(name: String, email: String) =
        """
        {"user":{"id":5,"agency_id":1,"role":"driver",
         "name":"$name","email":"$email","status":"active"}}
        """.trimIndent()

    @Test
    fun `a successful edit returns the updated user`() = runTest {
        server.enqueue(MockResponse().setResponseCode(200).setBody(userBody("Ramon V.", "ramon@rvms.local")))

        val result = repo.updateProfile("Ramon V.", "ramon@rvms.local", "", "")

        assertTrue(result is UpdateProfileResult.Success)
        assertEquals("Ramon V.", (result as UpdateProfileResult.Success).user.name)
    }

    /** The session's cached user must move, or the screens keep the old name. */
    @Test
    fun `a successful edit refreshes the cached session user`() = runTest {
        server.enqueue(MockResponse().setResponseCode(200).setBody(userBody("Ramon V.", "ramon@rvms.local")))

        repo.updateProfile("Ramon V.", "ramon@rvms.local", "", "")

        assertEquals("Ramon V.", session.currentUser.value?.name)
    }

    /** The whole point: no password field at all when it was left blank. */
    @Test
    fun `a blank password is omitted from the request body`() = runTest {
        server.enqueue(MockResponse().setResponseCode(200).setBody(userBody("Ramon", "ramon@rvms.local")))

        repo.updateProfile("Ramon", "ramon@rvms.local", "", "")

        val body = server.takeRequest().body.readUtf8()

        assertFalse("blank password must not be sent", body.contains("\"password\""))
        assertFalse(body.contains("password_confirmation"))
        assertTrue(body.contains("\"name\""))
    }

    @Test
    fun `a supplied password is sent with its confirmation`() = runTest {
        server.enqueue(MockResponse().setResponseCode(200).setBody(userBody("Ramon", "ramon@rvms.local")))

        repo.updateProfile("Ramon", "ramon@rvms.local", "new-password-123", "new-password-123")

        val body = server.takeRequest().body.readUtf8()

        assertTrue(body.contains("\"password\":\"new-password-123\""))
        assertTrue(body.contains("\"password_confirmation\":\"new-password-123\""))
    }

    /** Leading/trailing spaces in an email are a typo, not a different address. */
    @Test
    fun `name and email are trimmed before sending`() = runTest {
        server.enqueue(MockResponse().setResponseCode(200).setBody(userBody("Ramon", "ramon@rvms.local")))

        repo.updateProfile("  Ramon  ", "  ramon@rvms.local  ", "", "")

        val body = server.takeRequest().body.readUtf8()

        assertTrue(body.contains("\"name\":\"Ramon\""))
        assertTrue(body.contains("\"email\":\"ramon@rvms.local\""))
    }

    /** A 422 must surface the API's own field message, not a generic failure. */
    @Test
    fun `a duplicate email surfaces the servers message`() = runTest {
        server.enqueue(
            MockResponse().setResponseCode(422).setBody(
                """
                {"message":"The given data was invalid.",
                 "errors":{"email":["An account with this email address already exists."]}}
                """.trimIndent(),
            ),
        )

        val result = repo.updateProfile("Ramon", "taken@rvms.local", "", "")

        assertTrue(result is UpdateProfileResult.Error)
        assertEquals(
            "An account with this email address already exists.",
            (result as UpdateProfileResult.Error).message,
        )
    }

    @Test
    fun `a weak password surfaces the servers message`() = runTest {
        server.enqueue(
            MockResponse().setResponseCode(422).setBody(
                """
                {"message":"The given data was invalid.",
                 "errors":{"password":["The password field must be at least 8 characters."]}}
                """.trimIndent(),
            ),
        )

        val result = repo.updateProfile("Ramon", "ramon@rvms.local", "short", "short")

        assertTrue(result is UpdateProfileResult.Error)
        assertTrue(
            (result as UpdateProfileResult.Error).message.contains("8 characters"),
        )
    }

    /** A dead connection is a message, never a crash mid-edit. */
    @Test
    fun `an unreachable server is reported as an error`() = runTest {
        server.shutdown()

        val result = repo.updateProfile("Ramon", "ramon@rvms.local", "", "")

        assertTrue(result is UpdateProfileResult.Error)
    }
}
