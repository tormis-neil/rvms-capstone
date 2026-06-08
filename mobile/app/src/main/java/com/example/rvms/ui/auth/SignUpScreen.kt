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
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.foundation.verticalScroll
import androidx.compose.material3.Button
import androidx.compose.material3.ButtonDefaults
import androidx.compose.material3.DropdownMenuItem
import androidx.compose.material3.ExperimentalMaterial3Api
import androidx.compose.material3.ExposedDropdownMenuBox
import androidx.compose.material3.ExposedDropdownMenuDefaults
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
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.input.PasswordVisualTransformation
import androidx.compose.ui.tooling.preview.Preview
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import com.example.rvms.theme.DarkNavy
import com.example.rvms.theme.Gold
import com.example.rvms.theme.NavyBlue
import com.example.rvms.theme.RVMSTheme
import com.example.rvms.theme.TextSecondary
import com.example.rvms.theme.White

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun SignUpScreen(
    onNavigateToHome: () -> Unit,
    onNavigateToSignIn: () -> Unit,
    modifier: Modifier = Modifier,
) {
    var fullName by remember { mutableStateOf("") }
    var email by remember { mutableStateOf("") }
    var password by remember { mutableStateOf("") }
    var confirmPassword by remember { mutableStateOf("") }

    // Agency Dropdown State
    var expanded by remember { mutableStateOf(false) }
    val agencies = listOf("BFP", "PNP", "CDRRMO", "CHO")
    var selectedAgency by remember { mutableStateOf("") }

    val scrollState = rememberScrollState()

    Column(
        modifier =
            modifier
                .fillMaxSize()
                .background(White)
                .verticalScroll(scrollState)
                .padding(24.dp),
        horizontalAlignment = Alignment.CenterHorizontally,
        verticalArrangement = Arrangement.Center,
    ) {
        Spacer(modifier = Modifier.height(24.dp))
        // Small Logo
        Box(
            modifier =
                Modifier
                    .size(56.dp)
                    .clip(CircleShape)
                    .background(Gold),
            contentAlignment = Alignment.Center,
        ) {
            Text(
                text = "RVMS",
                color = DarkNavy,
                fontSize = 14.sp,
                fontWeight = FontWeight.ExtraBold,
            )
        }

        Spacer(modifier = Modifier.height(16.dp))

        Text(
            text = "Create Account",
            style = MaterialTheme.typography.headlineMedium,
            color = NavyBlue,
        )

        Spacer(modifier = Modifier.height(32.dp))

        // Full Name Field
        OutlinedTextField(
            value = fullName,
            onValueChange = { fullName = it },
            label = { Text("Full Name") },
            modifier = Modifier.fillMaxWidth(),
            singleLine = true,
            colors = OutlinedTextFieldDefaults.colors(
                focusedBorderColor = NavyBlue,
                focusedLabelColor = NavyBlue
            )
        )

        Spacer(modifier = Modifier.height(16.dp))

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

        // Agency Dropdown
        ExposedDropdownMenuBox(
            expanded = expanded,
            onExpandedChange = { expanded = !expanded },
        ) {
            OutlinedTextField(
                value = selectedAgency,
                onValueChange = {},
                readOnly = true,
                label = { Text("Agency") },
                trailingIcon = { ExposedDropdownMenuDefaults.TrailingIcon(expanded = expanded) },
                modifier = Modifier.fillMaxWidth().menuAnchor(),
                colors = OutlinedTextFieldDefaults.colors(
                    focusedBorderColor = NavyBlue,
                    focusedLabelColor = NavyBlue
                )
            )
            ExposedDropdownMenu(
                expanded = expanded,
                onDismissRequest = { expanded = false },
                modifier = Modifier.background(White)
            ) {
                agencies.forEach { agency ->
                    DropdownMenuItem(
                        text = { Text(agency) },
                        onClick = {
                            selectedAgency = agency
                            expanded = false
                        },
                    )
                }
            }
        }

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

        Spacer(modifier = Modifier.height(16.dp))

        // Confirm Password Field
        OutlinedTextField(
            value = confirmPassword,
            onValueChange = { confirmPassword = it },
            label = { Text("Confirm Password") },
            visualTransformation = PasswordVisualTransformation(),
            modifier = Modifier.fillMaxWidth(),
            singleLine = true,
            colors = OutlinedTextFieldDefaults.colors(
                focusedBorderColor = NavyBlue,
                focusedLabelColor = NavyBlue
            )
        )

        Spacer(modifier = Modifier.height(32.dp))

        // Sign Up Button
        Button(
            onClick = onNavigateToHome,
            modifier =
                Modifier
                    .fillMaxWidth()
                    .height(50.dp),
            shape = RoundedCornerShape(8.dp),
            colors = ButtonDefaults.buttonColors(containerColor = NavyBlue),
        ) {
            Text(
                text = "Sign Up",
                color = White,
                fontSize = 16.sp,
                fontWeight = FontWeight.SemiBold,
            )
        }

        Spacer(modifier = Modifier.height(24.dp))

        // Navigate to Sign In Link
        Row(
            horizontalArrangement = Arrangement.Center,
            modifier = Modifier.fillMaxWidth().padding(bottom = 24.dp)
        ) {
            Text(
                text = "Already have an account? ",
                color = TextSecondary,
                style = MaterialTheme.typography.bodyMedium,
            )
            Text(
                text = "Sign In",
                color = NavyBlue,
                style = MaterialTheme.typography.bodyMedium,
                fontWeight = FontWeight.Bold,
                modifier = Modifier.clickable { onNavigateToSignIn() },
            )
        }
    }
}

@Preview(showBackground = true)
@Composable
fun SignUpScreenPreview() {
    RVMSTheme {
        SignUpScreen(onNavigateToHome = {}, onNavigateToSignIn = {})
    }
}
