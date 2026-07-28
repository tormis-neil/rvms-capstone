package com.example.rvms

import android.app.Application
import com.example.rvms.data.ServiceLocator
import com.example.rvms.push.RvmsMessagingService

/**
 * Application entry point. Initialises the service locator (HTTP layer, token
 * store, session) once for the whole process.
 *
 * Registered via android:name=".RvmsApp" in the manifest.
 */
class RvmsApp : Application() {
    override fun onCreate() {
        super.onCreate()
        ServiceLocator.init(this)

        // Android 8.0+ silently drops any notification posted to a channel that
        // does not exist, so the channel is created before the first push can
        // arrive (FR-21).
        RvmsMessagingService.ensureChannel(this)
    }
}
