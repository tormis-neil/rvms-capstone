package com.example.rvms.ui.damage

import androidx.compose.foundation.background
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.foundation.verticalScroll
import androidx.compose.material3.Button
import androidx.compose.material3.ButtonDefaults
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.setValue
import androidx.compose.ui.Modifier
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import com.example.rvms.data.ServiceLocator
import com.example.rvms.ui.common.ScreenHeader
import com.example.rvms.theme.Background
import com.example.rvms.theme.NavyBlue
import com.example.rvms.theme.TextPrimary
import com.example.rvms.theme.TextSecondary
import com.example.rvms.theme.White

@Composable
fun DamageScreen(
    onSubmitNew: () -> Unit,
    modifier: Modifier = Modifier,
) {
    val scrollState = rememberScrollState()

    // Real assigned-vehicle context for the header. The damage report list is
    // empty until the damage-reports backend + submission lands in R4; a fresh
    // account correctly shows no reports (no seeded sample data).
    var vehicleLabel by remember { mutableStateOf("") }
    LaunchedEffect(Unit) {
        val vehicle = ServiceLocator.vehicleRepository.myVehicles().firstOrNull()
        vehicleLabel = vehicle?.let { "${it.type} — ${it.plateNumber}" }.orEmpty()
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

        Text(
            text = "No damage reports submitted yet.",
            style = MaterialTheme.typography.bodyMedium,
            color = TextSecondary,
        )

        Spacer(modifier = Modifier.height(16.dp))
    }
}
