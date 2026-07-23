package com.example.rvms.data.remote.dto

import kotlinx.serialization.SerialName
import kotlinx.serialization.Serializable

/*
 * DTOs for the driver damage-report flow (FR-11):
 *   - POST /damage-reports (multipart, photo optional)
 *   - GET  /damage-reports  (own history)
 * Shapes mirror DamageReportResource exactly.
 */

/** GET /damage-reports — history list wrapped in `{ "data": [...], ...pagination }`. */
@Serializable
data class DamageListDto(
    val data: List<DamageDto> = emptyList(),
)

/** POST /damage-reports response — `{ "data": {...} }`. */
@Serializable
data class DamageEnvelopeDto(
    val data: DamageDto,
)

@Serializable
data class DamageDto(
    val id: Long,
    @SerialName("vehicle_id") val vehicleId: Long,
    @SerialName("driver_id") val driverId: Long,
    @SerialName("nature_of_damage") val natureOfDamage: String,
    @SerialName("suspected_parts") val suspectedParts: String? = null,
    @SerialName("photo_path") val photoPath: String? = null,
    @SerialName("photo_url") val photoUrl: String? = null,
    @SerialName("date_reported") val dateReported: String? = null,
    val status: String,
    @SerialName("submitted_at") val submittedAt: String? = null,
    val vehicle: DamageVehicleDto? = null,
)

@Serializable
data class DamageVehicleDto(
    val id: Long,
    @SerialName("plate_number") val plateNumber: String,
    val type: String,
)
