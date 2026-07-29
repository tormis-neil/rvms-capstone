package com.example.rvms.data.remote.dto

import kotlinx.serialization.SerialName
import kotlinx.serialization.Serializable
import kotlinx.serialization.json.JsonObject

/*
 * DTOs for the driver notification inbox (FR-21):
 *   - GET   /notifications                 (own inbox, newest first)
 *   - PATCH /notifications/{id}/read
 *   - PATCH /notifications/read-all
 *   - POST  /fcm-token  /  DELETE /fcm-token
 * Shapes mirror NotificationResource exactly.
 *
 * Note the payload field is `payload`, not `data`: a resource array containing a
 * `data` key makes Laravel skip its own `data` envelope, so single resources
 * came back unwrapped while collections stayed wrapped. The API renames it to
 * keep one shape on both endpoints.
 */

/** GET /notifications — `{ "data": [...], "meta": { "unread_count": n } }`. */
@Serializable
data class NotificationListDto(
    val data: List<NotificationDto> = emptyList(),
    val meta: NotificationMetaDto? = null,
)

@Serializable
data class NotificationMetaDto(
    @SerialName("unread_count") val unreadCount: Int = 0,
)

/** PATCH /notifications/{id}/read — `{ "data": {...} }`. */
@Serializable
data class NotificationEnvelopeDto(
    val data: NotificationDto,
)

@Serializable
data class NotificationDto(
    val id: Long,
    /** One of the nine FR-21 types; drivers only ever receive two of them. */
    val type: String,
    val title: String,
    val message: String,
    /**
     * The reference payload, kept as raw JSON on purpose.
     *
     * It is NOT Map<String, String?>: the backend puts real ids in here
     * (`vehicle_id`, `pm_schedule_id`) and, for flagged inspections, an array of
     * item names. Typing it as strings made kotlinx.serialization throw on every
     * driver notification — and the repository's catch turned that into an empty
     * inbox, so the driver saw "No notifications yet" instead of their alerts.
     * JsonObject accepts whatever shape a type sends.
     */
    val payload: JsonObject? = null,
    @SerialName("is_read") val isRead: Boolean = false,
    @SerialName("read_at") val readAt: String? = null,
    @SerialName("created_at") val createdAt: String? = null,
    /** Presentation computed server-side so both platforms agree. */
    val icon: String? = null,
    val tone: String? = null,
    val group: String? = null,
    @SerialName("time_label") val timeLabel: String? = null,
)

/** POST /fcm-token — the device token Firebase issued for this install. */
@Serializable
data class FcmTokenRequestDto(
    @SerialName("fcm_token") val fcmToken: String,
)

/** Response envelope for both the register and clear token calls. */
@Serializable
data class FcmTokenResponseDto(
    val data: FcmTokenStateDto,
)

@Serializable
data class FcmTokenStateDto(
    val registered: Boolean,
)

/** PATCH /notifications/read-all — `{ "data": { marked_read, unread_count } }`. */
@Serializable
data class ReadAllResponseDto(
    val data: ReadAllStateDto,
)

@Serializable
data class ReadAllStateDto(
    @SerialName("marked_read") val markedRead: Int = 0,
    @SerialName("unread_count") val unreadCount: Int = 0,
)
