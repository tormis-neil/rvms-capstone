package com.rvms.driver.ui.components

import androidx.compose.foundation.background
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import com.rvms.driver.ui.theme.JetBrainsMonoFontFamily

enum class VehicleStatus(val label: String, val color: Color) {
    OPERATIONAL("OPERATIONAL", Color(0xFF22C55E)),
    DISPATCHED("DISPATCHED", Color(0xFF2563EB)),
    PARTIALLY_OPERATIONAL("PARTIALLY_OPERATIONAL", Color(0xFFF59E0B)),
    NOT_OPERATIONAL("NOT_OPERATIONAL", Color(0xFFEF4444)),
    UNDER_MAINTENANCE("UNDER_MAINTENANCE", Color(0xFF8B5CF6))
}

@Composable
fun StatusBadge(
    status: VehicleStatus,
    modifier: Modifier = Modifier
) {
    Box(
        modifier = modifier
            .background(
                color = status.color.copy(alpha = 0.15f), // 15% opacity background
                shape = RoundedCornerShape(50) // Pill shape
            )
            .padding(horizontal = 12.dp, vertical = 4.dp)
    ) {
        Text(
            text = status.label,
            color = status.color, // 100% opacity text
            fontFamily = JetBrainsMonoFontFamily,
            fontWeight = FontWeight.Bold,
            fontSize = 12.sp
        )
    }
}
