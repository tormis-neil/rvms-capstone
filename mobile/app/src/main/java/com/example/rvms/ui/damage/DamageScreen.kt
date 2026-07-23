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
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.CheckCircle
import androidx.compose.material.icons.filled.Info
import androidx.compose.material3.Button
import androidx.compose.material3.ButtonDefaults
import androidx.compose.material3.Card
import androidx.compose.material3.CardDefaults
import androidx.compose.material3.Icon
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateListOf
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import com.example.rvms.data.ServiceLocator
import com.example.rvms.data.remote.dto.DamageDto
import com.example.rvms.ui.common.ScreenHeader
import com.example.rvms.ui.common.formatIsoDate
import com.example.rvms.theme.Background
import com.example.rvms.theme.NavyBlue
import com.example.rvms.theme.ReportPending
import com.example.rvms.theme.ReportReviewed
import com.example.rvms.theme.Surface
import com.example.rvms.theme.TextPrimary
import com.example.rvms.theme.TextSecondary
import com.example.rvms.theme.White

@Composable
fun DamageScreen(
    onSubmitNew: () -> Unit,
    modifier: Modifier = Modifier,
) {
    val scrollState = rememberScrollState()

    // The driver's OWN damage reports (FR-11), empty for a fresh account.
    // Re-fetched whenever this tab is entered (Nav3 recomposes the shell on
    // return from the New Damage Report screen).
    val reports = remember { mutableStateListOf<DamageDto>() }
    var loaded by remember { mutableStateOf(false) }
    var vehicleLabel by remember { mutableStateOf("") }

    LaunchedEffect(Unit) {
        reports.clear()
        reports.addAll(ServiceLocator.damageRepository.history())
        val vehicle = ServiceLocator.vehicleRepository.myVehicles().firstOrNull()
        vehicleLabel = vehicle?.let { "${it.type} — ${it.plateNumber}" }.orEmpty()
        loaded = true
    }

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

        Spacer(modifier = Modifier.height(24.dp))

        Text(
            text = "Damage Reports History",
            style = MaterialTheme.typography.titleMedium,
            color = TextPrimary,
            fontWeight = FontWeight.Bold,
        )
        if (vehicleLabel.isNotBlank()) {
            Text(
                text = vehicleLabel,
                style = MaterialTheme.typography.bodySmall,
                color = TextSecondary,
            )
        }

        Spacer(modifier = Modifier.height(12.dp))

        if (reports.isEmpty()) {
            Text(
                text = if (loaded) "No damage reports submitted yet." else "Loading…",
                style = MaterialTheme.typography.bodyMedium,
                color = TextSecondary,
            )
        } else {
            reports.forEach { report ->
                DamageHistoryItem(report)
            }
        }

        Spacer(modifier = Modifier.height(16.dp))
    }
}

@Composable
private fun DamageHistoryItem(report: DamageDto) {
    // Report review statuses use their own colors, never the vehicle palette.
    val isPending = report.status == "Pending"
    val statusColor = if (isPending) ReportPending else ReportReviewed
    val icon = if (isPending) Icons.Default.Info else Icons.Default.CheckCircle

    Card(
        modifier = Modifier
            .fillMaxWidth()
            .padding(bottom = 10.dp),
        shape = RoundedCornerShape(12.dp),
        colors = CardDefaults.cardColors(containerColor = Surface),
    ) {
        Column(modifier = Modifier.padding(16.dp)) {
            Row(
                modifier = Modifier.fillMaxWidth(),
                verticalAlignment = Alignment.CenterVertically,
            ) {
                Box(
                    modifier = Modifier
                        .size(40.dp)
                        .clip(CircleShape)
                        .background(statusColor.copy(alpha = 0.12f)),
                    contentAlignment = Alignment.Center,
                ) {
                    Icon(
                        imageVector = icon,
                        contentDescription = null,
                        tint = statusColor,
                        modifier = Modifier.size(20.dp),
                    )
                }
                Spacer(modifier = Modifier.width(12.dp))
                Text(
                    text = formatIsoDate(report.dateReported),
                    style = MaterialTheme.typography.bodySmall,
                    color = TextSecondary,
                    modifier = Modifier.weight(1f),
                )
                Box(
                    modifier = Modifier
                        .clip(RoundedCornerShape(12.dp))
                        .background(statusColor.copy(alpha = 0.1f))
                        .padding(horizontal = 12.dp, vertical = 4.dp),
                ) {
                    Text(
                        text = report.status,
                        color = statusColor,
                        fontSize = 12.sp,
                        fontWeight = FontWeight.SemiBold,
                    )
                }
            }
            Spacer(modifier = Modifier.height(10.dp))
            Text(
                text = report.natureOfDamage,
                style = MaterialTheme.typography.bodyMedium,
                color = TextPrimary,
                fontWeight = FontWeight.Medium,
            )
            if (!report.suspectedParts.isNullOrBlank()) {
                Spacer(modifier = Modifier.height(2.dp))
                Text(
                    text = "Suspected parts: ${report.suspectedParts}",
                    style = MaterialTheme.typography.bodySmall,
                    color = TextSecondary,
                )
            }
        }
    }
}
