-- =====================================================================
-- Rescue Vehicle Management System (RVMS)
-- Figure 6.1: Driver Operations and Inspections Subsystem Schema
-- Contains exactly the 7 domain tables for Figure 6.1
-- Optimized for: MySQL Workbench -> Reverse Engineer MySQL Create Script
-- =====================================================================

DROP DATABASE IF EXISTS `rvms_driver_operations_db`;
CREATE DATABASE `rvms_driver_operations_db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `rvms_driver_operations_db`;

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
-- Accounts for Agency Administrators and Authorized Drivers
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
-- Operational vehicle inventory and driver assignments
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
-- Table 4: inspection_checklist_items (Table 8 in Data Dictionary)
-- Standardized catalog of BLOWBAGETS evaluation items
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `inspection_checklist_items`;
CREATE TABLE `inspection_checklist_items` (
    `id` INT AUTO_INCREMENT NOT NULL,
    `name` VARCHAR(100) NOT NULL COMMENT 'Battery, Brakes, Fire Pump, etc.',
    `is_bfp_only` BOOLEAN NOT NULL DEFAULT FALSE,
    `sort_order` SMALLINT NOT NULL DEFAULT 0,
    `created_at` DATETIME NULL,
    `updated_at` DATETIME NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Table 5: inspections (Table 9 in Data Dictionary)
-- Daily pre-trip BLOWBAGETS inspections submitted by drivers
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `inspections`;
CREATE TABLE `inspections` (
    `id` INT AUTO_INCREMENT NOT NULL,
    `agency_id` INT NOT NULL,
    `vehicle_id` INT NOT NULL,
    `driver_id` INT NOT NULL COMMENT 'Driver who performed inspection',
    `inspection_date` DATE NOT NULL,
    `review_status` ENUM('Pending', 'Reviewed') NOT NULL DEFAULT 'Pending',
    `reviewed_by` INT NULL COMMENT 'Admin who reviewed inspection',
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
-- Table 6: inspection_items (Table 10 in Data Dictionary)
-- Individual checklist item evaluation results for an inspection
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `inspection_items`;
CREATE TABLE `inspection_items` (
    `id` INT AUTO_INCREMENT NOT NULL,
    `inspection_id` INT NOT NULL,
    `checklist_item_id` INT NOT NULL,
    `status` ENUM('OK', 'Has Issue') NOT NULL,
    `remarks` TEXT NULL COMMENT 'Required when status is Has Issue',
    PRIMARY KEY (`id`),
    INDEX `idx_inspection_items_inspection_id` (`inspection_id`),
    INDEX `idx_inspection_items_checklist_item_id` (`checklist_item_id`),
    CONSTRAINT `fk_inspection_items_inspection` FOREIGN KEY (`inspection_id`) 
        REFERENCES `inspections` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_inspection_items_checklist_item` FOREIGN KEY (`checklist_item_id`) 
        REFERENCES `inspection_checklist_items` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Table 7: damage_reports (Table 11 in Data Dictionary)
-- Vehicle damage and incident reports submitted by drivers
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
    `reviewed_by` INT NULL COMMENT 'Admin who assessed report',
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

SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================================
-- End of Figure 6.1 Schema Script
-- =====================================================================
