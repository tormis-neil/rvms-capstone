package com.example.rvms.ui.common

import org.junit.Assert.assertEquals
import org.junit.Assert.assertTrue
import org.junit.Test
import java.text.SimpleDateFormat
import java.util.Calendar
import java.util.Locale

/**
 * Proves the client-side formatting/derivation helpers used by the real-data
 * screens (Home, Vehicle Info, Profile) — especially [licenseState], which
 * mirrors the backend's `User::licenseStatus()` boundary logic exactly
 * (expired if past today; expiring soon if within the agency's configurable
 * warning window, inclusive; otherwise valid).
 */
class FormattingTest {

    private val iso = SimpleDateFormat("yyyy-MM-dd", Locale.US)

    /** Today's date offset by [days] (may be negative), formatted yyyy-MM-dd. */
    private fun isoDateOffset(days: Int): String {
        val cal = Calendar.getInstance()
        cal.add(Calendar.DAY_OF_YEAR, days)
        return iso.format(cal.time)
    }

    @Test
    fun `formatMileage groups thousands and appends the unit`() {
        assertEquals("45,230 km", formatMileage(45230))
        assertEquals("0 km", formatMileage(0))
        assertEquals("124,000 km", formatMileage(124000))
    }

    @Test
    fun `formatIsoDate renders a readable date and falls back on blank or bad input`() {
        assertEquals("May 1, 2027", formatIsoDate("2027-05-01"))
        assertEquals("—", formatIsoDate(null))
        assertEquals("—", formatIsoDate(""))
        assertEquals("—", formatIsoDate("not-a-date"))
    }

    @Test
    fun `licenseState is NONE when no license is on file`() {
        assertEquals(LicenseState.NONE, licenseState(null, 30))
        assertEquals(LicenseState.NONE, licenseState("", 30))
    }

    @Test
    fun `licenseState is EXPIRED for any date before today`() {
        assertEquals(LicenseState.EXPIRED, licenseState(isoDateOffset(-1), 30))
        assertEquals(LicenseState.EXPIRED, licenseState(isoDateOffset(-365), 30))
    }

    @Test
    fun `licenseState is VALID well beyond the warning window`() {
        assertEquals(LicenseState.VALID, licenseState(isoDateOffset(90), 30))
    }

    @Test
    fun `licenseState flips to EXPIRING_SOON exactly at the threshold boundary (inclusive)`() {
        // Mirrors the backend: license_expiry_date.lte(today + warningDays) -> Expiring Soon.
        assertEquals(LicenseState.EXPIRING_SOON, licenseState(isoDateOffset(30), 30))
        // One day beyond the boundary is still Valid.
        assertEquals(LicenseState.VALID, licenseState(isoDateOffset(31), 30))
    }

    @Test
    fun `licenseState defaults the warning window to 30 days when the agency value is missing`() {
        assertEquals(LicenseState.EXPIRING_SOON, licenseState(isoDateOffset(30), null))
        assertEquals(LicenseState.VALID, licenseState(isoDateOffset(31), null))
    }

    @Test
    fun `initialsFor takes the first letter of the first two words`() {
        assertEquals("RV", initialsFor("Ramon Villanueva"))
        assertEquals("J", initialsFor("Jose"))
        assertEquals("?", initialsFor("  "))
    }

    @Test
    fun `logoForAgencyCode resolves a known code and falls back for an unknown one`() {
        // Real resource ids are > 0; an unknown code falls back to the app logo,
        // which is also a valid (non-zero) resource id.
        assertTrue(logoForAgencyCode("BFP") != 0)
        assertTrue(logoForAgencyCode("NOT-AN-AGENCY") != 0)
    }
}
