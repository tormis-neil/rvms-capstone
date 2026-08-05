package com.example.rvms

import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.safeDrawingPadding
import androidx.compose.runtime.Composable
import androidx.compose.runtime.rememberCoroutineScope
import androidx.compose.ui.Modifier
import androidx.compose.ui.unit.dp
import androidx.navigation3.runtime.entryProvider
import androidx.navigation3.runtime.rememberNavBackStack
import androidx.navigation3.ui.NavDisplay
import com.example.rvms.data.ServiceLocator
import com.example.rvms.ui.auth.SignInScreen
import com.example.rvms.ui.auth.SignUpScreen
import com.example.rvms.ui.damage.NewDamageReportScreen
import com.example.rvms.ui.inspection.InspectionDetailScreen
import com.example.rvms.ui.inspection.NewInspectionScreen
import com.example.rvms.ui.shell.DriverShellScreen
import com.example.rvms.ui.splash.SplashScreen
import com.example.rvms.ui.vehicle.VehicleInfoScreen
import kotlinx.coroutines.launch

@Composable
fun MainNavigation() {
    val backStack = rememberNavBackStack(Splash)
    val scope = rememberCoroutineScope()

    NavDisplay(
        backStack = backStack,
        onBack = { backStack.removeLastOrNull() },
        entryProvider =
            entryProvider {
                entry<Splash> {
                    SplashScreen(
                        // Verify a saved token against /me; route accordingly (FR-01).
                        checkSession = { ServiceLocator.sessionManager.bootstrap() },
                        onSplashFinished = { authenticated ->
                            backStack.clear()
                            backStack.add(if (authenticated) Home else SignIn)
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
                        onNavigateToSignIn = {
                            backStack.removeLastOrNull()
                        },
                        modifier = Modifier.safeDrawingPadding()
                    )
                }

                entry<Home> {
                    DriverShellScreen(
                        onNavigateToNewInspection = {
                            backStack.add(NewInspection)
                        },
                        onNavigateToNewDamageReport = {
                            backStack.add(NewDamageReport)
                        },
                        onNavigateToVehicleInfo = {
                            backStack.add(VehicleInfo)
                        },
                        onNavigateToInspectionDetail = { inspectionId ->
                            backStack.add(InspectionDetail(inspectionId))
                        },
                        onSignOut = {
                            // AuthRepository.logout() is the WHOLE sign-out: it releases
                            // this handset from push before revoking the session, and only
                            // it does. This called the session's own clear directly, which
                            // skipped that release — `users.fcm_token` survived sign-out,
                            // so the server kept pushing to a signed-out phone and a shared
                            // agency handset showed the previous driver's alerts (found in
                            // R10 manual testing).
                            scope.launch { ServiceLocator.authRepository.logout() }
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

                entry<NewDamageReport> {
                    NewDamageReportScreen(
                        onBack = { backStack.removeLastOrNull() },
                        onSubmitted = { backStack.removeLastOrNull() },
                        modifier = Modifier.safeDrawingPadding(),
                    )
                }

                entry<VehicleInfo> {
                    VehicleInfoScreen(
                        onBack = { backStack.removeLastOrNull() },
                        modifier = Modifier.safeDrawingPadding(),
                    )
                }

                entry<InspectionDetail> { key ->
                    InspectionDetailScreen(
                        inspectionId = key.inspectionId,
                        onBack = { backStack.removeLastOrNull() },
                        modifier = Modifier.safeDrawingPadding(),
                    )
                }
            },
    )
}
