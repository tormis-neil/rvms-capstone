package com.example.rvms.ui.common

import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.size
import androidx.compose.foundation.layout.width
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.Warning
import androidx.compose.material3.Card
import androidx.compose.material3.CardDefaults
import androidx.compose.material3.Icon
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import com.example.rvms.theme.StatusUnderPM
import com.example.rvms.theme.TextPrimary
import com.example.rvms.theme.TextSecondary

/**
 * Shown when a screen could not reach the server (R10 sub-task 1, NFR-04).
 *
 * Amber rather than red: nothing is broken and nothing was lost — the app
 * simply could not ask. Red is reserved for a vehicle that is Not Operational,
 * and spending it on a dropped connection would blunt the one colour that has
 * to mean something in this app.
 *
 * @param staleDataShown true when the screen still holds records from an
 *        earlier load. The wording changes, because "here is old data" and
 *        "here is no data" are different promises to a driver deciding
 *        whether to drive.
 */
@Composable
fun ConnectionErrorCard(
    message: String,
    staleDataShown: Boolean = false,
    modifier: Modifier = Modifier,
) {
    Card(
        modifier = modifier.fillMaxWidth(),
        shape = RoundedCornerShape(12.dp),
        colors = CardDefaults.cardColors(containerColor = StatusUnderPM.copy(alpha = 0.10f)),
    ) {
        Row(
            modifier = Modifier.padding(16.dp),
            verticalAlignment = Alignment.Top,
        ) {
            Icon(
                imageVector = Icons.Filled.Warning,
                contentDescription = null,
                tint = StatusUnderPM,
                modifier = Modifier.size(20.dp),
            )
            Spacer(modifier = Modifier.width(12.dp))
            Column {
                Text(
                    text = if (staleDataShown) "Showing last loaded data" else "No connection",
                    style = MaterialTheme.typography.bodyMedium,
                    color = TextPrimary,
                    fontWeight = FontWeight.SemiBold,
                )
                Spacer(modifier = Modifier.height(2.dp))
                Text(
                    text = message,
                    style = MaterialTheme.typography.bodySmall,
                    color = TextSecondary,
                )
            }
        }
    }
}
