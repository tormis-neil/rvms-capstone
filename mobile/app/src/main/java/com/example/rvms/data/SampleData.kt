package com.example.rvms.data

import com.example.rvms.R

/**
 * Static, in-memory sample data for the RVMS driver prototype.
 *
 * The prototype has no backend (rvms-prototype-plan §3). Every record here is
 * sample data that simulates real-world driver activity for the four
 * participating agencies. Each agency's [AgencyData] is self-contained and is
 * authored to exercise the full range of states the plan defines:
 *   - inspection history mixing "All OK" and "Has Issues" results,
 *   - damage reports in both Pending and Reviewed states, and
 *   - notifications / recent activity that walk the vehicle through all four
 *     vehicle statuses (Operational, Dispatched, Under PM, Not Operational)
 *     and both driver notification types (PM Reminder, Vehicle Status Update).
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

/** Status of a submitted damage report (Plan §6.5). */
enum class DamageStatus(val label: String) {
    PENDING("Pending"),
    REVIEWED("Reviewed"),
}

/** The two in-app notification types a driver receives (Plan §6.11). */
enum class NotificationType {
    PM_REMINDER,
    VEHICLE_STATUS_UPDATE,
}

/** Category of a Home "Recent Activity" entry, used to pick its dot color. */
enum class ActivityKind {
    INSPECTION_SUBMITTED,
    STATUS_UPDATE,
    PM_REMINDER,
    DAMAGE_SUBMITTED,
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
    val licenseExpiringSoon: Boolean = false,
)

data class InspectionRecord(
    val date: String,
    val time: String,
    val itemsChecked: Int,
    val issueCount: Int,
    val flaggedItems: List<String> = emptyList(),
) {
    val resultLabel: String
        get() = when (issueCount) {
            0 -> "All OK"
            1 -> "1 Issue"
            else -> "$issueCount Issues"
        }
}

data class DamageReport(
    val date: String,
    val nature: String,
    val suspectedParts: String,
    val status: DamageStatus,
)

data class DriverNotification(
    val type: NotificationType,
    val title: String,
    val body: String,
    val timeGroup: String,
    val time: String,
    val status: VehicleStatus? = null,
    val isRead: Boolean = false,
)

data class RecentActivity(
    val kind: ActivityKind,
    val title: String,
    val subtitle: String,
    val time: String,
    val status: VehicleStatus? = null,
)

/** Everything the driver app shows for one agency account. */
data class AgencyData(
    val driver: Driver,
    val vehicle: Vehicle,
    val inspectionHistory: List<InspectionRecord>,
    val damageReports: List<DamageReport>,
    val notifications: List<DriverNotification>,
    val recentActivity: List<RecentActivity>,
) {
    /** BFP vehicles add Hydraulic System + Fire Pump to the standard checklist. */
    val inspectionItems: List<String>
        get() = SampleData.inspectionItemsFor(driver.agency)
}

object SampleData {

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

    val agencyData: Map<Agency, AgencyData> = mapOf(
        Agency.BFP to bfp(),
        Agency.PNP to pnp(),
        Agency.CDRRMO to cdrrmo(),
        Agency.CHO to cho(),
    )

    // ---- Bureau of Fire Protection — current status: Operational ----
    private fun bfp() = AgencyData(
        driver = Driver(
            name = "Juan Dela Cruz",
            initials = "JD",
            email = "juan.delacruz@bfp.gov.ph",
            agency = Agency.BFP,
            licenseNo = "N01-12-345678",
            licenseExpiry = "December 15, 2027",
        ),
        vehicle = Vehicle(
            type = "Fire Truck",
            plateNo = "ABC-1234",
            make = "Isuzu",
            model = "FTR 850",
            engineNo = "4HK1-TC-587234",
            chassisNo = "JALC4W14697100345",
            mileage = "45,230 km",
            status = VehicleStatus.OPERATIONAL,
        ),
        inspectionHistory = listOf(
            InspectionRecord("June 8, 2026", "7:30 AM", 14, 0),
            InspectionRecord("June 7, 2026", "7:15 AM", 14, 1, listOf("Brakes")),
            InspectionRecord("June 6, 2026", "7:45 AM", 14, 0),
            InspectionRecord("June 4, 2026", "7:35 AM", 14, 2, listOf("Tires", "Fire Pump")),
        ),
        damageReports = listOf(
            DamageReport("June 8, 2026", "Cracked side mirror (driver side)", "Side mirror assembly", DamageStatus.PENDING),
            DamageReport("May 28, 2026", "Brake pad wear — unusual noise during braking", "Front brake pads", DamageStatus.REVIEWED),
            DamageReport("May 15, 2026", "Headlight bulb burned out (left)", "Headlight bulb", DamageStatus.REVIEWED),
        ),
        notifications = listOf(
            DriverNotification(NotificationType.VEHICLE_STATUS_UPDATE, "Vehicle Status Updated", "Your vehicle is now Operational.", "Today", "4:00 PM", VehicleStatus.OPERATIONAL, isRead = false),
            DriverNotification(NotificationType.PM_REMINDER, "PM Reminder", "Oil change is due soon at 46,000 km. Current mileage: 45,230 km.", "Today", "8:00 AM", isRead = false),
            DriverNotification(NotificationType.VEHICLE_STATUS_UPDATE, "Vehicle Status Updated", "Status changed to Under Preventive Maintenance.", "Yesterday", "2:30 PM", VehicleStatus.UNDER_PM, isRead = true),
            DriverNotification(NotificationType.VEHICLE_STATUS_UPDATE, "Vehicle Status Updated", "Status changed to Dispatched for a fire response.", "Earlier", "Jun 5", VehicleStatus.DISPATCHED, isRead = true),
            DriverNotification(NotificationType.VEHICLE_STATUS_UPDATE, "Vehicle Status Updated", "Status changed to Not Operational pending repair.", "Earlier", "Jun 2", VehicleStatus.NOT_OPERATIONAL, isRead = true),
        ),
        recentActivity = listOf(
            RecentActivity(ActivityKind.INSPECTION_SUBMITTED, "Daily Inspection Submitted", "All items OK", "Today, 7:30 AM"),
            RecentActivity(ActivityKind.DAMAGE_SUBMITTED, "Damage Report Submitted", "Cracked side mirror (driver side)", "Jun 8, 8:10 AM"),
        ),
    )

    // ---- Philippine National Police — current status: Dispatched ----
    private fun pnp() = AgencyData(
        driver = Driver(
            name = "Mark Santos",
            initials = "MS",
            email = "mark.santos@pnp.gov.ph",
            agency = Agency.PNP,
            licenseNo = "N01-15-123456",
            licenseExpiry = "June 30, 2026",
            licenseExpiringSoon = true,
        ),
        vehicle = Vehicle(
            type = "Patrol Vehicle",
            plateNo = "XYZ-9876",
            make = "Toyota",
            model = "Hilux",
            engineNo = "2GD-FTV-1123345",
            chassisNo = "MR0FB8CD9P0123456",
            mileage = "32,150 km",
            status = VehicleStatus.DISPATCHED,
        ),
        inspectionHistory = listOf(
            InspectionRecord("June 8, 2026", "6:50 AM", 12, 0),
            InspectionRecord("June 7, 2026", "6:40 AM", 12, 1, listOf("Lights")),
            InspectionRecord("June 5, 2026", "7:00 AM", 12, 0),
            InspectionRecord("June 3, 2026", "6:55 AM", 12, 2, listOf("Battery", "Brakes")),
        ),
        damageReports = listOf(
            DamageReport("June 7, 2026", "Dent on rear bumper after patrol", "Rear bumper", DamageStatus.PENDING),
            DamageReport("May 30, 2026", "Air-conditioning not cooling", "AC compressor", DamageStatus.REVIEWED),
            DamageReport("May 20, 2026", "Wiper blade torn", "Wiper blades", DamageStatus.REVIEWED),
        ),
        notifications = listOf(
            DriverNotification(NotificationType.VEHICLE_STATUS_UPDATE, "Vehicle Status Updated", "Your vehicle is now Dispatched on patrol.", "Today", "9:15 AM", VehicleStatus.DISPATCHED, isRead = false),
            DriverNotification(NotificationType.PM_REMINDER, "PM Reminder", "Tire rotation is due soon. Please coordinate with your agency admin.", "Today", "7:30 AM", isRead = false),
            DriverNotification(NotificationType.VEHICLE_STATUS_UPDATE, "Vehicle Status Updated", "Status changed to Operational after return.", "Yesterday", "5:00 PM", VehicleStatus.OPERATIONAL, isRead = true),
            DriverNotification(NotificationType.VEHICLE_STATUS_UPDATE, "Vehicle Status Updated", "Status changed to Under Preventive Maintenance.", "Earlier", "Jun 4", VehicleStatus.UNDER_PM, isRead = true),
            DriverNotification(NotificationType.VEHICLE_STATUS_UPDATE, "Vehicle Status Updated", "Status changed to Not Operational pending repair.", "Earlier", "Jun 1", VehicleStatus.NOT_OPERATIONAL, isRead = true),
        ),
        recentActivity = listOf(
            RecentActivity(ActivityKind.INSPECTION_SUBMITTED, "Daily Inspection Submitted", "All items OK", "Today, 6:50 AM"),
            RecentActivity(ActivityKind.DAMAGE_SUBMITTED, "Damage Report Submitted", "Dent on rear bumper after patrol", "Jun 7, 3:30 PM"),
        ),
    )

    // ---- City DRRM Office — current status: Under Preventive Maintenance ----
    private fun cdrrmo() = AgencyData(
        driver = Driver(
            name = "Pedro Penduko",
            initials = "PP",
            email = "pedro.penduko@cdrrmo.gov.ph",
            agency = Agency.CDRRMO,
            licenseNo = "N02-18-998877",
            licenseExpiry = "March 4, 2028",
        ),
        vehicle = Vehicle(
            type = "Rescue Truck",
            plateNo = "LMN-4567",
            make = "Isuzu",
            model = "ELF",
            engineNo = "4JJ1-TC-4455667",
            chassisNo = "JAANKR55LP7012345",
            mileage = "78,900 km",
            status = VehicleStatus.UNDER_PM,
        ),
        inspectionHistory = listOf(
            InspectionRecord("June 8, 2026", "7:10 AM", 12, 2, listOf("Oil", "Brakes")),
            InspectionRecord("June 6, 2026", "7:20 AM", 12, 0),
            InspectionRecord("June 4, 2026", "7:05 AM", 12, 1, listOf("Water")),
            InspectionRecord("June 2, 2026", "7:25 AM", 12, 0),
        ),
        damageReports = listOf(
            DamageReport("June 8, 2026", "Hydraulic leak on rescue lift", "Hydraulic hose", DamageStatus.PENDING),
            DamageReport("June 1, 2026", "Engine overheating during operation", "Radiator", DamageStatus.REVIEWED),
            DamageReport("May 18, 2026", "Worn tires (rear)", "Rear tires", DamageStatus.REVIEWED),
        ),
        notifications = listOf(
            DriverNotification(NotificationType.VEHICLE_STATUS_UPDATE, "Vehicle Status Updated", "Your vehicle is now Under Preventive Maintenance.", "Today", "10:00 AM", VehicleStatus.UNDER_PM, isRead = false),
            DriverNotification(NotificationType.PM_REMINDER, "PM Reminder", "Engine service is due now at 79,000 km. Current mileage: 78,900 km.", "Today", "8:00 AM", isRead = false),
            DriverNotification(NotificationType.VEHICLE_STATUS_UPDATE, "Vehicle Status Updated", "Status changed to Not Operational pending parts.", "Yesterday", "3:00 PM", VehicleStatus.NOT_OPERATIONAL, isRead = true),
            DriverNotification(NotificationType.VEHICLE_STATUS_UPDATE, "Vehicle Status Updated", "Status changed to Operational after service.", "Earlier", "Jun 3", VehicleStatus.OPERATIONAL, isRead = true),
            DriverNotification(NotificationType.VEHICLE_STATUS_UPDATE, "Vehicle Status Updated", "Status changed to Dispatched for a rescue operation.", "Earlier", "May 30", VehicleStatus.DISPATCHED, isRead = true),
        ),
        recentActivity = listOf(
            RecentActivity(ActivityKind.INSPECTION_SUBMITTED, "Daily Inspection Submitted", "2 issues found", "Today, 7:10 AM"),
            RecentActivity(ActivityKind.DAMAGE_SUBMITTED, "Damage Report Submitted", "Hydraulic leak on rescue lift", "Today, 9:30 AM"),
        ),
    )

    // ---- City Health Office — current status: Not Operational ----
    private fun cho() = AgencyData(
        driver = Driver(
            name = "Jose Rizal",
            initials = "JR",
            email = "jose.rizal@cho.gov.ph",
            agency = Agency.CHO,
            licenseNo = "N03-19-445566",
            licenseExpiry = "September 12, 2027",
        ),
        vehicle = Vehicle(
            type = "Ambulance",
            plateNo = "DEF-5678",
            make = "Toyota",
            model = "Hiace",
            engineNo = "1KD-FTV-7788990",
            chassisNo = "JTFRS22P3P0098765",
            mileage = "124,000 km",
            status = VehicleStatus.NOT_OPERATIONAL,
        ),
        inspectionHistory = listOf(
            InspectionRecord("June 8, 2026", "6:30 AM", 12, 3, listOf("Battery", "Brakes", "Lights")),
            InspectionRecord("June 7, 2026", "6:35 AM", 12, 1, listOf("Battery")),
            InspectionRecord("June 5, 2026", "6:40 AM", 12, 0),
            InspectionRecord("June 3, 2026", "6:45 AM", 12, 0),
        ),
        damageReports = listOf(
            DamageReport("June 8, 2026", "Transmission slipping — unsafe to deploy", "Transmission", DamageStatus.PENDING),
            DamageReport("June 5, 2026", "Siren not working", "Siren unit", DamageStatus.PENDING),
            DamageReport("May 25, 2026", "Stretcher latch broken", "Stretcher latch", DamageStatus.REVIEWED),
        ),
        notifications = listOf(
            DriverNotification(NotificationType.VEHICLE_STATUS_UPDATE, "Vehicle Status Updated", "Your vehicle is now Not Operational — unsafe for deployment.", "Today", "11:00 AM", VehicleStatus.NOT_OPERATIONAL, isRead = false),
            DriverNotification(NotificationType.PM_REMINDER, "PM Reminder", "Brake inspection is overdue. Please coordinate with your agency admin.", "Today", "7:00 AM", isRead = false),
            DriverNotification(NotificationType.VEHICLE_STATUS_UPDATE, "Vehicle Status Updated", "Status changed to Under Preventive Maintenance.", "Yesterday", "1:00 PM", VehicleStatus.UNDER_PM, isRead = true),
            DriverNotification(NotificationType.VEHICLE_STATUS_UPDATE, "Vehicle Status Updated", "Status changed to Operational after service.", "Earlier", "Jun 4", VehicleStatus.OPERATIONAL, isRead = true),
            DriverNotification(NotificationType.VEHICLE_STATUS_UPDATE, "Vehicle Status Updated", "Status changed to Dispatched for a medical response.", "Earlier", "Jun 1", VehicleStatus.DISPATCHED, isRead = true),
        ),
        recentActivity = listOf(
            RecentActivity(ActivityKind.INSPECTION_SUBMITTED, "Daily Inspection Submitted", "3 issues found", "Today, 6:30 AM"),
            RecentActivity(ActivityKind.DAMAGE_SUBMITTED, "Damage Report Submitted", "Transmission slipping", "Today, 8:15 AM"),
        ),
    )
}
