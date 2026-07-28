package com.example.rvms.data

import com.example.rvms.data.remote.ApiService
import com.example.rvms.data.remote.dto.FcmTokenRequestDto
import com.example.rvms.data.remote.dto.NotificationDto

/**
 * The driver's notification inbox (FR-21).
 *
 * Drivers receive two of the nine types: a vehicle status update for a vehicle
 * assigned to them, and a preventive-maintenance reminder. Everything else is
 * admin-facing and never reaches this inbox — the API filters by user id, so the
 * app does not have to.
 *
 * Reads degrade to an empty inbox rather than an error state: a driver in the
 * field with no signal should see "no notifications", not a crash.
 */
class NotificationRepository(private val api: ApiService) {

    data class Inbox(
        val notifications: List<NotificationDto> = emptyList(),
        val unreadCount: Int = 0,
    )

    suspend fun inbox(): Inbox = try {
        val response = api.notifications()
        if (response.isSuccessful) {
            val body = response.body()
            Inbox(
                notifications = body?.data.orEmpty(),
                unreadCount = body?.meta?.unreadCount ?: 0,
            )
        } else {
            Inbox()
        }
    } catch (e: Exception) {
        Inbox()
    }

    /** Idempotent server-side: re-reading keeps the original read_at. */
    suspend fun markRead(id: Long): Boolean = try {
        api.markNotificationRead(id).isSuccessful
    } catch (e: Exception) {
        false
    }

    suspend fun markAllRead(): Boolean = try {
        api.markAllNotificationsRead().isSuccessful
    } catch (e: Exception) {
        false
    }

    /**
     * Registers this device for push. Called after sign-in and whenever Firebase
     * rotates the token; the server takes the token off any previous owner, so a
     * handset passed to another driver stops receiving the old driver's pushes.
     */
    suspend fun registerDevice(token: String): Boolean = try {
        api.registerFcmToken(FcmTokenRequestDto(token)).isSuccessful
    } catch (e: Exception) {
        false
    }

    /** Called on sign-out, before the bearer token is discarded. */
    suspend fun clearDevice(): Boolean = try {
        api.clearFcmToken().isSuccessful
    } catch (e: Exception) {
        false
    }
}
