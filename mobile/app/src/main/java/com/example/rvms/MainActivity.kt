package com.example.rvms

import android.Manifest
import android.content.pm.PackageManager
import android.os.Build
import android.os.Bundle
import androidx.activity.ComponentActivity
import androidx.activity.compose.setContent
import androidx.activity.enableEdgeToEdge
import androidx.activity.result.contract.ActivityResultContracts
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Surface
import androidx.compose.ui.Modifier
import androidx.core.content.ContextCompat
import com.example.rvms.theme.RVMSTheme

class MainActivity : ComponentActivity() {

  /**
   * Android 13 (API 33) and above require POST_NOTIFICATIONS to be granted at
   * runtime. Without it every push is dropped SILENTLY, which is
   * indistinguishable from a broken backend — hence asking up front (FR-21).
   *
   * Declining is not an error state: the notification is still stored and the
   * driver reads it in the app's Notifications tab. Only the tray banner is lost.
   */
  private val requestNotificationPermission =
    registerForActivityResult(ActivityResultContracts.RequestPermission()) { /* either way, carry on */ }

  override fun onCreate(savedInstanceState: Bundle?) {
    super.onCreate(savedInstanceState)

    askForNotificationPermissionIfNeeded()

    enableEdgeToEdge()
    setContent {
      RVMSTheme { Surface(modifier = Modifier.fillMaxSize(), color = MaterialTheme.colorScheme.background) { MainNavigation() } }
    }
  }

  private fun askForNotificationPermissionIfNeeded() {
    if (Build.VERSION.SDK_INT < Build.VERSION_CODES.TIRAMISU) return

    val granted = ContextCompat.checkSelfPermission(
      this,
      Manifest.permission.POST_NOTIFICATIONS,
    ) == PackageManager.PERMISSION_GRANTED

    if (!granted) {
      requestNotificationPermission.launch(Manifest.permission.POST_NOTIFICATIONS)
    }
  }
}
