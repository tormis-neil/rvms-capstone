package com.example.rvms.push

import android.util.Log
import com.example.rvms.data.ServiceLocator
import com.google.firebase.messaging.FirebaseMessaging
import kotlinx.coroutines.tasks.await

/**
 * Registers this device with the backend so it can receive pushes (FR-21).
 *
 * Every entry point swallows everything, including [Error], because push is an
 * extra on top of the stored notification the driver can always read in the
 * app. Nothing here may ever fail a sign-in or a sign-out — an earlier version
 * let a Firebase failure escape into AuthRepository, where a SUCCESSFUL login
 * was reported to the driver as "cannot reach the server".
 *
 * `Throwable` rather than `Exception` on purpose: a build with no Firebase, or
 * a JVM unit test where the Android framework classes are stubs, raises
 * NoClassDefFoundError and ExceptionInInitializerError, neither of which is an
 * Exception.
 */
object PushTokenRegistrar {

    private const val TAG = "RvmsPush"

    /** Called after a successful sign-in, and from the service on token rotation. */
    suspend fun register() {
        val token = currentToken() ?: return
        registerToken(token)
    }

    /**
     * Called on sign-out, BEFORE the bearer token is discarded — the request
     * needs to be authenticated to identify whose device this is.
     */
    suspend fun unregister() {
        runCatchingSilently { ServiceLocator.notificationRepository.clearDevice() }
    }

    /** Sends a token the service just received on rotation. */
    suspend fun registerToken(token: String) {
        runCatchingSilently { ServiceLocator.notificationRepository.registerDevice(token) }
    }

    /**
     * Null whenever this install cannot receive push today: no
     * google-services.json, no Play Services, no network, or a unit-test JVM.
     * All of them mean the same thing to the caller.
     */
    private suspend fun currentToken(): String? = try {
        FirebaseMessaging.getInstance().token.await()
    } catch (t: Throwable) {
        log("No FCM token available (${t.javaClass.simpleName}). Push is inert.")
        null
    }

    private inline fun runCatchingSilently(block: () -> Unit) {
        try {
            block()
        } catch (t: Throwable) {
            log("Push token call failed (${t.javaClass.simpleName}).")
        }
    }

    /** android.util.Log is a throwing stub under plain JVM unit tests. */
    private fun log(message: String) {
        try {
            Log.d(TAG, message)
        } catch (t: Throwable) {
            // Nothing to do — this is diagnostics, not behaviour.
        }
    }
}
