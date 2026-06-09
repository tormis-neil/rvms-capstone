package com.example.rvms.ui.damage

import androidx.compose.foundation.background
import androidx.compose.foundation.border
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
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.foundation.verticalScroll
import androidx.compose.material3.AlertDialog
import androidx.compose.material3.Button
import androidx.compose.material3.ButtonDefaults
import androidx.compose.material3.Card
import androidx.compose.material3.CardDefaults
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.OutlinedTextField
import androidx.compose.material3.OutlinedTextFieldDefaults
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
import com.example.rvms.data.Session
import com.example.rvms.ui.common.ScreenHeader
import com.example.rvms.theme.Background
import com.example.rvms.theme.ErrorRed
import com.example.rvms.theme.NavyBlue
import com.example.rvms.theme.StatusOperational
import com.example.rvms.theme.Surface
import com.example.rvms.theme.TextPrimary
import com.example.rvms.theme.TextSecondary
import com.example.rvms.theme.White

/**
 * Damage report form (Plan §6.5, driver side).
 *
 * Vehicle info is auto-filled and read-only. Nature of Damage is required.
 * Photo attachment is optional (mocked for the prototype). On submit a
 * confirmation is shown and the form resets to Pending state.
 */
@Composable
fun DamageReportScreen(
    onViewReports: () -> Unit,
    modifier: Modifier = Modifier,
) {
    val vehicle = Session.current.vehicle
    val driver = Session.current.driver

    var natureOfDamage by remember { mutableStateOf("") }
    var suspectedParts by remember { mutableStateOf("") }
    var photoAttached by remember { mutableStateOf(false) }
    var error by remember { mutableStateOf<String?>(null) }
    var showSuccess by remember { mutableStateOf(false) }
    val scrollState = rememberScrollState()

    Column(
        modifier = modifier
            .fillMaxSize()
            .background(Background)
            .verticalScroll(scrollState)
            .padding(16.dp),
    ) {
        ScreenHeader(
            title = "Damage Report",
            subtitle = "Report vehicle damage or defects",
        )

        Spacer(modifier = Modifier.height(20.dp))

        // Vehicle Info (Read-Only)
        Card(
            modifier = Modifier.fillMaxWidth(),
            shape = RoundedCornerShape(12.dp),
            colors = CardDefaults.cardColors(containerColor = Surface),
        ) {
            Column(modifier = Modifier.padding(16.dp)) {
                Text(
                    text = "Vehicle Information",
                    style = MaterialTheme.typography.titleSmall,
                    color = NavyBlue,
                    fontWeight = FontWeight.Bold,
                )
                Spacer(modifier = Modifier.height(12.dp))
                ReadOnlyField("Vehicle Type", vehicle.type)
                ReadOnlyField("Plate No.", vehicle.plateNo)
                ReadOnlyField("Make / Model", "${vehicle.make} ${vehicle.model}")
                ReadOnlyField("Assigned Driver", driver.name)
                ReadOnlyField("Date Reported", "June 9, 2026")
            }
        }

        Spacer(modifier = Modifier.height(16.dp))

        // Damage Details Form
        Card(
            modifier = Modifier.fillMaxWidth(),
            shape = RoundedCornerShape(12.dp),
            colors = CardDefaults.cardColors(containerColor = Surface),
        ) {
            Column(modifier = Modifier.padding(16.dp)) {
                Text(
                    text = "Damage Details",
                    style = MaterialTheme.typography.titleSmall,
                    color = NavyBlue,
                    fontWeight = FontWeight.Bold,
                )
                Spacer(modifier = Modifier.height(12.dp))

                OutlinedTextField(
                    value = natureOfDamage,
                    onValueChange = {
                        natureOfDamage = it
                        error = null
                    },
                    label = { Text("Nature of Damage") },
                    modifier = Modifier.fillMaxWidth(),
                    minLines = 3,
                    isError = error != null && natureOfDamage.isBlank(),
                    colors = OutlinedTextFieldDefaults.colors(
                        focusedBorderColor = NavyBlue,
                        focusedLabelColor = NavyBlue,
                    ),
                )

                Spacer(modifier = Modifier.height(12.dp))

                OutlinedTextField(
                    value = suspectedParts,
                    onValueChange = { suspectedParts = it },
                    label = { Text("Suspected Defective Parts") },
                    modifier = Modifier.fillMaxWidth(),
                    minLines = 2,
                    colors = OutlinedTextFieldDefaults.colors(
                        focusedBorderColor = NavyBlue,
                        focusedLabelColor = NavyBlue,
                    ),
                )

                Spacer(modifier = Modifier.height(12.dp))

                // Photo attachment (mocked toggle for the prototype)
                Box(
                    modifier = Modifier
                        .fillMaxWidth()
                        .height(80.dp)
                        .clip(RoundedCornerShape(8.dp))
                        .background(if (photoAttached) StatusOperational.copy(alpha = 0.1f) else Background)
                        .border(
                            width = 1.dp,
                            color = if (photoAttached) StatusOperational else TextSecondary.copy(alpha = 0.3f),
                            shape = RoundedCornerShape(8.dp),
                        )
                        .clickable { photoAttached = !photoAttached },
                    contentAlignment = Alignment.Center,
                ) {
                    Column(horizontalAlignment = Alignment.CenterHorizontally) {
                        Text(text = if (photoAttached) "✓" else "📷", fontSize = 24.sp)
                        Text(
                            text = if (photoAttached) "Photo attached (tap to remove)" else "Tap to attach photo (optional)",
                            style = MaterialTheme.typography.bodySmall,
                            color = if (photoAttached) StatusOperational else TextSecondary,
                        )
                    }
                }
            }
        }

        if (error != null) {
            Spacer(modifier = Modifier.height(8.dp))
            Text(
                text = error!!,
                color = ErrorRed,
                style = MaterialTheme.typography.bodySmall,
                fontWeight = FontWeight.Medium,
            )
        }

        Spacer(modifier = Modifier.height(20.dp))

        Button(
            onClick = {
                if (natureOfDamage.isBlank()) {
                    error = "Nature of Damage is required."
                } else {
                    error = null
                    showSuccess = true
                }
            },
            modifier = Modifier
                .fillMaxWidth()
                .height(50.dp),
            shape = RoundedCornerShape(12.dp),
            colors = ButtonDefaults.buttonColors(containerColor = NavyBlue),
        ) {
            Text(
                text = "Submit Report",
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

        Spacer(modifier = Modifier.height(16.dp))
    }

    if (showSuccess) {
        AlertDialog(
            onDismissRequest = { /* require explicit action */ },
            confirmButton = {
                TextButton(onClick = {
                    showSuccess = false
                    natureOfDamage = ""
                    suspectedParts = ""
                    photoAttached = false
                }) { Text("Done", color = NavyBlue, fontWeight = FontWeight.Bold) }
            },
            title = { Text("Report Submitted", fontWeight = FontWeight.Bold) },
            text = {
                Text(
                    "Your damage report for ${vehicle.plateNo} has been submitted with status Pending. " +
                        "The agency administrator has been notified for review."
                )
            },
        )
    }
}

@Composable
private fun ReadOnlyField(label: String, value: String) {
    Row(
        modifier = Modifier
            .fillMaxWidth()
            .padding(bottom = 8.dp),
        horizontalArrangement = Arrangement.SpaceBetween,
    ) {
        Text(
            text = label,
            style = MaterialTheme.typography.bodySmall,
            color = TextSecondary,
        )
        Text(
            text = value,
            style = MaterialTheme.typography.bodySmall,
            color = TextPrimary,
            fontWeight = FontWeight.Medium,
        )
    }
}
