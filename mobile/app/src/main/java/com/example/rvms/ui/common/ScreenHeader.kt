package com.example.rvms.ui.common

import androidx.compose.foundation.layout.Column
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.ui.Modifier
import com.example.rvms.theme.TextPrimary
import com.example.rvms.theme.TextSecondary

/**
 * Standard title block for the primary (bottom-nav) screens.
 *
 * Uses headlineLarge (24sp, bold) so the screen title sits clearly above the
 * 16sp section headers, giving a consistent hierarchy across Home, Inspection,
 * Damage, and Notifications. Sub-screens use a TopAppBar instead.
 */
@Composable
fun ScreenHeader(
    title: String,
    subtitle: String? = null,
    modifier: Modifier = Modifier,
) {
    Column(modifier = modifier) {
        Text(
            text = title,
            style = MaterialTheme.typography.headlineLarge,
            color = TextPrimary,
        )
        if (subtitle != null) {
            Text(
                text = subtitle,
                style = MaterialTheme.typography.bodyMedium,
                color = TextSecondary,
            )
        }
    }
}
