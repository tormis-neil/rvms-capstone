package com.example.rvms.ui.notification

import androidx.compose.foundation.background
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
import androidx.compose.material3.Card
import androidx.compose.material3.CardDefaults
import androidx.compose.material3.ExperimentalMaterial3Api
import androidx.compose.material3.Icon
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Text
import androidx.compose.material3.pulltorefresh.PullToRefreshBox
import androidx.compose.runtime.Composable
import androidx.compose.runtime.getValue
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
import com.example.rvms.data.NotificationType
import com.example.rvms.data.Session
import com.example.rvms.ui.common.ScreenHeader
import com.example.rvms.ui.common.notificationColor
import com.example.rvms.theme.Background
import com.example.rvms.theme.NavyBlue
import com.example.rvms.theme.Surface
import com.example.rvms.theme.TextPrimary
import com.example.rvms.theme.TextSecondary
import kotlinx.coroutines.delay
import kotlinx.coroutines.launch

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun NotificationScreen(
    modifier: Modifier = Modifier,
) {
    val scrollState = rememberScrollState()
    val notifications = Session.current.notifications

    // Simulated refresh — with the backend this will re-fetch notifications
    var isRefreshing by remember { mutableStateOf(false) }
    val scope = rememberCoroutineScope()

    PullToRefreshBox(
        isRefreshing = isRefreshing,
        onRefresh = {
            isRefreshing = true
            scope.launch {
                delay(900)
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
        ScreenHeader(title = "Notifications")

        Spacer(modifier = Modifier.height(20.dp))

        // Group by time bucket, preserving the data's order (Today, Yesterday, Earlier)
        notifications
            .groupBy { it.timeGroup }
            .forEach { (group, items) ->
                Text(
                    text = group,
                    style = MaterialTheme.typography.titleSmall,
                    color = TextSecondary,
                    fontWeight = FontWeight.SemiBold,
                )
                Spacer(modifier = Modifier.height(8.dp))

                items.forEach { notification ->
                    val icon = when (notification.type) {
                        NotificationType.PM_REMINDER -> Icons.Default.Build
                        NotificationType.VEHICLE_STATUS_UPDATE -> Icons.Default.CarRental
                    }
                    NotificationItem(
                        title = notification.title,
                        body = notification.body,
                        time = notification.time,
                        icon = icon,
                        iconTint = notificationColor(notification.type, notification.status),
                        isRead = notification.isRead,
                    )
                }

                Spacer(modifier = Modifier.height(16.dp))
            }
    }
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
) {
    Card(
        modifier = Modifier
            .fillMaxWidth()
            .padding(bottom = 8.dp),
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
