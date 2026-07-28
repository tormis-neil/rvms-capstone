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
 * Proves NotificationRepository maps the FR-21 inbox endpoints.
 *
 * Drivers receive two of the nine types — Vehicle_Status_Update and
 * PM_Reminder — and the API filters by user id, so the app never has to.
 * Reads degrade to an empty inbox rather than an error: a driver in the field
 * with no signal should see "no notifications", not a crash.
 */
class NotificationRepositoryTest {

    private lateinit var server: MockWebServer
    private lateinit var repo: NotificationRepository

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

        repo = NotificationRepository(api)
    }

    @After
    fun tearDown() {
        server.shutdown()
    }

    private fun jsonResponse(body: String) = MockResponse()
        .setResponseCode(200)
        .setHeader("Content-Type", "application/json")
        .setBody(body)

    @Test
    fun `maps the inbox with its unread count`() = runTest {
        server.enqueue(
            jsonResponse(
                """
                {
                  "data": [
                    {
                      "id": 7,
                      "type": "Vehicle_Status_Update",
                      "title": "Vehicle Status Updated",
                      "message": "BFP-0001 is now Not Operational.",
                      "payload": {"plate": "BFP-0001", "to": "Not Operational"},
                      "is_read": false,
                      "read_at": null,
                      "created_at": "2026-07-28T08:10:00.000000Z",
                      "icon": "bi-truck",
                      "tone": "primary",
                      "group": "Today",
                      "time_label": "8:10 AM"
                    },
                    {
                      "id": 6,
                      "type": "PM_Reminder",
                      "title": "Preventive Maintenance",
                      "message": "BFP-0001 — Oil Change & Filter is due soon.",
                      "payload": {"plate": "BFP-0001"},
                      "is_read": true,
                      "read_at": "2026-07-27T10:00:00.000000Z",
                      "created_at": "2026-07-27T08:00:00.000000Z",
                      "icon": "bi-wrench-adjustable",
                      "tone": "warning",
                      "group": "Yesterday",
                      "time_label": "8:00 AM"
                    }
                  ],
                  "meta": {"unread_count": 1}
                }
                """.trimIndent(),
            ),
        )

        val inbox = repo.inbox()

        assertEquals(2, inbox.notifications.size)
        assertEquals(1, inbox.unreadCount)

        val first = inbox.notifications.first()
        assertEquals("Vehicle_Status_Update", first.type)
        assertEquals("Vehicle Status Updated", first.title)
        assertEquals("Today", first.group)
        assertEquals("8:10 AM", first.timeLabel)
        assertFalse(first.isRead)
        assertEquals("BFP-0001", first.payload?.get("plate"))

        val second = inbox.notifications[1]
        assertEquals("PM_Reminder", second.type)
        assertEquals("Yesterday", second.group)
        assertTrue(second.isRead)
    }

    /** The payload is `payload`, not `data` — it would collide with the envelope. */
    @Test
    fun `a notification without a payload still parses`() = runTest {
        server.enqueue(
            jsonResponse(
                """
                {"data":[{"id":1,"type":"PM_Reminder","title":"Preventive Maintenance",
                "message":"Service due.","is_read":false}],"meta":{"unread_count":1}}
                """.trimIndent(),
            ),
        )

        val inbox = repo.inbox()

        assertEquals(1, inbox.notifications.size)
        assertEquals(null, inbox.notifications.first().payload)
    }

    @Test
    fun `an empty inbox maps to no notifications`() = runTest {
        server.enqueue(jsonResponse("""{"data":[],"meta":{"unread_count":0}}"""))

        val inbox = repo.inbox()

        assertTrue(inbox.notifications.isEmpty())
        assertEquals(0, inbox.unreadCount)
    }

    /** A failure must look like an empty inbox, never an exception. */
    @Test
    fun `a server error degrades to an empty inbox`() = runTest {
        server.enqueue(MockResponse().setResponseCode(500))

        val inbox = repo.inbox()

        assertTrue(inbox.notifications.isEmpty())
        assertEquals(0, inbox.unreadCount)
    }

    @Test
    fun `an unauthenticated response degrades to an empty inbox`() = runTest {
        server.enqueue(MockResponse().setResponseCode(401))

        assertTrue(repo.inbox().notifications.isEmpty())
    }

    @Test
    fun `marking one read reports success`() = runTest {
        server.enqueue(
            jsonResponse(
                """
                {"data":{"id":7,"type":"PM_Reminder","title":"Preventive Maintenance",
                "message":"Service due.","is_read":true,"read_at":"2026-07-28T09:00:00.000000Z"}}
                """.trimIndent(),
            ),
        )

        assertTrue(repo.markRead(7))

        val request = server.takeRequest()
        assertEquals("PATCH", request.method)
        assertEquals("/notifications/7/read", request.path)
    }

    /** Someone else's notification 404s; the app must not treat that as success. */
    @Test
    fun `marking a foreign notification read reports failure`() = runTest {
        server.enqueue(MockResponse().setResponseCode(404))

        assertFalse(repo.markRead(999))
    }

    @Test
    fun `mark all read hits the read-all endpoint`() = runTest {
        server.enqueue(jsonResponse("""{"data":{"marked_read":3,"unread_count":0}}"""))

        assertTrue(repo.markAllRead())

        val request = server.takeRequest()
        assertEquals("PATCH", request.method)
        assertEquals("/notifications/read-all", request.path)
    }

    @Test
    fun `registering a device posts the token`() = runTest {
        server.enqueue(jsonResponse("""{"data":{"registered":true}}"""))

        assertTrue(repo.registerDevice("device-abc"))

        val request = server.takeRequest()
        assertEquals("POST", request.method)
        assertEquals("/fcm-token", request.path)
        assertTrue(request.body.readUtf8().contains("\"fcm_token\":\"device-abc\""))
    }

    @Test
    fun `clearing a device sends a delete`() = runTest {
        server.enqueue(jsonResponse("""{"data":{"registered":false}}"""))

        assertTrue(repo.clearDevice())

        val request = server.takeRequest()
        assertEquals("DELETE", request.method)
        assertEquals("/fcm-token", request.path)
    }

    /** Sign-out must not fail because the token could not be cleared. */
    @Test
    fun `a failed device clear reports false rather than throwing`() = runTest {
        server.enqueue(MockResponse().setResponseCode(500))

        assertFalse(repo.clearDevice())
    }
}
