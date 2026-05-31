package com.rvms.driver.navigation

import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.Home
import androidx.compose.material.icons.filled.Notifications
import androidx.compose.material.icons.outlined.Checklist
import androidx.compose.material.icons.outlined.ReportProblem
import androidx.compose.material3.Icon
import androidx.compose.material3.NavigationBar
import androidx.compose.material3.NavigationBarItem
import androidx.compose.material3.NavigationBarItemDefaults
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.graphics.vector.ImageVector
import com.rvms.driver.ui.theme.PrimaryBlue

// These are the 4 tabs visible at the bottom of the driver's app.
// Each tab maps to one main screen that the driver can navigate to.
enum class Screen(val label: String, val icon: ImageVector) {
    HOME("Home", Icons.Filled.Home),
    INSPECTIONS("Inspections", Icons.Outlined.Checklist),
    DEFECTS("Defects", Icons.Outlined.ReportProblem),
    NOTIFICATIONS("Alerts", Icons.Filled.Notifications)
}

// The Bottom Navigation Bar component.
// It highlights the currently active tab with the RVMS Primary Blue color,
// and shows inactive tabs in a muted gray color.
@Composable
fun BottomNav(
    currentScreen: Screen,
    onScreenSelected: (Screen) -> Unit
) {
    NavigationBar(
        containerColor = Color.White,
        contentColor = PrimaryBlue
    ) {
        Screen.entries.forEach { screen ->
            NavigationBarItem(
                icon = {
                    if (screen == Screen.NOTIFICATIONS) {
                        androidx.compose.material3.BadgedBox(
                            badge = {
                                androidx.compose.material3.Badge(
                                    containerColor = Color(0xFFEF4444) // Red for unread
                                )
                            }
                        ) {
                            Icon(screen.icon, contentDescription = screen.label)
                        }
                    } else {
                        Icon(screen.icon, contentDescription = screen.label)
                    }
                },
                label = { Text(screen.label) },
                selected = currentScreen == screen,
                onClick = { onScreenSelected(screen) },
                colors = NavigationBarItemDefaults.colors(
                    selectedIconColor = PrimaryBlue,
                    selectedTextColor = PrimaryBlue,
                    indicatorColor = PrimaryBlue.copy(alpha = 0.1f),
                    unselectedIconColor = Color(0xFF94A3B8),
                    unselectedTextColor = Color(0xFF94A3B8)
                )
            )
        }
    }
}
