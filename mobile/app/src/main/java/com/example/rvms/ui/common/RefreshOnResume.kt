package com.example.rvms.ui.common

import androidx.compose.runtime.Composable
import androidx.compose.runtime.rememberCoroutineScope
import androidx.lifecycle.compose.LifecycleResumeEffect
import kotlinx.coroutines.launch

/**
 * Refetches a screen's data every time the app comes back to the foreground
 * (FR-18, NFR-04).
 *
 * `LaunchedEffect(Unit)` runs once per composition, and returning from the
 * background does not recompose — so a screen loaded at 8am still showed 8am's
 * data at noon. That mattered most on the one path the driver actually uses:
 * the push notification's PendingIntent carries FLAG_ACTIVITY_CLEAR_TOP |
 * FLAG_ACTIVITY_SINGLE_TOP, which RESUMES the existing activity rather than
 * recreating it. So tapping "ABC-1234 is now Not Operational" opened the app
 * onto the old status, and only a full restart cleared it — the phone
 * contradicting the dashboard, which is exactly what NFR-04 forbids.
 *
 * Applied to every screen that reads server data, so all of them behave the
 * same way and none can be forgotten.
 *
 * @param key re-runs the effect when this changes, e.g. a signed-in user id.
 */
@Composable
fun RefreshOnResume(key: Any? = Unit, block: suspend () -> Unit) {
    val scope = rememberCoroutineScope()

    LifecycleResumeEffect(key) {
        scope.launch { block() }

        // Nothing to unwind: the fetch owns no listener or registration, and
        // the coroutine dies with the composition's scope.
        onPauseOrDispose { }
    }
}
