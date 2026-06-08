package com.example.rvms.ui.splash

import androidx.compose.foundation.background
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.size
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextAlign
import androidx.compose.ui.tooling.preview.Preview
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import com.example.rvms.theme.DarkNavy
import com.example.rvms.theme.Gold
import com.example.rvms.theme.NavyBlue
import com.example.rvms.theme.RVMSTheme
import com.example.rvms.theme.TextSecondary
import com.example.rvms.theme.White
import kotlinx.coroutines.delay

@Composable
fun SplashScreen(
    onSplashFinished: () -> Unit,
    modifier: Modifier = Modifier,
) {
    // Brief delay then navigate to sign-in
    LaunchedEffect(Unit) {
        delay(2000L)
        onSplashFinished()
    }

    Box(
        modifier =
            modifier
                .fillMaxSize()
                .background(NavyBlue),
        contentAlignment = Alignment.Center,
    ) {
        Column(
            horizontalAlignment = Alignment.CenterHorizontally,
            verticalArrangement = Arrangement.Center,
        ) {
            // Logo mark — circular gold container with "RVMS" text
            Box(
                modifier =
                    Modifier
                        .size(100.dp)
                        .clip(CircleShape)
                        .background(Gold),
                contentAlignment = Alignment.Center,
            ) {
                Text(
                    text = "RVMS",
                    color = DarkNavy,
                    fontSize = 28.sp,
                    fontWeight = FontWeight.ExtraBold,
                    letterSpacing = 1.sp,
                )
            }

            Spacer(modifier = Modifier.height(24.dp))

            // App name
            Text(
                text = "RVMS",
                color = White,
                fontSize = 32.sp,
                fontWeight = FontWeight.Bold,
                letterSpacing = 4.sp,
                textAlign = TextAlign.Center,
            )

            Spacer(modifier = Modifier.height(8.dp))

            // Full system name
            Text(
                text = "Rescue Vehicle\nManagement System",
                color = White.copy(alpha = 0.85f),
                fontSize = 14.sp,
                fontWeight = FontWeight.Normal,
                textAlign = TextAlign.Center,
                lineHeight = 20.sp,
            )

            Spacer(modifier = Modifier.height(48.dp))

            // Subtitle
            Text(
                text = "Vehicle Monitoring & Maintenance Platform",
                color = TextSecondary,
                fontSize = 11.sp,
                fontWeight = FontWeight.Normal,
                textAlign = TextAlign.Center,
                letterSpacing = 0.5.sp,
            )
        }
    }
}

@Preview(showBackground = true, showSystemUi = true)
@Composable
fun SplashScreenPreview() {
    RVMSTheme {
        SplashScreen(onSplashFinished = {})
    }
}
