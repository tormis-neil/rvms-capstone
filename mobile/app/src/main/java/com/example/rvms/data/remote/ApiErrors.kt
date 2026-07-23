package com.example.rvms.data.remote

import kotlinx.serialization.json.jsonArray
import kotlinx.serialization.json.jsonObject
import kotlinx.serialization.json.jsonPrimitive
import retrofit2.Response

/**
 * Pull a human message out of a Laravel error body. Validation errors (422)
 * arrive as `{ "message": ..., "errors": { field: [msg, ...] } }`; other
 * errors (e.g. 403 with a reason) as `{ "message": ... }`. Prefer the first
 * field error, then the top-level message, then the fallback.
 */
fun laravelErrorMessage(response: Response<*>, fallback: String): String {
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
