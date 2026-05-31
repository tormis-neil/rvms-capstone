package com.rvms.driver.model

data class InspectionHistoryItem(
    val id: String,
    val date: String,
    val status: String,
    val details: String,
    val isPerfect: Boolean
)

data class DefectHistoryItem(
    val id: String,
    val date: String,
    val category: String,
    val description: String,
    val status: String // e.g. "Pending Repair", "Resolved"
)

val MockInspectionsHistory = listOf(
    InspectionHistoryItem(
        id = "1",
        date = "May 30, 2026",
        status = "PASSED",
        details = "All 14 BLOWBAGETS items checked OK.",
        isPerfect = true
    ),
    InspectionHistoryItem(
        id = "2",
        date = "May 28, 2026",
        status = "PASSED (WITH MINOR ISSUES)",
        details = "Low Tire Pressure on Rear Left.",
        isPerfect = false
    ),
    InspectionHistoryItem(
        id = "3",
        date = "May 26, 2026",
        status = "PASSED",
        details = "All 14 BLOWBAGETS items checked OK.",
        isPerfect = true
    )
)

val MockDefectsHistory = listOf(
    DefectHistoryItem(
        id = "1",
        date = "May 25, 2026",
        category = "Electrical",
        description = "Broken right taillight assembly.",
        status = "Pending Repair"
    ),
    DefectHistoryItem(
        id = "2",
        date = "May 10, 2026",
        category = "Engine",
        description = "Strange rattling noise during startup.",
        status = "Resolved"
    )
)
