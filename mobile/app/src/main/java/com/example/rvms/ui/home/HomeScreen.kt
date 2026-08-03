package com.example.rvms.ui.home

import androidx.compose.foundation.Image
import androidx.compose.foundation.background
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
import androidx.compose.foundation.layout.width
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.foundation.verticalScroll
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.Info
import androidx.compose.material.icons.filled.List
import androidx.compose.material.icons.filled.Warning
import androidx.compose.material3.Card
import androidx.compose.material3.CardDefaults
import androidx.compose.material3.ExperimentalMaterial3Api
import androidx.compose.material3.Icon
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Text
import androidx.compose.material3.pulltorefresh.PullToRefreshBox
import androidx.compose.ui.graphics.vector.ImageVector
import androidx.compose.runtime.Composable
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.rememberCoroutineScope
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.layout.ContentScale
import androidx.compose.ui.res.painterResource
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextAlign
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.compose.runtime.collectAsState
import com.example.rvms.data.ServiceLocator
import com.example.rvms.data.VehicleStatus
import com.example.rvms.data.remote.dto.InspectionDto
import com.example.rvms.data.remote.dto.VehicleDto
import com.example.rvms.ui.common.ConnectionErrorCard
import com.example.rvms.ui.common.LicenseState
import com.example.rvms.ui.common.RefreshOnResume
import com.example.rvms.ui.common.formatIsoDate
import com.example.rvms.ui.common.formatMileage
import com.example.rvms.ui.common.licenseBadge
import com.example.rvms.ui.common.licenseColor
import com.example.rvms.ui.common.licenseLabel
import com.example.rvms.ui.common.licenseMessage
import com.example.rvms.ui.common.licenseState
import com.example.rvms.ui.common.logoForAgencyCode
import com.example.rvms.ui.common.statusColor
import kotlinx.coroutines.launch
import com.example.rvms.theme.Background
import com.example.rvms.theme.Gold
import com.example.rvms.theme.NavyBlue
import com.example.rvms.theme.Surface
import com.example.rvms.theme.TextPrimary
import com.example.rvms.theme.TextSecondary
import com.example.rvms.theme.White

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun HomeScreen(
    onNavigateToVehicle: () -> Unit,
    onNavigateToInspection: () -> Unit,
    onNavigateToDamageReport: () -> Unit,
    modifier: Modifier = Modifier,
) {
    val scrollState = rememberScrollState()

    // Driver session (FR-01) + assigned vehicle(s) (FR-07), both from the API.
    val currentUser by ServiceLocator.sessionManager.currentUser.collectAsState()
    var vehicles by remember { mutableStateOf<List<VehicleDto>>(emptyList()) }
    var recentInspections by remember { mutableStateOf<List<InspectionDto>>(emptyList()) }
    var isRefreshing by remember { mutableStateOf(false) }
    var loadError by remember { mutableStateOf<String?>(null) }
    var loaded by remember { mutableStateOf(false) }
    val scope = rememberCoroutineScope()

    suspend fun refresh() {
        // /me too, not just the vehicle: the licence card below is drawn from
        // the session, so an admin editing an expiry date would otherwise stay
        // invisible until the next cold start.
        ServiceLocator.sessionManager.loadMe()

        val vehicleResult = ServiceLocator.vehicleRepository.myVehicles()
        val inspectionResult = ServiceLocator.inspectionRepository.history()

        // Records are replaced only on success, so a dropped connection keeps
        // the last known fleet on screen instead of blanking it — with the
        // banner below saying so (R10 sub-task 1).
        vehicleResult.dataOrNull?.let { vehicles = it }
        inspectionResult.dataOrNull?.let { recentInspections = it.take(3) }

        loadError = vehicleResult.errorOrNull ?: inspectionResult.errorOrNull
        loaded = true
    }

    // Loads on entry AND every time the app returns to the foreground, so a
    // status change announced by a push is already applied when the driver
    // taps the banner (FR-18, NFR-04).
    RefreshOnResume { refresh() }

    PullToRefreshBox(
        isRefreshing = isRefreshing,
        onRefresh = {
            isRefreshing = true
            scope.launch {
                refresh()
                isRefreshing = false
            }
        },
        modifier = modifier
            .fillMaxSize()
            .background(Background),
    ) {
    Column(
        modifier = Modifier
            .fillMaxSize()
            .verticalScroll(scrollState)
            .padding(16.dp),
    ) {
        // Greeting with agency logo
        val firstName = currentUser?.name?.substringBefore(' ') ?: "Driver"
        val agencyLogo = logoForAgencyCode(currentUser?.agency?.code)
        val agencyName = currentUser?.agency?.name.orEmpty()
        Row(verticalAlignment = Alignment.CenterVertically) {
            Image(
                painter = painterResource(id = agencyLogo),
                contentDescription = "${currentUser?.agency?.code} logo",
                contentScale = ContentScale.Fit,
                modifier = Modifier
                    .size(48.dp)
                    .clip(CircleShape)
                    .background(White),
            )
            Spacer(modifier = Modifier.width(12.dp))
            Column {
                Text(
                    text = "Good day, $firstName!",
                    style = MaterialTheme.typography.headlineLarge,
                    color = TextPrimary,
                )
                Text(
                    text = agencyName,
                    style = MaterialTheme.typography.bodyMedium,
                    color = TextSecondary,
                )
            }
        }

        Spacer(modifier = Modifier.height(20.dp))

        loadError?.let { message ->
            ConnectionErrorCard(message = message, staleDataShown = vehicles.isNotEmpty())
            Spacer(modifier = Modifier.height(12.dp))
        }

        // Assigned Vehicle Card — the driver's primary vehicle from GET
        // /my-vehicle; a driver with several vehicles sees all of them on the
        // Vehicle Info screen (FR-07).
        val vehicle = vehicles.firstOrNull()
        // Ordered vehicle-first on purpose. Testing the vehicle for null in the
        // FIRST branch is what lets the card below read `vehicle.status`
        // directly: with the null test buried in a compound condition the
        // compiler cannot narrow the type in the else branch, and an offline
        // start — no vehicle, no answer — would have walked straight into the
        // card and dereferenced null.
        if (vehicle != null) {
            val vehicleStatus = VehicleStatus.fromApiLabel(vehicle.status)
            Card(
                modifier = Modifier
                    .fillMaxWidth()
                    .clickable { onNavigateToVehicle() },
                shape = RoundedCornerShape(12.dp),
                colors = CardDefaults.cardColors(containerColor = NavyBlue),
            ) {
                Column(modifier = Modifier.padding(20.dp)) {
                    Row(
                        modifier = Modifier.fillMaxWidth(),
                        verticalAlignment = Alignment.CenterVertically,
                    ) {
                        Text(
                            text = "Assigned Vehicle",
                            color = White.copy(alpha = 0.8f),
                            style = MaterialTheme.typography.bodySmall,
                            modifier = Modifier.weight(1f),
                        )
                        Spacer(modifier = Modifier.width(8.dp))
                        // Status Badge — long labels (e.g. "Under Preventive
                        // Maintenance") wrap centered inside the pill
                        val vehicleStatusColor = statusColor(vehicleStatus)
                        Box(
                            modifier = Modifier
                                .clip(RoundedCornerShape(12.dp))
                                .background(vehicleStatusColor.copy(alpha = 0.2f))
                                .padding(horizontal = 12.dp, vertical = 6.dp),
                        ) {
                            Text(
                                text = vehicleStatus.label,
                                color = vehicleStatusColor,
                                fontSize = 12.sp,
                                fontWeight = FontWeight.SemiBold,
                                textAlign = TextAlign.Center,
                                lineHeight = 16.sp,
                            )
                        }
                    }

                    Spacer(modifier = Modifier.height(8.dp))

                    Text(
                        text = vehicle.type,
                        color = White,
                        style = MaterialTheme.typography.titleLarge,
                        fontWeight = FontWeight.Bold,
                    )
                    Text(
                        text = vehicle.plateNumber,
                        color = Gold,
                        style = MaterialTheme.typography.titleMedium,
                        fontWeight = FontWeight.SemiBold,
                    )

                    Spacer(modifier = Modifier.height(12.dp))

                    Row(modifier = Modifier.fillMaxWidth()) {
                        InfoChip(vehicle.make, Modifier.weight(1f))
                        Spacer(modifier = Modifier.width(8.dp))
                        InfoChip(vehicle.model, Modifier.weight(1f))
                        Spacer(modifier = Modifier.width(8.dp))
                        InfoChip(formatMileage(vehicle.currentMileage), Modifier.weight(1f))
                    }
                }
            }
        } else if (loadError == null && loaded) {
            // "No Vehicle Assigned" is only ever said when the server actually
            // answered and said so — never because the request failed and never
            // while it is still in flight (R10 sub-task 1). Offline, the
            // connection banner above stands in its place.
            Card(
                modifier = Modifier.fillMaxWidth(),
                shape = RoundedCornerShape(12.dp),
                colors = CardDefaults.cardColors(containerColor = Surface),
            ) {
                Column(modifier = Modifier.padding(20.dp)) {
                    Text(
                        text = "No Vehicle Assigned",
                        style = MaterialTheme.typography.titleMedium,
                        color = TextPrimary,
                        fontWeight = FontWeight.Bold,
                    )
                    Spacer(modifier = Modifier.height(4.dp))
                    Text(
                        text = "Your agency administrator has not assigned a vehicle to your account yet.",
                        style = MaterialTheme.typography.bodySmall,
                        color = TextSecondary,
                    )
                }
            }
        }

        Spacer(modifier = Modifier.height(12.dp))

        // License Status (moved from Profile — driver readiness shown alongside
        // vehicle readiness; license details remain on the Profile screen).
        // Computed from the real /me payload — no license on file hides the card.
        val license = licenseState(currentUser?.licenseExpiryDate, currentUser?.agency?.licenseExpiryWarningDays)
        if (license != LicenseState.NONE) {
            // Three states, three tones — matching the admin dashboard, where
            // Expired has always been red and Expiring Soon amber (see LicenseUi).
            val licenseColor = licenseColor(license)
            val licenseLabel = licenseLabel(license)
            val licenseBadge = licenseBadge(license)
            val licenseMessage = licenseMessage(license, currentUser?.licenseExpiryDate)
            Card(
                modifier = Modifier.fillMaxWidth(),
                shape = RoundedCornerShape(12.dp),
                colors = CardDefaults.cardColors(containerColor = Surface),
            ) {
                Column(modifier = Modifier.padding(16.dp)) {
                    Row(
                        modifier = Modifier.fillMaxWidth(),
                        horizontalArrangement = Arrangement.SpaceBetween,
                        verticalAlignment = Alignment.CenterVertically,
                    ) {
                        Column {
                            Text(
                                text = "License Status",
                                style = MaterialTheme.typography.bodySmall,
                                color = TextSecondary,
                            )
                            Text(
                                text = licenseLabel,
                                style = MaterialTheme.typography.bodyLarge,
                                color = licenseColor,
                                fontWeight = FontWeight.Bold,
                            )
                        }
                        Box(
                            modifier = Modifier
                                .clip(RoundedCornerShape(12.dp))
                                .background(licenseColor.copy(alpha = 0.1f))
                                .padding(horizontal = 16.dp, vertical = 8.dp),
                        ) {
                            Text(
                                text = licenseBadge,
                                color = licenseColor,
                                fontSize = 13.sp,
                                fontWeight = FontWeight.SemiBold,
                            )
                        }
                    }
                    if (licenseMessage != null) {
                        Spacer(modifier = Modifier.height(8.dp))
                        Text(
                            text = licenseMessage,
                            style = MaterialTheme.typography.bodySmall,
                            color = TextSecondary,
                        )
                    }
                }
            }
        }

        Spacer(modifier = Modifier.height(24.dp))

        // Quick Actions
        Text(
            text = "Quick Actions",
            style = MaterialTheme.typography.titleMedium,
            color = TextPrimary,
            fontWeight = FontWeight.Bold,
        )

        Spacer(modifier = Modifier.height(12.dp))

        Row(
            modifier = Modifier.fillMaxWidth(),
            horizontalArrangement = Arrangement.spacedBy(12.dp),
        ) {
            QuickActionCard(
                title = "Daily\nInspection",
                icon = Icons.Default.List,
                onClick = onNavigateToInspection,
                modifier = Modifier.weight(1f),
            )
            QuickActionCard(
                title = "Report\nDamage",
                icon = Icons.Default.Warning,
                onClick = onNavigateToDamageReport,
                modifier = Modifier.weight(1f),
            )
            QuickActionCard(
                title = "Vehicle\nInfo",
                icon = Icons.Default.Info,
                onClick = onNavigateToVehicle,
                modifier = Modifier.weight(1f),
            )
        }

        Spacer(modifier = Modifier.height(24.dp))

        // Recent Activity — the driver's most recent inspection submissions
        // (real data from GET /inspections). Empty for a fresh account.
        Text(
            text = "Recent Activity",
            style = MaterialTheme.typography.titleMedium,
            color = TextPrimary,
            fontWeight = FontWeight.Bold,
        )

        Spacer(modifier = Modifier.height(12.dp))

        if (recentInspections.isEmpty()) {
            Text(
                text = "No recent activity yet.",
                style = MaterialTheme.typography.bodyMedium,
                color = TextSecondary,
            )
        } else {
            recentInspections.forEach { inspection ->
                val issues = inspection.items.count { it.status == "Has Issue" }
                ActivityItem(
                    title = "Daily Inspection Submitted",
                    subtitle = if (issues == 0) "All items OK" else "$issues issue(s) found",
                    time = formatIsoDate(inspection.inspectionDate),
                    icon = Icons.Default.List,
                    iconTint = NavyBlue,
                )
            }
        }
    }
    }
}

@Composable
private fun InfoChip(label: String, modifier: Modifier = Modifier) {
    Box(
        modifier = modifier
            .clip(RoundedCornerShape(8.dp))
            .background(White.copy(alpha = 0.15f))
            .padding(vertical = 6.dp),
        contentAlignment = Alignment.Center,
    ) {
        Text(
            text = label,
            color = White.copy(alpha = 0.9f),
            fontSize = 12.sp,
            fontWeight = FontWeight.Medium,
        )
    }
}

@Composable
private fun QuickActionCard(
    title: String,
    icon: ImageVector,
    onClick: () -> Unit,
    modifier: Modifier = Modifier,
) {
    Card(
        modifier = modifier
            .height(100.dp)
            .clickable { onClick() },
        shape = RoundedCornerShape(12.dp),
        colors = CardDefaults.cardColors(containerColor = Surface),
    ) {
        Column(
            modifier = Modifier
                .fillMaxSize()
                .padding(12.dp),
            verticalArrangement = Arrangement.SpaceBetween,
        ) {
            Box(
                modifier = Modifier
                    .size(32.dp)
                    .clip(RoundedCornerShape(8.dp))
                    .background(NavyBlue.copy(alpha = 0.1f)),
                contentAlignment = Alignment.Center,
            ) {
                Icon(
                    imageVector = icon,
                    contentDescription = null,
                    tint = NavyBlue,
                    modifier = Modifier.size(18.dp),
                )
            }
            Text(
                text = title,
                style = MaterialTheme.typography.bodySmall,
                color = TextPrimary,
                fontWeight = FontWeight.SemiBold,
                lineHeight = 16.sp,
            )
        }
    }
}

@Composable
private fun ActivityItem(
    title: String,
    subtitle: String,
    time: String,
    icon: androidx.compose.ui.graphics.vector.ImageVector,
    iconTint: Color,
) {
    Card(
        modifier = Modifier
            .fillMaxWidth()
            .padding(bottom = 8.dp),
        shape = RoundedCornerShape(10.dp),
        colors = CardDefaults.cardColors(containerColor = Surface),
    ) {
        // Single left-aligned text column (title, subtitle, then time) so
        // every card lays out identically regardless of text length
        Row(
            modifier = Modifier.padding(16.dp),
            verticalAlignment = Alignment.Top,
        ) {
            Box(
                modifier = Modifier
                    .size(36.dp)
                    .clip(CircleShape)
                    .background(iconTint.copy(alpha = 0.12f)),
                contentAlignment = Alignment.Center,
            ) {
                Icon(
                    imageVector = icon,
                    contentDescription = null,
                    tint = iconTint,
                    modifier = Modifier.size(18.dp),
                )
            }
            Spacer(modifier = Modifier.width(12.dp))
            Column(modifier = Modifier.weight(1f)) {
                Text(
                    text = title,
                    style = MaterialTheme.typography.bodyMedium,
                    color = TextPrimary,
                    fontWeight = FontWeight.SemiBold,
                )
                Text(
                    text = subtitle,
                    style = MaterialTheme.typography.bodySmall,
                    color = TextSecondary,
                )
                Spacer(modifier = Modifier.height(4.dp))
                Text(
                    text = time,
                    style = MaterialTheme.typography.labelSmall,
                    color = TextSecondary,
                )
            }
        }
    }
}
