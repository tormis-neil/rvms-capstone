package com.example.rvms.data

import com.example.rvms.data.remote.ApiClient
import com.example.rvms.data.remote.ApiService
import kotlinx.coroutines.test.runTest
import okhttp3.MediaType.Companion.toMediaType
import okhttp3.mockwebserver.MockResponse
import okhttp3.mockwebserver.MockWebServer
import org.junit.After
import org.junit.Assert.assertEquals
import org.junit.Assert.assertTrue
import org.junit.Before
import org.junit.Test
import retrofit2.Retrofit
import retrofit2.converter.kotlinx.serialization.asConverterFactory

/**
 * Proves DamageRepository maps the FR-11 endpoints: submit success/422 (photo
 * optional), and the driver's own history (mapped, empty on failure).
 */
class DamageRepositoryTest {

    private lateinit var server: MockWebServer
    private lateinit var repo: DamageRepository

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

        repo = DamageRepository(api)
    }

    @After
    fun tearDown() {
        server.shutdown()
    }

    @Test
    fun `submit without a photo succeeds`() = runTest {
        server.enqueue(
            MockResponse().setResponseCode(201).setBody(
                """{"data":{"id":1,"vehicle_id":1,"driver_id":1,"nature_of_damage":"Cracked mirror","status":"Pending"}}""",
            ),
        )

        val result = repo.submit(
            vehicleId = 1,
            natureOfDamage = "Cracked mirror",
            suspectedParts = "Mirror assembly",
            photo = null,
        )

        assertTrue(result is SubmitDamageResult.Success)

        // The multipart request carried the text fields.
        val body = server.takeRequest().body.readUtf8()
        assertTrue(body.contains("Cracked mirror"))
        assertTrue(body.contains("name=\"vehicle_id\""))
    }

    @Test
    fun `submit validation failure surfaces the 422 message`() = runTest {
        server.enqueue(
            MockResponse().setResponseCode(422).setBody(
                """{"message":"The given data was invalid.","errors":{"nature_of_damage":["Please describe the nature of the damage."]}}""",
            ),
        )

        val result = repo.submit(
            vehicleId = 1,
            natureOfDamage = "",
            suspectedParts = null,
            photo = null,
        )

        assertTrue(result is SubmitDamageResult.Error)
        assertEquals(
            "Please describe the nature of the damage.",
            (result as SubmitDamageResult.Error).message,
        )
    }

    @Test
    fun `history maps the driver's own reports`() = runTest {
        server.enqueue(
            MockResponse().setResponseCode(200).setBody(
                """
                {"data":[
                  {"id":3,"vehicle_id":1,"driver_id":1,"nature_of_damage":"Dented bumper",
                   "suspected_parts":"Rear bumper","date_reported":"2026-07-23","status":"Pending"}
                ]}
                """.trimIndent(),
            ),
        )

        val history = repo.history()

        assertEquals(1, history.size)
        assertEquals("Dented bumper", history[0].natureOfDamage)
        assertEquals("Pending", history[0].status)
    }

    @Test
    fun `history is empty on a failed request`() = runTest {
        server.enqueue(MockResponse().setResponseCode(500))

        assertTrue(repo.history().isEmpty())
    }
}
