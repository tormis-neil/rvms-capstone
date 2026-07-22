package com.example.rvms.data

import com.example.rvms.data.remote.ApiClient
import com.example.rvms.data.remote.ApiService
import com.example.rvms.data.remote.dto.AgencyDto
import com.example.rvms.data.remote.dto.LoginRequestDto
import com.example.rvms.data.remote.dto.RegisterRequestDto
import com.example.rvms.data.remote.dto.UserDto
import kotlinx.serialization.json.jsonArray
import kotlinx.serialization.json.jsonObject
import kotlinx.serialization.json.jsonPrimitive
import retrofit2.Response

/** Outcome of a login attempt, mapped from the API's HTTP contract. */
sealed interface LoginResult {
    data class Success(val user: UserDto) : LoginResult
    /** Bad credentials (422), non-active account (403 pending/rejected), or a network failure. */
    data class Error(val message: String) : LoginResult
}

/** Outcome of a driver self-registration attempt. */
sealed interface RegisterResult {
    /** Created, pending admin approval (201). */
    data object Success : RegisterResult
    /** Validation failure (422, e.g. email already taken) or a network failure. */
    data class Error(val message: String) : RegisterResult
}

/**
 * The single entry point the auth screens use (Sign In / Sign Up / Sign Out /
 * splash routing). It calls [ApiService] and translates the API's status-code
 * contract into simple result types the Compose UI can render:
 *
 *   login:    200 success · 422 bad credentials · 403 pending/rejected reason
 *   register: 201 success (pending) · 422 validation (e.g. duplicate email)
 *
 * On a successful login the token + user are handed to [SessionManager] so the
 * bearer token is persisted and every later request is authenticated.
 */
class AuthRepository(
    private val api: ApiService,
    private val session: SessionManager,
) {
    suspend fun login(email: String, password: String): LoginResult = try {
        val response = api.login(LoginRequestDto(email.trim(), password))
        if (response.isSuccessful) {
            val body = response.body()!!
            session.onLoggedIn(body.token, body.user)
            LoginResult.Success(body.user)
        } else {
            LoginResult.Error(
                errorMessage(response, fallback = "Unable to sign in. Please try again."),
            )
        }
    } catch (e: Exception) {
        LoginResult.Error(NETWORK_ERROR)
    }

    suspend fun register(
        agencyId: Long,
        name: String,
        email: String,
        password: String,
        passwordConfirmation: String,
        licenseNumber: String? = null,
        licenseExpiryDate: String? = null,
    ): RegisterResult = try {
        val response = api.register(
            RegisterRequestDto(
                agencyId = agencyId,
                name = name.trim(),
                email = email.trim(),
                password = password,
                passwordConfirmation = passwordConfirmation,
                licenseNumber = licenseNumber?.ifBlank { null },
                licenseExpiryDate = licenseExpiryDate?.ifBlank { null },
            ),
        )
        if (response.isSuccessful) {
            RegisterResult.Success
        } else {
            RegisterResult.Error(
                errorMessage(response, fallback = "Unable to register. Please try again."),
            )
        }
    } catch (e: Exception) {
        RegisterResult.Error(NETWORK_ERROR)
    }

    /** Agency directory for the Sign Up dropdown; empty list on any failure. */
    suspend fun agencies(): List<AgencyDto> = try {
        val response = api.agencies()
        if (response.isSuccessful) response.body()?.data.orEmpty() else emptyList()
    } catch (e: Exception) {
        emptyList()
    }

    /** Sign out: best-effort server revoke + clear the local token. */
    suspend fun logout() = session.signOut()

    /**
     * Pull a human message out of a Laravel error body. Validation errors (422)
     * arrive as `{ "message": ..., "errors": { field: [msg, ...] } }`; other
     * errors (e.g. 403 pending/rejected) as `{ "message": ... }`. We prefer the
     * first field error, then the top-level message, then the fallback.
     */
    private fun errorMessage(response: Response<*>, fallback: String): String {
        val raw = response.errorBody()?.string().orEmpty()
        if (raw.isBlank()) return fallback
        return try {
            val obj = ApiClient.json.parseToJsonElement(raw).jsonObject
            val firstFieldError = obj["errors"]?.jsonObject
                ?.values?.firstOrNull()?.jsonArray
                ?.firstOrNull()?.jsonPrimitive?.content
            firstFieldError
                ?: obj["message"]?.jsonPrimitive?.content
                ?: fallback
        } catch (e: Exception) {
            fallback
        }
    }

    private companion object {
        const val NETWORK_ERROR = "Cannot reach the server. Check your connection and try again."
    }
}
