package com.example.rvms.data

import com.example.rvms.data.remote.ApiService
import com.example.rvms.data.remote.dto.VehicleDto

/**
 * The driver's assigned vehicle(s) (FR-07). A driver may be the primary driver
 * of more than one vehicle, so a success always carries a list — empty when
 * none are assigned yet.
 *
 * An empty list and a failed call are deliberately DIFFERENT outcomes now
 * (R10 sub-task 1). They used to be the same value, so a driver with no
 * signal was shown "No Vehicle Assigned" — the wrong answer, stated
 * confidently, about the fact they most need.
 */
class VehicleRepository(private val api: ApiService) {

    suspend fun myVehicles(): FetchResult<List<VehicleDto>> = fetchCatching {
        val response = api.myVehicle()
        if (response.isSuccessful) {
            FetchResult.Success(response.body()?.data.orEmpty())
        } else {
            FetchResult.Failure(FetchResult.OFFLINE_MESSAGE)
        }
    }
}
