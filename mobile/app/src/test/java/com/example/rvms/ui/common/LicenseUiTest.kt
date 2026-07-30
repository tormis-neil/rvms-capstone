package com.example.rvms.ui.common

import com.example.rvms.theme.StatusNotOperational
import com.example.rvms.theme.StatusOperational
import com.example.rvms.theme.StatusUnderPM
import org.junit.Assert.assertEquals
import org.junit.Assert.assertNotEquals
import org.junit.Assert.assertNull
import org.junit.Assert.assertTrue
import org.junit.Test

/**
 * The licence card's three states must stay three (FR-08).
 *
 * The screen previously derived its colour from a single
 * `EXPIRING_SOON || EXPIRED` boolean, so a licence that had already lapsed was
 * drawn exactly like one with weeks left — same amber, same chip, same
 * "expires on…" sentence about a date already in the past. The admin dashboard
 * has always shown those as amber and red respectively, so the two platforms
 * disagreed about a fact a driver acts on.
 *
 * These assertions are deliberately about DISTINCTNESS as much as values: the
 * regression was two states sharing one presentation, and any future edit that
 * collapses them again fails here rather than on someone's handset.
 */
class LicenseUiTest {

    @Test
    fun `each state maps to the dashboard's own tone`() {
        assertEquals(StatusOperational, licenseColor(LicenseState.VALID))
        assertEquals(StatusUnderPM, licenseColor(LicenseState.EXPIRING_SOON))
        assertEquals(StatusNotOperational, licenseColor(LicenseState.EXPIRED))
    }

    /** The bug itself: expiring and expired sharing one colour. */
    @Test
    fun `expiring soon and expired never share a colour`() {
        assertNotEquals(
            licenseColor(LicenseState.EXPIRING_SOON),
            licenseColor(LicenseState.EXPIRED),
        )
    }

    @Test
    fun `no two states share a colour`() {
        val colors = listOf(LicenseState.VALID, LicenseState.EXPIRING_SOON, LicenseState.EXPIRED)
            .map { licenseColor(it) }

        assertEquals(3, colors.toSet().size)
    }

    @Test
    fun `labels match the wording used on the dashboard`() {
        assertEquals("Valid", licenseLabel(LicenseState.VALID))
        assertEquals("Expiring Soon", licenseLabel(LicenseState.EXPIRING_SOON))
        assertEquals("Expired", licenseLabel(LicenseState.EXPIRED))
    }

    @Test
    fun `each state carries its own chip`() {
        val badges = listOf(LicenseState.VALID, LicenseState.EXPIRING_SOON, LicenseState.EXPIRED)
            .map { licenseBadge(it) }

        assertEquals(3, badges.toSet().size)
    }

    /** A valid licence needs no advice, so the card shows no advisory line. */
    @Test
    fun `a valid licence has no message`() {
        assertNull(licenseMessage(LicenseState.VALID, "2030-01-01"))
        assertNull(licenseMessage(LicenseState.NONE, null))
    }

    /**
     * Tense matters here: "expires on <past date>" reads as though there were
     * still time to act, which is the opposite of what an expired licence means.
     */
    @Test
    fun `an expired licence is described in the past tense`() {
        val message = licenseMessage(LicenseState.EXPIRED, "2026-01-15").orEmpty()

        assertTrue(message, message.startsWith("Expired "))
        assertTrue(message, message.contains("January 15, 2026"))
        assertTrue(message, message.contains("before operating a vehicle"))
    }

    @Test
    fun `an expiring licence is described in the future tense`() {
        val message = licenseMessage(LicenseState.EXPIRING_SOON, "2026-08-20").orEmpty()

        assertTrue(message, message.startsWith("Expires "))
        assertTrue(message, message.contains("August 20, 2026"))
        assertTrue(message, message.contains("renewal"))
    }

    /** A missing expiry date must not print "null" at the driver. */
    @Test
    fun `a missing date degrades to a dash rather than null`() {
        val message = licenseMessage(LicenseState.EXPIRED, null).orEmpty()

        assertTrue(message, message.contains("—"))
        assertTrue(message, !message.contains("null"))
    }
}
