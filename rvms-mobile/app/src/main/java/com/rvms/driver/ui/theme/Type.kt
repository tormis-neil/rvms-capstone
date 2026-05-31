package com.rvms.driver.ui.theme

import androidx.compose.material3.Typography
import androidx.compose.ui.text.TextStyle
import androidx.compose.ui.text.font.FontFamily
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.sp

// For the prototype, we use the system default sans-serif font (which is
// Roboto on most Android devices â€” very similar to Inter in readability).
// For the full production build, we will bundle the exact Inter and
// JetBrains Mono font files as static .ttf resources.
val InterFontFamily = FontFamily.SansSerif
val JetBrainsMonoFontFamily = FontFamily.Monospace

val Typography = Typography(
    // H1 (Page Titles) â€” used for screen titles like "Dashboard", "Inspections"
    headlineMedium = TextStyle(
        fontFamily = InterFontFamily,
        fontWeight = FontWeight.SemiBold,
        fontSize = 24.sp,
        lineHeight = 32.sp
    ),
    // H2 (Card Titles) â€” used for card headers like vehicle name, section titles
    titleLarge = TextStyle(
        fontFamily = InterFontFamily,
        fontWeight = FontWeight.Medium,
        fontSize = 18.sp,
        lineHeight = 24.sp
    ),
    // Body Text â€” main readable text
    bodyLarge = TextStyle(
        fontFamily = InterFontFamily,
        fontWeight = FontWeight.Normal,
        fontSize = 16.sp,
        lineHeight = 24.sp,
        color = TextPrimary
    ),
    // Body Secondary â€” subtitles, helper text, timestamps
    bodyMedium = TextStyle(
        fontFamily = InterFontFamily,
        fontWeight = FontWeight.Normal,
        fontSize = 14.sp,
        lineHeight = 20.sp,
        color = TextSecondary
    ),
    // Small/Caption â€” used for timestamps like "2 mins ago"
    labelSmall = TextStyle(
        fontFamily = InterFontFamily,
        fontWeight = FontWeight.Light,
        fontSize = 14.sp,
        lineHeight = 20.sp,
        color = TextSecondary
    ),
    // Data Fields â€” plate numbers, odometers, license numbers
    // Uses monospace so digits align perfectly in tables and cards
    labelLarge = TextStyle(
        fontFamily = JetBrainsMonoFontFamily,
        fontWeight = FontWeight.Normal,
        fontSize = 16.sp,
        lineHeight = 24.sp,
        color = TextPrimary
    )
)
