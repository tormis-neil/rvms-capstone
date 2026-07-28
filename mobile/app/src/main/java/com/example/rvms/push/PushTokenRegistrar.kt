package com.example.rvms.push

import android.util.Log
import com.example.rvms.data.ServiceLocator
import com.google.firebase.messaging.FirebaseMessaging
import kotlinx.coroutines.tasks.await

/**
 * Registers this device with the backend so it can receive pushes (FR-21).
 *
 * Every call is best-effort and never throws: push is an extra on top of the
 * stored notification, which the driver can always read in the app. In
 * particular, when google-services.json is absent Firebase never initialises and
 * asking for a token throws — that is caught here and treated as "no token",
 * so a build without Firebase behaves exactly like a device that has not
 * granted notification permission.
 */
object PushTokenRegistrar {

    private const val TAG = "RvmsPush"

    /** Called after a successful sign-in, and from the service on token rotation. */
    suspend fun register() {
        val token = currentToken() ?: return

        val ok = ServiceLocator.notificationRepository.registerDevice(token)
        Log.d(TAG, if (ok) "Device registered for push." else "Device registration failed.")
    }

    /**
     * Called on sign-out, BEFORE the bearer token is discarded — the request
     * needs to be authenticated to identify whose device this is.
     */
    suspend fun unregister() {
        try {
            ServiceLocator.notificationRepository.clearDevice()
        } catch (e: Exception) {
            Log.d(TAG, "Could not clear device token: ${e.message}")
        }
    }

    /** Sends a token the service just received on rotation. */
    suspend fun registerToken(token: String) {
        ServiceLocator.notificationRepository.registerDevice(token)
    }

    private suspend fun currentToken(): String? = try {
        FirebaseMessaging.getInstance().token.await()
    } catch (e: Exception) {
        // No google-services.json, no Play Services, or no network. All three
        // mean the same thing here: this install cannot receive push today.
        Log.d(TAG, "No FCM token available (${e.javaClass.simpleName}). Push is inert.")
        null
    }
}
