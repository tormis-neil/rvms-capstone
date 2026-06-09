package com.example.rvms.ui.damage

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
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.foundation.verticalScroll
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.automirrored.filled.ArrowBack
import androidx.compose.material3.Card
import androidx.compose.material3.CardDefaults
import androidx.compose.material3.ExperimentalMaterial3Api
import androidx.compose.material3.Icon
import androidx.compose.material3.IconButton
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Scaffold
import androidx.compose.material3.Text
import androidx.compose.material3.TopAppBar
import androidx.compose.material3.TopAppBarDefaults
import androidx.compose.runtime.Composable
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import com.example.rvms.theme.Background
import com.example.rvms.theme.NavyBlue
import com.example.rvms.theme.StatusOperational
import com.example.rvms.theme.StatusUnderPM
import com.example.rvms.theme.Surface
import com.example.rvms.theme.TextPrimary
import com.example.rvms.theme.TextSecondary

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun DamageReportListScreen(
    onBack: () -> Unit,
    modifier: Modifier = Modifier,
) {
    val scrollState = rememberScrollState()

    Scaffold(
        modifier = modifier,
        topBar = {
            TopAppBar(
                title = { Text("My Damage Reports", color = TextPrimary) },
                navigationIcon = {
                    IconButton(onClick = onBack) {
                        Icon(Icons.AutoMirrored.Filled.ArrowBack, contentDescription = "Back")
                    }
                },
                colors = TopAppBarDefaults.topAppBarColors(containerColor = Background)
            )
        }
    ) { innerPadding ->
        Column(
            modifier = Modifier
                .fillMaxSize()
                .background(Background)
                .verticalScroll(scrollState)
                .padding(innerPadding)
                .padding(16.dp),
        ) {
        Text(
            text = "Fire Truck — ABC-1234",
            style = MaterialTheme.typography.bodyMedium,
            color = TextSecondary,
        )

        Spacer(modifier = Modifier.height(20.dp))

        ReportItem(
            date = "June 8, 2026",
            nature = "Cracked side mirror (driver side)",
            status = "Pending",
        )
        ReportItem(
            date = "May 28, 2026",
            nature = "Brake pad wear — unusual noise during braking",
            status = "Reviewed",
        )
        ReportItem(
            date = "May 15, 2026",
            nature = "Headlight bulb burned out (left)",
            status = "Reviewed",
        )

        Spacer(modifier = Modifier.height(16.dp))
        }
    }
}

@Composable
private fun ReportItem(
    date: String,
    nature: String,
    status: String,
) {
    val statusColor = if (status == "Pending") StatusUnderPM else StatusOperational

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
                horizontalArrangement = Arrangement.SpaceBetween,
                verticalAlignment = Alignment.CenterVertically,
            ) {
                Text(
                    text = date,
                    style = MaterialTheme.typography.bodySmall,
                    color = TextSecondary,
                )
                Box(
                    modifier = Modifier
                        .clip(RoundedCornerShape(12.dp))
                        .background(statusColor.copy(alpha = 0.1f))
                        .padding(horizontal = 12.dp, vertical = 4.dp),
                ) {
                    Text(
                        text = status,
                        color = statusColor,
                        fontSize = 12.sp,
                        fontWeight = FontWeight.SemiBold,
                    )
                }
            }
            Spacer(modifier = Modifier.height(8.dp))
            Text(
                text = nature,
                style = MaterialTheme.typography.bodyMedium,
                color = TextPrimary,
                fontWeight = FontWeight.Medium,
            )
        }
    }
}
