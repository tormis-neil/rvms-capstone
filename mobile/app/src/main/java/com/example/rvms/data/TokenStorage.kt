package com.example.rvms.data

/**
 * Abstraction over bearer-token persistence so the session layer can be unit
 * tested without Android's DataStore. Production uses [TokenStore] (DataStore);
 * tests use an in-memory fake.
 *
 * [cachedToken] is the synchronous mirror the OkHttp auth interceptor reads.
 */
interface TokenStorage {
    val cachedToken: String?

    /** Load the persisted token into [cachedToken] (call once at startup). */
    suspend fun prime()

    /** Persist the token and update [cachedToken]. */
    suspend fun save(token: String)

    /** Read the persisted token (null if none). */
    suspend fun read(): String?

    /** Clear the token on sign-out. */
    suspend fun clear()
}
