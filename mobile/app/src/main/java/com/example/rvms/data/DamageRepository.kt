package com.example.rvms.data

import com.example.rvms.data.remote.ApiService
import com.example.rvms.data.remote.dto.DamageDto
import com.example.rvms.data.remote.laravelErrorMessage
import okhttp3.MediaType.Companion.toMediaTypeOrNull
import okhttp3.MultipartBody
import okhttp3.RequestBody.Companion.toRequestBody

/** Outcome of filing a damage report (FR-11). */
sealed interface SubmitDamageResult {
    data object Success : SubmitDamageResult
    data class Error(val message: String) : SubmitDamageResult
}

/**
 * The driver's damage-report flow (FR-11): file a report (photo optional) and
 * read the driver's own history. A read distinguishes "no reports yet" from
 * "could not reach the server" (R10 sub-task 1) — the first is a fresh
 * account, the second is something the driver can act on.
 */
class DamageRepository(private val api: ApiService) {

    suspend fun history(): FetchResult<List<DamageDto>> = fetchCatching {
        val response = api.myDamageReports()
        if (response.isSuccessful) {
            FetchResult.Success(response.body()?.data.orEmpty())
        } else {
            FetchResult.Failure(FetchResult.OFFLINE_MESSAGE)
        }
    }

    /**
     * @param photo raw image bytes + filename, or null when the driver did not
     *              attach a photo (it is optional, FR-11).
     */
    suspend fun submit(
        vehicleId: Long,
        natureOfDamage: String,
        suspectedParts: String?,
        photo: Pair<ByteArray, String>? = null,
    ): SubmitDamageResult = try {
        val text = "text/plain".toMediaTypeOrNull()

        val photoPart = photo?.let { (bytes, name) ->
            MultipartBody.Part.createFormData(
                "photo",
                name,
                bytes.toRequestBody("image/*".toMediaTypeOrNull()),
            )
        }

        val response = api.submitDamage(
            vehicleId = vehicleId.toString().toRequestBody(text),
            natureOfDamage = natureOfDamage.trim().toRequestBody(text),
            suspectedParts = suspectedParts?.trim()?.ifBlank { null }?.toRequestBody(text),
            photo = photoPart,
        )

        if (response.isSuccessful) {
            SubmitDamageResult.Success
        } else {
            SubmitDamageResult.Error(
                laravelErrorMessage(response, fallback = "Unable to submit the report. Please try again."),
            )
        }
    } catch (e: Exception) {
        SubmitDamageResult.Error("Cannot reach the server. Check your connection and try again.")
    }
}
