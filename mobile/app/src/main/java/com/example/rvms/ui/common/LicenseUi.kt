package com.example.rvms.ui.common

import androidx.compose.ui.graphics.Color
import com.example.rvms.theme.StatusNotOperational
import com.example.rvms.theme.StatusOperational
import com.example.rvms.theme.StatusUnderPM

/**
 * How a licence state is presented on the driver's Home screen (FR-08).
 *
 * The admin dashboard has shown three distinct tones since R2 — Valid green,
 * Expiring Soon amber, Expired red — but the phone collapsed the last two into
 * a single "expiring soon" boolean, so a licence that had already lapsed
 * looked exactly like one with weeks left to run. Those are different facts:
 * one is a reminder, the other means the driver should not be taking a vehicle
 * out. The three states are kept apart here, in one place, so a future edit
 * cannot quietly merge them again.
 *
 * The hex values are the same ones the dashboard's status badges use
 * (#16A34A / #D97706 / #DC2626), so a colour means the same thing on both
 * platforms.
 */
fun licenseColor(state: LicenseState): Color = when (state) {
    LicenseState.EXPIRED -> StatusNotOperational
    LicenseState.EXPIRING_SOON -> StatusUnderPM
    else -> StatusOperational
}

/** The dashboard's own wording for each state (FR-08). */
fun licenseLabel(state: LicenseState): String = when (state) {
    LicenseState.EXPIRED -> "Expired"
    LicenseState.EXPIRING_SOON -> "Expiring Soon"
    else -> "Valid"
}

/** The short chip beside the label — mobile-only, the dashboard has no chip. */
fun licenseBadge(state: LicenseState): String = when (state) {
    LicenseState.EXPIRED -> "Renewal Required"
    LicenseState.EXPIRING_SOON -> "Action Needed"
    else -> "Active"
}

/**
 * The advisory line under the card, or null when the licence is fine.
 *
 * Worded per state for the same reason the colours are: telling a driver whose
 * licence lapsed last month that it "expires" on a past date reads as though
 * there were still time.
 */
fun licenseMessage(state: LicenseState, expiryIso: String?): String? = when (state) {
    LicenseState.EXPIRED ->
        "Expired ${formatIsoDate(expiryIso)}. Coordinate with your agency administrator " +
            "before operating a vehicle."
    LicenseState.EXPIRING_SOON ->
        "Expires ${formatIsoDate(expiryIso)}. Please coordinate with your agency " +
            "administrator for renewal."
    else -> null
}
