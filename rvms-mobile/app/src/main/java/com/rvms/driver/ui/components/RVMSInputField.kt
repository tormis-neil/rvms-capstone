package com.rvms.driver.ui.components

import androidx.compose.foundation.border
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.foundation.text.BasicTextField
import androidx.compose.foundation.text.KeyboardOptions
import androidx.compose.material3.Surface
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.setValue
import androidx.compose.ui.Modifier
import androidx.compose.ui.focus.onFocusChanged
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.unit.dp
import com.rvms.driver.ui.theme.PrimaryBlue
import com.rvms.driver.ui.theme.TextPrimary
import com.rvms.driver.ui.theme.TextSecondary
import com.rvms.driver.ui.theme.Typography

/**
 * RVMSInputField
 *
 * A standardized text input field following the RVMS Design System:
 * - 1px solid border (#CBD5E1) by default
 * - 2px solid border (#2563EB) on focus
 * - Label placed above the input
 */
@Composable
fun RVMSInputField(
    value: String,
    onValueChange: (String) -> Unit,
    label: String,
    modifier: Modifier = Modifier,
    placeholder: String = "",
    minLines: Int = 1,
    maxLines: Int = Int.MAX_VALUE,
    keyboardOptions: KeyboardOptions = KeyboardOptions.Default
) {
    var isFocused by remember { mutableStateOf(false) }

    val borderColor = if (isFocused) PrimaryBlue else Color(0xFFCBD5E1)
    val borderWidth = if (isFocused) 2.dp else 1.dp

    Column(modifier = modifier.fillMaxWidth()) {
        Text(
            text = label,
            style = Typography.labelMedium,
            color = TextSecondary
        )
        Spacer(modifier = Modifier.height(4.dp))
        
        Surface(
            modifier = Modifier
                .fillMaxWidth()
                .border(borderWidth, borderColor, RoundedCornerShape(8.dp)),
            shape = RoundedCornerShape(8.dp),
            color = Color.White
        ) {
            BasicTextField(
                value = value,
                onValueChange = onValueChange,
                modifier = Modifier
                    .fillMaxWidth()
                    .onFocusChanged { isFocused = it.isFocused }
                    .padding(horizontal = 16.dp, vertical = 14.dp),
                textStyle = Typography.bodyMedium.copy(color = TextPrimary),
                minLines = minLines,
                maxLines = maxLines,
                keyboardOptions = keyboardOptions,
                decorationBox = { innerTextField ->
                    if (value.isEmpty()) {
                        Text(
                            text = placeholder,
                            style = Typography.bodyMedium,
                            color = Color(0xFF94A3B8)
                        )
                    }
                    innerTextField()
                }
            )
        }
    }
}
