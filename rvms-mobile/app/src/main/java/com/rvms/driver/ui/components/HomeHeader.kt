package com.rvms.driver.ui.components

import androidx.compose.foundation.background
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.size
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.LocalPolice
import androidx.compose.material3.Icon
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import com.rvms.driver.model.Driver
import com.rvms.driver.ui.theme.PrimaryBlue
import com.rvms.driver.ui.theme.TextPrimary
import com.rvms.driver.ui.theme.TextSecondary
import com.rvms.driver.ui.theme.Typography

/**
 * HomeHeader
 *
 * Displays the welcome message for the driver and the agency logo.
 * Uses a Material Icon (LocalPolice shield) as a placeholder for the actual agency logo.
 */
@Composable
fun HomeHeader(
    driver: Driver,
    modifier: Modifier = Modifier
) {
    Row(
        modifier = modifier
            .fillMaxWidth()
            .padding(vertical = 16.dp),
        horizontalArrangement = Arrangement.SpaceBetween,
        verticalAlignment = Alignment.CenterVertically
    ) {
        Column {
            Text(
                text = "Welcome,",
                style = Typography.bodyMedium,
                color = TextSecondary
            )
            Text(
                text = driver.name,
                style = Typography.titleLarge,
                color = TextPrimary,
                fontWeight = FontWeight.Bold
            )
            Text(
                text = driver.agency.displayName,
                style = Typography.labelSmall,
                color = PrimaryBlue
            )
        }

        // Agency Logo Placeholder
        Box(
            modifier = Modifier
                .size(48.dp)
                .clip(CircleShape)
                .background(Color(0xFFE2E8F0)),
            contentAlignment = Alignment.Center
        ) {
            Icon(
                imageVector = Icons.Default.LocalPolice,
                contentDescription = "Agency Logo",
                tint = PrimaryBlue,
                modifier = Modifier.size(28.dp)
            )
        }
    }
}
