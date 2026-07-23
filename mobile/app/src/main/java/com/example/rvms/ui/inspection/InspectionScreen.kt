package com.example.rvms.ui.inspection

import androidx.compose.foundation.background
import androidx.compose.foundation.clickable
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
import androidx.compose.material.icons.filled.Warning
import androidx.compose.material3.AlertDialog
import androidx.compose.material3.Button
import androidx.compose.material3.ButtonDefaults
import androidx.compose.material3.Card
import androidx.compose.material3.CardDefaults
import androidx.compose.material3.Icon
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Text
import androidx.compose.material3.TextButton
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
import com.example.rvms.data.remote.dto.InspectionDto
import com.example.rvms.ui.common.ScreenHeader
import com.example.rvms.ui.common.formatIsoDate
import com.example.rvms.ui.common.formatIsoTime
import com.example.rvms.ui.common.todayIso
import com.example.rvms.theme.Background
import com.example.rvms.theme.NavyBlue
import com.example.rvms.theme.StatusNotOperational
import com.example.rvms.theme.StatusOperational
import com.example.rvms.theme.Surface
import com.example.rvms.theme.TextPrimary
import com.example.rvms.theme.TextSecondary
import com.example.rvms.theme.White

/** Issue count from a real inspection's items. */
private fun InspectionDto.issueCount(): Int = items.count { it.status == "Has Issue" }

/** Names of flagged items. */
private fun InspectionDto.flaggedNames(): List<String> =
    items.filter { it.status == "Has Issue" }.mapNotNull { it.name }

private fun InspectionDto.resultLabel(): String =
    result ?: when (val n = issueCount()) {
        0 -> "All OK"
        1 -> "1 Issue"
        else -> "$n Issues"
    }

@Composable
fun InspectionScreen(
    onStartInspection: () -> Unit,
    onOpenDetail: (Long) -> Unit,
    modifier: Modifier = Modifier,
) {
    val scrollState = rememberScrollState()

    // The driver's OWN inspection history (FR-09), empty for a fresh account.
    // Re-fetched whenever this tab is entered (Nav3 recomposes the shell on
    // return from the New Inspection screen).
    val history = remember { mutableStateListOf<InspectionDto>() }
    var loaded by remember { mutableStateOf(false) }
    var vehicleLabel by remember { mutableStateOf("") }

    LaunchedEffect(Unit) {
        history.clear()
        history.addAll(ServiceLocator.inspectionRepository.history())
        val vehicle = ServiceLocator.vehicleRepository.myVehicles().firstOrNull()
        vehicleLabel = vehicle?.let { "${it.type} — ${it.plateNumber}" }.orEmpty()
        loaded = true
    }

    val today = todayIso()
    val todaysInspection = history.firstOrNull { it.inspectionDate == today }
    var showResubmitWarning by remember { mutableStateOf(false) }

    Column(
        modifier = modifier
            .fillMaxSize()
            .background(Background)
            .verticalScroll(scrollState)
            .padding(16.dp),
    ) {
        ScreenHeader(
            title = "BLOWBAGETS Inspection",
            subtitle = "Daily vehicle inspection checklist",
        )

        Spacer(modifier = Modifier.height(20.dp))

        if (todaysInspection == null) {
            // No inspection yet today — primary call to action
            Button(
                onClick = onStartInspection,
                modifier = Modifier
                    .fillMaxWidth()
                    .height(56.dp),
                shape = RoundedCornerShape(12.dp),
                colors = ButtonDefaults.buttonColors(containerColor = NavyBlue),
            ) {
                Text(
                    text = "Start New Inspection",
                    color = White,
                    fontSize = 16.sp,
                    fontWeight = FontWeight.SemiBold,
                )
            }
        } else {
            // Daily inspection already done (Plan §6.4): show today's state
            TodaysInspectionBanner(record = todaysInspection)

            Spacer(modifier = Modifier.height(12.dp))

            Button(
                onClick = { onOpenDetail(todaysInspection.id) },
                modifier = Modifier
                    .fillMaxWidth()
                    .height(56.dp),
                shape = RoundedCornerShape(12.dp),
                colors = ButtonDefaults.buttonColors(containerColor = NavyBlue),
            ) {
                Text(
                    text = "View Today's Inspection",
                    color = White,
                    fontSize = 16.sp,
                    fontWeight = FontWeight.SemiBold,
                )
            }

            TextButton(
                onClick = { showResubmitWarning = true },
                modifier = Modifier.fillMaxWidth(),
            ) {
                Text(
                    text = "Submit Another Inspection",
                    color = TextSecondary,
                    fontWeight = FontWeight.SemiBold,
                )
            }
        }

        Spacer(modifier = Modifier.height(24.dp))

        // Inspection History
        Text(
            text = "Inspection History",
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

        if (history.isEmpty()) {
            Text(
                text = if (loaded) "No inspections submitted yet." else "Loading…",
                style = MaterialTheme.typography.bodyMedium,
                color = TextSecondary,
            )
        } else {
            history.forEach { record ->
                InspectionHistoryItem(record, onClick = { onOpenDetail(record.id) })
            }
        }

        Spacer(modifier = Modifier.height(16.dp))
    }

    if (showResubmitWarning && todaysInspection != null) {
        AlertDialog(
            onDismissRequest = { showResubmitWarning = false },
            title = { Text("Inspection Already Submitted", fontWeight = FontWeight.Bold) },
            text = {
                Text(
                    "You already submitted today's inspection at " +
                        "${formatIsoTime(todaysInspection.submittedAt)}. " +
                        "Are you sure you want to submit another one?"
                )
            },
            confirmButton = {
                TextButton(onClick = {
                    showResubmitWarning = false
                    onStartInspection()
                }) { Text("Submit Anyway", color = NavyBlue, fontWeight = FontWeight.Bold) }
            },
            dismissButton = {
                TextButton(onClick = { showResubmitWarning = false }) {
                    Text("Cancel", color = TextSecondary)
                }
            },
        )
    }
}

@Composable
private fun TodaysInspectionBanner(record: InspectionDto) {
    // Always green: the banner confirms the daily inspection is done.
    // Any flagged items still show through the result label below.
    Card(
        modifier = Modifier.fillMaxWidth(),
        shape = RoundedCornerShape(12.dp),
        colors = CardDefaults.cardColors(containerColor = StatusOperational.copy(alpha = 0.08f)),
    ) {
        Row(
            modifier = Modifier.padding(16.dp),
            verticalAlignment = Alignment.CenterVertically,
        ) {
            Icon(
                imageVector = Icons.Default.CheckCircle,
                contentDescription = null,
                tint = StatusOperational,
                modifier = Modifier.size(28.dp),
            )
            Spacer(modifier = Modifier.width(12.dp))
            Column {
                Text(
                    text = "Inspection submitted today",
                    style = MaterialTheme.typography.bodyLarge,
                    color = TextPrimary,
                    fontWeight = FontWeight.Bold,
                )
                Text(
                    text = listOfNotNull(
                        formatIsoTime(record.submittedAt).takeIf { it.isNotBlank() },
                        "${record.items.size} items checked",
                        record.resultLabel(),
                    ).joinToString(" • "),
                    style = MaterialTheme.typography.bodySmall,
                    color = TextSecondary,
                )
            }
        }
    }
}

@Composable
private fun InspectionHistoryItem(record: InspectionDto, onClick: () -> Unit) {
    val passed = record.issueCount() == 0
    val statusColor = if (passed) StatusOperational else StatusNotOperational
    val icon = if (passed) Icons.Default.CheckCircle else Icons.Default.Warning
    val flagged = record.flaggedNames()

    Card(
        modifier = Modifier
            .fillMaxWidth()
            .padding(bottom = 10.dp)
            .clickable { onClick() },
        shape = RoundedCornerShape(12.dp),
        colors = CardDefaults.cardColors(containerColor = Surface),
    ) {
        Row(
            modifier = Modifier
                .fillMaxWidth()
                .padding(16.dp),
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
            Column(modifier = Modifier.weight(1f)) {
                Text(
                    text = formatIsoDate(record.inspectionDate),
                    style = MaterialTheme.typography.bodyLarge,
                    color = TextPrimary,
                    fontWeight = FontWeight.SemiBold,
                )
                Text(
                    text = listOfNotNull(
                        formatIsoTime(record.submittedAt).takeIf { it.isNotBlank() },
                        "${record.items.size} items checked",
                    ).joinToString(" • "),
                    style = MaterialTheme.typography.bodySmall,
                    color = TextSecondary,
                )
                if (flagged.isNotEmpty()) {
                    Text(
                        text = "Flagged: ${flagged.joinToString(", ")}",
                        style = MaterialTheme.typography.bodySmall,
                        color = statusColor,
                    )
                }
            }
            Spacer(modifier = Modifier.width(12.dp))
            Box(
                modifier = Modifier
                    .clip(RoundedCornerShape(12.dp))
                    .background(statusColor.copy(alpha = 0.1f))
                    .padding(horizontal = 12.dp, vertical = 6.dp),
            ) {
                Text(
                    text = record.resultLabel(),
                    color = statusColor,
                    fontSize = 12.sp,
                    fontWeight = FontWeight.SemiBold,
                )
            }
        }
    }
}
