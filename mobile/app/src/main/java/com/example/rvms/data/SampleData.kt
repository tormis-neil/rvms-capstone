package com.example.rvms.data

import com.example.rvms.R

/**
 * Static, in-memory sample data for the RVMS prototype.
 *
 * The prototype has no backend (see rvms-prototype-plan §3). All data here is
 * sample data that simulates real-world records for the four participating
 * agencies. This is the single source of truth for the driver app so that the
 * Home, Vehicle, Profile, Damage and Inspection screens stay consistent.
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

/** A vehicle operational status with its canonical label. */
enum class VehicleStatus(val label: String) {
    OPERATIONAL("Operational"),
    DISPATCHED("Dispatched"),
    UNDER_PM("Under PM"),
    NOT_OPERATIONAL("Not Operational"),
}

data class Vehicle(
    val type: String,
    val plateNo: String,
    val make: String,
    val model: String,
    val engineNo: String,
    val chassisNo: String,
    val mileage: String,
    val status: VehicleStatus,
)

data class Driver(
    val name: String,
    val initials: String,
    val email: String,
    val agency: Agency,
    val licenseNo: String,
    val licenseExpiry: String,
)

/**
 * The currently signed-in driver for this prototype session.
 *
 * Defaults to a Bureau of Fire Protection driver so the BFP-specific inspection
 * items (Hydraulic System, Fire Pump) are exercised. Other agency records live
 * in [SampleData.allDrivers] / [SampleData.allVehicles] for future demos.
 */
object SampleData {

    val currentDriver = Driver(
        name = "Juan Dela Cruz",
        initials = "JD",
        email = "juan.delacruz@bfp.gov.ph",
        agency = Agency.BFP,
        licenseNo = "N01-12-345678",
        licenseExpiry = "December 15, 2027",
    )

    val currentVehicle = Vehicle(
        type = "Fire Truck",
        plateNo = "ABC-1234",
        make = "Isuzu",
        model = "FTR 850",
        engineNo = "4HK1-TC-587234",
        chassisNo = "JALC4W14697100345",
        mileage = "45,230 km",
        status = VehicleStatus.OPERATIONAL,
    )

    /** Standard BLOWBAGETS checklist — applies to all agencies (12 items). */
    val standardInspectionItems = listOf(
        "Battery", "Lights", "Oil", "Water", "Brakes", "Air",
        "Gas", "Engine", "Tires", "Power Steering",
        "Horn/Siren", "Directional Signals",
    )

    /** Additional items required only for BFP vehicles (2 items). */
    val bfpAdditionalItems = listOf("Hydraulic System", "Fire Pump")

    /** Checklist for a given agency — BFP gets the two extra items. */
    fun inspectionItemsFor(agency: Agency): List<String> =
        if (agency == Agency.BFP) standardInspectionItems + bfpAdditionalItems
        else standardInspectionItems

    /** Representative records across all four agencies (for future multi-agency demos). */
    val allDrivers = listOf(
        currentDriver,
        Driver("Mark Santos", "MS", "mark.santos@pnp.gov.ph", Agency.PNP, "N01-15-123456", "June 30, 2026"),
        Driver("Pedro Penduko", "PP", "pedro.penduko@cdrrmo.gov.ph", Agency.CDRRMO, "N02-18-998877", "March 4, 2028"),
        Driver("Jose Rizal", "JR", "jose.rizal@cho.gov.ph", Agency.CHO, "N03-19-445566", "September 12, 2027"),
    )
}
