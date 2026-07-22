package com.example.rvms.data.remote.dto

import kotlinx.serialization.SerialName
import kotlinx.serialization.Serializable

/*
 * Request/response DTOs matching the RVMS API JSON envelope exactly.
 *
 * The API uses snake_case keys (Laravel API Resources), so each property
 * carries an @SerialName mapping its idiomatic camelCase Kotlin name to the
 * wire key. Unknown keys are ignored by the Json config in ApiClient, so the
 * API can add fields without breaking the app.
 *
 * Shapes are taken verbatim from the backend:
 *   - AuthController@login   -> { token, user }
 *   - AuthController@me       -> { user }
 *   - AuthController@register -> { message, user }   (201)
 *   - UserResource / AgencyResource
 */

/** POST /login body. */
@Serializable
data class LoginRequestDto(
    val email: String,
    val password: String,
)

/**
 * POST /register body (driver self-registration, FR-03). Laravel expects the
 * confirmation field `password_confirmation`; license fields are optional.
 */
@Serializable
data class RegisterRequestDto(
    @SerialName("agency_id") val agencyId: Long,
    val name: String,
    val email: String,
    val password: String,
    @SerialName("password_confirmation") val passwordConfirmation: String,
    @SerialName("license_number") val licenseNumber: String? = null,
    @SerialName("license_expiry_date") val licenseExpiryDate: String? = null,
)

/** POST /login success body: token + the authenticated user. */
@Serializable
data class LoginResponseDto(
    val token: String,
    val user: UserDto,
)

/** GET /me and POST /register both nest the user under `user`. */
@Serializable
data class UserEnvelopeDto(
    val user: UserDto,
    val message: String? = null,
)

/** A plain `{ "message": "..." }` body (logout, 403 reasons, etc.). */
@Serializable
data class MessageDto(
    val message: String? = null,
)

/** GET /agencies — the public agency directory, wrapped in `{ "data": [...] }`. */
@Serializable
data class AgencyListDto(
    val data: List<AgencyDto> = emptyList(),
)

/** UserResource — a driver or admin account. License fields are null for admins. */
@Serializable
data class UserDto(
    val id: Long,
    @SerialName("agency_id") val agencyId: Long,
    val role: String,
    val name: String,
    val email: String,
    val status: String,
    @SerialName("license_number") val licenseNumber: String? = null,
    @SerialName("license_expiry_date") val licenseExpiryDate: String? = null,
    val agency: AgencyDto? = null,
)

/** AgencyResource — the caller's agency (scoping + dashboard chrome). */
@Serializable
data class AgencyDto(
    val id: Long,
    val code: String,
    val name: String,
    val location: String? = null,
    @SerialName("contact_number") val contactNumber: String? = null,
    val email: String? = null,
    @SerialName("logo_path") val logoPath: String? = null,
    @SerialName("license_expiry_warning_days") val licenseExpiryWarningDays: Int? = null,
)
