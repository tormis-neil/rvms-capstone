package com.rvms.driver.model

data class Notification(
    val id: String,
    val title: String,
    val message: String,
    val timestamp: String,
    val isRead: Boolean,
    val type: NotificationType
)

enum class NotificationType {
    INFO, WARNING, SUCCESS
}

val MockNotifications = listOf(
    Notification(
        id = "1",
        title = "Maintenance Due",
        message = "Sched PM for SDA-1234 tomorrow at 08:00 AM.",
        timestamp = "5 mins ago",
        isRead = false,
        type = NotificationType.WARNING
    ),
    Notification(
        id = "2",
        title = "Status Updated",
        message = "Vehicle SDA-1234 marked as OPERATIONAL.",
        timestamp = "1 hour ago",
        isRead = false,
        type = NotificationType.INFO
    ),
    Notification(
        id = "3",
        title = "Defect Report Acknowledged",
        message = "Your defect report for the broken taillight has been received by the dispatcher.",
        timestamp = "Yesterday",
        isRead = true,
        type = NotificationType.SUCCESS
    )
)
