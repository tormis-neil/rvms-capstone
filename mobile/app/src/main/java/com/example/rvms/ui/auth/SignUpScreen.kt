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
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.rememberCoroutineScope
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
import com.example.rvms.data.RegisterResult
import com.example.rvms.data.ServiceLocator
import com.example.rvms.data.remote.dto.AgencyDto
import com.example.rvms.theme.ErrorRed
import com.example.rvms.theme.NavyBlue
import com.example.rvms.theme.RVMSTheme
import com.example.rvms.theme.TextSecondary
import com.example.rvms.theme.White
import kotlinx.coroutines.launch

/**
 * Driver self-registration screen (FR-03). A driver registers by entering their
 * credentials and selecting their agency; the account is created with a pending
 * status and cannot sign in until an agency administrator approves it. Submitting
 * here never signs the user in or grants access to the driver app.
 */
@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun SignUpScreen(
    onNavigateToSignIn: () -> Unit,
    modifier: Modifier = Modifier,
) {
    var fullName by remember { mutableStateOf("") }
    var email by remember { mutableStateOf("") }
    var password by remember { mutableStateOf("") }
    var confirmPassword by remember { mutableStateOf("") }
    var error by remember { mutableStateOf<String?>(null) }
    var submitted by remember { mutableStateOf(false) }
    var loading by remember { mutableStateOf(false) }
    val scope = rememberCoroutineScope()

    // Agency Dropdown State — real agencies fetched from the public directory so
    // the driver submits a real agency_id (FR-03).
    var expanded by remember { mutableStateOf(false) }
    var agencies by remember { mutableStateOf<List<AgencyDto>>(emptyList()) }
    var selectedAgency by remember { mutableStateOf<AgencyDto?>(null) }

    LaunchedEffect(Unit) {
        agencies = runCatching { ServiceLocator.authRepository.agencies() }.getOrDefault(emptyList())
    }

    val scrollState = rememberScrollState()

    if (submitted) {
        AlertDialog(
            onDismissRequest = { },
            confirmButton = {
                TextButton(onClick = onNavigateToSignIn) {
                    Text("Back to Sign In", color = NavyBlue, fontWeight = FontWeight.Bold)
                }
            },
            title = { Text("Registration Submitted", color = NavyBlue, fontWeight = FontWeight.Bold) },
            text = {
                Text(
                    "Your account has been created and is pending approval. " +
                        "You will be able to sign in once your agency administrator approves it.",
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
            text = "Create Account",
            style = MaterialTheme.typography.headlineMedium,
            color = NavyBlue,
        )

        Spacer(modifier = Modifier.height(8.dp))

        Text(
            text = "Register by selecting your agency and setting a password. " +
                "Your account will remain pending until your agency administrator approves it.",
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

        // Password Field
        OutlinedTextField(
            value = password,
            onValueChange = { password = it },
            label = { Text("Password") },
            modifier = Modifier.fillMaxWidth(),
            singleLine = true,
            visualTransformation = PasswordVisualTransformation(),
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
            modifier = Modifier.fillMaxWidth(),
            singleLine = true,
            visualTransformation = PasswordVisualTransformation(),
            colors = OutlinedTextFieldDefaults.colors(
                focusedBorderColor = NavyBlue,
                focusedLabelColor = NavyBlue
            )
        )

        Spacer(modifier = Modifier.height(16.dp))

        // Agency Dropdown — real agencies from GET /agencies
        ExposedDropdownMenuBox(
            expanded = expanded,
            onExpandedChange = { expanded = !expanded },
        ) {
            OutlinedTextField(
                value = selectedAgency?.code.orEmpty(),
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
                        text = { Text(agency.code) },
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

        // Register Button — real POST /register (driver-only, created pending, FR-03).
        Button(
            onClick = {
                val agency = selectedAgency
                when {
                    fullName.isBlank() || email.isBlank() ||
                        password.isBlank() || confirmPassword.isBlank() ->
                        error = "Please complete all fields."
                    !email.contains("@") ->
                        error = "Please enter a valid email address."
                    password.length < 8 ->
                        error = "Password must be at least 8 characters."
                    password != confirmPassword ->
                        error = "Passwords do not match."
                    agency == null ->
                        error = "Please select your agency."
                    else -> {
                        error = null
                        loading = true
                        scope.launch {
                            val result = ServiceLocator.authRepository.register(
                                agencyId = agency.id,
                                name = fullName,
                                email = email,
                                password = password,
                                passwordConfirmation = confirmPassword,
                            )
                            loading = false
                            when (result) {
                                is RegisterResult.Success -> submitted = true
                                is RegisterResult.Error -> error = result.message
                            }
                        }
                    }
                }
            },
            enabled = !loading,
            modifier =
                Modifier
                    .fillMaxWidth()
                    .height(50.dp),
            shape = RoundedCornerShape(8.dp),
            colors = ButtonDefaults.buttonColors(containerColor = NavyBlue),
        ) {
            Text(
                text = if (loading) "Registering…" else "Register",
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
