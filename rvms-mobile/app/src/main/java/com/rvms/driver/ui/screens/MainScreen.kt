package com.rvms.driver.ui.screens

import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.padding
import androidx.compose.material3.Scaffold
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.getValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.navigation.compose.NavHost
import androidx.navigation.compose.composable
import androidx.navigation.compose.currentBackStackEntryAsState
import androidx.navigation.compose.rememberNavController
import com.rvms.driver.navigation.BottomNav
import com.rvms.driver.navigation.Screen

@Composable
fun MainScreen() {
    val navController = rememberNavController()
    val navBackStackEntry by navController.currentBackStackEntryAsState()
    val currentRoute = navBackStackEntry?.destination?.route

    // Check if current route is one of the bottom nav screens
    val isBottomNavScreen = Screen.entries.any { it.name == currentRoute }
    val currentBottomScreen = Screen.entries.find { it.name == currentRoute } ?: Screen.HOME

    Scaffold(
        bottomBar = {
            if (isBottomNavScreen) {
                BottomNav(
                    currentScreen = currentBottomScreen,
                    onScreenSelected = { screen ->
                        navController.navigate(screen.name) {
                            // Pop up to the start destination of the graph to
                            // avoid building up a large stack of destinations
                            popUpTo(Screen.HOME.name) {
                                saveState = true
                            }
                            // Avoid multiple copies of the same destination when
                            // reselecting the same item
                            launchSingleTop = true
                            // Restore state when reselecting a previously selected item
                            restoreState = true
                        }
                    }
                )
            }
        }
    ) { paddingValues ->
        Box(
            modifier = Modifier
                .fillMaxSize()
                .padding(paddingValues),
            contentAlignment = Alignment.TopStart
        ) {
            NavHost(
                navController = navController,
                startDestination = Screen.HOME.name
            ) {
                // Bottom Nav Tabs
                composable(Screen.HOME.name) {
                    HomeScreen(
                        onNavigateToMyVehicle = { navController.navigate("MY_VEHICLE") }
                    )
                }
                composable(Screen.INSPECTIONS.name) {
                    Box(modifier = Modifier.fillMaxSize(), contentAlignment = Alignment.Center) {
                        Text("Inspections Placeholder")
                    }
                }
                composable(Screen.DEFECTS.name) {
                    Box(modifier = Modifier.fillMaxSize(), contentAlignment = Alignment.Center) {
                        Text("Defects Placeholder")
                    }
                }
                composable(Screen.NOTIFICATIONS.name) {
                    Box(modifier = Modifier.fillMaxSize(), contentAlignment = Alignment.Center) {
                        Text("Notifications Placeholder")
                    }
                }

                // Deep Screens
                composable("MY_VEHICLE") {
                    MyVehicleScreen(
                        onBack = { navController.popBackStack() }
                    )
                }
            }
        }
    }
}
