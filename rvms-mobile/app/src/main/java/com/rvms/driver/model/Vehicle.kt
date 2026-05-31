package com.rvms.driver.model

import com.rvms.driver.ui.components.VehicleStatus

data class Vehicle(
    val id: String,
    val plateNumber: String,
    val type: String,
    val model: String,
    val currentOdometer: Int,
    var status: VehicleStatus
)

// Dummy Data
val DummyAssignedVehicle = Vehicle(
    id = "VEH-2026",
    plateNumber = "SDA-1234",
    type = "Ambulance",
    model = "Toyota Hiace Commuter 2024",
    currentOdometer = 12500,
    status = VehicleStatus.OPERATIONAL
)
