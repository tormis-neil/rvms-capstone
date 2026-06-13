package com.example.rvms.ui.auth

import androidx.compose.foundation.background
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.size
import androidx.compose.foundation.Image
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material3.Button
import androidx.compose.material3.ButtonDefaults
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.OutlinedTextField
import androidx.compose.material3.OutlinedTextFieldDefaults
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.res.painterResource
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.input.PasswordVisualTransformation
import androidx.compose.ui.text.style.TextAlign
import androidx.compose.ui.tooling.preview.Preview
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import com.example.rvms.R
import com.example.rvms.data.Agency
import com.example.rvms.data.Session
import com.example.rvms.theme.Background
import com.example.rvms.theme.ErrorRed
import com.example.rvms.theme.NavyBlue
import com.example.rvms.theme.RVMSTheme
import com.example.rvms.theme.TextPrimary
import com.example.rvms.theme.TextSecondary
import com.example.rvms.theme.White

@Composable
fun SignInScreen(
    onNavigateToHome: () -> Unit,
    onNavigateToSignUp: () -> Unit,
    modifier: Modifier = Modifier,
) {
    var email by remember { mutableStateOf("") }
    var password by remember { mutableStateOf("") }
    var error by remember { mutableStateOf<String?>(null) }
    var selectedAgency by remember { mutableStateOf(Agency.BFP) }

    Column(
        modifier =
            modifier
                .fillMaxSize()
                .background(White)
                .padding(24.dp),
        horizontalAlignment = Alignment.CenterHorizontally,
        verticalArrangement = Arrangement.Center,
    ) {
        // Command Shield logo
        Image(
            painter = painterResource(id = R.drawable.rvms_logo),
            contentDescription = "RVMS logo",
            modifier = Modifier.size(80.dp),
        )

        Spacer(modifier = Modifier.height(16.dp))

        // System Name
        Text(
            text = "Rescue Vehicle\nManagement System",
            style = MaterialTheme.typography.headlineSmall,
            color = NavyBlue,
            textAlign = TextAlign.Center,
        )

        Spacer(modifier = Modifier.height(32.dp))

        // Demo agency selector (prototype only) — picks which agency account to sign in as
        Text(
            text = "Sign in as",
            style = MaterialTheme.typography.labelMedium,
            color = TextSecondary,
            fontWeight = FontWeight.SemiBold,
            modifier = Modifier.fillMaxWidth(),
        )
        Spacer(modifier = Modifier.height(8.dp))
        Row(
            modifier = Modifier.fillMaxWidth(),
            horizontalArrangement = Arrangement.spacedBy(8.dp),
        ) {
            Agency.entries.forEach { agency ->
                AgencyChip(
                    label = agency.code,
                    selected = selectedAgency == agency,
                    onClick = { selectedAgency = agency },
                    modifier = Modifier.weight(1f),
                )
            }
        }

        Spacer(modifier = Modifier.height(24.dp))

        // Email Field
        OutlinedTextField(
            value = email,
            onValueChange = { email = it },
            label = { Text("Email") },
            modifier = Modifier.fillMaxWidth(),
            singleLine = true,
            colors = OutlinedTextFieldDefaults.colors(
                focusedBorderColor = NavyBlue,
                focusedLabelColor = NavyBlue
            )
        )

        Spacer(modifier = Modifier.height(16.dp))

        // Password Field
        OutlinedTextField(
            value = password,
            onValueChange = { password = it },
            label = { Text("Password") },
            visualTransformation = PasswordVisualTransformation(),
            modifier = Modifier.fillMaxWidth(),
            singleLine = true,
            colors = OutlinedTextFieldDefaults.colors(
                focusedBorderColor = NavyBlue,
                focusedLabelColor = NavyBlue
            )
        )

        if (error != null) {
            Spacer(modifier = Modifier.height(12.dp))
            Text(
                text = error!!,
                color = ErrorRed,
                style = MaterialTheme.typography.bodySmall,
                fontWeight = FontWeight.Medium,
                textAlign = TextAlign.Center,
                modifier = Modifier.fillMaxWidth(),
            )
        }

        Spacer(modifier = Modifier.height(32.dp))

        // Sign In Button
        Button(
            onClick = {
                when {
                    email.isBlank() || password.isBlank() ->
                        error = "Please enter your email and password."
                    !email.contains("@") ->
                        error = "Please enter a valid email address."
                    else -> {
                        error = null
                        Session.signInAs(selectedAgency)
                        onNavigateToHome()
                    }
                }
            },
            modifier =
                Modifier
                    .fillMaxWidth()
                    .height(50.dp),
            shape = RoundedCornerShape(8.dp),
            colors = ButtonDefaults.buttonColors(containerColor = NavyBlue),
        ) {
            Text(
                text = "Sign In",
                color = White,
                fontSize = 16.sp,
                fontWeight = FontWeight.SemiBold,
            )
        }

        Spacer(modifier = Modifier.height(24.dp))

        // Navigate to Sign Up Link
        Row(
            horizontalArrangement = Arrangement.Center,
            modifier = Modifier.fillMaxWidth()
        ) {
            Text(
                text = "Need an account? ",
                color = TextSecondary,
                style = MaterialTheme.typography.bodyMedium,
            )
            Text(
                text = "Request one",
                color = NavyBlue,
                style = MaterialTheme.typography.bodyMedium,
                fontWeight = FontWeight.Bold,
                modifier = Modifier.clickable { onNavigateToSignUp() },
            )
        }
    }
}

@Composable
private fun AgencyChip(
    label: String,
    selected: Boolean,
    onClick: () -> Unit,
    modifier: Modifier = Modifier,
) {
    Box(
        modifier = modifier
            .clip(RoundedCornerShape(8.dp))
            .background(if (selected) NavyBlue else Background)
            .clickable { onClick() }
            .padding(vertical = 10.dp),
        contentAlignment = Alignment.Center,
    ) {
        Text(
            text = label,
            color = if (selected) White else TextPrimary,
            fontSize = 12.sp,
            fontWeight = FontWeight.SemiBold,
        )
    }
}

@Preview(showBackground = true)
@Composable
fun SignInScreenPreview() {
    RVMSTheme {
        SignInScreen(onNavigateToHome = {}, onNavigateToSignUp = {})
    }
}
