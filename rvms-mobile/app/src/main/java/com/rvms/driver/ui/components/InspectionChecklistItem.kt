package com.rvms.driver.ui.components

import androidx.compose.animation.AnimatedVisibility
import androidx.compose.foundation.BorderStroke
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.width
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material3.ButtonDefaults
import androidx.compose.material3.Card
import androidx.compose.material3.CardDefaults
import androidx.compose.material3.OutlinedButton
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.unit.dp
import com.rvms.driver.ui.theme.PrimaryBlue
import com.rvms.driver.ui.theme.TextPrimary
import com.rvms.driver.ui.theme.Typography

enum class InspectionStatus {
    UNCHECKED, OK, ISSUE
}

/**
 * InspectionChecklistItem
 *
 * A reusable row for the BLOWBAGETS checklist.
 * Displays the item name (e.g. "Brakes"), and lets the driver select OK or ISSUE.
 * If ISSUE is selected, it expands an input field for remarks.
 */
@Composable
fun InspectionChecklistItem(
    itemName: String,
    status: InspectionStatus,
    onStatusChange: (InspectionStatus) -> Unit,
    remarks: String,
    onRemarksChange: (String) -> Unit,
    modifier: Modifier = Modifier
) {
    Card(
        modifier = modifier.fillMaxWidth(),
        shape = RoundedCornerShape(8.dp),
        colors = CardDefaults.cardColors(containerColor = Color.White),
        border = BorderStroke(1.dp, Color(0xFFE2E8F0)),
        elevation = CardDefaults.cardElevation(defaultElevation = 0.dp)
    ) {
        Column(modifier = Modifier.padding(16.dp)) {
            Row(
                modifier = Modifier.fillMaxWidth(),
                verticalAlignment = Alignment.CenterVertically,
                horizontalArrangement = Arrangement.SpaceBetween
            ) {
                Text(
                    text = itemName,
                    style = Typography.titleMedium,
                    color = TextPrimary,
                    modifier = Modifier.weight(1f)
                )
                
                Row {
                    SelectionButton(
                        text = "OK",
                        isSelected = status == InspectionStatus.OK,
                        selectedColor = Color(0xFF22C55E), // Operational Green
                        onClick = { onStatusChange(InspectionStatus.OK) }
                    )
                    Spacer(modifier = Modifier.width(8.dp))
                    SelectionButton(
                        text = "Issue",
                        isSelected = status == InspectionStatus.ISSUE,
                        selectedColor = Color(0xFFEF4444), // Not Operational Red
                        onClick = { onStatusChange(InspectionStatus.ISSUE) }
                    )
                }
            }
            
            AnimatedVisibility(visible = status == InspectionStatus.ISSUE) {
                Column(modifier = Modifier.padding(top = 16.dp)) {
                    RVMSInputField(
                        value = remarks,
                        onValueChange = onRemarksChange,
                        label = "Remarks / Issue Details",
                        placeholder = "Describe the issue found with the $itemName...",
                        minLines = 2
                    )
                }
            }
        }
    }
}

@Composable
private fun SelectionButton(
    text: String,
    isSelected: Boolean,
    selectedColor: Color,
    onClick: () -> Unit
) {
    val backgroundColor = if (isSelected) selectedColor.copy(alpha = 0.15f) else Color.Transparent
    val contentColor = if (isSelected) selectedColor else Color(0xFF64748B)
    val borderColor = if (isSelected) selectedColor else Color(0xFFCBD5E1)

    OutlinedButton(
        onClick = onClick,
        colors = ButtonDefaults.outlinedButtonColors(
            containerColor = backgroundColor,
            contentColor = contentColor
        ),
        border = BorderStroke(1.dp, borderColor),
        shape = RoundedCornerShape(6.dp)
    ) {
        Text(text = text, style = Typography.labelLarge)
    }
}
