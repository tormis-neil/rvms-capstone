package com.rvms.driver.ui.screens

import androidx.compose.foundation.background
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.verticalScroll
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.automirrored.filled.ArrowBack
import androidx.compose.material3.CircularProgressIndicator
import androidx.compose.material3.ExperimentalMaterial3Api
import androidx.compose.material3.Icon
import androidx.compose.material3.IconButton
import androidx.compose.material3.Scaffold
import androidx.compose.material3.Text
import androidx.compose.material3.TopAppBar
import androidx.compose.material3.TopAppBarDefaults
import androidx.compose.runtime.Composable
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateListOf
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.rememberCoroutineScope
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.unit.dp
import com.rvms.driver.ui.components.InspectionChecklistItem
import com.rvms.driver.ui.components.InspectionStatus
import com.rvms.driver.ui.components.RVMSPrimaryButton
import com.rvms.driver.ui.theme.BackgroundLight
import com.rvms.driver.ui.theme.PrimaryBlue
import com.rvms.driver.ui.theme.Typography
import kotlinx.coroutines.delay
import kotlinx.coroutines.launch

val blowbagetsItems = listOf(
    "Battery",
    "Lights (Headlights/Tail)",
    "Lights (Signal/Hazards)",
    "Oil Level",
    "Water / Coolant",
    "Brakes",
    "Air (Tire Pressure)",
    "Gas / Fuel Level",
    "Engine Condition",
    "Tires (Tread Condition)",
    "Self (Driver Readiness)",
    "Horns & Sirens",
    "Wipers & Washer Fluid",
    "Emergency Equipment"
)

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun NewInspectionScreen(
    onBack: () -> Unit,
    onSubmitSuccess: () -> Unit,
    modifier: Modifier = Modifier
) {
    val coroutineScope = rememberCoroutineScope()
    var isSubmitting by remember { mutableStateOf(false) }

    // State for checklist
    val itemStatuses = remember { mutableStateListOf(*Array(14) { InspectionStatus.UNCHECKED }) }
    val itemRemarks = remember { mutableStateListOf(*Array(14) { "" }) }

    Scaffold(
        topBar = {
            TopAppBar(
                title = { Text("New Inspection", style = Typography.titleLarge) },
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
            Text(
                text = "Pre-Dispatch BLOWBAGETS",
                style = Typography.headlineMedium
            )
            Spacer(modifier = Modifier.height(4.dp))
            Text(
                text = "Please verify all items. Mark any issues found.",
                style = Typography.bodyMedium,
                color = Color.Gray
            )
            
            Spacer(modifier = Modifier.height(24.dp))

            blowbagetsItems.forEachIndexed { index, name ->
                InspectionChecklistItem(
                    itemName = name,
                    status = itemStatuses[index],
                    onStatusChange = { itemStatuses[index] = it },
                    remarks = itemRemarks[index],
                    onRemarksChange = { itemRemarks[index] = it }
                )
                Spacer(modifier = Modifier.height(12.dp))
            }

            Spacer(modifier = Modifier.height(32.dp))

            if (isSubmitting) {
                CircularProgressIndicator(modifier = Modifier.align(Alignment.CenterHorizontally))
            } else {
                val allChecked = itemStatuses.none { it == InspectionStatus.UNCHECKED }
                RVMSPrimaryButton(
                    text = "Submit Inspection",
                    onClick = {
                        isSubmitting = true
                        coroutineScope.launch {
                            delay(1500) // Simulate network request
                            isSubmitting = false
                            onSubmitSuccess()
                        }
                    },
                    enabled = allChecked,
                    modifier = Modifier.fillMaxWidth()
                )
            }
            Spacer(modifier = Modifier.height(32.dp))
        }
    }
}
