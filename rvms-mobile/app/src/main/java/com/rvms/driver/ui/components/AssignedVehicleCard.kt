package com.rvms.driver.ui.components

import androidx.compose.foundation.BorderStroke
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material3.Card
import androidx.compose.material3.CardDefaults
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.unit.dp
import com.rvms.driver.model.Vehicle
import com.rvms.driver.ui.theme.TextPrimary
import com.rvms.driver.ui.theme.TextSecondary
import com.rvms.driver.ui.theme.Typography

/**
 * AssignedVehicleCard
 *
 * Displays the current assigned vehicle and its real-time operational status.
 * This is a highly prominent card on the driver's home dashboard.
 */
@Composable
fun AssignedVehicleCard(
    vehicle: Vehicle,
    modifier: Modifier = Modifier
) {
    Card(
        modifier = modifier.fillMaxWidth(),
        shape = RoundedCornerShape(12.dp),
        colors = CardDefaults.cardColors(containerColor = Color.White),
        border = BorderStroke(1.dp, Color(0xFFE2E8F0)),
        elevation = CardDefaults.cardElevation(defaultElevation = 2.dp)
    ) {
        Column(
            modifier = Modifier.padding(20.dp)
        ) {
            Text(
                text = "ASSIGNED VEHICLE",
                style = Typography.labelSmall,
                color = TextSecondary
            )
            Spacer(modifier = Modifier.height(8.dp))
            
            Row(
                modifier = Modifier.fillMaxWidth(),
                horizontalArrangement = Arrangement.SpaceBetween,
                verticalAlignment = Alignment.CenterVertically
            ) {
                // Plate Number uses JetBrains Mono for readability
                Text(
                    text = vehicle.plateNumber,
                    style = Typography.headlineMedium,
                    color = TextPrimary
                )
                
                // Status Badge displays operational status with official colors
                StatusBadge(status = vehicle.status)
            }
            
            Spacer(modifier = Modifier.height(12.dp))
            
            // Vehicle Description (Type & Model)
            Text(
                text = "${vehicle.type} • ${vehicle.model}",
                style = Typography.bodyMedium,
                color = TextSecondary
            )
            
            Spacer(modifier = Modifier.height(4.dp))
            
            // Odometer Reading
            Text(
                text = "Odometer: ${String.format("%,d", vehicle.currentOdometer)} km",
                style = Typography.bodyMedium,
                color = TextSecondary
            )
        }
    }
}
