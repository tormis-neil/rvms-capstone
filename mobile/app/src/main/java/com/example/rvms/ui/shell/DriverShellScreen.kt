package com.example.rvms.ui.shell

import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.safeDrawingPadding
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.Home
import androidx.compose.material.icons.filled.List
import androidx.compose.material.icons.filled.Notifications
import androidx.compose.material.icons.filled.Person
import androidx.compose.material.icons.filled.Warning
import androidx.compose.material3.Icon
import androidx.compose.material3.NavigationBar
import androidx.compose.material3.NavigationBarItem
import androidx.compose.material3.NavigationBarItemDefaults
import androidx.compose.material3.Scaffold
import androidx.compose.material3.Text
import androidx.compose.material3.MaterialTheme
import androidx.compose.runtime.Composable
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableIntStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.setValue
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.graphics.vector.ImageVector
import androidx.compose.ui.res.painterResource
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import com.example.rvms.theme.Gold
import com.example.rvms.theme.NavyBlue
import com.example.rvms.theme.TextSecondary
import com.example.rvms.theme.White
import com.example.rvms.ui.damage.DamageReportScreen
import com.example.rvms.ui.home.HomeScreen
import com.example.rvms.ui.inspection.InspectionScreen
import com.example.rvms.ui.notification.NotificationScreen
import com.example.rvms.ui.profile.ProfileScreen
import com.example.rvms.ui.vehicle.VehicleInfoScreen

data class BottomNavItem(
    val label: String,
    val icon: ImageVector,
)

@Composable
fun DriverShellScreen(
    onNavigateToInspectionHistory: () -> Unit,
    onNavigateToNewInspection: () -> Unit,
    onNavigateToDamageReportList: () -> Unit,
    onNavigateToVehicleInfo: () -> Unit,
    onSignOut: () -> Unit,
    modifier: Modifier = Modifier,
) {
    var selectedTab by remember { mutableIntStateOf(0) }

    val navItems = listOf(
        BottomNavItem("Home", Icons.Default.Home),
        BottomNavItem("Inspect", Icons.Default.List),
        BottomNavItem("Damage", Icons.Default.Warning),
        BottomNavItem("Alerts", Icons.Default.Notifications),
        BottomNavItem("Profile", Icons.Default.Person),
    )

    Scaffold(
        modifier = modifier.safeDrawingPadding(),
        bottomBar = {
            NavigationBar(
                containerColor = White,
                tonalElevation = 8.dp,
            ) {
                navItems.forEachIndexed { index, item ->
                    NavigationBarItem(
                        selected = selectedTab == index,
                        onClick = { selectedTab = index },
                        icon = {
                            Icon(
                                imageVector = item.icon,
                                contentDescription = item.label,
                            )
                        },
                        label = {
                            Text(
                                text = item.label,
                                fontSize = 10.sp,
                                fontWeight = if (selectedTab == index)
                                    FontWeight.Bold else FontWeight.Normal,
                            )
                        },
                        colors = NavigationBarItemDefaults.colors(
                            selectedIconColor = NavyBlue,
                            selectedTextColor = NavyBlue,
                            indicatorColor = Gold.copy(alpha = 0.15f),
                            unselectedIconColor = TextSecondary,
                            unselectedTextColor = TextSecondary,
                        ),
                    )
                }
            }
        },
    ) { innerPadding ->
        val contentModifier = Modifier.padding(innerPadding)

        when (selectedTab) {
            0 -> HomeScreen(
                onNavigateToVehicle = { selectedTab = 4 }, // Vehicle info now in profile
                onNavigateToInspection = { selectedTab = 1 },
                onNavigateToDamageReport = { selectedTab = 2 },
                modifier = contentModifier,
            )
            1 -> InspectionScreen(
                onStartInspection = onNavigateToNewInspection,
                onViewHistory = onNavigateToInspectionHistory,
                modifier = contentModifier,
            )
            2 -> DamageReportScreen(
                onViewReports = onNavigateToDamageReportList,
                modifier = contentModifier,
            )
            3 -> NotificationScreen(modifier = contentModifier)
            4 -> ProfileScreen(
                onSignOut = onSignOut,
                onNavigateToVehicle = onNavigateToVehicleInfo,
                modifier = contentModifier,
            )
        }
    }
}
