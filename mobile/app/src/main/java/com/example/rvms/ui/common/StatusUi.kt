package com.example.rvms.ui.common

import androidx.compose.ui.graphics.Color
import com.example.rvms.data.NotificationType
import com.example.rvms.data.VehicleStatus
import com.example.rvms.theme.NavyBlue
import com.example.rvms.theme.StatusDispatched
import com.example.rvms.theme.StatusNotOperational
import com.example.rvms.theme.StatusOperational
import com.example.rvms.theme.StatusUnderPM

/** Maps a vehicle status to its dedicated status color (Plan §5). */
fun statusColor(status: VehicleStatus): Color = when (status) {
    VehicleStatus.OPERATIONAL -> StatusOperational
    VehicleStatus.DISPATCHED -> StatusDispatched
    VehicleStatus.UNDER_PM -> StatusUnderPM
    VehicleStatus.NOT_OPERATIONAL -> StatusNotOperational
}

/**
 * Dot color for a driver notification. Vehicle status updates use the relevant
 * status color; PM reminders use the orange "Under PM" tone.
 */
fun notificationColor(type: NotificationType, status: VehicleStatus?): Color = when (type) {
    NotificationType.VEHICLE_STATUS_UPDATE -> status?.let(::statusColor) ?: NavyBlue
    NotificationType.PM_REMINDER -> StatusUnderPM
}
