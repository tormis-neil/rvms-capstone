package com.example.rvms.ui.profile

import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material3.AlertDialog
import androidx.compose.material3.CircularProgressIndicator
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.OutlinedTextField
import androidx.compose.material3.Text
import androidx.compose.material3.TextButton
import androidx.compose.runtime.Composable
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.rememberCoroutineScope
import androidx.compose.runtime.setValue
import androidx.compose.ui.Modifier
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.input.PasswordVisualTransformation
import androidx.compose.ui.unit.dp
import com.example.rvms.data.ServiceLocator
import com.example.rvms.data.UpdateProfileResult
import com.example.rvms.theme.ErrorRed
import com.example.rvms.theme.NavyBlue
import com.example.rvms.theme.TextSecondary
import kotlinx.coroutines.launch

/**
 * Self-service edit of the driver's own account (FR-04).
 *
 * The same four fields the admin dashboard's Profile page offers — name,
 * email, new password, confirm — because FR-04 grants drivers and
 * administrators exactly the same self-edit, and a driver who can change their
 * password on one platform but not the other would be an inconsistency, not a
 * simplification.
 *
 * A blank password means "keep the current one": the repository drops the
 * field from the request entirely rather than sending an empty string, which
 * the API's `min:8` rule would reject.
 *
 * No approval step and no notification, per FR-04.
 */
@Composable
fun EditProfileDialog(
    initialName: String,
    initialEmail: String,
    onDismiss: () -> Unit,
    onSaved: () -> Unit,
) {
    val scope = rememberCoroutineScope()

    var name by remember { mutableStateOf(initialName) }
    var email by remember { mutableStateOf(initialEmail) }
    var password by remember { mutableStateOf("") }
    var confirmPassword by remember { mutableStateOf("") }
    var error by remember { mutableStateOf<String?>(null) }
    var saving by remember { mutableStateOf(false) }

    // Checked here as well as on the server so the driver is told immediately,
    // rather than after a round trip that can only fail.
    val mismatch = password.isNotBlank() && password != confirmPassword
    val tooShort = password.isNotBlank() && password.length < 8
    val canSave = !saving && name.isNotBlank() && email.isNotBlank() && !mismatch && !tooShort

    AlertDialog(
        onDismissRequest = { if (!saving) onDismiss() },
        shape = RoundedCornerShape(12.dp),
        title = {
            Text("Edit Profile", fontWeight = FontWeight.Bold, color = NavyBlue)
        },
        text = {
            Column {
                OutlinedTextField(
                    value = name,
                    onValueChange = { name = it },
                    label = { Text("Full Name") },
                    singleLine = true,
                    enabled = !saving,
                    modifier = Modifier.fillMaxWidth(),
                )
                Spacer(modifier = Modifier.height(12.dp))

                OutlinedTextField(
                    value = email,
                    onValueChange = { email = it },
                    label = { Text("Email Address") },
                    singleLine = true,
                    enabled = !saving,
                    modifier = Modifier.fillMaxWidth(),
                )
                Spacer(modifier = Modifier.height(12.dp))

                OutlinedTextField(
                    value = password,
                    onValueChange = { password = it },
                    label = { Text("New Password") },
                    placeholder = { Text("Leave blank to keep current") },
                    singleLine = true,
                    enabled = !saving,
                    isError = tooShort,
                    visualTransformation = PasswordVisualTransformation(),
                    supportingText = if (tooShort) {
                        { Text("At least 8 characters.", color = ErrorRed) }
                    } else {
                        null
                    },
                    modifier = Modifier.fillMaxWidth(),
                )
                Spacer(modifier = Modifier.height(12.dp))

                OutlinedTextField(
                    value = confirmPassword,
                    onValueChange = { confirmPassword = it },
                    label = { Text("Confirm Password") },
                    singleLine = true,
                    enabled = !saving,
                    isError = mismatch,
                    visualTransformation = PasswordVisualTransformation(),
                    supportingText = if (mismatch) {
                        { Text("Passwords do not match.", color = ErrorRed) }
                    } else {
                        null
                    },
                    modifier = Modifier.fillMaxWidth(),
                )

                error?.let {
                    Spacer(modifier = Modifier.height(12.dp))
                    Text(it, color = ErrorRed, style = MaterialTheme.typography.bodySmall)
                }
            }
        },
        confirmButton = {
            TextButton(
                enabled = canSave,
                onClick = {
                    saving = true
                    error = null
                    scope.launch {
                        val result = ServiceLocator.authRepository.updateProfile(
                            name = name,
                            email = email,
                            password = password,
                            passwordConfirmation = confirmPassword,
                        )
                        saving = false
                        when (result) {
                            is UpdateProfileResult.Success -> onSaved()
                            is UpdateProfileResult.Error -> error = result.message
                        }
                    }
                },
            ) {
                if (saving) {
                    CircularProgressIndicator(modifier = Modifier.height(18.dp), strokeWidth = 2.dp)
                } else {
                    Text("Save Changes", color = NavyBlue, fontWeight = FontWeight.Bold)
                }
            }
        },
        dismissButton = {
            TextButton(enabled = !saving, onClick = onDismiss) {
                Text("Cancel", color = TextSecondary)
            }
        },
    )
}
