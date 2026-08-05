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
 * Proves VehicleRepository maps GET /my-vehicle (FR-07): the data-wrapped
 * list on success, an empty list when none are assigned or the call fails —
 * the driver-facing screens then render an explicit "no vehicle" state
 * rather than crashing on a null/empty collection.
 */
class VehicleRepositoryTest {

    private lateinit var server: MockWebServer
    private lateinit var repo: VehicleRepository

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

        repo = VehicleRepository(api)
    }

    @After
    fun tearDown() {
        // Some tests shut the server down themselves; a second call is a no-op.
        runCatching { server.shutdown() }
    }

    @Test
    fun `maps a single assigned vehicle`() = runTest {
        server.enqueue(
            MockResponse().setResponseCode(200).setBody(
                """
                {"data":[{"id":1,"agency_id":1,"type":"Fire Truck","plate_number":"ABC-1234",
                 "make":"Isuzu","model":"FTR 850","engine_number":"4HK1","chassis_number":"JALC",
                 "current_mileage":45230,"status":"Operational","remarks":null}]}
                """.trimIndent(),
            ),
        )

        val vehicles = repo.myVehicles().dataOrNull.orEmpty()

        assertEquals(1, vehicles.size)
        assertEquals("ABC-1234", vehicles[0].plateNumber)
        assertEquals("Operational", vehicles[0].status)
        assertEquals(45230, vehicles[0].currentMileage)
    }

    @Test
    fun `maps multiple vehicles for a driver assigned to several`() = runTest {
        server.enqueue(
            MockResponse().setResponseCode(200).setBody(
                """
                {"data":[
                  {"id":1,"agency_id":1,"type":"Fire Truck","plate_number":"ABC-1234","make":"Isuzu",
                   "model":"FTR 850","current_mileage":45230,"status":"Operational"},
                  {"id":2,"agency_id":1,"type":"Ambulance","plate_number":"DEF-5678","make":"Toyota",
                   "model":"Hiace","current_mileage":12000,"status":"Dispatched"}
                ]}
                """.trimIndent(),
            ),
        )

        val vehicles = repo.myVehicles().dataOrNull.orEmpty()

        assertEquals(2, vehicles.size)
        assertEquals("DEF-5678", vehicles[1].plateNumber)
    }

    /** The server answering "none" is a real, distinct outcome (FR-07). */
    @Test
    fun `no vehicle assigned is a SUCCESS carrying an empty list`() = runTest {
        server.enqueue(MockResponse().setResponseCode(200).setBody("""{"data":[]}"""))

        val result = repo.myVehicles()

        assertTrue(result is FetchResult.Success)
        assertTrue(result.dataOrNull.orEmpty().isEmpty())
    }

    /**
     * R10 sub-task 1: a failed request is a FAILURE, never an empty list.
     * The old behaviour collapsed both into emptyList(), so a driver with no
     * signal was told "No Vehicle Assigned" — a confident wrong answer.
     */
    @Test
    fun `a failed request is a Failure not an empty list`() = runTest {
        server.enqueue(MockResponse().setResponseCode(500))

        val result = repo.myVehicles()

        assertTrue(result is FetchResult.Failure)
        assertEquals(FetchResult.OFFLINE_MESSAGE, result.errorOrNull)
    }

    /** A dead connection maps the same way — and never throws. */
    @Test
    fun `an unreachable server is a Failure not a crash`() = runTest {
        server.shutdown()

        assertTrue(repo.myVehicles() is FetchResult.Failure)
    }
}
