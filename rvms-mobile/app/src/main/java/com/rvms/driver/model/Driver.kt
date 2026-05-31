package com.rvms.driver.model

data class Driver(
    val id: String,
    val name: String,
    val licenseNumber: String,
    val agency: Agency
)

enum class Agency(val displayName: String) {
    CDRRMO("CDRRMO (Disaster Risk Reduction)"),
    BFP("BFP (Bureau of Fire Protection)"),
    CHO("CHO (City Health Office)"),
    PNP("PNP (Philippine National Police)")
}

// Dummy Data
val DummyDriver = Driver(
    id = "DRV-1001",
    name = "Neil Mayo Tormis",
    licenseNumber = "N01-12-345678",
    agency = Agency.CDRRMO
)
