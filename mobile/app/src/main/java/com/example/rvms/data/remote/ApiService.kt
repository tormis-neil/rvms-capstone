package com.example.rvms.data.remote

import com.example.rvms.data.remote.dto.AgencyListDto
import com.example.rvms.data.remote.dto.LoginRequestDto
import com.example.rvms.data.remote.dto.LoginResponseDto
import com.example.rvms.data.remote.dto.MessageDto
import com.example.rvms.data.remote.dto.RegisterRequestDto
import com.example.rvms.data.remote.dto.UserEnvelopeDto
import retrofit2.Response
import retrofit2.http.Body
import retrofit2.http.GET
import retrofit2.http.POST

/**
 * The RVMS REST API (all endpoints under /api/v1, base URL set in ApiClient).
 *
 * R0 (networking foundation) covers auth only — login/register/logout/me — the
 * "electricity" the rest of the app runs on. Feature endpoints (my-vehicle,
 * inspections, damage reports, notifications) are added to this interface in
 * their own phases (R2, R3, R4, R7).
 *
 * Calls return retrofit2.Response<T> so repositories can branch on the HTTP
 * status the API uses as its contract: 200/201 success, 401 bad credentials,
 * 403 non-active account (with a reason), 422 validation errors.
 */
interface ApiService {

    @POST("login")
    suspend fun login(@Body body: LoginRequestDto): Response<LoginResponseDto>

    @POST("register")
    suspend fun register(@Body body: RegisterRequestDto): Response<UserEnvelopeDto>

    /** Public agency directory for the Sign Up dropdown (FR-03). */
    @GET("agencies")
    suspend fun agencies(): Response<AgencyListDto>

    @POST("logout")
    suspend fun logout(): Response<MessageDto>

    @GET("me")
    suspend fun me(): Response<UserEnvelopeDto>
}
