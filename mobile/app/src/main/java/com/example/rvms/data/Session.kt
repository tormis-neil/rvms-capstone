package com.example.rvms.data

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

    fun signInAs(agency: Agency) {
        current = SampleData.agencyData.getValue(agency)
    }
}
