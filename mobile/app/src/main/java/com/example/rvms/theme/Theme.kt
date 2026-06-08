package com.example.rvms.theme

import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.lightColorScheme
import androidx.compose.runtime.Composable

// RVMS uses the "Structured Authority" theme — light surfaces with navy structure.
// No dark theme variant is needed for this prototype.
private val RVMSColorScheme =
    lightColorScheme(
        primary = NavyBlue,
        onPrimary = White,
        primaryContainer = LightNavy,
        onPrimaryContainer = White,
        secondary = Gold,
        onSecondary = DarkNavy,
        secondaryContainer = LightGold,
        onSecondaryContainer = DarkNavy,
        tertiary = LightNavy,
        onTertiary = White,
        error = ErrorRed,
        onError = White,
        background = Background,
        onBackground = TextPrimary,
        surface = Surface,
        onSurface = TextPrimary,
        surfaceVariant = Background,
        onSurfaceVariant = TextSecondary,
        outline = Border,
        outlineVariant = Border,
    )

@Composable
fun RVMSTheme(
    content: @Composable () -> Unit,
) {
    MaterialTheme(
        colorScheme = RVMSColorScheme,
        typography = Typography,
        content = content,
    )
}
