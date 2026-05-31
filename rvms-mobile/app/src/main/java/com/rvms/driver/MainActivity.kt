package com.rvms.driver

import android.os.Bundle
import androidx.activity.ComponentActivity
import androidx.activity.compose.setContent
import androidx.activity.enableEdgeToEdge
import com.rvms.driver.ui.screens.MainScreen
import com.rvms.driver.ui.theme.RVMSDriverTheme

class MainActivity : ComponentActivity() {
    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        enableEdgeToEdge()
        setContent {
            RVMSDriverTheme {
                MainScreen()
            }
        }
    }
}
