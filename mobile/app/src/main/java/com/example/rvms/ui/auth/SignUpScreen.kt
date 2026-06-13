package com.example.rvms.ui.auth

import androidx.compose.foundation.background
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.size
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.Image
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.foundation.verticalScroll
import androidx.compose.material3.AlertDialog
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
import androidx.compose.material3.TextButton
import androidx.compose.runtime.Composable
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.res.painterResource
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.input.PasswordVisualTransformation
import androidx.compose.ui.tooling.preview.Preview
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.compose.ui.text.style.TextAlign
import com.example.rvms.R
import com.example.rvms.theme.ErrorRed
import com.example.rvms.theme.NavyBlue
import com.example.rvms.theme.RVMSTheme
import com.example.rvms.theme.TextSecondary
import com.example.rvms.theme.White

/**
 * Account request screen. The system has no driver self-registration: submitting
 * here only sends a request to the agency administrator, who creates the account.
 * It never signs the user in or grants access to the driver app.
 */
@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun SignUpScreen(
    onNavigateToSignIn: () -> Unit,
    modifier: Modifier = Modifier,
) {
    var fullName by remember { mutableStateOf("") }
    var email by remember { mutableStateOf("") }
    var error by remember { mutableStateOf<String?>(null) }
    var submitted by remember { mutableStateOf(false) }

    // Agency Dropdown State
    var expanded by remember { mutableStateOf(false) }
    val agencies = listOf("BFP", "PNP", "CDRRMO", "CHO")
    var selectedAgency by remember { mutableStateOf("") }

    val scrollState = rememberScrollState()

    if (submitted) {
        AlertDialog(
            onDismissRequest = { },
            confirmButton = {
                TextButton(onClick = onNavigateToSignIn) {
                    Text("Back to Sign In", color = NavyBlue, fontWeight = FontWeight.Bold)
                }
            },
            title = { Text("Request Submitted", color = NavyBlue, fontWeight = FontWeight.Bold) },
            text = {
                Text(
                    "Your account request has been sent to your agency administrator for review. " +
                        "You will be able to sign in once your account is approved and created.",
                    color = TextSecondary,
                )
            },
            containerColor = White,
        )
    }

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
        // Command Shield logo
        Image(
            painter = painterResource(id = R.drawable.rvms_logo),
            contentDescription = "RVMS logo",
            modifier = Modifier.size(72.dp),
        )

        Spacer(modifier = Modifier.height(16.dp))

        Text(
            text = "Request an Account",
            style = MaterialTheme.typography.headlineMedium,
            color = NavyBlue,
        )

        Spacer(modifier = Modifier.height(8.dp))

        Text(
            text = "Submit your details to your agency administrator. " +
                "Accounts are created by the administrator — this form only sends a request.",
            style = MaterialTheme.typography.bodySmall,
            color = TextSecondary,
            textAlign = TextAlign.Center,
            modifier = Modifier.fillMaxWidth(),
        )

        Spacer(modifier = Modifier.height(28.dp))

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

        // Submit Request Button
        Button(
            onClick = {
                when {
                    fullName.isBlank() || email.isBlank() ->
                        error = "Please complete all fields."
                    !email.contains("@") ->
                        error = "Please enter a valid email address."
                    selectedAgency.isBlank() ->
                        error = "Please select your agency."
                    else -> {
                        error = null
                        submitted = true
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
                text = "Submit Request",
                color = White,
                fontSize = 16.sp,
                fontWeight = FontWeight.SemiBold,
            )
        }

        Spacer(modifier = Modifier.height(24.dp))

        // Navigate back to Sign In Link
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
        SignUpScreen(onNavigateToSignIn = {})
    }
}
