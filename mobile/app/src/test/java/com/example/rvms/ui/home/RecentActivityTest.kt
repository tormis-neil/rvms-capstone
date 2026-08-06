package com.example.rvms.ui.home

import com.example.rvms.data.remote.dto.DamageDto
import com.example.rvms.data.remote.dto.InspectionDto
import com.example.rvms.data.remote.dto.InspectionItemDto
import org.junit.Assert.assertEquals
import org.junit.Assert.assertTrue
import org.junit.Test

/**
 * Home → Recent Activity merges the driver's inspections AND damage reports
 * (FR-09, FR-11 — 2026-08, lead-reported).
 *
 * The list showed inspections only, so a driver who had just filed a damage
 * report saw no trace of it under a heading that claimed to show recent
 * activity. These tests pin the merge: both kinds appear, newest first, capped
 * at three.
 */
class RecentActivityTest {

    private fun inspection(
        id: Long,
        submittedAt: String,
        issues: Int = 0,
    ) = InspectionDto(
        id = id,
        vehicleId = 1,
        driverId = 1,
        inspectionDate = submittedAt.substringBefore('T'),
        reviewStatus = "Pending",
        submittedAt = submittedAt,
        items = List(issues) {
            InspectionItemDto(checklistItemId = it.toLong(), status = "Has Issue")
        },
    )

    private fun damage(
        id: Long,
        submittedAt: String,
        status: String = "Pending",
    ) = DamageDto(
        id = id,
        vehicleId = 1,
        driverId = 1,
        natureOfDamage = "Cracked windscreen",
        dateReported = submittedAt.substringBefore('T'),
        status = status,
        submittedAt = submittedAt,
    )

    @Test
    fun `both kinds of submission appear`() {
        val entries = buildRecentActivity(
            inspections = listOf(inspection(1, "2026-08-06T08:00:00Z")),
            damages = listOf(damage(1, "2026-08-06T09:00:00Z")),
        )

        assertEquals(2, entries.size)
        assertTrue(entries.any { it.title == "Daily Inspection Submitted" })
        assertTrue(entries.any { it.title == "Damage Report Filed" })
    }

    @Test
    fun `entries are ordered newest first across both kinds`() {
        val entries = buildRecentActivity(
            inspections = listOf(
                inspection(1, "2026-08-06T06:00:00Z"),
                inspection(2, "2026-08-06T10:00:00Z"),
            ),
            damages = listOf(damage(1, "2026-08-06T08:00:00Z")),
        )

        assertEquals(
            listOf(
                "Daily Inspection Submitted",
                "Damage Report Filed",
                "Daily Inspection Submitted",
            ),
            entries.map { it.title },
        )
    }

    /** A damage report filed moments ago must not be pushed off by older inspections. */
    @Test
    fun `a fresh damage report survives the three-row cap`() {
        val entries = buildRecentActivity(
            inspections = listOf(
                inspection(1, "2026-08-05T08:00:00Z"),
                inspection(2, "2026-08-04T08:00:00Z"),
                inspection(3, "2026-08-03T08:00:00Z"),
                inspection(4, "2026-08-02T08:00:00Z"),
            ),
            damages = listOf(damage(1, "2026-08-06T09:00:00Z")),
        )

        assertEquals(3, entries.size)
        assertEquals("Damage Report Filed", entries.first().title)
    }

    @Test
    fun `an inspection reports its issue count and a damage report its review state`() {
        val entries = buildRecentActivity(
            inspections = listOf(inspection(1, "2026-08-06T08:00:00Z", issues = 2)),
            damages = listOf(damage(1, "2026-08-06T09:00:00Z", status = "Reviewed")),
        )

        assertEquals("Reviewed", entries.first().subtitle)
        assertEquals("2 issue(s) found", entries.last().subtitle)
    }

    @Test
    fun `an all-OK inspection says so`() {
        val entries = buildRecentActivity(
            inspections = listOf(inspection(1, "2026-08-06T08:00:00Z")),
            damages = emptyList(),
        )

        assertEquals("All items OK", entries.first().subtitle)
    }

    /** A fresh account has nothing to show, and that is not an error. */
    @Test
    fun `no submissions yields an empty list`() {
        assertTrue(buildRecentActivity(emptyList(), emptyList()).isEmpty())
    }

    /** A record with no timestamp must sort last rather than break the comparison. */
    @Test
    fun `a missing timestamp does not crash the ordering`() {
        val entries = buildRecentActivity(
            inspections = listOf(inspection(1, "2026-08-06T08:00:00Z").copy(submittedAt = null)),
            damages = listOf(damage(1, "2026-08-06T09:00:00Z")),
        )

        assertEquals(2, entries.size)
        assertEquals("Damage Report Filed", entries.first().title)
    }
}
