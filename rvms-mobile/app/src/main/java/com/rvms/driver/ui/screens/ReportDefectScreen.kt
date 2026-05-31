package com.rvms.driver.ui.screens

import androidx.compose.foundation.background
import androidx.compose.foundation.border
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
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.foundation.verticalScroll
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.automirrored.filled.ArrowBack
import androidx.compose.material.icons.filled.AddAPhoto
import androidx.compose.material.icons.filled.ArrowDropDown
import androidx.compose.material3.CircularProgressIndicator
import androidx.compose.material3.DropdownMenu
import androidx.compose.material3.DropdownMenuItem
import androidx.compose.material3.ExperimentalMaterial3Api
import androidx.compose.material3.Icon
import androidx.compose.material3.IconButton
import androidx.compose.material3.Scaffold
import androidx.compose.material3.Text
import androidx.compose.material3.TopAppBar
import androidx.compose.material3.TopAppBarDefaults
import androidx.compose.runtime.Composable
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.rememberCoroutineScope
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import com.rvms.driver.model.DummyAssignedVehicle
import com.rvms.driver.ui.components.RVMSInputField
import com.rvms.driver.ui.components.RVMSPrimaryButton
import com.rvms.driver.ui.theme.BackgroundLight
import com.rvms.driver.ui.theme.PrimaryBlue
import com.rvms.driver.ui.theme.TextPrimary
import com.rvms.driver.ui.theme.TextSecondary
import com.rvms.driver.ui.theme.Typography
import kotlinx.coroutines.delay
import kotlinx.coroutines.launch

val defectCategories = listOf("Engine", "Tires/Wheels", "Electrical", "Body Damage", "Interior", "Other")

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun ReportDefectScreen(
    onBack: () -> Unit,
    onSubmitSuccess: () -> Unit,
    modifier: Modifier = Modifier
) {
    var selectedCategory by remember { mutableStateOf(defectCategories[0]) }
    var description by remember { mutableStateOf("") }
    var isSubmitting by remember { mutableStateOf(false) }
    var dropdownExpanded by remember { mutableStateOf(false) }
    
    val coroutineScope = rememberCoroutineScope()
    val vehicle = DummyAssignedVehicle

    Scaffold(
        topBar = {
            TopAppBar(
                title = { Text("Report Defect", style = Typography.titleLarge) },
                navigationIcon = {
                    IconButton(onClick = onBack) {
                        Icon(Icons.AutoMirrored.Filled.ArrowBack, contentDescription = "Back")
                    }
                },
                colors = TopAppBarDefaults.topAppBarColors(
                    containerColor = Color.White,
                    titleContentColor = PrimaryBlue,
                    navigationIconContentColor = PrimaryBlue
                )
            )
        }
    ) { paddingValues ->
        Column(
            modifier = modifier
                .fillMaxSize()
                .background(BackgroundLight)
                .padding(paddingValues)
                .padding(16.dp)
                .verticalScroll(rememberScrollState())
        ) {
            // Vehicle Info (Pre-filled)
            Text("Vehicle", style = Typography.labelMedium, color = TextSecondary)
            Text(
                text = "${vehicle.plateNumber} - ${vehicle.type}",
                style = Typography.bodyLarge,
                color = TextPrimary,
                fontWeight = FontWeight.Medium,
                modifier = Modifier.padding(top = 4.dp, bottom = 16.dp)
            )

            // Category Dropdown
            Text("Defect Category", style = Typography.labelMedium, color = TextSecondary)
            Spacer(modifier = Modifier.height(4.dp))
            Box(
                modifier = Modifier
                    .fillMaxWidth()
                    .border(1.dp, Color(0xFFCBD5E1), RoundedCornerShape(8.dp))
                    .background(Color.White, RoundedCornerShape(8.dp))
                    .clickable { dropdownExpanded = true }
                    .padding(16.dp)
            ) {
                Row(
                    modifier = Modifier.fillMaxWidth(),
                    horizontalArrangement = Arrangement.SpaceBetween
                ) {
                    Text(text = selectedCategory, style = Typography.bodyMedium)
                    Icon(Icons.Default.ArrowDropDown, contentDescription = "Select")
                }
                DropdownMenu(
                    expanded = dropdownExpanded,
                    onDismissRequest = { dropdownExpanded = false }
                ) {
                    defectCategories.forEach { category ->
                        DropdownMenuItem(
                            text = { Text(category) },
                            onClick = {
                                selectedCategory = category
                                dropdownExpanded = false
                            }
                        )
                    }
                }
            }

            Spacer(modifier = Modifier.height(16.dp))

            // Description
            RVMSInputField(
                value = description,
                onValueChange = { description = it },
                label = "Description",
                placeholder = "Describe the defect or damage in detail...",
                minLines = 4
            )

            Spacer(modifier = Modifier.height(16.dp))

            // Photo Upload Mock
            Text("Attach Photo", style = Typography.labelMedium, color = TextSecondary)
            Spacer(modifier = Modifier.height(4.dp))
            Box(
                modifier = Modifier
                    .fillMaxWidth()
                    .height(120.dp)
                    // Dashed border representation by just making it grey and outlined for prototype
                    .border(1.dp, Color(0xFF94A3B8), RoundedCornerShape(8.dp))
                    .background(Color(0xFFF1F5F9), RoundedCornerShape(8.dp))
                    .clickable { /* MOCK PHOTO PICKER */ },
                contentAlignment = Alignment.Center
            ) {
                Column(horizontalAlignment = Alignment.CenterHorizontally) {
                    Icon(
                        imageVector = Icons.Default.AddAPhoto,
                        contentDescription = "Upload Photo",
                        tint = Color(0xFF64748B),
                        modifier = Modifier.size(32.dp)
                    )
                    Spacer(modifier = Modifier.height(8.dp))
                    Text(
                        text = "Tap to upload image",
                        style = Typography.bodyMedium,
                        color = Color(0xFF64748B)
                    )
                }
            }

            Spacer(modifier = Modifier.height(40.dp))

            if (isSubmitting) {
                CircularProgressIndicator(modifier = Modifier.align(Alignment.CenterHorizontally))
            } else {
                RVMSPrimaryButton(
                    text = "Submit Report",
                    onClick = {
                        isSubmitting = true
                        coroutineScope.launch {
                            delay(1500) // Simulate network request
                            isSubmitting = false
                            onSubmitSuccess()
                        }
                    },
                    enabled = description.isNotBlank(),
                    modifier = Modifier.fillMaxWidth()
                )
            }
        }
    }
}
