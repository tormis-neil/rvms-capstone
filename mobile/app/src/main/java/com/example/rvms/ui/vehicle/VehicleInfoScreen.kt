package com.example.rvms.ui.vehicle

import androidx.compose.foundation.background
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Column
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
import androidx.compose.material3.HorizontalDivider
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
import com.example.rvms.data.SampleData
import com.example.rvms.data.VehicleStatus
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
    val vehicle = SampleData.currentVehicle
    val driver = SampleData.currentDriver
    val scrollState = rememberScrollState()

    val statusColor = when (vehicle.status) {
        VehicleStatus.OPERATIONAL -> StatusOperational
        VehicleStatus.DISPATCHED -> StatusDispatched
        VehicleStatus.UNDER_PM -> StatusUnderPM
        VehicleStatus.NOT_OPERATIONAL -> StatusNotOperational
    }

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

        // Vehicle Status Banner
        Card(
            modifier = Modifier.fillMaxWidth(),
            shape = RoundedCornerShape(12.dp),
            colors = CardDefaults.cardColors(containerColor = NavyBlue),
        ) {
            Column(
                modifier = Modifier.padding(20.dp),
                horizontalAlignment = Alignment.CenterHorizontally,
            ) {
                Text(
                    text = vehicle.type,
                    color = White,
                    style = MaterialTheme.typography.titleLarge,
                    fontWeight = FontWeight.Bold,
                )
                Text(
                    text = vehicle.plateNo,
                    color = Gold,
                    style = MaterialTheme.typography.titleMedium,
                    fontWeight = FontWeight.SemiBold,
                )
                Spacer(modifier = Modifier.height(12.dp))
                Box(
                    modifier = Modifier
                        .clip(RoundedCornerShape(20.dp))
                        .background(statusColor.copy(alpha = 0.2f))
                        .padding(horizontal = 20.dp, vertical = 6.dp),
                ) {
                    Text(
                        text = vehicle.status.label,
                        color = statusColor,
                        fontSize = 14.sp,
                        fontWeight = FontWeight.SemiBold,
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
                DetailRow("Plate Number", vehicle.plateNo)
                DetailRow("Make", vehicle.make)
                DetailRow("Model", vehicle.model)
                DetailRow("Engine No.", vehicle.engineNo)
                DetailRow("Chassis No.", vehicle.chassisNo)
                DetailRow("Current Mileage", vehicle.mileage)
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

                DetailRow("Assigned Driver", driver.name)
                DetailRow("Agency", driver.agency.fullName)
            }
        }

        Spacer(modifier = Modifier.height(16.dp))
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
