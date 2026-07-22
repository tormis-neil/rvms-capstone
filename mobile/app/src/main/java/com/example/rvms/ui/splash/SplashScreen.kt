package com.example.rvms.ui.splash

import androidx.compose.foundation.Image
import androidx.compose.foundation.background
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.size
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.res.painterResource
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextAlign
import androidx.compose.ui.tooling.preview.Preview
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import com.example.rvms.R
import com.example.rvms.theme.NavyBlue
import com.example.rvms.theme.RVMSTheme
import com.example.rvms.theme.White
import kotlinx.coroutines.delay

@Composable
fun SplashScreen(
    onSplashFinished: (isAuthenticated: Boolean) -> Unit,
    checkSession: suspend () -> Boolean = { false },
    modifier: Modifier = Modifier,
) {
    // Brief delay for the splash, then route by the saved token (FR-01):
    // a valid token → driver shell, none/invalid → Sign In.
    LaunchedEffect(Unit) {
        delay(1500L)
        val authenticated = runCatching { checkSession() }.getOrDefault(false)
        onSplashFinished(authenticated)
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
            // Command Shield logo
            Image(
                painter = painterResource(id = R.drawable.rvms_logo),
                contentDescription = "RVMS logo",
                modifier = Modifier.size(140.dp),
            )

            Spacer(modifier = Modifier.height(24.dp))

            // Full system name
            Text(
                text = "Rescue Vehicle\nManagement System",
                color = White.copy(alpha = 0.85f),
                fontSize = 14.sp,
                fontWeight = FontWeight.Normal,
                textAlign = TextAlign.Center,
                lineHeight = 20.sp,
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

