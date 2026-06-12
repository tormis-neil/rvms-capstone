package com.example.rvms.data

import androidx.compose.runtime.mutableStateListOf

/**
 * The current driver session for the prototype.
 *
 * Set at sign-in (the agency chips on the Sign In screen). Because sign-in
 * always navigates into a fresh Home, a simple singleton is sufficient — there
 * is no backend or persisted auth in the prototype. Every driver screen reads
 * [current] so the whole app reflects the selected agency.
 */
object Session {
    var current: AgencyData = SampleData.agencyData.getValue(Agency.BFP)
        private set

    /** Inspections submitted during this session (in-memory, prototype only). */
    private val sessionInspections = mutableStateListOf<InspectionRecord>()

    fun signInAs(agency: Agency) {
        current = SampleData.agencyData.getValue(agency)
        sessionInspections.clear()
    }

    /** Session submissions first (newest), then the agency's sample history. */
    val inspectionHistory: List<InspectionRecord>
        get() = sessionInspections + current.inspectionHistory

    /** Today's inspection, if one has already been submitted (Plan §6.4: daily). */
    fun todaysInspection(): InspectionRecord? =
        inspectionHistory.firstOrNull { it.date == SampleData.todayLabel }

    fun submitInspection(record: InspectionRecord) {
        sessionInspections.add(0, record)
    }
}
