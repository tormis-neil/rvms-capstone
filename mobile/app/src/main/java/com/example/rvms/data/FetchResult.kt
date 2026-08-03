package com.example.rvms.data

/**
 * The outcome of reading records from the API (R10 sub-task 1, NFR-04).
 *
 * Every read repository previously returned an empty list on failure, which
 * made "the server could not be reached" indistinguishable from "you have no
 * records". A driver in the field with no signal was therefore told
 * "No Vehicle Assigned" — not an error, but a confident wrong answer about the
 * one fact they most need. Chapter 1 already states the system requires a
 * connection, so the honest answer is that we could not ask.
 *
 * The distinction only matters for READS. Writes already return their own
 * result types (SubmitDamageResult, UpdateProfileResult…) because a failed
 * write always had to be reported.
 */
sealed interface FetchResult<out T> {
    data class Success<T>(val data: T) : FetchResult<T>

    /** Reachable but refused, or not reachable at all — both mean "no answer". */
    data class Failure(val message: String) : FetchResult<Nothing>

    /** The records, or null when the fetch failed. */
    val dataOrNull: T?
        get() = (this as? Success<T>)?.data

    val errorOrNull: String?
        get() = (this as? Failure)?.message

    companion object {
        /** Shown wherever a read fails; the screens offer pull-to-refresh to retry. */
        const val OFFLINE_MESSAGE =
            "Cannot reach the server. Check your connection and pull down to retry."
    }
}

/**
 * Runs an API read and maps every failure shape onto [FetchResult.Failure].
 *
 * `Throwable` rather than `Exception` for the same reason PushTokenRegistrar
 * catches Throwable: a JVM unit test with stubbed Android classes raises
 * Errors, not Exceptions, and a read must never be the thing that takes the
 * app down.
 */
inline fun <T> fetchCatching(block: () -> FetchResult<T>): FetchResult<T> = try {
    block()
} catch (t: Throwable) {
    FetchResult.Failure(FetchResult.OFFLINE_MESSAGE)
}
