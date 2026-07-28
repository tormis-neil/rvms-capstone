package com.example.rvms.push

import android.app.NotificationChannel
import android.app.NotificationManager
import android.app.PendingIntent
import android.content.Context
import android.content.Intent
import androidx.core.app.NotificationCompat
import androidx.core.app.NotificationManagerCompat
import com.example.rvms.MainActivity
import com.example.rvms.R
import com.google.firebase.messaging.FirebaseMessagingService
import com.google.firebase.messaging.RemoteMessage
import kotlinx.coroutines.CoroutineScope
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.launch

/**
 * Receives pushes from FCM (FR-21).
 *
 * The backend sends a `notification` block, so Android draws the tray item
 * itself while the app is in the background. This service exists for the two
 * cases Android will not handle:
 *
 *   - the app is in the FOREGROUND, where a notification-block message is
 *     delivered here instead of to the tray, so nothing would appear at all;
 *   - the token ROTATES, which happens on reinstall, restore-to-new-device and
 *     occasionally at Firebase's discretion — the backend would keep pushing to
 *     a dead token until the driver signs in again.
 */
class RvmsMessagingService : FirebaseMessagingService() {

    override fun onMessageReceived(message: RemoteMessage) {
        val title = message.notification?.title
            ?: message.data["title"]
            ?: return
        val body = message.notification?.body
            ?: message.data["body"]
            ?: ""

        showNotification(title, body)
    }

    /**
     * Firebase issued a new token for this install. Send it straight away; the
     * request is skipped by the repository if there is no session, and the next
     * sign-in registers it anyway.
     */
    override fun onNewToken(token: String) {
        CoroutineScope(Dispatchers.IO).launch {
            PushTokenRegistrar.registerToken(token)
        }
    }

    private fun showNotification(title: String, body: String) {
        ensureChannel(this)

        val intent = Intent(this, MainActivity::class.java).apply {
            flags = Intent.FLAG_ACTIVITY_CLEAR_TOP or Intent.FLAG_ACTIVITY_SINGLE_TOP
        }
        val pendingIntent = PendingIntent.getActivity(
            this,
            0,
            intent,
            PendingIntent.FLAG_UPDATE_CURRENT or PendingIntent.FLAG_IMMUTABLE,
        )

        val notification = NotificationCompat.Builder(this, CHANNEL_ID)
            .setSmallIcon(R.mipmap.ic_launcher)
            .setContentTitle(title)
            .setContentText(body)
            .setStyle(NotificationCompat.BigTextStyle().bigText(body))
            .setPriority(NotificationCompat.PRIORITY_HIGH)
            .setAutoCancel(true)
            .setContentIntent(pendingIntent)
            .build()

        try {
            NotificationManagerCompat.from(this)
                .notify(System.currentTimeMillis().toInt(), notification)
        } catch (e: SecurityException) {
            // POST_NOTIFICATIONS not granted (Android 13+). The notification is
            // still stored server-side and readable in the app's inbox.
        }
    }

    companion object {
        /** Must match the channel_id the backend sets on its android payload. */
        const val CHANNEL_ID = "rvms_alerts"

        /**
         * Android 8.0+ drops any notification posted to a channel that does not
         * exist, silently. Created here and on app start so neither path misses it.
         */
        fun ensureChannel(context: Context) {
            val channel = NotificationChannel(
                CHANNEL_ID,
                "Vehicle alerts",
                NotificationManager.IMPORTANCE_HIGH,
            ).apply {
                description = "Vehicle status updates and preventive maintenance reminders."
            }

            context.getSystemService(NotificationManager::class.java)
                ?.createNotificationChannel(channel)
        }
    }
}
