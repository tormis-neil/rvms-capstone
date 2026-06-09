package com.example.rvms.ui.damage

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
import androidx.compose.material3.Button
import androidx.compose.material3.ButtonDefaults
import androidx.compose.material3.Card
import androidx.compose.material3.CardDefaults
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import com.example.rvms.data.DamageStatus
import com.example.rvms.data.Session
import com.example.rvms.ui.common.ScreenHeader
import com.example.rvms.theme.Background
import com.example.rvms.theme.NavyBlue
import com.example.rvms.theme.StatusNotOperational
import com.example.rvms.theme.StatusOperational
import com.example.rvms.theme.Surface
import com.example.rvms.theme.TextPrimary
import com.example.rvms.theme.TextSecondary
import com.example.rvms.theme.White

@Composable
fun DamageScreen(
    onSubmitNew: () -> Unit,
    onViewReports: () -> Unit,
    modifier: Modifier = Modifier,
) {
    val scrollState = rememberScrollState()
    val data = Session.current
    val lastReport = data.damageReports.firstOrNull()

    Column(
        modifier = modifier
            .fillMaxSize()
            .background(Background)
            .verticalScroll(scrollState)
            .padding(16.dp),
    ) {
        ScreenHeader(
            title = "Damage Report",
            subtitle = "Submit and track vehicle damage reports",
        )

        Spacer(modifier = Modifier.height(20.dp))

        Button(
            onClick = onSubmitNew,
            modifier = Modifier
                .fillMaxWidth()
                .height(56.dp),
            shape = RoundedCornerShape(12.dp),
            colors = ButtonDefaults.buttonColors(containerColor = NavyBlue),
        ) {
            Text(
                text = "Submit New Report",
                color = White,
                fontSize = 16.sp,
                fontWeight = FontWeight.SemiBold,
            )
        }

        Spacer(modifier = Modifier.height(12.dp))

        Button(
            onClick = onViewReports,
            modifier = Modifier
                .fillMaxWidth()
                .height(48.dp),
            shape = RoundedCornerShape(12.dp),
            colors = ButtonDefaults.buttonColors(
                containerColor = Surface,
                contentColor = NavyBlue,
            ),
        ) {
            Text(
                text = "View My Reports",
                fontSize = 14.sp,
                fontWeight = FontWeight.SemiBold,
            )
        }

        Spacer(modifier = Modifier.height(24.dp))

        Text(
            text = "Latest Report",
            style = MaterialTheme.typography.titleMedium,
            color = TextPrimary,
            fontWeight = FontWeight.Bold,
        )

        Spacer(modifier = Modifier.height(12.dp))

        Card(
            modifier = Modifier.fillMaxWidth(),
            shape = RoundedCornerShape(12.dp),
            colors = CardDefaults.cardColors(containerColor = Surface),
        ) {
            Row(
                modifier = Modifier.padding(20.dp),
                verticalAlignment = Alignment.CenterVertically,
            ) {
                val isPending = lastReport?.status == DamageStatus.PENDING
                val statusColor = if (isPending) StatusNotOperational else StatusOperational
                Box(
                    modifier = Modifier
                        .size(48.dp)
                        .clip(CircleShape)
                        .background(statusColor.copy(alpha = 0.1f)),
                    contentAlignment = Alignment.Center,
                ) {
                    Text(
                        text = if (lastReport == null) "—" else if (isPending) "!" else "✓",
                        color = if (lastReport == null) TextSecondary else statusColor,
                        fontSize = 24.sp,
                        fontWeight = FontWeight.Bold,
                    )
                }
                Spacer(modifier = Modifier.width(16.dp))
                Column {
                    Text(
                        text = if (lastReport != null) lastReport.status.label else "No Reports Yet",
                        style = MaterialTheme.typography.bodyLarge,
                        color = TextPrimary,
                        fontWeight = FontWeight.SemiBold,
                    )
                    Text(
                        text = lastReport?.let { "${it.date} — ${it.nature}" }
                            ?: "Submit a damage report to track vehicle issues.",
                        style = MaterialTheme.typography.bodySmall,
                        color = TextSecondary,
                    )
                }
            }
        }
    }
}
