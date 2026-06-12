package com.example.rvms.ui.inspection

import androidx.compose.foundation.background
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.defaultMinSize
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.size
import androidx.compose.foundation.layout.width
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.foundation.verticalScroll
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.automirrored.filled.ArrowBack
import androidx.compose.material3.AlertDialog
import androidx.compose.material3.Button
import androidx.compose.material3.ButtonDefaults
import androidx.compose.material3.Card
import androidx.compose.material3.CardDefaults
import androidx.compose.material3.ExperimentalMaterial3Api
import androidx.compose.material3.Icon
import androidx.compose.material3.IconButton
import androidx.compose.material3.LinearProgressIndicator
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.OutlinedTextField
import androidx.compose.material3.OutlinedTextFieldDefaults
import androidx.compose.material3.Scaffold
import androidx.compose.material3.Text
import androidx.compose.material3.TextButton
import androidx.compose.material3.TopAppBar
import androidx.compose.material3.TopAppBarDefaults
import androidx.compose.runtime.Composable
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateMapOf
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
import com.example.rvms.data.SampleData
import com.example.rvms.data.Session
import com.example.rvms.theme.Background
import com.example.rvms.theme.ErrorRed
import com.example.rvms.theme.Gold
import com.example.rvms.theme.NavyBlue
import com.example.rvms.theme.StatusNotOperational
import com.example.rvms.theme.StatusOperational
import com.example.rvms.theme.Surface
import com.example.rvms.theme.TextPrimary
import com.example.rvms.theme.TextSecondary
import com.example.rvms.theme.White
import java.text.SimpleDateFormat
import java.util.Date
import java.util.Locale

/**
 * The daily BLOWBAGETS inspection form (Plan §6.4, driver side).
 *
 * The assigned vehicle is auto-loaded. Each checklist item is marked OK or
 * Has Issue; remarks are required for any flagged item. BFP vehicles include
 * the two additional items (Hydraulic System, Fire Pump). The submit button
 * and progress indicator stay pinned at the bottom so the driver always
 * knows how far along the checklist they are.
 */
@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun NewInspectionScreen(
    onBack: () -> Unit,
    onSubmitted: () -> Unit,
    modifier: Modifier = Modifier,
) {
    val data = Session.current
    val driver = data.driver
    val vehicle = data.vehicle
    val items = remember { data.inspectionItems }
    val standardItems = SampleData.standardInspectionItems
    val extraItems = items.filter { it !in standardItems }

    // status: true = OK, false = Has Issue, absent = not yet marked
    val statuses = remember { mutableStateMapOf<String, Boolean>() }
    val remarks = remember { mutableStateMapOf<String, String>() }
    var error by remember { mutableStateOf<String?>(null) }
    var submittedRecord by remember { mutableStateOf<InspectionRecord?>(null) }

    val scrollState = rememberScrollState()
    val marked = statuses.size

    Scaffold(
        modifier = modifier,
        topBar = {
            TopAppBar(
                title = { Text("New Inspection", color = TextPrimary) },
                navigationIcon = {
                    IconButton(onClick = onBack) {
                        Icon(Icons.AutoMirrored.Filled.ArrowBack, contentDescription = "Back")
                    }
                },
                colors = TopAppBarDefaults.topAppBarColors(containerColor = Background),
            )
        },
        bottomBar = {
            // Sticky progress + submit so the action is always reachable
            Card(
                modifier = Modifier.fillMaxWidth(),
                shape = RoundedCornerShape(topStart = 16.dp, topEnd = 16.dp),
                colors = CardDefaults.cardColors(containerColor = Surface),
                elevation = CardDefaults.cardElevation(defaultElevation = 8.dp),
            ) {
                Column(modifier = Modifier.padding(16.dp)) {
                    Text(
                        text = "Marked $marked of ${items.size} items",
                        style = MaterialTheme.typography.bodyMedium,
                        color = if (marked == items.size) StatusOperational else TextSecondary,
                        fontWeight = FontWeight.SemiBold,
                    )
                    Spacer(modifier = Modifier.height(6.dp))
                    LinearProgressIndicator(
                        progress = { if (items.isEmpty()) 0f else marked / items.size.toFloat() },
                        modifier = Modifier.fillMaxWidth(),
                        color = if (marked == items.size) StatusOperational else NavyBlue,
                        trackColor = Background,
                    )
                    if (error != null) {
                        Spacer(modifier = Modifier.height(8.dp))
                        Text(
                            text = error!!,
                            color = ErrorRed,
                            style = MaterialTheme.typography.bodySmall,
                            fontWeight = FontWeight.Medium,
                        )
                    }
                    Spacer(modifier = Modifier.height(12.dp))
                    Button(
                        onClick = {
                            val unmarked = items.count { statuses[it] == null }
                            val missingRemarks =
                                items.any { statuses[it] == false && remarks[it].isNullOrBlank() }
                            when {
                                unmarked > 0 ->
                                    error = "Please mark all items — $unmarked remaining."
                                missingRemarks ->
                                    error = "Remarks are required for every item marked Has Issue."
                                else -> {
                                    error = null
                                    val flagged = items.filter { statuses[it] == false }
                                    val record = InspectionRecord(
                                        date = SampleData.todayLabel,
                                        time = SimpleDateFormat("h:mm a", Locale.US).format(Date()),
                                        itemsChecked = items.size,
                                        issueCount = flagged.size,
                                        flaggedItems = flagged,
                                        flaggedRemarks = flagged.associateWith {
                                            remarks[it].orEmpty()
                                        },
                                    )
                                    Session.submitInspection(record)
                                    submittedRecord = record
                                }
                            }
                        },
                        modifier = Modifier
                            .fillMaxWidth()
                            .height(52.dp),
                        shape = RoundedCornerShape(12.dp),
                        colors = ButtonDefaults.buttonColors(containerColor = NavyBlue),
                    ) {
                        Text(
                            text = "Submit Inspection",
                            color = White,
                            fontSize = 16.sp,
                            fontWeight = FontWeight.SemiBold,
                        )
                    }
                }
            }
        },
    ) { innerPadding ->
        Column(
            modifier = Modifier
                .fillMaxSize()
                .background(Background)
                .verticalScroll(scrollState)
                .padding(innerPadding)
                .padding(16.dp),
        ) {
            // Auto-loaded vehicle context
            Card(
                modifier = Modifier.fillMaxWidth(),
                shape = RoundedCornerShape(12.dp),
                colors = CardDefaults.cardColors(containerColor = Surface),
            ) {
                Column(modifier = Modifier.padding(16.dp)) {
                    Text(
                        text = "Assigned Vehicle",
                        style = MaterialTheme.typography.bodySmall,
                        color = TextSecondary,
                    )
                    Text(
                        text = "${vehicle.type} • ${vehicle.plateNo}",
                        style = MaterialTheme.typography.titleMedium,
                        color = TextPrimary,
                        fontWeight = FontWeight.Bold,
                    )
                    Text(
                        text = "${driver.agency.fullName} — ${driver.name}",
                        style = MaterialTheme.typography.bodySmall,
                        color = TextSecondary,
                    )
                }
            }

            Spacer(modifier = Modifier.height(16.dp))

            Text(
                text = "Standard Items",
                style = MaterialTheme.typography.titleSmall,
                color = NavyBlue,
                fontWeight = FontWeight.Bold,
            )
            Spacer(modifier = Modifier.height(8.dp))

            standardItems.forEach { item ->
                InspectionItemRow(
                    name = item,
                    letter = SampleData.blowbagetsLetters[item],
                    status = statuses[item],
                    remark = remarks[item].orEmpty(),
                    onStatusChange = { ok ->
                        statuses[item] = ok
                        if (ok) remarks.remove(item)
                        error = null
                    },
                    onRemarkChange = { remarks[item] = it },
                )
            }

            if (extraItems.isNotEmpty()) {
                Spacer(modifier = Modifier.height(8.dp))
                Text(
                    text = "${driver.agency.code} Additional Items",
                    style = MaterialTheme.typography.titleSmall,
                    color = Gold,
                    fontWeight = FontWeight.Bold,
                )
                Spacer(modifier = Modifier.height(8.dp))
                extraItems.forEach { item ->
                    InspectionItemRow(
                        name = item,
                        letter = null,
                        status = statuses[item],
                        remark = remarks[item].orEmpty(),
                        onStatusChange = { ok ->
                            statuses[item] = ok
                            if (ok) remarks.remove(item)
                            error = null
                        },
                        onRemarkChange = { remarks[item] = it },
                    )
                }
            }

            Spacer(modifier = Modifier.height(8.dp))
        }
    }

    submittedRecord?.let { record ->
        AlertDialog(
            onDismissRequest = { /* require explicit action */ },
            confirmButton = {
                TextButton(onClick = {
                    submittedRecord = null
                    onSubmitted()
                }) { Text("Done", color = NavyBlue, fontWeight = FontWeight.Bold) }
            },
            title = { Text("Inspection Submitted", fontWeight = FontWeight.Bold) },
            text = {
                Text(
                    if (record.issueCount == 0)
                        "All ${record.itemsChecked} items passed. The inspection for " +
                            "${vehicle.plateNo} has been submitted for admin review."
                    else
                        "${record.issueCount} item(s) flagged. The inspection for " +
                            "${vehicle.plateNo} has been submitted for admin review.\n\n" +
                            "Flagged items: ${record.flaggedItems.joinToString(", ")}"
                )
            },
        )
    }
}

@Composable
private fun InspectionItemRow(
    name: String,
    letter: String?,
    status: Boolean?,
    remark: String,
    onStatusChange: (Boolean) -> Unit,
    onRemarkChange: (String) -> Unit,
) {
    Card(
        modifier = Modifier
            .fillMaxWidth()
            .padding(bottom = 8.dp),
        shape = RoundedCornerShape(10.dp),
        colors = CardDefaults.cardColors(containerColor = Surface),
    ) {
        Column(modifier = Modifier.padding(12.dp)) {
            Row(
                modifier = Modifier.fillMaxWidth(),
                verticalAlignment = Alignment.CenterVertically,
            ) {
                // BLOWBAGETS acronym letter, mirroring the paper checklist
                if (letter != null) {
                    Box(
                        modifier = Modifier
                            .size(24.dp)
                            .clip(RoundedCornerShape(6.dp))
                            .background(NavyBlue.copy(alpha = 0.1f)),
                        contentAlignment = Alignment.Center,
                    ) {
                        Text(
                            text = letter,
                            color = NavyBlue,
                            fontSize = 12.sp,
                            fontWeight = FontWeight.Bold,
                        )
                    }
                    Spacer(modifier = Modifier.width(8.dp))
                }
                Text(
                    text = name,
                    style = MaterialTheme.typography.bodyMedium,
                    color = TextPrimary,
                    fontWeight = FontWeight.Medium,
                    modifier = Modifier.weight(1f),
                )
                ToggleChip(
                    label = "OK",
                    selected = status == true,
                    selectedColor = StatusOperational,
                    onClick = { onStatusChange(true) },
                )
                Spacer(modifier = Modifier.width(8.dp))
                ToggleChip(
                    label = "Has Issue",
                    selected = status == false,
                    selectedColor = StatusNotOperational,
                    onClick = { onStatusChange(false) },
                )
            }

            if (status == false) {
                Spacer(modifier = Modifier.height(8.dp))
                OutlinedTextField(
                    value = remark,
                    onValueChange = onRemarkChange,
                    label = { Text("Remarks (required)") },
                    placeholder = { Text("Describe the issue / Ilarawan ang problema") },
                    modifier = Modifier.fillMaxWidth(),
                    minLines = 2,
                    isError = remark.isBlank(),
                    colors = OutlinedTextFieldDefaults.colors(
                        focusedBorderColor = NavyBlue,
                        focusedLabelColor = NavyBlue,
                    ),
                )
            }
        }
    }
}

@Composable
private fun ToggleChip(
    label: String,
    selected: Boolean,
    selectedColor: androidx.compose.ui.graphics.Color,
    onClick: () -> Unit,
) {
    // 48dp minimum touch target — field staff may be wearing gloves
    Box(
        modifier = Modifier
            .defaultMinSize(minWidth = 48.dp, minHeight = 48.dp)
            .clip(RoundedCornerShape(8.dp))
            .background(if (selected) selectedColor else Background)
            .clickable { onClick() }
            .padding(horizontal = 14.dp),
        contentAlignment = Alignment.Center,
    ) {
        Text(
            text = label,
            color = if (selected) White else TextSecondary,
            fontSize = 13.sp,
            fontWeight = FontWeight.SemiBold,
        )
    }
}
