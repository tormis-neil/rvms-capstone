package com.example.rvms

import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.safeDrawingPadding
import androidx.compose.runtime.Composable
import androidx.compose.ui.Modifier
import androidx.compose.ui.unit.dp
import androidx.navigation3.runtime.entryProvider
import androidx.navigation3.runtime.rememberNavBackStack
import androidx.navigation3.ui.NavDisplay
import com.example.rvms.ui.auth.SignInScreen
import com.example.rvms.ui.auth.SignUpScreen
import com.example.rvms.ui.damage.DamageReportListScreen
import com.example.rvms.ui.damage.NewDamageReportScreen
import com.example.rvms.ui.inspection.InspectionHistoryScreen
import com.example.rvms.ui.inspection.NewInspectionScreen
import com.example.rvms.ui.shell.DriverShellScreen
import com.example.rvms.ui.splash.SplashScreen
import com.example.rvms.ui.vehicle.VehicleInfoScreen

@Composable
fun MainNavigation() {
    val backStack = rememberNavBackStack(Splash)

    NavDisplay(
        backStack = backStack,
        onBack = { backStack.removeLastOrNull() },
        entryProvider =
            entryProvider {
                entry<Splash> {
                    SplashScreen(
                        onSplashFinished = {
                            backStack.clear()
                            backStack.add(SignIn)
                        },
                    )
                }

                entry<SignIn> {
                    SignInScreen(
                        onNavigateToHome = {
                            backStack.clear()
                            backStack.add(Home)
                        },
                        onNavigateToSignUp = {
                            backStack.add(SignUp)
                        },
                        modifier = Modifier.safeDrawingPadding()
                    )
                }

                entry<SignUp> {
                    SignUpScreen(
                        onNavigateToHome = {
                            backStack.clear()
                            backStack.add(Home)
                        },
                        onNavigateToSignIn = {
                            backStack.removeLastOrNull()
                        },
                        modifier = Modifier.safeDrawingPadding()
                    )
                }

                entry<Home> {
                    DriverShellScreen(
                        onNavigateToInspectionHistory = {
                            backStack.add(InspectionHistory)
                        },
                        onNavigateToNewInspection = {
                            backStack.add(NewInspection)
                        },
                        onNavigateToNewDamageReport = {
                            backStack.add(NewDamageReport)
                        },
                        onNavigateToDamageReportList = {
                            backStack.add(DamageReportList)
                        },
                        onNavigateToVehicleInfo = {
                            backStack.add(VehicleInfo)
                        },
                        onSignOut = {
                            backStack.clear()
                            backStack.add(SignIn)
                        },
                    )
                }

                entry<NewInspection> {
                    NewInspectionScreen(
                        onBack = { backStack.removeLastOrNull() },
                        onSubmitted = { backStack.removeLastOrNull() },
                        modifier = Modifier.safeDrawingPadding(),
                    )
                }

                entry<InspectionHistory> {
                    InspectionHistoryScreen(
                        onBack = { backStack.removeLastOrNull() },
                        modifier = Modifier.safeDrawingPadding(),
                    )
                }

                entry<NewDamageReport> {
                    NewDamageReportScreen(
                        onBack = { backStack.removeLastOrNull() },
                        onSubmitted = { backStack.removeLastOrNull() },
                        modifier = Modifier.safeDrawingPadding(),
                    )
                }

                entry<DamageReportList> {
                    DamageReportListScreen(
                        onBack = { backStack.removeLastOrNull() },
                        modifier = Modifier.safeDrawingPadding(),
                    )
                }

                entry<VehicleInfo> {
                    VehicleInfoScreen(
                        onBack = { backStack.removeLastOrNull() },
                        modifier = Modifier.safeDrawingPadding(),
                    )
                }
            },
    )
}
