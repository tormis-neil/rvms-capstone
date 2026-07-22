package com.example.rvms.data

import com.example.rvms.data.remote.ApiService
import com.example.rvms.data.remote.dto.VehicleDto

/**
 * The driver's assigned vehicle(s) (FR-07). A driver may be the primary
 * driver of more than one vehicle, so [myVehicles] always returns a list —
 * empty when none are assigned yet, or on any network/API failure (the Home
 * and Vehicle Info screens render an explicit "no vehicle assigned" state
 * for the empty case rather than treating it as an error).
 */
class VehicleRepository(private val api: ApiService) {

    suspend fun myVehicles(): List<VehicleDto> = try {
        val response = api.myVehicle()
        if (response.isSuccessful) response.body()?.data.orEmpty() else emptyList()
    } catch (e: Exception) {
        emptyList()
    }
}
