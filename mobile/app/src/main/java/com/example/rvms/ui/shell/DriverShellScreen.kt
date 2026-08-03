package com.example.rvms.ui.shell

import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.safeDrawingPadding
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.Home
import androidx.compose.material.icons.filled.List
import androidx.compose.material.icons.filled.Notifications
import androidx.compose.material.icons.filled.Person
import androidx.compose.material.icons.filled.Warning
import androidx.compose.material3.Badge
import androidx.compose.material3.BadgedBox
import androidx.compose.material3.Icon
import androidx.compose.material3.NavigationBar
import androidx.compose.material3.NavigationBarItem
import androidx.compose.material3.NavigationBarItemDefaults
import androidx.compose.material3.Scaffold
import androidx.compose.material3.Text
import androidx.compose.material3.MaterialTheme
import androidx.compose.runtime.Composable
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableIntStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.saveable.rememberSaveable
import androidx.compose.runtime.setValue
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.vector.ImageVector
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import com.example.rvms.data.ServiceLocator
import com.example.rvms.ui.common.RefreshOnResume
import com.example.rvms.theme.Gold
import com.example.rvms.theme.NavyBlue
import com.example.rvms.theme.StatusNotOperational
import com.example.rvms.theme.TextSecondary
import com.example.rvms.theme.White
import com.example.rvms.ui.damage.DamageScreen
import com.example.rvms.ui.home.HomeScreen
import com.example.rvms.ui.inspection.InspectionScreen
import com.example.rvms.ui.notification.NotificationScreen
import com.example.rvms.ui.profile.ProfileScreen
import kotlinx.coroutines.delay

/** How often the shell re-checks the unread count while the app is open. */
private const val UNREAD_POLL_MILLIS = 30_000L

data class BottomNavItem(
    val label: String,
    val icon: ImageVector,
)

@Composable
fun DriverShellScreen(
    onNavigateToNewInspection: () -> Unit,
    onNavigateToNewDamageReport: () -> Unit,
    onNavigateToVehicleInfo: () -> Unit,
    onNavigateToInspectionDetail: (Long) -> Unit,
    onSignOut: () -> Unit,
    modifier: Modifier = Modifier,
) {
    var selectedTab by rememberSaveable { mutableIntStateOf(0) }

    /*
     * Unread count on the Alerts tab (FR-21).
     *
     * Refreshed on entry, whenever the driver switches tabs, and on a slow poll
     * while the app is open. The poll matters: a push banner only appears if
     * Firebase is configured AND the driver granted notification permission, so
     * without it a driver would have no way to learn a notification had arrived
     * short of opening the Alerts tab and guessing.
     */
    var unreadAlerts by remember { mutableIntStateOf(0) }

    suspend fun refreshUnread() {
        // On failure the badge keeps its last value rather than dropping to
        // zero — a lost connection must not read as "no unread alerts"
        // (R10 sub-task 1).
        ServiceLocator.notificationRepository.inbox().dataOrNull?.let {
            unreadAlerts = it.unreadCount
        }
    }

    LaunchedEffect(selectedTab) { refreshUnread() }

    // Returning to the app is the moment a driver is most likely to have
    // missed a push, so the badge is brought up to date before the poll's next
    // tick rather than after it.
    RefreshOnResume { refreshUnread() }

    LaunchedEffect(Unit) {
        while (true) {
            delay(UNREAD_POLL_MILLIS)
            refreshUnread()
        }
    }

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
                            // Unread count badge on the Alerts tab
                            if (item.label == "Alerts" && unreadAlerts > 0) {
                                BadgedBox(
                                    badge = {
                                        Badge(
                                            containerColor = StatusNotOperational,
                                            contentColor = White,
                                        ) { Text("$unreadAlerts") }
                                    },
                                ) {
                                    Icon(
                                        imageVector = item.icon,
                                        contentDescription = item.label,
                                    )
                                }
                            } else {
                                Icon(
                                    imageVector = item.icon,
                                    contentDescription = item.label,
                                )
                            }
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
                onNavigateToVehicle = onNavigateToVehicleInfo,
                onNavigateToInspection = { selectedTab = 1 },
                onNavigateToDamageReport = { selectedTab = 2 },
                modifier = contentModifier,
            )
            1 -> InspectionScreen(
                onStartInspection = onNavigateToNewInspection,
                onOpenDetail = onNavigateToInspectionDetail,
                modifier = contentModifier,
            )
            2 -> DamageScreen(
                onSubmitNew = onNavigateToNewDamageReport,
                modifier = contentModifier,
            )
            3 -> NotificationScreen(
                // Marking read inside the screen must clear the badge immediately,
                // rather than waiting for the next poll.
                onUnreadCountChanged = { unreadAlerts = it },
                modifier = contentModifier,
            )
            4 -> ProfileScreen(
                onSignOut = onSignOut,
                onNavigateToVehicle = onNavigateToVehicleInfo,
                modifier = contentModifier,
            )
        }
    }
}
