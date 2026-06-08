package com.example.rvms.ui.notification

import androidx.compose.foundation.background
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
import androidx.compose.material3.Card
import androidx.compose.material3.CardDefaults
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import com.example.rvms.theme.Background
import com.example.rvms.theme.InfoBlue
import com.example.rvms.theme.NavyBlue
import com.example.rvms.theme.StatusOperational
import com.example.rvms.theme.Surface
import com.example.rvms.theme.TextPrimary
import com.example.rvms.theme.TextSecondary
import com.example.rvms.theme.WarningOrange

@Composable
fun NotificationScreen(
    modifier: Modifier = Modifier,
) {
    val scrollState = rememberScrollState()

    Column(
        modifier = modifier
            .fillMaxSize()
            .background(Background)
            .verticalScroll(scrollState)
            .padding(16.dp),
    ) {
        Text(
            text = "Notifications",
            style = MaterialTheme.typography.headlineSmall,
            color = TextPrimary,
            fontWeight = FontWeight.Bold,
        )

        Spacer(modifier = Modifier.height(20.dp))

        // Today
        Text(
            text = "Today",
            style = MaterialTheme.typography.titleSmall,
            color = TextSecondary,
            fontWeight = FontWeight.SemiBold,
        )
        Spacer(modifier = Modifier.height(8.dp))

        NotificationItem(
            title = "Vehicle Status Updated",
            body = "Your vehicle status has been changed to Operational.",
            time = "4:00 PM",
            dotColor = StatusOperational,
            isRead = false,
        )
        NotificationItem(
            title = "PM Reminder",
            body = "Oil change is due soon at 46,000 km. Current mileage: 45,230 km.",
            time = "8:00 AM",
            dotColor = WarningOrange,
            isRead = false,
        )

        Spacer(modifier = Modifier.height(16.dp))

        // Yesterday
        Text(
            text = "Yesterday",
            style = MaterialTheme.typography.titleSmall,
            color = TextSecondary,
            fontWeight = FontWeight.SemiBold,
        )
        Spacer(modifier = Modifier.height(8.dp))

        NotificationItem(
            title = "Vehicle Status Updated",
            body = "Your vehicle status has been changed to Under PM.",
            time = "2:30 PM",
            dotColor = InfoBlue,
            isRead = true,
        )

        Spacer(modifier = Modifier.height(16.dp))

        // Earlier
        Text(
            text = "Earlier",
            style = MaterialTheme.typography.titleSmall,
            color = TextSecondary,
            fontWeight = FontWeight.SemiBold,
        )
        Spacer(modifier = Modifier.height(8.dp))

        NotificationItem(
            title = "PM Reminder",
            body = "Tire rotation is due. Please coordinate with your agency admin.",
            time = "Jun 5",
            dotColor = WarningOrange,
            isRead = true,
        )
        NotificationItem(
            title = "Vehicle Status Updated",
            body = "Your vehicle status has been changed to Operational.",
            time = "Jun 3",
            dotColor = StatusOperational,
            isRead = true,
        )

        Spacer(modifier = Modifier.height(16.dp))
    }
}

@Composable
private fun NotificationItem(
    title: String,
    body: String,
    time: String,
    dotColor: Color,
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
                    .padding(top = 4.dp)
                    .size(10.dp)
                    .clip(CircleShape)
                    .background(dotColor),
            )
            Spacer(modifier = Modifier.width(12.dp))
            Column(modifier = Modifier.weight(1f)) {
                Row(
                    modifier = Modifier.fillMaxWidth(),
                    horizontalArrangement = androidx.compose.foundation.layout.Arrangement.SpaceBetween,
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
