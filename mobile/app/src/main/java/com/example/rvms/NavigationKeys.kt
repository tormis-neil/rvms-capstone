package com.example.rvms

import androidx.navigation3.runtime.NavKey
import kotlinx.serialization.Serializable

@Serializable data object Splash : NavKey

@Serializable data object SignIn : NavKey

@Serializable data object SignUp : NavKey

@Serializable data object Main : NavKey
