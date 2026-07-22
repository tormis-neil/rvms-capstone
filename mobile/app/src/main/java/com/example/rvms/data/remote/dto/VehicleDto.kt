package com.example.rvms.data.remote.dto

import kotlinx.serialization.SerialName
import kotlinx.serialization.Serializable

/**
 * DTOs for GET /my-vehicle (FR-07) — matches VehicleResource exactly. A driver
 * may be the primary driver of more than one vehicle (Ch4 ERD), so the
 * endpoint always returns a list, wrapped in the standard `{ "data": [...] }`
 * Laravel resource-collection envelope.
 */
@Serializable
data class VehicleListDto(
    val data: List<VehicleDto> = emptyList(),
)

@Serializable
data class VehicleDto(
    val id: Long,
    @SerialName("agency_id") val agencyId: Long,
    val type: String,
    @SerialName("plate_number") val plateNumber: String,
    val make: String,
    val model: String,
    @SerialName("engine_number") val engineNumber: String? = null,
    @SerialName("chassis_number") val chassisNumber: String? = null,
    @SerialName("current_mileage") val currentMileage: Int,
    /** One of the exact four vehicle statuses (FR-18). */
    val status: String,
    val remarks: String? = null,
    @SerialName("assigned_driver") val assignedDriver: AssignedDriverDto? = null,
)

@Serializable
data class AssignedDriverDto(
    val id: Long,
    val name: String,
)
