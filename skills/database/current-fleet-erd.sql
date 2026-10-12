-- =====================================================================
-- Rescue Vehicle Management System (RVMS)
-- Figure 6.2: Fleet Management, Maintenance, and Dispatch Subsystem Schema
-- Contains exactly the 7 domain tables for Figure 6.2
-- Optimized for: MySQL Workbench -> Reverse Engineer MySQL Create Script
-- =====================================================================

DROP DATABASE IF EXISTS `rvms_fleet_maintenance_db`;
CREATE DATABASE `rvms_fleet_maintenance_db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `rvms_fleet_maintenance_db`;

SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------------------------------
-- Table 1: agencies (Table 5 in Data Dictionary)
-- Multi-tenancy anchor for BFP, PNP, CDRRMO, and CHO
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `agencies`;
CREATE TABLE `agencies` (
    `id` INT AUTO_INCREMENT NOT NULL,
    `code` VARCHAR(10) NOT NULL COMMENT 'BFP, PNP, CDRRMO, or CHO',
    `name` VARCHAR(255) NOT NULL COMMENT 'Full official agency name',
    `location` VARCHAR(255) NULL,
    `contact_number` VARCHAR(50) NULL,
    `email` VARCHAR(255) NULL,
    `logo_path` VARCHAR(255) NULL,
    `license_expiry_warning_days` SMALLINT NOT NULL DEFAULT 30,
    `created_at` DATETIME NULL,
    `updated_at` DATETIME NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_agencies_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Table 2: users (Table 6 in Data Dictionary)
-- Accounts for Agency Administrators, drivers, and crew members
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
-- Table 3: vehicles (Table 7 in Data Dictionary)
-- Operational fleet inventory, status, and odometer mileage
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
-- Table 4: dispatches (Table 14 in Data Dictionary)
-- Mission trip logs, vehicle deployments, and odometer tracking
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `dispatches`;
CREATE TABLE `dispatches` (
    `id` INT AUTO_INCREMENT NOT NULL,
    `agency_id` INT NOT NULL,
    `vehicle_id` INT NOT NULL,
    `driver_id` INT NOT NULL COMMENT 'Primary dispatched driver',
    `second_driver_id` INT NULL COMMENT 'Optional secondary crew driver',
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
-- Table 5: pm_schedules (Table 13 in Data Dictionary)
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
-- Table 6: repair_logs (Table 12 in Data Dictionary)
-- Corrective repair logs and maintenance history
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
-- Table 7: notifications (Table 15 in Data Dictionary)
-- In-app alerts and notifications for administrators and drivers
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

SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================================
-- End of Figure 6.2 Schema Script
-- =====================================================================
