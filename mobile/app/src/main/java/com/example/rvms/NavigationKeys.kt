package com.example.rvms

import androidx.navigation3.runtime.NavKey
import kotlinx.serialization.Serializable

@Serializable data object Splash : NavKey

@Serializable data object SignIn : NavKey

@Serializable data object SignUp : NavKey

@Serializable data object Main : NavKey

// Bottom nav destinations
@Serializable data object Home : NavKey
@Serializable data object VehicleInfo : NavKey
@Serializable data object Inspection : NavKey
@Serializable data object DamageReport : NavKey
@Serializable data object Notifications : NavKey
@Serializable data object Profile : NavKey

// Sub-screens
@Serializable data object NewInspection : NavKey
@Serializable data object NewDamageReport : NavKey
