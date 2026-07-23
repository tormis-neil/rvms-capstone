package com.example.rvms.data

import com.example.rvms.data.remote.ApiClient
import com.example.rvms.data.remote.ApiService
import com.example.rvms.data.remote.dto.SubmitInspectionItemDto
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
 * Proves InspectionRepository maps the FR-09 endpoints: the agency-correct
 * checklist (14 vs 12 by payload), submit success/422, and the driver's own
 * history (mapped, empty on failure).
 */
class InspectionRepositoryTest {

    private lateinit var server: MockWebServer
    private lateinit var repo: InspectionRepository

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

        repo = InspectionRepository(api)
    }

    @After
    fun tearDown() {
        server.shutdown()
    }

    @Test
    fun `checklist maps a BFP driver's 14 items including the two extras`() = runTest {
        val standard = (1..12).map {
            """{"id":$it,"name":"Item $it","is_bfp_only":false,"sort_order":$it}"""
        }
        val extras = listOf(
            """{"id":13,"name":"Hydraulic System","is_bfp_only":true,"sort_order":13}""",
            """{"id":14,"name":"Fire Pump","is_bfp_only":true,"sort_order":14}""",
        )
        val body = """{"data":[${(standard + extras).joinToString(",")}]}"""
        server.enqueue(MockResponse().setResponseCode(200).setBody(body))

        val checklist = repo.checklist()

        assertEquals(14, checklist.size)
        assertEquals(2, checklist.count { it.isBfpOnly })
    }

    @Test
    fun `checklist maps a non-BFP driver's 12 items`() = runTest {
        val items = (1..12).joinToString(",") {
            """{"id":$it,"name":"Item $it","is_bfp_only":false,"sort_order":$it}"""
        }
        server.enqueue(MockResponse().setResponseCode(200).setBody("""{"data":[$items]}"""))

        val checklist = repo.checklist()

        assertEquals(12, checklist.size)
        assertTrue(checklist.none { it.isBfpOnly })
    }

    @Test
    fun `submit success returns Success`() = runTest {
        server.enqueue(
            MockResponse().setResponseCode(201).setBody(
                """{"data":{"id":1,"vehicle_id":1,"driver_id":1,"review_status":"Pending","items":[]}}""",
            ),
        )

        val result = repo.submit(
            vehicleId = 1,
            items = listOf(SubmitInspectionItemDto(1, "OK", null)),
        )

        assertTrue(result is SubmitInspectionResult.Success)
    }

    @Test
    fun `submit validation failure surfaces the 422 message`() = runTest {
        server.enqueue(
            MockResponse().setResponseCode(422).setBody(
                """
                {"message":"The given data was invalid.",
                 "errors":{"items.0.remarks":["Remarks are required for a flagged item."]}}
                """.trimIndent(),
            ),
        )

        val result = repo.submit(
            vehicleId = 1,
            items = listOf(SubmitInspectionItemDto(1, "Has Issue", null)),
        )

        assertTrue(result is SubmitInspectionResult.Error)
        assertEquals(
            "Remarks are required for a flagged item.",
            (result as SubmitInspectionResult.Error).message,
        )
    }

    @Test
    fun `history maps the driver's own inspections`() = runTest {
        server.enqueue(
            MockResponse().setResponseCode(200).setBody(
                """
                {"data":[
                  {"id":5,"vehicle_id":1,"driver_id":1,"inspection_date":"2026-07-23",
                   "review_status":"Pending","result":"1 Issue",
                   "items":[{"checklist_item_id":5,"name":"Brakes","status":"Has Issue","remarks":"Soft"}]}
                ]}
                """.trimIndent(),
            ),
        )

        val history = repo.history()

        assertEquals(1, history.size)
        assertEquals(5L, history[0].id)
        assertEquals("Brakes", history[0].items.first().name)
    }

    @Test
    fun `history is empty on a failed request`() = runTest {
        server.enqueue(MockResponse().setResponseCode(500))

        assertTrue(repo.history().isEmpty())
    }
}
