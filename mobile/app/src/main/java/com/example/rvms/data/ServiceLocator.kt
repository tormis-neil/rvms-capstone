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

    lateinit var api: ApiService
        private set

    lateinit var sessionManager: SessionManager
        private set

    lateinit var authRepository: AuthRepository
        private set

    fun init(context: Context) {
        tokenStore = TokenStore(context.applicationContext)
        api = ApiClient.create { tokenStore.cachedToken }
        sessionManager = SessionManager(api, tokenStore)
        authRepository = AuthRepository(api, sessionManager)
    }
}
