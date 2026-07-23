package com.example.rvms.data

import com.example.rvms.R

/**
 * Small domain enums shared by the UI. These are reference values (agency
 * identity + the four vehicle statuses), NOT sample account data — the mock
 * prototype data (SampleData/Session) was removed once every screen was wired
 * to the real API.
 */

/** The four participating agencies, each with its official logo asset. */
enum class Agency(
    val code: String,
    val fullName: String,
    val logo: Int,
) {
    BFP("BFP", "Bureau of Fire Protection", R.drawable.logo_bfp),
    PNP("PNP", "Philippine National Police", R.drawable.logo_pnp),
    CDRRMO("CDRRMO", "City Disaster Risk Reduction and Management Office", R.drawable.logo_cdrrmo),
    CHO("CHO", "City Health Office", R.drawable.logo_cho),
}

/** A vehicle operational status with its canonical label (FR-18). */
enum class VehicleStatus(val label: String) {
    OPERATIONAL("Operational"),
    DISPATCHED("Dispatched"),
    UNDER_PM("Under Preventive Maintenance"),
    NOT_OPERATIONAL("Not Operational"),
    ;

    companion object {
        /** Maps the API's exact status string (FR-18) to this enum. */
        fun fromApiLabel(label: String): VehicleStatus =
            entries.firstOrNull { it.label == label } ?: OPERATIONAL
    }
}
