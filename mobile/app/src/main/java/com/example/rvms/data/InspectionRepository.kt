package com.example.rvms.data

import com.example.rvms.data.remote.ApiService
import com.example.rvms.data.remote.dto.ChecklistItemDto
import com.example.rvms.data.remote.dto.InspectionDto
import com.example.rvms.data.remote.dto.SubmitInspectionDto
import com.example.rvms.data.remote.dto.SubmitInspectionItemDto
import com.example.rvms.data.remote.laravelErrorMessage

/** Outcome of submitting a BLOWBAGETS inspection (FR-09). */
sealed interface SubmitInspectionResult {
    data object Success : SubmitInspectionResult
    /** Validation failure (422 — incomplete/flagged-without-remark) or network error. */
    data class Error(val message: String) : SubmitInspectionResult
}

/**
 * The driver's daily inspection flow (FR-09): fetch the agency-correct
 * checklist (14 for BFP, 12 for others), submit a completed inspection, and
 * read the driver's own history/detail. History/detail return empty/null on
 * failure so a fresh account simply shows an empty history rather than an error.
 */
class InspectionRepository(private val api: ApiService) {

    suspend fun checklist(): List<ChecklistItemDto> = try {
        val response = api.inspectionChecklist()
        if (response.isSuccessful) response.body()?.data.orEmpty() else emptyList()
    } catch (e: Exception) {
        emptyList()
    }

    suspend fun history(): List<InspectionDto> = try {
        val response = api.myInspections()
        if (response.isSuccessful) response.body()?.data.orEmpty() else emptyList()
    } catch (e: Exception) {
        emptyList()
    }

    suspend fun detail(id: Long): InspectionDto? = try {
        val response = api.inspectionDetail(id)
        if (response.isSuccessful) response.body()?.data else null
    } catch (e: Exception) {
        null
    }

    suspend fun submit(
        vehicleId: Long,
        items: List<SubmitInspectionItemDto>,
    ): SubmitInspectionResult = try {
        val response = api.submitInspection(SubmitInspectionDto(vehicleId, items))
        if (response.isSuccessful) {
            SubmitInspectionResult.Success
        } else {
            SubmitInspectionResult.Error(
                laravelErrorMessage(response, fallback = "Unable to submit the inspection. Please try again."),
            )
        }
    } catch (e: Exception) {
        SubmitInspectionResult.Error("Cannot reach the server. Check your connection and try again.")
    }
}
