package com.example.rvms.data

import com.example.rvms.data.remote.ApiService
import com.example.rvms.data.remote.dto.UserDto
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow

/**
 * The REAL driver session — backed by the API's `/me` and the persisted bearer
 * token (TokenStore), replacing the prototype's in-memory [Session] singleton.
 *
 * R0 scope (networking foundation, no screen wired yet): this holds the
 * authenticated user and knows how to bootstrap from a saved token, record a
 * successful login, and sign out. The screens are switched over to read this
 * (instead of mock SampleData/Session) in their own phases — Sign In/Up/Splash
 * in R1, My Vehicle in R2, and so on — so the mock layer is intentionally left
 * in place for now and nothing in the Compose UI changes this phase.
 *
 * The login/register flows themselves are thin and live in AuthRepository (R1),
 * which calls the same ApiService and then hands the result to [onLoggedIn].
 */
class SessionManager(
    private val api: ApiService,
    private val tokenStore: TokenStore,
) {
    private val _currentUser = MutableStateFlow<UserDto?>(null)
    val currentUser: StateFlow<UserDto?> = _currentUser.asStateFlow()

    /** True once a token is persisted (used by the Splash router in R1). */
    val hasToken: Boolean
        get() = tokenStore.cachedToken != null

    /**
     * Startup routing: load the saved token into memory and, if present, verify
     * it by fetching `/me`. Returns true when the session is authenticated.
     */
    suspend fun bootstrap(): Boolean {
        tokenStore.prime()
        if (tokenStore.cachedToken == null) return false
        return loadMe()
    }

    /** Fetch the authenticated user; on 401 the token is stale, so clear it. */
    suspend fun loadMe(): Boolean {
        val response = api.me()
        return if (response.isSuccessful) {
            _currentUser.value = response.body()?.user
            true
        } else {
            if (response.code() == 401) {
                tokenStore.clear()
                _currentUser.value = null
            }
            false
        }
    }

    /** Record a successful login: persist the token and cache the user. */
    suspend fun onLoggedIn(token: String, user: UserDto) {
        tokenStore.save(token)
        _currentUser.value = user
    }

    /** Best-effort token revoke on the server, then clear locally. */
    suspend fun signOut() {
        runCatching { api.logout() }
        tokenStore.clear()
        _currentUser.value = null
    }
}
