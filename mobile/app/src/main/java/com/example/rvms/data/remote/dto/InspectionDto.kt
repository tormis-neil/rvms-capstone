package com.example.rvms.data.remote.dto

import kotlinx.serialization.SerialName
import kotlinx.serialization.Serializable

/*
 * DTOs for the driver inspection flow (FR-09):
 *   - GET  /inspections/checklist  -> the items to fill (14 for BFP, 12 others)
 *   - POST /inspections            -> submit
 *   - GET  /inspections            -> own history
 *   - GET  /inspections/{id}       -> full detail
 * Shapes mirror InspectionChecklistItemResource / InspectionResource /
 * InspectionItemResource exactly.
 */

/** GET /inspections/checklist — collection wrapped in `{ "data": [...] }`. */
@Serializable
data class ChecklistDto(
    val data: List<ChecklistItemDto> = emptyList(),
)

@Serializable
data class ChecklistItemDto(
    val id: Long,
    val name: String,
    @SerialName("is_bfp_only") val isBfpOnly: Boolean = false,
    @SerialName("sort_order") val sortOrder: Int = 0,
)

/** POST /inspections request body. */
@Serializable
data class SubmitInspectionDto(
    @SerialName("vehicle_id") val vehicleId: Long,
    val items: List<SubmitInspectionItemDto>,
)

@Serializable
data class SubmitInspectionItemDto(
    @SerialName("checklist_item_id") val checklistItemId: Long,
    val status: String, // "OK" | "Has Issue"
    val remarks: String? = null,
)

/** GET /inspections — history list wrapped in `{ "data": [...], ...pagination }`. */
@Serializable
data class InspectionListDto(
    val data: List<InspectionDto> = emptyList(),
)

/** Single inspection under `{ "data": {...} }` (show), or an element of the list. */
@Serializable
data class InspectionEnvelopeDto(
    val data: InspectionDto,
)

@Serializable
data class InspectionDto(
    val id: Long,
    @SerialName("vehicle_id") val vehicleId: Long,
    @SerialName("driver_id") val driverId: Long,
    @SerialName("inspection_date") val inspectionDate: String? = null,
    @SerialName("review_status") val reviewStatus: String,
    @SerialName("submitted_at") val submittedAt: String? = null,
    /** "All OK" / "N Issue(s)" — present when items are loaded. */
    val result: String? = null,
    val vehicle: InspectionVehicleDto? = null,
    val driver: InspectionDriverDto? = null,
    val items: List<InspectionItemDto> = emptyList(),
)

@Serializable
data class InspectionVehicleDto(
    val id: Long,
    @SerialName("plate_number") val plateNumber: String,
    val type: String,
)

@Serializable
data class InspectionDriverDto(
    val id: Long,
    val name: String,
)

@Serializable
data class InspectionItemDto(
    @SerialName("checklist_item_id") val checklistItemId: Long,
    val name: String? = null,
    @SerialName("is_bfp_only") val isBfpOnly: Boolean = false,
    val status: String, // "OK" | "Has Issue"
    val remarks: String? = null,
)
