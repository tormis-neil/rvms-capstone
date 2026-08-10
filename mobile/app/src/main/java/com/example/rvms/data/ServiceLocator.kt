package com.example.rvms.data

import android.content.Context
import com.example.rvms.data.remote.ApiClient
import com.example.rvms.data.remote.ApiService

/**
 * Minimal, framework-free dependency wiring for the app.
 *
 * The app is small enough that a hand-rolled service locator is clearer than a
 * DI framework. It is initialised once from [com.example.rvms.RvmsApp.onCreate]
 * and exposes the single shared HTTP layer + session so ViewModels/repositories
 * (added per phase) can reach them without passing Context around.
 *
 * The auth interceptor reads the token lazily as `tokenStore.cachedToken`, so
 * the same [ApiService] instance always sends the current token.
 */
object ServiceLocator {

    lateinit var tokenStore: TokenStore
        private set

    /** Which server the app talks to — switchable at runtime (2026-08). */
    lateinit var serverUrlStore: ServerUrlStore
        private set

    lateinit var api: ApiService
        private set

    lateinit var sessionManager: SessionManager
        private set

    lateinit var authRepository: AuthRepository
        private set

    lateinit var vehicleRepository: VehicleRepository
        private set

    lateinit var inspectionRepository: InspectionRepository
        private set

    lateinit var damageRepository: DamageRepository
        private set

    lateinit var notificationRepository: NotificationRepository
        private set

    /**
     * The current server address, or the built-in default when the locator has
     * not been initialised — which is the case inside a Compose @Preview, where
     * touching a lateinit property would throw at composition time.
     */
    fun serverOriginOrDefault(): String =
        if (::serverUrlStore.isInitialized) serverUrlStore.cachedOrigin else ServerUrlStore.DEFAULT_ORIGIN

    fun init(context: Context) {
        tokenStore = TokenStore(context.applicationContext)
        serverUrlStore = ServerUrlStore(context.applicationContext)
        api = ApiClient.create(
            tokenProvider = { tokenStore.cachedToken },
            originProvider = { serverUrlStore.cachedOrigin },
        )
        sessionManager = SessionManager(api, tokenStore)
        authRepository = AuthRepository(api, sessionManager)
        vehicleRepository = VehicleRepository(api)
        inspectionRepository = InspectionRepository(api)
        damageRepository = DamageRepository(api)
        notificationRepository = NotificationRepository(api)
    }
}
