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
import androidx.compose.material3.Card
import androidx.compose.material3.CardDefaults
import androidx.compose.material3.ExperimentalMaterial3Api
import androidx.compose.material3.Icon
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Text
import androidx.compose.material3.TextButton
import androidx.compose.material3.pulltorefresh.PullToRefreshBox
import androidx.compose.runtime.Composable
import androidx.compose.runtime.LaunchedEffect
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
import com.example.rvms.ui.common.ScreenHeader
import com.example.rvms.theme.Background
import com.example.rvms.theme.NavyBlue
import com.example.rvms.theme.Surface
import com.example.rvms.theme.TextPrimary
import com.example.rvms.theme.TextSecondary
import kotlinx.coroutines.launch

/**
 * The driver's notification inbox (FR-21).
 *
 * Drivers receive two of the nine types — a vehicle status update for a vehicle
 * assigned to them, and a preventive-maintenance reminder. Everything else is
 * admin-facing and is filtered out server-side by user id.
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

    suspend fun load() {
        val inbox = ServiceLocator.notificationRepository.inbox()
        notifications.clear()
        notifications.addAll(inbox.notifications)
        unreadCount = inbox.unreadCount
        onUnreadCountChanged(inbox.unreadCount)
        loaded = true
    }

    LaunchedEffect(Unit) { load() }

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

                if (unreadCount > 0) {
                    TextButton(onClick = {
                        scope.launch {
                            ServiceLocator.notificationRepository.markAllRead()
                            load()
                        }
                    }) {
                        Text(
                            text = "Mark all read",
                            style = MaterialTheme.typography.labelLarge,
                            color = NavyBlue,
                        )
                    }
                }
            }

            Spacer(modifier = Modifier.height(20.dp))

            if (loaded && notifications.isEmpty()) {
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
                    NotificationItem(
                        title = notification.title,
                        body = notification.message,
                        time = notification.timeLabel.orEmpty(),
                        icon = iconFor(notification.type),
                        iconTint = toneColor(notification.tone),
                        isRead = notification.isRead,
                        onClick = {
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
            }
        }
    }
}
