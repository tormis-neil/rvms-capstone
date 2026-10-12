-- =====================================================================
-- Rescue Vehicle Management System (RVMS)
-- Master Database Schema DDL Script (MySQL 8.0+)
-- Source of Truth: Chapter 4 Data Dictionary (Tables 5 to 15)
-- Optimized for MySQL Workbench: Reverse Engineer MySQL Create Script
-- =====================================================================

DROP DATABASE IF EXISTS `rvms_db`;
CREATE DATABASE `rvms_db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `rvms_db`;

-- Temporarily disable foreign key checks for clean creation order
SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------------------------------
-- Table 5: agencies
-- Multi-tenancy anchor for BFP, PNP, CDRRMO, and CHO
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `agencies`;
CREATE TABLE `agencies` (
    `id` INT AUTO_INCREMENT NOT NULL,
    `code` VARCHAR(10) NOT NULL COMMENT 'BFP, PNP, CDRRMO, or CHO',
    `name` VARCHAR(255) NOT NULL COMMENT 'Full official agency name',
    `location` VARCHAR(255) NULL COMMENT 'Office station location',
    `contact_number` VARCHAR(50) NULL,
    `email` VARCHAR(255) NULL,
    `logo_path` VARCHAR(255) NULL,
    `license_expiry_warning_days` SMALLINT NOT NULL DEFAULT 30 COMMENT 'Configurable warning threshold',
    `created_at` DATETIME NULL,
    `updated_at` DATETIME NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_agencies_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Table 6: users
-- Accounts for both Agency Administrators and Authorized Drivers
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
    `id` INT AUTO_INCREMENT NOT NULL,
    `agency_id` INT NOT NULL,
    `role` ENUM('admin', 'driver') NOT NULL,
    `name` VARCHAR(255) NOT NULL,
    `email` VARCHAR(255) NOT NULL,
    `password` VARCHAR(255) NOT NULL,
    `status` ENUM('pending', 'active', 'rejected') NOT NULL DEFAULT 'pending',
    `license_number` VARCHAR(50) NULL,
    `license_expiry_date` DATE NULL,
    `nc_ii_number` VARCHAR(50) NULL,
    `nc_ii_expiry_date` DATE NULL,
    `fcm_token` VARCHAR(255) NULL,
    `email_verified_at` DATETIME NULL,
    `remember_token` VARCHAR(100) NULL,
    `created_at` DATETIME NULL,
    `updated_at` DATETIME NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_users_email` (`email`),
    INDEX `idx_users_agency_id` (`agency_id`),
    CONSTRAINT `fk_users_agency` FOREIGN KEY (`agency_id`) 
        REFERENCES `agencies` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Table 7: vehicles
-- Fleet inventory with single shared operational status
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `vehicles`;
CREATE TABLE `vehicles` (
    `id` INT AUTO_INCREMENT NOT NULL,
    `agency_id` INT NOT NULL,
    `assigned_driver_id` INT NULL COMMENT 'Primary assigned driver',
    `secondary_driver_id` INT NULL COMMENT 'Optional secondary/backup driver',
    `type` VARCHAR(100) NOT NULL COMMENT 'Fire Truck, Ambulance, Patrol Car, etc.',
    `plate_number` VARCHAR(20) NOT NULL,
    `make` VARCHAR(100) NOT NULL,
    `model` VARCHAR(100) NOT NULL,
    `engine_number` VARCHAR(50) NULL,
    `chassis_number` VARCHAR(50) NULL,
    `current_mileage` INT NOT NULL DEFAULT 0,
    `status` ENUM('Operational', 'Dispatched', 'Not Operational', 'Under Preventive Maintenance') NOT NULL DEFAULT 'Operational',
    `created_at` DATETIME NULL,
    `updated_at` DATETIME NULL,
    PRIMARY KEY (`id`),
    INDEX `idx_vehicles_agency_id` (`agency_id`),
    INDEX `idx_vehicles_assigned_driver_id` (`assigned_driver_id`),
    INDEX `idx_vehicles_secondary_driver_id` (`secondary_driver_id`),
    CONSTRAINT `fk_vehicles_agency` FOREIGN KEY (`agency_id`) 
        REFERENCES `agencies` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_vehicles_assigned_driver` FOREIGN KEY (`assigned_driver_id`) 
        REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_vehicles_secondary_driver` FOREIGN KEY (`secondary_driver_id`) 
        REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Table 8: inspection_checklist_items
-- Catalog of standardized BLOWBAGETS criteria (shared reference)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `inspection_checklist_items`;
CREATE TABLE `inspection_checklist_items` (
    `id` INT AUTO_INCREMENT NOT NULL,
    `name` VARCHAR(100) NOT NULL COMMENT 'Battery, Brakes, Fire Pump, etc.',
    `is_bfp_only` BOOLEAN NOT NULL DEFAULT FALSE COMMENT 'Hydraulic System & Fire Pump',
    `sort_order` SMALLINT NOT NULL DEFAULT 0,
    `created_at` DATETIME NULL,
    `updated_at` DATETIME NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Table 9: inspections
-- Daily pre-trip BLOWBAGETS checklist submissions
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `inspections`;
CREATE TABLE `inspections` (
    `id` INT AUTO_INCREMENT NOT NULL,
    `agency_id` INT NOT NULL,
    `vehicle_id` INT NOT NULL,
    `driver_id` INT NOT NULL COMMENT 'Driver who performed inspection',
    `inspection_date` DATE NOT NULL,
    `review_status` ENUM('Pending', 'Reviewed') NOT NULL DEFAULT 'Pending',
    `reviewed_by` INT NULL COMMENT 'Administrator who reviewed inspection',
    `reviewed_at` DATETIME NULL,
    `created_at` DATETIME NULL,
    `updated_at` DATETIME NULL,
    PRIMARY KEY (`id`),
    INDEX `idx_inspections_agency_id` (`agency_id`),
    INDEX `idx_inspections_vehicle_id` (`vehicle_id`),
    INDEX `idx_inspections_driver_id` (`driver_id`),
    INDEX `idx_inspections_reviewed_by` (`reviewed_by`),
    CONSTRAINT `fk_inspections_agency` FOREIGN KEY (`agency_id`) 
        REFERENCES `agencies` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_inspections_vehicle` FOREIGN KEY (`vehicle_id`) 
        REFERENCES `vehicles` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_inspections_driver` FOREIGN KEY (`driver_id`) 
        REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_inspections_reviewer` FOREIGN KEY (`reviewed_by`) 
        REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Table 10: inspection_items
-- Granular assessment result for each checklist item in an inspection
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `inspection_items`;
CREATE TABLE `inspection_items` (
    `id` INT AUTO_INCREMENT NOT NULL,
    `inspection_id` INT NOT NULL,
    `checklist_item_id` INT NOT NULL,
    `status` ENUM('OK', 'Has Issue') NOT NULL,
    `remarks` TEXT NULL COMMENT 'Mandatory when status is Has Issue',
    PRIMARY KEY (`id`),
    INDEX `idx_inspection_items_inspection_id` (`inspection_id`),
    INDEX `idx_inspection_items_checklist_item_id` (`checklist_item_id`),
    CONSTRAINT `fk_inspection_items_inspection` FOREIGN KEY (`inspection_id`) 
        REFERENCES `inspections` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_inspection_items_checklist_item` FOREIGN KEY (`checklist_item_id`) 
        REFERENCES `inspection_checklist_items` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Table 11: damage_reports
-- Incident and physical defect reports filed by drivers
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `damage_reports`;
CREATE TABLE `damage_reports` (
    `id` INT AUTO_INCREMENT NOT NULL,
    `agency_id` INT NOT NULL,
    `vehicle_id` INT NOT NULL,
    `driver_id` INT NOT NULL COMMENT 'Driver who submitted report',
    `nature_of_damage` TEXT NOT NULL,
    `suspected_parts` VARCHAR(255) NULL,
    `photo_path` VARCHAR(255) NULL,
    `date_reported` DATE NOT NULL,
    `status` ENUM('Pending', 'Reviewed') NOT NULL DEFAULT 'Pending',
    `reviewed_by` INT NULL COMMENT 'Administrator who assessed report',
    `reviewed_at` DATETIME NULL,
    `created_at` DATETIME NULL,
    `updated_at` DATETIME NULL,
    PRIMARY KEY (`id`),
    INDEX `idx_damage_reports_agency_id` (`agency_id`),
    INDEX `idx_damage_reports_vehicle_id` (`vehicle_id`),
    INDEX `idx_damage_reports_driver_id` (`driver_id`),
    INDEX `idx_damage_reports_reviewed_by` (`reviewed_by`),
    CONSTRAINT `fk_damage_reports_agency` FOREIGN KEY (`agency_id`) 
        REFERENCES `agencies` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_damage_reports_vehicle` FOREIGN KEY (`vehicle_id`) 
        REFERENCES `vehicles` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_damage_reports_driver` FOREIGN KEY (`driver_id`) 
        REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_damage_reports_reviewer` FOREIGN KEY (`reviewed_by`) 
        REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Table 12: repair_logs
-- Corrective repairs and service maintenance history
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `repair_logs`;
CREATE TABLE `repair_logs` (
    `id` INT AUTO_INCREMENT NOT NULL,
    `agency_id` INT NOT NULL,
    `vehicle_id` INT NOT NULL,
    `driver_id` INT NULL COMMENT 'Assigned driver at time of repair',
    `repair_date` DATE NOT NULL,
    `scope_of_work` TEXT NOT NULL,
    `parts_replaced` TEXT NULL,
    `cost` DECIMAL(10,2) NULL,
    `repair_source` ENUM('Internal Office', 'GSO Motorpool', 'External Repair Shop') NOT NULL,
    `external_shop_name` VARCHAR(255) NULL,
    `remarks` TEXT NULL,
    `receipt_path` VARCHAR(255) NULL,
    `created_at` DATETIME NULL,
    `updated_at` DATETIME NULL,
    PRIMARY KEY (`id`),
    INDEX `idx_repair_logs_agency_id` (`agency_id`),
    INDEX `idx_repair_logs_vehicle_id` (`vehicle_id`),
    INDEX `idx_repair_logs_driver_id` (`driver_id`),
    CONSTRAINT `fk_repair_logs_agency` FOREIGN KEY (`agency_id`) 
        REFERENCES `agencies` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_repair_logs_vehicle` FOREIGN KEY (`vehicle_id`) 
        REFERENCES `vehicles` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_repair_logs_driver` FOREIGN KEY (`driver_id`) 
        REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Table 13: pm_schedules
-- Preventive maintenance plans (Mileage-based or Time-based)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `pm_schedules`;
CREATE TABLE `pm_schedules` (
    `id` INT AUTO_INCREMENT NOT NULL,
    `agency_id` INT NOT NULL,
    `vehicle_id` INT NOT NULL,
    `service_target` VARCHAR(255) NOT NULL COMMENT 'e.g. Oil Change, Brake Service',
    `schedule_document_path` VARCHAR(255) NULL,
    `pm_type` ENUM('Mileage-Based', 'Time-Based') NOT NULL,
    `interval_km` INT NULL,
    `last_pm_mileage` INT NULL,
    `due_mileage` INT NULL,
    `due_date` DATE NULL,
    `due_soon_threshold_km` INT NULL,
    `due_soon_threshold_days` SMALLINT NULL,
    `status` ENUM('Upcoming', 'Due Soon', 'Due', 'Completed') NOT NULL DEFAULT 'Upcoming',
    `date_serviced` DATE NULL,
    `completion_mileage` INT NULL,
    `completion_repair_source` ENUM('Internal Office', 'GSO Motorpool', 'External Repair Shop') NULL,
    `completion_external_shop_name` VARCHAR(255) NULL,
    `completion_parts_replaced` TEXT NULL,
    `completion_remarks` TEXT NULL,
    `completion_receipt_path` VARCHAR(255) NULL,
    `created_at` DATETIME NULL,
    `updated_at` DATETIME NULL,
    PRIMARY KEY (`id`),
    INDEX `idx_pm_schedules_agency_id` (`agency_id`),
    INDEX `idx_pm_schedules_vehicle_id` (`vehicle_id`),
    CONSTRAINT `fk_pm_schedules_agency` FOREIGN KEY (`agency_id`) 
        REFERENCES `agencies` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_pm_schedules_vehicle` FOREIGN KEY (`vehicle_id`) 
        REFERENCES `vehicles` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Table 14: dispatches
-- Mission dispatch logs, vehicle trip tracking, and odometer updates
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `dispatches`;
CREATE TABLE `dispatches` (
    `id` INT AUTO_INCREMENT NOT NULL,
    `agency_id` INT NOT NULL,
    `vehicle_id` INT NOT NULL,
    `driver_id` INT NOT NULL COMMENT 'Primary dispatched driver',
    `second_driver_id` INT NULL COMMENT 'Optional secondary driver',
    `mission_type` ENUM('Fire Response', 'Medical Response', 'Rescue Operation', 'Patrol', 'Administrative Travel', 'Others') NOT NULL,
    `mission_other` VARCHAR(255) NULL,
    `location` VARCHAR(255) NOT NULL,
    `time_out` DATETIME NOT NULL,
    `travel_order_path` VARCHAR(255) NULL,
    `odometer_out` INT NULL,
    `time_in` DATETIME NULL,
    `odometer_in` INT NULL,
    `return_status` ENUM('Operational', 'Not Operational', 'Under Preventive Maintenance') NULL,
    `remarks` TEXT NULL,
    `created_at` DATETIME NULL,
    `updated_at` DATETIME NULL,
    PRIMARY KEY (`id`),
    INDEX `idx_dispatches_agency_id` (`agency_id`),
    INDEX `idx_dispatches_vehicle_id` (`vehicle_id`),
    INDEX `idx_dispatches_driver_id` (`driver_id`),
    INDEX `idx_dispatches_second_driver_id` (`second_driver_id`),
    CONSTRAINT `fk_dispatches_agency` FOREIGN KEY (`agency_id`) 
        REFERENCES `agencies` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_dispatches_vehicle` FOREIGN KEY (`vehicle_id`) 
        REFERENCES `vehicles` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_dispatches_driver` FOREIGN KEY (`driver_id`) 
        REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_dispatches_second_driver` FOREIGN KEY (`second_driver_id`) 
        REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Table 15: notifications
-- System alerts and notifications for drivers and administrators
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `notifications`;
CREATE TABLE `notifications` (
    `id` INT AUTO_INCREMENT NOT NULL,
    `agency_id` INT NOT NULL,
    `user_id` INT NOT NULL COMMENT 'Recipient user account',
    `type` ENUM('New Damage Report', 'Inspection Flagged', 'License Expiring', 'NC II Expiring', 'NC II Expired', 'PM Due', 'New Administrator', 'Password Reset') NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `message` TEXT NOT NULL,
    `data` JSON NULL,
    `is_read` BOOLEAN NOT NULL DEFAULT FALSE,
    `read_at` DATETIME NULL,
    `created_at` DATETIME NULL,
    `updated_at` DATETIME NULL,
    PRIMARY KEY (`id`),
    INDEX `idx_notifications_agency_id` (`agency_id`),
    INDEX `idx_notifications_user_id` (`user_id`),
    CONSTRAINT `fk_notifications_agency` FOREIGN KEY (`agency_id`) 
        REFERENCES `agencies` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_notifications_user` FOREIGN KEY (`user_id`) 
        REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Re-enable foreign key checks
SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================================
-- End of RVMS Database Schema Definition
-- =====================================================================
