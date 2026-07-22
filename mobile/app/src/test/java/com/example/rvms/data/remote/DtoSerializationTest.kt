package com.example.rvms.data.remote

import com.example.rvms.data.remote.dto.LoginResponseDto
import com.example.rvms.data.remote.dto.RegisterRequestDto
import com.example.rvms.data.remote.dto.UserEnvelopeDto
import kotlinx.serialization.decodeFromString
import org.junit.Assert.assertEquals
import org.junit.Assert.assertFalse
import org.junit.Assert.assertNull
import org.junit.Assert.assertTrue
import org.junit.Test

/**
 * Proves the DTOs (de)serialize against the real RVMS API JSON envelope
 * (snake_case keys, nested agency, nullable license fields) using the same
 * Json config the app runs with.
 */
class DtoSerializationTest {

    private val json = ApiClient.json

    @Test
    fun `login response decodes token plus nested user and agency`() {
        val body = """
            {
              "token": "12|abcdefTOKEN",
              "user": {
                "id": 5,
                "agency_id": 1,
                "role": "driver",
                "name": "Ramon Villanueva",
                "email": "ramon.villanueva@rvms.local",
                "status": "active",
                "license_number": "N01-11-111111",
                "license_expiry_date": "2027-05-01",
                "agency": {
                  "id": 1,
                  "code": "BFP",
                  "name": "Bureau of Fire Protection",
                  "location": "Calbayog City",
                  "contact_number": "0900-000-0000",
                  "email": "bfp@rvms.local",
                  "logo_path": "assets/img/logo-bfp.svg",
                  "license_expiry_warning_days": 30
                }
              }
            }
        """.trimIndent()

        val decoded = json.decodeFromString<LoginResponseDto>(body)

        assertEquals("12|abcdefTOKEN", decoded.token)
        assertEquals(5L, decoded.user.id)
        assertEquals("driver", decoded.user.role)
        assertEquals("ramon.villanueva@rvms.local", decoded.user.email)
        assertEquals("N01-11-111111", decoded.user.licenseNumber)
        assertEquals("2027-05-01", decoded.user.licenseExpiryDate)
        assertEquals("BFP", decoded.user.agency?.code)
        assertEquals(30, decoded.user.agency?.licenseExpiryWarningDays)
    }

    @Test
    fun `admin user with null license fields and unknown keys decodes`() {
        // Admins carry null license fields; the envelope may add fields the app
        // does not model (ignoreUnknownKeys must swallow them).
        val body = """
            {
              "user": {
                "id": 1,
                "agency_id": 2,
                "role": "admin",
                "name": "PNP Admin",
                "email": "pnp.admin@rvms.local",
                "status": "active",
                "license_number": null,
                "license_expiry_date": null,
                "some_future_field": "ignored",
                "agency": { "id": 2, "code": "PNP", "name": "Philippine National Police" }
              }
            }
        """.trimIndent()

        val decoded = json.decodeFromString<UserEnvelopeDto>(body)

        assertEquals("admin", decoded.user.role)
        assertNull(decoded.user.licenseNumber)
        assertNull(decoded.user.licenseExpiryDate)
        assertEquals("PNP", decoded.user.agency?.code)
    }

    @Test
    fun `register request encodes snake_case keys and omits null license fields`() {
        val request = RegisterRequestDto(
            agencyId = 1,
            name = "New Driver",
            email = "new.driver@rvms.local",
            password = "password",
            passwordConfirmation = "password",
            // license fields left null
        )

        val encoded = json.encodeToString(RegisterRequestDto.serializer(), request)

        assertTrue(encoded.contains("\"agency_id\":1"))
        assertTrue(encoded.contains("\"password_confirmation\":\"password\""))
        // explicitNulls = false → nullable, unset fields are omitted, not sent as null.
        assertFalse(encoded.contains("license_number"))
        assertFalse(encoded.contains("license_expiry_date"))
    }
}
