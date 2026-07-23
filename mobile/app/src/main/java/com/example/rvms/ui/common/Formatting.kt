package com.example.rvms.ui.common

import com.example.rvms.R
import com.example.rvms.data.Agency
import java.text.NumberFormat
import java.text.SimpleDateFormat
import java.util.Calendar
import java.util.Locale

/*
 * Small formatting/derivation helpers shared by the real-data screens (Home,
 * Vehicle Info, Profile). Deliberately avoid java.time: minSdk is 24 and the
 * app does not enable core-library desugaring, so java.time (API 26+) is not
 * safely available — java.text (SimpleDateFormat/NumberFormat) and
 * java.util.Calendar work on every supported device.
 */

private val isoDateFormat = SimpleDateFormat("yyyy-MM-dd", Locale.US)
private val displayDateFormat = SimpleDateFormat("MMMM d, yyyy", Locale.US)

/** Today as yyyy-MM-dd (device local date) — used to detect today's inspection. */
fun todayIso(): String = isoDateFormat.format(java.util.Date())

/**
 * "2026-07-23T14:30:00.000000Z" -> "2:30 PM" in the device's local time.
 * Returns "" when the timestamp is missing or unparseable (the UI then just
 * omits the time).
 */
fun formatIsoTime(iso: String?): String {
    if (iso.isNullOrBlank()) return ""
    return try {
        val trimmed = iso.substringBefore('.').substringBefore('Z')
        val parser = SimpleDateFormat("yyyy-MM-dd'T'HH:mm:ss", Locale.US).apply {
            timeZone = java.util.TimeZone.getTimeZone("UTC")
        }
        SimpleDateFormat("h:mm a", Locale.US).format(parser.parse(trimmed)!!)
    } catch (e: Exception) {
        ""
    }
}

/** "45230" -> "45,230 km" (grouped, matching the web/prototype mileage label). */
fun formatMileage(km: Int): String =
    "${NumberFormat.getIntegerInstance(Locale.US).format(km)} km"

/** "2027-05-01" -> "May 1, 2027"; null/unparseable -> "—". */
fun formatIsoDate(iso: String?): String {
    if (iso.isNullOrBlank()) return "—"
    return try {
        displayDateFormat.format(isoDateFormat.parse(iso)!!)
    } catch (e: Exception) {
        "—"
    }
}

/** Mirrors the admin dashboard's three license states, plus NONE for no license on file. */
enum class LicenseState { NONE, VALID, EXPIRING_SOON, EXPIRED }

/**
 * Mirrors backend `User::licenseStatus()` exactly (expired if past today;
 * expiring soon if within the agency's configurable warning window;
 * otherwise valid), computed client-side from data the driver already has
 * from `/me` — no separate endpoint needed.
 */
fun licenseState(expiryIso: String?, warningDays: Int?): LicenseState {
    if (expiryIso.isNullOrBlank()) return LicenseState.NONE

    val expiry = try {
        isoDateFormat.parse(expiryIso) ?: return LicenseState.NONE
    } catch (e: Exception) {
        return LicenseState.NONE
    }

    val today = Calendar.getInstance().apply {
        set(Calendar.HOUR_OF_DAY, 0); set(Calendar.MINUTE, 0)
        set(Calendar.SECOND, 0); set(Calendar.MILLISECOND, 0)
    }

    if (expiry.before(today.time)) return LicenseState.EXPIRED

    val warningCutoff = (today.clone() as Calendar).apply {
        add(Calendar.DAY_OF_YEAR, warningDays ?: 30)
    }

    return if (!expiry.after(warningCutoff.time)) LicenseState.EXPIRING_SOON else LicenseState.VALID
}

/** "Ramon Villanueva" -> "RV" (first letter of the first two words). */
fun initialsFor(name: String): String =
    name.trim().split(Regex("\\s+"))
        .filter { it.isNotEmpty() }
        .take(2)
        .joinToString("") { it.first().uppercase() }
        .ifEmpty { "?" }

/** Agency logo drawable for a code like "BFP"; falls back to the app logo. */
fun logoForAgencyCode(code: String?): Int =
    Agency.entries.firstOrNull { it.code == code }?.logo ?: R.drawable.rvms_logo

/**
 * BLOWBAGETS acronym letter for a checklist item name, mirroring the paper
 * checklist drivers already know. Reference data (not account data): items
 * outside the acronym (Horn/Siren, Directional Signals, BFP extras) have no
 * letter and return null.
 */
private val blowbagetsLetters = mapOf(
    "Battery" to "B", "Lights" to "L", "Oil" to "O", "Water" to "W",
    "Brakes" to "B", "Air" to "A", "Gas" to "G", "Engine" to "E",
    "Tires" to "T", "Power Steering" to "S",
)

fun blowbagetsLetter(itemName: String): String? = blowbagetsLetters[itemName]
