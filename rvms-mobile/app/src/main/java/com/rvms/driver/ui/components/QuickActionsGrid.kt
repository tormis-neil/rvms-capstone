package com.rvms.driver.ui.components

import androidx.compose.foundation.BorderStroke
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.size
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.Build
import androidx.compose.material.icons.filled.DirectionsCar
import androidx.compose.material.icons.filled.History
import androidx.compose.material.icons.filled.PlaylistAddCheck
import androidx.compose.material3.Card
import androidx.compose.material3.CardDefaults
import androidx.compose.material3.Icon
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.graphics.vector.ImageVector
import androidx.compose.ui.text.style.TextAlign
import androidx.compose.ui.unit.dp
import com.rvms.driver.ui.theme.PrimaryBlue
import com.rvms.driver.ui.theme.TextPrimary
import com.rvms.driver.ui.theme.Typography

/**
 * QuickActionsGrid
 *
 * Displays a 2x2 grid of quick action buttons for the driver to perform common tasks
 * like logging an inspection, reporting a defect, viewing history, or viewing vehicle details.
 */
@Composable
fun QuickActionsGrid(
    onNewInspectionClick: () -> Unit,
    onReportDefectClick: () -> Unit,
    onViewHistoryClick: () -> Unit,
    onMyVehicleClick: () -> Unit,
    modifier: Modifier = Modifier
) {
    Column(modifier = modifier.fillMaxWidth()) {
        Row(
            modifier = Modifier.fillMaxWidth(),
            horizontalArrangement = Arrangement.spacedBy(12.dp)
        ) {
            QuickActionCard(
                title = "New\nInspection",
                icon = Icons.Default.PlaylistAddCheck,
                onClick = onNewInspectionClick,
                modifier = Modifier.weight(1f)
            )
            QuickActionCard(
                title = "Report\nDefect",
                icon = Icons.Default.Build,
                onClick = onReportDefectClick,
                modifier = Modifier.weight(1f)
            )
        }
        Spacer(modifier = Modifier.height(12.dp))
        Row(
            modifier = Modifier.fillMaxWidth(),
            horizontalArrangement = Arrangement.spacedBy(12.dp)
        ) {
            QuickActionCard(
                title = "View\nHistory",
                icon = Icons.Default.History,
                onClick = onViewHistoryClick,
                modifier = Modifier.weight(1f)
            )
            QuickActionCard(
                title = "My\nVehicle",
                icon = Icons.Default.DirectionsCar,
                onClick = onMyVehicleClick,
                modifier = Modifier.weight(1f)
            )
        }
    }
}

@Composable
private fun QuickActionCard(
    title: String,
    icon: ImageVector,
    onClick: () -> Unit,
    modifier: Modifier = Modifier
) {
    Card(
        modifier = modifier
            .height(100.dp)
            .clickable(onClick = onClick),
        shape = RoundedCornerShape(12.dp),
        colors = CardDefaults.cardColors(containerColor = Color.White),
        border = BorderStroke(1.dp, Color(0xFFE2E8F0)),
        elevation = CardDefaults.cardElevation(defaultElevation = 0.dp)
    ) {
        Column(
            modifier = Modifier
                .fillMaxWidth()
                .padding(12.dp),
            horizontalAlignment = Alignment.CenterHorizontally,
            verticalArrangement = Arrangement.Center
        ) {
            Icon(
                imageVector = icon,
                contentDescription = null,
                tint = PrimaryBlue,
                modifier = Modifier.size(28.dp)
            )
            Spacer(modifier = Modifier.height(8.dp))
            Text(
                text = title,
                style = Typography.bodyMedium,
                color = TextPrimary,
                textAlign = TextAlign.Center
            )
        }
    }
}
