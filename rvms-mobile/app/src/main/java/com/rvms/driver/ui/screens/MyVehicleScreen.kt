package com.rvms.driver.ui.screens

import androidx.compose.foundation.background
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.verticalScroll
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.automirrored.filled.ArrowBack
import androidx.compose.material3.Divider
import androidx.compose.material3.ExperimentalMaterial3Api
import androidx.compose.material3.Icon
import androidx.compose.material3.IconButton
import androidx.compose.material3.Scaffold
import androidx.compose.material3.Text
import androidx.compose.material3.TopAppBar
import androidx.compose.material3.TopAppBarDefaults
import androidx.compose.runtime.Composable
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import com.rvms.driver.model.DummyAssignedVehicle
import com.rvms.driver.ui.components.StatusBadge
import com.rvms.driver.ui.theme.BackgroundLight
import com.rvms.driver.ui.theme.PrimaryBlue
import com.rvms.driver.ui.theme.TextPrimary
import com.rvms.driver.ui.theme.TextSecondary
import com.rvms.driver.ui.theme.Typography

/**
 * MyVehicleScreen
 *
 * A detailed view showing the driver's assigned vehicle information
 * and a basic mock history of maintenance or recent dispatches.
 */
@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun MyVehicleScreen(
    onBack: () -> Unit,
    modifier: Modifier = Modifier
) {
    val vehicle = DummyAssignedVehicle

    Scaffold(
        topBar = {
            TopAppBar(
                title = { Text("My Vehicle", style = Typography.titleLarge) },
                navigationIcon = {
                    IconButton(onClick = onBack) {
                        Icon(Icons.AutoMirrored.Filled.ArrowBack, contentDescription = "Back")
                    }
                },
                colors = TopAppBarDefaults.topAppBarColors(
                    containerColor = Color.White,
                    titleContentColor = PrimaryBlue,
                    navigationIconContentColor = PrimaryBlue
                )
            )
        }
    ) { paddingValues ->
        Column(
            modifier = modifier
                .fillMaxSize()
                .background(BackgroundLight)
                .padding(paddingValues)
                .padding(16.dp)
                .verticalScroll(rememberScrollState())
        ) {
            // Main Info Header
            Row(
                modifier = Modifier.fillMaxWidth(),
                verticalAlignment = Alignment.CenterVertically
            ) {
                Column(modifier = Modifier.weight(1f)) {
                    Text(
                        text = vehicle.plateNumber,
                        style = Typography.headlineMedium,
                        color = TextPrimary
                    )
                    Text(
                        text = "${vehicle.type} • ${vehicle.model}",
                        style = Typography.bodyMedium,
                        color = TextSecondary
                    )
                }
                StatusBadge(status = vehicle.status)
            }

            Spacer(modifier = Modifier.height(24.dp))

            // Specs Section
            Text(
                text = "Vehicle Specifications",
                style = Typography.titleLarge,
                color = TextPrimary
            )
            Spacer(modifier = Modifier.height(16.dp))
            SpecRow(label = "Odometer", value = "${String.format("%,d", vehicle.currentOdometer)} km")
            Divider(color = Color(0xFFE2E8F0), modifier = Modifier.padding(vertical = 8.dp))
            SpecRow(label = "Vehicle ID", value = vehicle.id)

            Spacer(modifier = Modifier.height(32.dp))

            // Mock History Section
            Text(
                text = "Recent Activity",
                style = Typography.titleLarge,
                color = TextPrimary
            )
            Spacer(modifier = Modifier.height(16.dp))
            HistoryItem(date = "Oct 12, 2026", event = "Routine Oil Change (PM)")
            HistoryItem(date = "Sep 05, 2026", event = "Brake Pad Replacement")
            HistoryItem(date = "Aug 20, 2026", event = "Monthly Inspection Passed")
        }
    }
}

@Composable
private fun SpecRow(label: String, value: String) {
    Row(
        modifier = Modifier.fillMaxWidth(),
        verticalAlignment = Alignment.CenterVertically
    ) {
        Text(
            text = label,
            style = Typography.bodyMedium,
            color = TextSecondary,
            modifier = Modifier.weight(1f)
        )
        Text(
            text = value,
            style = Typography.labelLarge, // JetBrains Mono for data
            color = TextPrimary,
            fontWeight = FontWeight.Medium
        )
    }
}

@Composable
private fun HistoryItem(date: String, event: String) {
    Column(modifier = Modifier.padding(bottom = 16.dp)) {
        Text(
            text = date,
            style = Typography.labelSmall,
            color = TextSecondary
        )
        Text(
            text = event,
            style = Typography.bodyLarge,
            color = TextPrimary
        )
    }
}
