package com.example.rvms.ui.vehicle

import androidx.compose.foundation.background
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.width
import androidx.compose.foundation.layout.widthIn
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.foundation.verticalScroll
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.automirrored.filled.ArrowBack
import androidx.compose.material3.Card
import androidx.compose.material3.CardDefaults
import androidx.compose.material3.ExperimentalMaterial3Api
import androidx.compose.material3.HorizontalDivider
import androidx.compose.material3.Icon
import androidx.compose.material3.IconButton
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Scaffold
import androidx.compose.material3.Text
import androidx.compose.material3.TopAppBar
import androidx.compose.material3.TopAppBarDefaults
import androidx.compose.runtime.Composable
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.collectAsState
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextAlign
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import com.example.rvms.data.ServiceLocator
import com.example.rvms.data.VehicleStatus
import com.example.rvms.data.remote.dto.VehicleDto
import com.example.rvms.ui.common.formatMileage
import com.example.rvms.theme.Background
import com.example.rvms.theme.Gold
import com.example.rvms.theme.NavyBlue
import com.example.rvms.theme.StatusDispatched
import com.example.rvms.theme.StatusNotOperational
import com.example.rvms.theme.StatusOperational
import com.example.rvms.theme.StatusUnderPM
import com.example.rvms.theme.Surface
import com.example.rvms.theme.TextPrimary
import com.example.rvms.theme.TextSecondary
import com.example.rvms.theme.White

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun VehicleInfoScreen(
    onBack: () -> Unit,
    modifier: Modifier = Modifier,
) {
    // The driver's assigned vehicle(s) from GET /my-vehicle (FR-07). A driver
    // may hold several — each renders the same card layout below.
    val currentUser by ServiceLocator.sessionManager.currentUser.collectAsState()
    var vehicles by remember { mutableStateOf<List<VehicleDto>>(emptyList()) }

    LaunchedEffect(Unit) {
        vehicles = ServiceLocator.vehicleRepository.myVehicles()
    }

    val driverName = currentUser?.name.orEmpty()
    val agencyName = currentUser?.agency?.name.orEmpty()
    val scrollState = rememberScrollState()

    Scaffold(
        modifier = modifier,
        topBar = {
            TopAppBar(
                title = { Text("Vehicle Information", color = TextPrimary) },
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
            if (vehicles.isEmpty()) {
                Card(
                    modifier = Modifier.fillMaxWidth(),
                    shape = RoundedCornerShape(12.dp),
                    colors = CardDefaults.cardColors(containerColor = Surface),
                ) {
                    Column(modifier = Modifier.padding(20.dp)) {
                        Text(
                            text = "No Vehicle Assigned",
                            style = MaterialTheme.typography.titleMedium,
                            color = TextPrimary,
                            fontWeight = FontWeight.Bold,
                        )
                        Spacer(modifier = Modifier.height(4.dp))
                        Text(
                            text = "Your agency administrator has not assigned a vehicle to your account yet.",
                            style = MaterialTheme.typography.bodySmall,
                            color = TextSecondary,
                        )
                    }
                }
                return@Column
            }

            if (vehicles.size > 1) {
                // A driver may be the primary driver of more than one vehicle
                // (Ch4 ERD) — call that out once, above the repeated cards below.
                Text(
                    text = "You are assigned to ${vehicles.size} vehicles.",
                    style = MaterialTheme.typography.bodyMedium,
                    color = TextSecondary,
                )
                Spacer(modifier = Modifier.height(12.dp))
            }

            vehicles.forEachIndexed { index, vehicle ->
                VehicleCard(vehicle = vehicle, driverName = driverName, agencyName = agencyName)
                if (index != vehicles.lastIndex) {
                    Spacer(modifier = Modifier.height(20.dp))
                }
            }

            Spacer(modifier = Modifier.height(16.dp))
        }
    }
}

@Composable
private fun VehicleCard(vehicle: VehicleDto, driverName: String, agencyName: String) {
    val status = VehicleStatus.fromApiLabel(vehicle.status)
    val statusColor = when (status) {
        VehicleStatus.OPERATIONAL -> StatusOperational
        VehicleStatus.DISPATCHED -> StatusDispatched
        VehicleStatus.UNDER_PM -> StatusUnderPM
        VehicleStatus.NOT_OPERATIONAL -> StatusNotOperational
    }

    Column {
        // Vehicle Status Banner
        Card(
            modifier = Modifier.fillMaxWidth(),
            shape = RoundedCornerShape(12.dp),
            colors = CardDefaults.cardColors(containerColor = NavyBlue),
        ) {
            Row(
                modifier = Modifier
                    .fillMaxWidth()
                    .padding(20.dp),
                verticalAlignment = Alignment.CenterVertically,
            ) {
                Column(modifier = Modifier.weight(1f)) {
                    Text(
                        text = vehicle.type,
                        color = White,
                        style = MaterialTheme.typography.titleLarge,
                        fontWeight = FontWeight.Bold,
                    )
                    Text(
                        text = vehicle.plateNumber,
                        color = Gold,
                        style = MaterialTheme.typography.titleMedium,
                        fontWeight = FontWeight.SemiBold,
                    )
                }
                Spacer(modifier = Modifier.width(12.dp))
                Box(
                    modifier = Modifier
                        .widthIn(max = 160.dp)
                        .clip(RoundedCornerShape(20.dp))
                        .background(statusColor.copy(alpha = 0.2f))
                        .padding(horizontal = 14.dp, vertical = 6.dp),
                ) {
                    Text(
                        text = status.label,
                        color = statusColor,
                        fontSize = 12.sp,
                        fontWeight = FontWeight.SemiBold,
                        textAlign = TextAlign.Center,
                        lineHeight = 16.sp,
                    )
                }
            }
        }

        Spacer(modifier = Modifier.height(16.dp))

        // Vehicle Details Card
        Card(
            modifier = Modifier.fillMaxWidth(),
            shape = RoundedCornerShape(12.dp),
            colors = CardDefaults.cardColors(containerColor = Surface),
        ) {
            Column(modifier = Modifier.padding(20.dp)) {
                Text(
                    text = "Vehicle Details",
                    style = MaterialTheme.typography.titleMedium,
                    color = NavyBlue,
                    fontWeight = FontWeight.Bold,
                )
                Spacer(modifier = Modifier.height(16.dp))

                DetailRow("Vehicle Type", vehicle.type)
                DetailRow("Plate Number", vehicle.plateNumber)
                DetailRow("Make", vehicle.make)
                DetailRow("Model", vehicle.model)
                DetailRow("Engine No.", vehicle.engineNumber ?: "—")
                DetailRow("Chassis No.", vehicle.chassisNumber ?: "—")
                DetailRow("Current Mileage", formatMileage(vehicle.currentMileage))
            }
        }

        Spacer(modifier = Modifier.height(12.dp))

        // Assignment Card
        Card(
            modifier = Modifier.fillMaxWidth(),
            shape = RoundedCornerShape(12.dp),
            colors = CardDefaults.cardColors(containerColor = Surface),
        ) {
            Column(modifier = Modifier.padding(20.dp)) {
                Text(
                    text = "Assignment",
                    style = MaterialTheme.typography.titleMedium,
                    color = NavyBlue,
                    fontWeight = FontWeight.Bold,
                )
                Spacer(modifier = Modifier.height(16.dp))

                DetailRow("Assigned Driver", driverName)
                DetailRow("Agency", agencyName)
            }
        }
    }
}

@Composable
private fun DetailRow(label: String, value: String) {
    Column(modifier = Modifier.padding(bottom = 12.dp)) {
        Text(
            text = label,
            style = MaterialTheme.typography.bodySmall,
            color = TextSecondary,
        )
        Text(
            text = value,
            style = MaterialTheme.typography.bodyLarge,
            color = TextPrimary,
            fontWeight = FontWeight.Medium,
        )
        Spacer(modifier = Modifier.height(8.dp))
        HorizontalDivider(thickness = 0.5.dp, color = TextSecondary.copy(alpha = 0.2f))
    }
}
