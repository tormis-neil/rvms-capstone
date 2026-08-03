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
 * A read distinguishes an empty inbox from an unreachable server (R10
 * sub-task 1). Both used to render as "no notifications", which told a driver
 * with no signal that nothing had happened — the one thing an alerts screen
 * must never say wrongly.
 */
class NotificationRepository(private val api: ApiService) {

    data class Inbox(
        val notifications: List<NotificationDto> = emptyList(),
        val unreadCount: Int = 0,
    )

    suspend fun inbox(): FetchResult<Inbox> = fetchCatching {
        val response = api.notifications()
        if (response.isSuccessful) {
            val body = response.body()
            FetchResult.Success(
                Inbox(
                    notifications = body?.data.orEmpty(),
                    unreadCount = body?.meta?.unreadCount ?: 0,
                ),
            )
        } else {
            FetchResult.Failure(FetchResult.OFFLINE_MESSAGE)
        }
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
