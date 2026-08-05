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
 * Holds the authenticated user, and knows how to bootstrap from a saved
 * token, record a successful login, and sign out. Introduced in R0 as the
 * networking foundation; every screen was moved onto it phase by phase —
 * Sign In/Up/Splash in R1, My Vehicle in R2, and so on — and the mock layer
 * it replaced is gone.
 *
 * The login/register flows themselves are thin and live in AuthRepository (R1),
 * which calls the same ApiService and then hands the result to [onLoggedIn].
 */
class SessionManager(
    private val api: ApiService,
    private val tokenStore: TokenStorage,
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

    /**
     * Fetch the authenticated user; on 401 the token is stale, so clear it.
     *
     * Never throws (R10 sub-task 1). Every repository already caught its own
     * network failures, but this did not — and it is called from three places
     * that run unattended: bootstrap() at cold start, and the resume refresh
     * on Home and Profile. On a dead connection the exception escaped into a
     * coroutine and took the app down, which is the one failure a driver in
     * the field cannot work around.
     *
     * A network failure returns false and leaves the cached user ALONE: being
     * briefly offline is no reason to forget who is signed in. Only a 401 —
     * the server actively rejecting the token — clears the session.
     */
    suspend fun loadMe(): Boolean = try {
        val response = api.me()
        if (response.isSuccessful) {
            _currentUser.value = response.body()?.user
            true
        } else {
            if (response.code() == 401) {
                tokenStore.clear()
                _currentUser.value = null
            }
            false
        }
    } catch (t: Throwable) {
        false
    }

    /**
     * Replace the cached user after a self-service edit (FR-04).
     *
     * The token is untouched — changing your own name or password does not
     * end the session, and every screen reading `currentUser` picks the new
     * values up on the next recomposition without a sign-out.
     */
    fun refreshUser(user: UserDto) {
        _currentUser.value = user
    }

    /** Record a successful login: persist the token and cache the user. */
    suspend fun onLoggedIn(token: String, user: UserDto) {
        tokenStore.save(token)
        _currentUser.value = user
    }

    /**
     * Best-effort token revoke on the server, then clear locally.
     *
     * **This is only HALF of signing out — call [AuthRepository.logout] instead.**
     * Signing out must also release this handset from push, and that happens in
     * the repository, while the bearer token is still valid.
     *
     * Deliberately NOT named `signOut()`: it was, and a call site reasonably
     * reached for the obvious name, skipped the push release, and left
     * `users.fcm_token` alive on the server — so a signed-out phone kept
     * receiving alerts, and a shared agency handset showed the previous
     * driver's. The name now says what it does rather than what a caller wants.
     */
    suspend fun revokeAndClear() {
        runCatching { api.logout() }
        tokenStore.clear()
        _currentUser.value = null
    }
}
