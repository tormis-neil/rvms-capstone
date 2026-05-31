package com.rvms.driver.ui.screens

import androidx.compose.foundation.background
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.verticalScroll
import androidx.compose.runtime.Composable
import androidx.compose.ui.Modifier
import androidx.compose.ui.unit.dp
import com.rvms.driver.model.DummyAssignedVehicle
import com.rvms.driver.model.DummyDriver
import com.rvms.driver.ui.components.AssignedVehicleCard
import com.rvms.driver.ui.components.HomeHeader
import com.rvms.driver.ui.components.QuickActionsGrid
import com.rvms.driver.ui.theme.BackgroundLight

/**
 * HomeScreen
 *
 * The main dashboard for the driver. Displays the welcome header,
 * the assigned vehicle's current status, and quick action buttons.
 */
@Composable
fun HomeScreen(
    onNavigateToMyVehicle: () -> Unit,
    onNavigateToNewInspection: () -> Unit,
    onNavigateToReportDefect: () -> Unit,
    modifier: Modifier = Modifier
) {
    Column(
        modifier = modifier
            .fillMaxSize()
            .background(BackgroundLight)
            .padding(horizontal = 16.dp)
            .verticalScroll(rememberScrollState())
    ) {
        HomeHeader(driver = DummyDriver)
        
        Spacer(modifier = Modifier.height(8.dp))
        
        AssignedVehicleCard(vehicle = DummyAssignedVehicle)
        
        Spacer(modifier = Modifier.height(24.dp))
        
        QuickActionsGrid(
            onNewInspectionClick = onNavigateToNewInspection,
            onReportDefectClick = onNavigateToReportDefect,
            onViewHistoryClick = { /* TODO Phase 4 */ },
            onMyVehicleClick = onNavigateToMyVehicle
        )
        
        Spacer(modifier = Modifier.height(24.dp))
    }
}
