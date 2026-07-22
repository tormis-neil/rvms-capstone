package com.example.rvms

import android.app.Application
import com.example.rvms.data.ServiceLocator

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
    }
}
