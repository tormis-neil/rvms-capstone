package com.example.rvms.ui.notification

import androidx.compose.foundation.background
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.size
import androidx.compose.foundation.layout.width
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.foundation.verticalScroll
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.Build
import androidx.compose.material.icons.filled.CarRental
import androidx.compose.material.icons.filled.Notifications
import androidx.compose.material3.AlertDialog
import androidx.compose.material3.Card
import androidx.compose.material3.CardDefaults
import androidx.compose.material3.ExperimentalMaterial3Api
import androidx.compose.material3.HorizontalDivider
import androidx.compose.material3.Icon
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Text
import androidx.compose.material3.TextButton
import androidx.compose.material3.pulltorefresh.PullToRefreshBox
import androidx.compose.runtime.Composable
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableIntStateOf
import androidx.compose.runtime.mutableStateListOf
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.rememberCoroutineScope
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.graphics.vector.ImageVector
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import com.example.rvms.data.ServiceLocator
import com.example.rvms.data.remote.dto.NotificationDto
import com.example.rvms.ui.common.ConnectionErrorCard
import com.example.rvms.ui.common.RefreshOnResume
import com.example.rvms.ui.common.ScreenHeader
import com.example.rvms.theme.Background
import com.example.rvms.theme.NavyBlue
import com.example.rvms.theme.Surface
import com.example.rvms.theme.TextPrimary
import com.example.rvms.theme.TextSecondary
import kotlinx.coroutines.launch
import kotlinx.serialization.json.JsonObject
import kotlinx.serialization.json.contentOrNull
import kotlinx.serialization.json.jsonPrimitive

/**
 * The driver's notification inbox (FR-21).
 *
 * Drivers receive two of the nine types — a vehicle status update for a vehicle
 * assigned to them, and a preventive-maintenance reminder. Everything else is
 * admin-facing and is filtered out server-side by user id.
 *
 * Tapping a notification opens its diagnostic detail dialog (showing the
 * status transition, source module, and the administrator's mechanical remarks
 * or findings) and marks the alert read.
 *
 * Rows are grouped Today / Yesterday / Earlier exactly as the web dashboard
 * groups them; the grouping and the time label are both computed by the API so
 * the two platforms cannot disagree.
 */
@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun NotificationScreen(
    /** Reports the unread count up so the Alerts tab badge stays in step. */
    onUnreadCountChanged: (Int) -> Unit = {},
    modifier: Modifier = Modifier,
) {
    val scrollState = rememberScrollState()
    val scope = rememberCoroutineScope()

    val notifications = remember { mutableStateListOf<NotificationDto>() }
    var unreadCount by remember { mutableIntStateOf(0) }
    var isRefreshing by remember { mutableStateOf(false) }
    var loaded by remember { mutableStateOf(false) }
    var selectedNotification by remember { mutableStateOf<NotificationDto?>(null) }

    var loadError by remember { mutableStateOf<String?>(null) }

    suspend fun load() {
        val result = ServiceLocator.notificationRepository.inbox()
        // Replaced only on success — an unreachable server must not blank the
        // inbox and read as "nothing has happened" (R10 sub-task 1).
        result.dataOrNull?.let { inbox ->
            notifications.clear()
            notifications.addAll(inbox.notifications)
            unreadCount = inbox.unreadCount
            onUnreadCountChanged(inbox.unreadCount)
        }
        loadError = result.errorOrNull
        loaded = true
    }

    RefreshOnResume { load() }

    selectedNotification?.let { selected ->
        NotificationDetailDialog(
            notification = selected,
            onDismiss = { selectedNotification = null },
        )
    }

    PullToRefreshBox(
        isRefreshing = isRefreshing,
        onRefresh = {
            isRefreshing = true
            scope.launch {
                load()
                isRefreshing = false
            }
        },
        modifier = modifier
            .fillMaxSize()
            .background(Background),
    ) {
        Column(
            modifier = Modifier
                .fillMaxSize()
                .verticalScroll(scrollState)
                .padding(16.dp),
        ) {
            Row(
                modifier = Modifier.fillMaxWidth(),
                horizontalArrangement = Arrangement.SpaceBetween,
                verticalAlignment = Alignment.CenterVertically,
            ) {
                ScreenHeader(title = "Notifications")

                // Always shown, disabled with nothing unread — the same way
                // the admin dashboard and the prototype present it. Hiding it
                // at zero made the control look absent rather than inactive.
                TextButton(
                    onClick = {
                        scope.launch {
                            ServiceLocator.notificationRepository.markAllRead()
                            load()
                        }
                    },
                    enabled = unreadCount > 0,
                ) {
                    Text(
                        text = "Mark all read",
                        style = MaterialTheme.typography.labelLarge,
                        // Set explicitly, so TextButton's own disabled tint
                        // cannot be overridden by the enabled colour.
                        color = if (unreadCount > 0) NavyBlue else TextSecondary.copy(alpha = 0.5f),
                    )
                }
            }

            Spacer(modifier = Modifier.height(20.dp))

            loadError?.let { message ->
                ConnectionErrorCard(message = message, staleDataShown = notifications.isNotEmpty())
                Spacer(modifier = Modifier.height(12.dp))
            }

            // The empty state is only truthful when the server actually
            // answered "empty" (R10 sub-task 1).
            if (loaded && notifications.isEmpty() && loadError == null) {
                EmptyInbox()
            }

            // Today / Yesterday / Earlier, in that order, skipping empty groups —
            // the same grouping the web dashboard uses.
            listOf("Today", "Yesterday", "Earlier").forEach { group ->
                val rows = notifications.filter { it.group == group }
                if (rows.isEmpty()) return@forEach

                Text(
                    text = group.uppercase(),
                    style = MaterialTheme.typography.labelSmall,
                    color = TextSecondary,
                    fontWeight = FontWeight.Bold,
                    modifier = Modifier.padding(bottom = 8.dp),
                )

                rows.forEach { notification ->
                    val remarksPreview = notification.payload.stringField("remarks")
                    NotificationItem(
                        title = notification.title,
                        body = notification.message,
                        remarksSnippet = remarksPreview,
                        time = notification.timeLabel.orEmpty(),
                        icon = iconFor(notification.type),
                        iconTint = toneColor(notification.tone),
                        isRead = notification.isRead,
                        onClick = {
                            selectedNotification = notification
                            if (!notification.isRead) {
                                scope.launch {
                                    ServiceLocator.notificationRepository.markRead(notification.id)
                                    load()
                                }
                            }
                        },
                    )
                }

                Spacer(modifier = Modifier.height(12.dp))
            }
        }
    }
}

private fun JsonObject?.stringField(key: String): String? =
    this?.get(key)
        ?.runCatching { jsonPrimitive.contentOrNull }
        ?.getOrNull()
        ?.takeIf { it.isNotBlank() && it != "null" }

/**
 * Diagnostic detail dialog shown when a driver taps a notification row.
 * Exposes the administrator's mechanical remarks, status transition, and source
 * module on status alerts so the driver knows why a vehicle was grounded,
 * placed under maintenance, dispatched, or cleared for service.
 */
@Composable
private fun NotificationDetailDialog(
    notification: NotificationDto,
    onDismiss: () -> Unit,
) {
    val payload = notification.payload
    val plate = payload.stringField("plate")
    val fromStatus = payload.stringField("from_status")
    val toStatus = payload.stringField("to_status")
    val source = payload.stringField("source")
    val remarks = payload.stringField("remarks")
    val serviceTarget = payload.stringField("service_target")
    val dueTarget = payload.stringField("due_target")
    val pmState = payload.stringField("state")

    AlertDialog(
        onDismissRequest = onDismiss,
        containerColor = Surface,
        title = {
            Column {
                Text(
                    text = notification.title,
                    style = MaterialTheme.typography.titleMedium,
                    fontWeight = FontWeight.Bold,
                    color = TextPrimary,
                )
                notification.timeLabel?.takeIf { it.isNotBlank() }?.let { time ->
                    Spacer(modifier = Modifier.height(2.dp))
                    Text(
                        text = time,
                        style = MaterialTheme.typography.labelSmall,
                        color = TextSecondary,
                    )
                }
            }
        },
        text = {
            Column(verticalArrangement = Arrangement.spacedBy(10.dp)) {
                Text(
                    text = notification.message,
                    style = MaterialTheme.typography.bodyMedium,
                    color = TextPrimary,
                    fontWeight = FontWeight.Medium,
                )

                HorizontalDivider(color = Background)

                plate?.let {
                    DiagnosticRow(label = "Vehicle Plate", value = it)
                }

                if (fromStatus != null && toStatus != null) {
                    DiagnosticRow(label = "Status Transition", value = "$fromStatus → $toStatus")
                } else if (toStatus != null) {
                    DiagnosticRow(label = "Updated Status", value = toStatus)
                }

                source?.let {
                    DiagnosticRow(label = "Source Module", value = it)
                }

                serviceTarget?.let {
                    DiagnosticRow(label = "Service Target", value = it)
                }

                dueTarget?.let {
                    DiagnosticRow(label = "Due At (Whichever Comes First)", value = it)
                }

                pmState?.let {
                    DiagnosticRow(label = "Maintenance State", value = it)
                }

                if (notification.type == "Vehicle_Status_Update" || remarks != null) {
                    Column(
                        modifier = Modifier
                            .fillMaxWidth()
                            .clip(RoundedCornerShape(8.dp))
                            .background(Background)
                            .padding(12.dp),
                    ) {
                        Text(
                            text = "Administrative Findings / Mechanical Remarks",
                            style = MaterialTheme.typography.labelSmall,
                            color = TextSecondary,
                            fontWeight = FontWeight.SemiBold,
                        )
                        Spacer(modifier = Modifier.height(4.dp))
                        Text(
                            text = remarks ?: "No additional mechanical remarks recorded.",
                            style = MaterialTheme.typography.bodyMedium,
                            color = TextPrimary,
                        )
                    }
                }
            }
        },
        confirmButton = {
            TextButton(onClick = onDismiss) {
                Text(
                    text = "Close",
                    color = NavyBlue,
                    fontWeight = FontWeight.SemiBold,
                )
            }
        },
    )
}

@Composable
private fun DiagnosticRow(
    label: String,
    value: String,
) {
    Column(modifier = Modifier.fillMaxWidth()) {
        Text(
            text = label,
            style = MaterialTheme.typography.labelSmall,
            color = TextSecondary,
        )
        Text(
            text = value,
            style = MaterialTheme.typography.bodyMedium,
            color = TextPrimary,
            fontWeight = FontWeight.SemiBold,
        )
    }
}

/**
 * The two driver-facing types. Everything else is admin-facing and never
 * reaches this inbox, but the fallback keeps an unknown type renderable rather
 * than crashing the screen.
 */
private fun iconFor(type: String): ImageVector = when (type) {
    "Vehicle_Status_Update" -> Icons.Filled.CarRental
    "PM_Reminder" -> Icons.Filled.Build
    else -> Icons.Filled.Notifications
}

/** Mirrors the server-side tone so both platforms colour a row identically. */
private fun toneColor(tone: String?): Color = when (tone) {
    "danger" -> Color(0xFFDC3545)
    "warning" -> Color(0xFFFFC107)
    "success" -> Color(0xFF198754)
    else -> NavyBlue
}

@Composable
private fun EmptyInbox() {
    Column(
        modifier = Modifier.fillMaxWidth(),
        horizontalAlignment = Alignment.CenterHorizontally,
    ) {
        Text(
            text = "No notifications yet.",
            style = MaterialTheme.typography.bodyMedium,
            color = TextSecondary,
        )
        Spacer(modifier = Modifier.height(4.dp))
        Text(
            text = "Vehicle status changes and preventive maintenance reminders will appear here.",
            style = MaterialTheme.typography.bodySmall,
            color = TextSecondary,
        )
    }
}

@Composable
private fun NotificationItem(
    title: String,
    body: String,
    remarksSnippet: String? = null,
    time: String,
    icon: ImageVector,
    iconTint: Color,
    isRead: Boolean,
    onClick: () -> Unit = {},
) {
    Card(
        modifier = Modifier
            .fillMaxWidth()
            .padding(bottom = 8.dp)
            .clickable(onClick = onClick),
        shape = RoundedCornerShape(12.dp),
        colors = CardDefaults.cardColors(
            containerColor = if (isRead) Surface else NavyBlue.copy(alpha = 0.05f),
        ),
    ) {
        Row(
            modifier = Modifier.padding(16.dp),
            verticalAlignment = Alignment.Top,
        ) {
            Box(
                modifier = Modifier
                    .size(40.dp)
                    .clip(CircleShape)
                    .background(iconTint.copy(alpha = 0.12f)),
                contentAlignment = Alignment.Center,
            ) {
                Icon(
                    imageVector = icon,
                    contentDescription = null,
                    tint = iconTint,
                    modifier = Modifier.size(20.dp),
                )
            }
            Spacer(modifier = Modifier.width(12.dp))
            Column(modifier = Modifier.weight(1f)) {
                Row(
                    modifier = Modifier.fillMaxWidth(),
                    horizontalArrangement = Arrangement.SpaceBetween,
                ) {
                    Text(
                        text = title,
                        style = MaterialTheme.typography.bodyMedium,
                        color = TextPrimary,
                        fontWeight = if (isRead) FontWeight.Normal else FontWeight.SemiBold,
                    )
                    Text(
                        text = time,
                        style = MaterialTheme.typography.labelSmall,
                        color = TextSecondary,
                    )
                }
                Spacer(modifier = Modifier.height(4.dp))
                Text(
                    text = body,
                    style = MaterialTheme.typography.bodySmall,
                    color = TextSecondary,
                )
                remarksSnippet?.let { note ->
                    Spacer(modifier = Modifier.height(4.dp))
                    Text(
                        text = "Remarks: $note",
                        style = MaterialTheme.typography.labelSmall,
                        color = NavyBlue,
                        fontWeight = FontWeight.Medium,
                        maxLines = 1,
                    )
                }
            }
        }
    }
}
