package com.example.rvms.ui.inspection

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
import androidx.compose.foundation.layout.width
import androidx.compose.foundation.rememberScrollState
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
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import com.example.rvms.theme.Background
import com.example.rvms.theme.StatusNotOperational
import com.example.rvms.theme.StatusOperational
import com.example.rvms.theme.Surface
import com.example.rvms.theme.TextPrimary
import com.example.rvms.theme.TextSecondary

@Composable
fun InspectionHistoryScreen(
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
            text = "Inspection History",
            style = MaterialTheme.typography.headlineSmall,
            color = TextPrimary,
            fontWeight = FontWeight.Bold,
        )
        Text(
            text = "Fire Truck — ABC-1234",
            style = MaterialTheme.typography.bodyMedium,
            color = TextSecondary,
        )

        Spacer(modifier = Modifier.height(20.dp))

        // Sample history entries
        HistoryEntry(
            date = "June 8, 2026",
            time = "7:30 AM",
            result = "All OK",
            itemsChecked = 14,
            issueCount = 0,
        )
        HistoryEntry(
            date = "June 7, 2026",
            time = "7:15 AM",
            result = "1 Issue",
            itemsChecked = 14,
            issueCount = 1,
        )
        HistoryEntry(
            date = "June 6, 2026",
            time = "7:45 AM",
            result = "All OK",
            itemsChecked = 14,
            issueCount = 0,
        )
        HistoryEntry(
            date = "June 5, 2026",
            time = "7:20 AM",
            result = "All OK",
            itemsChecked = 14,
            issueCount = 0,
        )
        HistoryEntry(
            date = "June 4, 2026",
            time = "7:35 AM",
            result = "2 Issues",
            itemsChecked = 14,
            issueCount = 2,
        )

        Spacer(modifier = Modifier.height(16.dp))
    }
}

@Composable
private fun HistoryEntry(
    date: String,
    time: String,
    result: String,
    itemsChecked: Int,
    issueCount: Int,
) {
    val statusColor = if (issueCount == 0) StatusOperational else StatusNotOperational

    Card(
        modifier = Modifier
            .fillMaxWidth()
            .padding(bottom = 10.dp),
        shape = RoundedCornerShape(12.dp),
        colors = CardDefaults.cardColors(containerColor = Surface),
    ) {
        Row(
            modifier = Modifier
                .fillMaxWidth()
                .padding(16.dp),
            horizontalArrangement = Arrangement.SpaceBetween,
            verticalAlignment = Alignment.CenterVertically,
        ) {
            Column {
                Text(
                    text = date,
                    style = MaterialTheme.typography.bodyLarge,
                    color = TextPrimary,
                    fontWeight = FontWeight.SemiBold,
                )
                Text(
                    text = "$time • $itemsChecked items checked",
                    style = MaterialTheme.typography.bodySmall,
                    color = TextSecondary,
                )
            }
            Box(
                modifier = Modifier
                    .clip(RoundedCornerShape(12.dp))
                    .background(statusColor.copy(alpha = 0.1f))
                    .padding(horizontal = 12.dp, vertical = 6.dp),
            ) {
                Text(
                    text = result,
                    color = statusColor,
                    fontSize = 12.sp,
                    fontWeight = FontWeight.SemiBold,
                )
            }
        }
    }
}
