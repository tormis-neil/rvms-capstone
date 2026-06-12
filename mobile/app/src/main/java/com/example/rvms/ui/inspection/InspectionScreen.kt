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
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import com.example.rvms.data.InspectionRecord
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
fun InspectionScreen(
    onStartInspection: () -> Unit,
    onOpenDetail: (Int) -> Unit,
    modifier: Modifier = Modifier,
) {
    val scrollState = rememberScrollState()
    val data = Session.current
    val history = Session.inspectionHistory
    val todaysInspection = Session.todaysInspection()
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
                onClick = { onOpenDetail(history.indexOf(todaysInspection)) },
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
        Text(
            text = "${data.vehicle.type} — ${data.vehicle.plateNo}",
            style = MaterialTheme.typography.bodySmall,
            color = TextSecondary,
        )

        Spacer(modifier = Modifier.height(12.dp))

        if (history.isEmpty()) {
            Text(
                text = "No inspections submitted yet.",
                style = MaterialTheme.typography.bodyMedium,
                color = TextSecondary,
            )
        } else {
            history.forEachIndexed { index, record ->
                InspectionHistoryItem(record, onClick = { onOpenDetail(index) })
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
                    "You already submitted today's inspection at ${todaysInspection.time}. " +
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
private fun TodaysInspectionBanner(record: InspectionRecord) {
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
                    text = "${record.time} • ${record.itemsChecked} items checked • ${record.resultLabel}",
                    style = MaterialTheme.typography.bodySmall,
                    color = TextSecondary,
                )
            }
        }
    }
}

@Composable
private fun InspectionHistoryItem(record: InspectionRecord, onClick: () -> Unit) {
    val passed = record.issueCount == 0
    val statusColor = if (passed) StatusOperational else StatusNotOperational
    val icon = if (passed) Icons.Default.CheckCircle else Icons.Default.Warning

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
                    text = record.date,
                    style = MaterialTheme.typography.bodyLarge,
                    color = TextPrimary,
                    fontWeight = FontWeight.SemiBold,
                )
                Text(
                    text = "${record.time} • ${record.itemsChecked} items checked",
                    style = MaterialTheme.typography.bodySmall,
                    color = TextSecondary,
                )
                if (record.flaggedItems.isNotEmpty()) {
                    Text(
                        text = "Flagged: ${record.flaggedItems.joinToString(", ")}",
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
                    text = record.resultLabel,
                    color = statusColor,
                    fontSize = 12.sp,
                    fontWeight = FontWeight.SemiBold,
                )
            }
        }
    }
}
