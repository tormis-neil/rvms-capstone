-- =====================================================================
-- Rescue Vehicle Management System (RVMS) — ERD / schema script
-- Chapter 4 data model, for MySQL Workbench.
--
-- HOW TO TURN THIS INTO AN ERD DIAGRAM IN MYSQL WORKBENCH:
--   1. Open MySQL Workbench.
--   2. File > New Model  (or use an existing model).
--   3. File > Import > Reverse Engineer MySQL Create Script...
--   4. Choose this file (rvms-erd.sql), click Continue/Execute.
--   5. Workbench builds an EER Diagram with every table and the
--      relationship lines drawn from the FOREIGN KEY constraints below.
--      Drag the boxes to lay it out for the manuscript figure.
--   (Alternatively: run this script on a MySQL 8 server, then
--    Database > Reverse Engineer... and pick the `rvms` schema.)
--
-- ENGINE/CHARSET: MySQL 8.0+, InnoDB, utf8mb4 — matches the deployed stack.
--
-- SCOPE OF THIS FILE — read before editing:
--   * This is the LOGICAL model that matches the Chapter 4 data dictionary:
--     the 11 domain tables and their relationships. It is the ERD source,
--     not a Laravel migration.
--   * The standard Laravel/Sanctum framework tables (personal_access_tokens,
--     password_reset_tokens, jobs, failed_jobs, cache, sessions) are
--     intentionally omitted — they are boilerplate and are not part of the
--     ERD figure.
--   * Three columns that exist in the live `vehicles` table are intentionally
--     LEFT OUT here because they are repo/implementation-only and are
--     deliberately excluded from the manuscript data dictionary:
--         vehicles.status_source, vehicles.status_changed_at, vehicles.remarks
--     If your members want the PHYSICAL schema instead of the manuscript
--     model, add those three back to the `vehicles` table.
-- =====================================================================

CREATE SCHEMA IF NOT EXISTS `rvms` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `rvms`;

-- ---------------------------------------------------------------------
-- agencies — the four participating agencies (BFP, PNP, CDRRMO, CHO).
-- Every operational table hangs off this for agency-scoped access.
-- ---------------------------------------------------------------------
CREATE TABLE `agencies` (
  `id`                          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `code`                        VARCHAR(10)     NOT NULL,
  `name`                        VARCHAR(255)    NOT NULL,
  `location`                    VARCHAR(255)    NULL,
  `contact_number`              VARCHAR(50)     NULL,
  `email`                       VARCHAR(255)    NULL,
  `logo_path`                   VARCHAR(255)    NULL,
  `license_expiry_warning_days` SMALLINT UNSIGNED NOT NULL DEFAULT 30,
  `created_at`                  TIMESTAMP       NULL,
  `updated_at`                  TIMESTAMP       NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `agencies_code_unique` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- users — administrators (role='admin') and drivers (role='driver').
-- Drivers carry the licence fields; admins leave them NULL.
-- ---------------------------------------------------------------------
CREATE TABLE `users` (
  `id`                  BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `agency_id`           BIGINT UNSIGNED NOT NULL,
  `role`                ENUM('admin','driver') NOT NULL,
  `name`                VARCHAR(255)    NOT NULL,
  `email`               VARCHAR(255)    NOT NULL,
  `password`            VARCHAR(255)    NOT NULL,
  `status`              ENUM('pending','active','rejected') NOT NULL DEFAULT 'active',
  `license_number`      VARCHAR(50)     NULL,
  `license_expiry_date` DATE            NULL,
  `fcm_token`           VARCHAR(255)    NULL,
  `email_verified_at`   TIMESTAMP       NULL,
  `remember_token`      VARCHAR(100)    NULL,
  `created_at`          TIMESTAMP       NULL,
  `updated_at`          TIMESTAMP       NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`),                              -- email is the global sign-in identifier
  UNIQUE KEY `users_agency_license_unique` (`agency_id`, `license_number`), -- licence unique per agency (NULLs allowed)
  KEY `users_agency_id_index` (`agency_id`),
  CONSTRAINT `users_agency_id_fk` FOREIGN KEY (`agency_id`) REFERENCES `agencies` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- vehicles — the fleet. One primary driver per vehicle (nullable); a
-- single shared operational status written from every module.
-- ---------------------------------------------------------------------
CREATE TABLE `vehicles` (
  `id`                 BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `agency_id`          BIGINT UNSIGNED NOT NULL,
  `assigned_driver_id` BIGINT UNSIGNED NULL,
  `type`               VARCHAR(100)    NOT NULL,
  `plate_number`       VARCHAR(20)     NOT NULL,
  `make`               VARCHAR(100)    NOT NULL,
  `model`              VARCHAR(100)    NOT NULL,
  `engine_number`      VARCHAR(50)     NULL,
  `chassis_number`     VARCHAR(50)     NULL,
  `current_mileage`    INT UNSIGNED    NOT NULL DEFAULT 0,
  `status`             ENUM('Operational','Dispatched','Not Operational','Under Preventive Maintenance') NOT NULL DEFAULT 'Operational',
  `created_at`         TIMESTAMP       NULL,
  `updated_at`         TIMESTAMP       NULL,
  -- NOTE: repo-only columns status_source, status_changed_at, remarks are
  --       intentionally omitted here (see header).
  PRIMARY KEY (`id`),
  UNIQUE KEY `vehicles_agency_plate_unique`   (`agency_id`, `plate_number`),
  UNIQUE KEY `vehicles_agency_engine_unique`  (`agency_id`, `engine_number`),
  UNIQUE KEY `vehicles_agency_chassis_unique` (`agency_id`, `chassis_number`),
  KEY `vehicles_agency_id_index` (`agency_id`),
  KEY `vehicles_assigned_driver_id_index` (`assigned_driver_id`),
  CONSTRAINT `vehicles_agency_id_fk` FOREIGN KEY (`agency_id`) REFERENCES `agencies` (`id`),
  CONSTRAINT `vehicles_assigned_driver_id_fk` FOREIGN KEY (`assigned_driver_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- inspection_checklist_items — the BLOWBAGETS catalog (12 standard + 2
-- BFP-only). Reference/seed data; not agency-scoped.
-- ---------------------------------------------------------------------
CREATE TABLE `inspection_checklist_items` (
  `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`        VARCHAR(100)    NOT NULL,
  `is_bfp_only` TINYINT(1)      NOT NULL DEFAULT 0,
  `sort_order`  SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  `created_at`  TIMESTAMP       NULL,
  `updated_at`  TIMESTAMP       NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- inspections — one submitted daily BLOWBAGETS inspection.
-- ---------------------------------------------------------------------
CREATE TABLE `inspections` (
  `id`              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `agency_id`       BIGINT UNSIGNED NOT NULL,
  `vehicle_id`      BIGINT UNSIGNED NOT NULL,
  `driver_id`       BIGINT UNSIGNED NOT NULL,
  `inspection_date` DATE            NOT NULL,
  `review_status`   ENUM('Pending','Reviewed') NOT NULL DEFAULT 'Pending',
  `reviewed_by`     BIGINT UNSIGNED NULL,
  `reviewed_at`     DATETIME        NULL,
  `created_at`      TIMESTAMP       NULL,
  `updated_at`      TIMESTAMP       NULL,
  PRIMARY KEY (`id`),
  KEY `inspections_agency_id_index` (`agency_id`),
  KEY `inspections_vehicle_id_index` (`vehicle_id`),
  KEY `inspections_driver_id_index` (`driver_id`),
  KEY `inspections_reviewed_by_index` (`reviewed_by`),
  CONSTRAINT `inspections_agency_id_fk`   FOREIGN KEY (`agency_id`)   REFERENCES `agencies` (`id`),
  CONSTRAINT `inspections_vehicle_id_fk`  FOREIGN KEY (`vehicle_id`)  REFERENCES `vehicles` (`id`),
  CONSTRAINT `inspections_driver_id_fk`   FOREIGN KEY (`driver_id`)   REFERENCES `users` (`id`),
  CONSTRAINT `inspections_reviewed_by_fk` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- inspection_items — the per-item results of one inspection.
-- ---------------------------------------------------------------------
CREATE TABLE `inspection_items` (
  `id`                BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `inspection_id`     BIGINT UNSIGNED NOT NULL,
  `checklist_item_id` BIGINT UNSIGNED NOT NULL,
  `status`            ENUM('OK','Has Issue') NOT NULL,
  `remarks`           TEXT            NULL,          -- required when status = 'Has Issue' (enforced in the app)
  PRIMARY KEY (`id`),
  KEY `inspection_items_inspection_id_index` (`inspection_id`),
  KEY `inspection_items_checklist_item_id_index` (`checklist_item_id`),
  CONSTRAINT `inspection_items_inspection_id_fk`     FOREIGN KEY (`inspection_id`)     REFERENCES `inspections` (`id`) ON DELETE CASCADE,
  CONSTRAINT `inspection_items_checklist_item_id_fk` FOREIGN KEY (`checklist_item_id`) REFERENCES `inspection_checklist_items` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- damage_reports — a driver-filed fault report, reviewed by an admin.
-- ---------------------------------------------------------------------
CREATE TABLE `damage_reports` (
  `id`               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `agency_id`        BIGINT UNSIGNED NOT NULL,
  `vehicle_id`       BIGINT UNSIGNED NOT NULL,
  `driver_id`        BIGINT UNSIGNED NOT NULL,
  `nature_of_damage` TEXT            NOT NULL,
  `suspected_parts`  VARCHAR(255)    NULL,
  `photo_path`       VARCHAR(255)    NULL,
  `date_reported`    DATE            NOT NULL,
  `status`           ENUM('Pending','Reviewed') NOT NULL DEFAULT 'Pending',
  `reviewed_by`      BIGINT UNSIGNED NULL,
  `reviewed_at`      DATETIME        NULL,
  `created_at`       TIMESTAMP       NULL,
  `updated_at`       TIMESTAMP       NULL,
  PRIMARY KEY (`id`),
  KEY `damage_reports_agency_id_index` (`agency_id`),
  KEY `damage_reports_vehicle_id_index` (`vehicle_id`),
  KEY `damage_reports_driver_id_index` (`driver_id`),
  KEY `damage_reports_reviewed_by_index` (`reviewed_by`),
  CONSTRAINT `damage_reports_agency_id_fk`   FOREIGN KEY (`agency_id`)   REFERENCES `agencies` (`id`),
  CONSTRAINT `damage_reports_vehicle_id_fk`  FOREIGN KEY (`vehicle_id`)  REFERENCES `vehicles` (`id`),
  CONSTRAINT `damage_reports_driver_id_fk`   FOREIGN KEY (`driver_id`)   REFERENCES `users` (`id`),
  CONSTRAINT `damage_reports_reviewed_by_fk` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- repair_logs — repairs performed on a vehicle (FR-15).
-- ---------------------------------------------------------------------
CREATE TABLE `repair_logs` (
  `id`                 BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `agency_id`          BIGINT UNSIGNED NOT NULL,
  `vehicle_id`         BIGINT UNSIGNED NOT NULL,
  `driver_id`          BIGINT UNSIGNED NULL,
  `repair_date`        DATE            NOT NULL,
  `scope_of_work`      TEXT            NOT NULL,
  `parts_replaced`     TEXT            NULL,
  `cost`               DECIMAL(10,2)   NULL,
  `repair_source`      ENUM('Internal Office','GSO Motorpool','External Repair Shop') NOT NULL,
  `external_shop_name` VARCHAR(255)    NULL,
  `receipt_path`       VARCHAR(255)    NULL,
  `remarks`            TEXT            NULL,
  `created_at`         TIMESTAMP       NULL,
  `updated_at`         TIMESTAMP       NULL,
  PRIMARY KEY (`id`),
  KEY `repair_logs_agency_id_index` (`agency_id`),
  KEY `repair_logs_vehicle_id_index` (`vehicle_id`),
  KEY `repair_logs_driver_id_index` (`driver_id`),
  CONSTRAINT `repair_logs_agency_id_fk`  FOREIGN KEY (`agency_id`)  REFERENCES `agencies` (`id`),
  CONSTRAINT `repair_logs_vehicle_id_fk` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`id`),
  CONSTRAINT `repair_logs_driver_id_fk`  FOREIGN KEY (`driver_id`)  REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- pm_schedules — preventive maintenance schedules (FR-16). Status is
-- recalculated by a scheduled job; completion fields filled on service.
-- ---------------------------------------------------------------------
CREATE TABLE `pm_schedules` (
  `id`                            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `agency_id`                     BIGINT UNSIGNED NOT NULL,
  `vehicle_id`                    BIGINT UNSIGNED NOT NULL,
  `service_target`                VARCHAR(255)    NOT NULL,
  `pm_type`                       ENUM('Mileage-Based','Time-Based') NOT NULL,
  `interval_km`                   INT UNSIGNED    NULL,
  `last_pm_mileage`               INT UNSIGNED    NULL,
  `due_mileage`                   INT UNSIGNED    NULL,
  `due_date`                      DATE            NULL,
  `due_soon_threshold_km`         INT UNSIGNED    NULL,
  `due_soon_threshold_days`       SMALLINT UNSIGNED NULL,
  `status`                        ENUM('Upcoming','Due Soon','Due','Completed') NOT NULL DEFAULT 'Upcoming',
  `date_serviced`                 DATE            NULL,
  `completion_mileage`            INT UNSIGNED    NULL,
  `completion_repair_source`      ENUM('Internal Office','GSO Motorpool','External Repair Shop') NULL,
  `completion_external_shop_name` VARCHAR(255)    NULL,
  `completion_receipt_path`       VARCHAR(255)    NULL,
  `completion_parts_replaced`     TEXT            NULL,
  `completion_remarks`            TEXT            NULL,
  `created_at`                    TIMESTAMP       NULL,
  `updated_at`                    TIMESTAMP       NULL,
  PRIMARY KEY (`id`),
  KEY `pm_schedules_agency_id_index` (`agency_id`),
  KEY `pm_schedules_vehicle_id_index` (`vehicle_id`),
  CONSTRAINT `pm_schedules_agency_id_fk`  FOREIGN KEY (`agency_id`)  REFERENCES `agencies` (`id`),
  CONSTRAINT `pm_schedules_vehicle_id_fk` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- dispatches — vehicle dispatch log. Active when time_in IS NULL.
-- ---------------------------------------------------------------------
CREATE TABLE `dispatches` (
  `id`            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `agency_id`     BIGINT UNSIGNED NOT NULL,
  `vehicle_id`    BIGINT UNSIGNED NOT NULL,
  `driver_id`     BIGINT UNSIGNED NOT NULL,
  `mission_type`  ENUM('Fire Response','Medical Response','Rescue Operation','Patrol','Administrative Travel','Others') NOT NULL,
  `mission_other` VARCHAR(255)    NULL,
  `location`      VARCHAR(255)    NOT NULL,
  `time_out`      DATETIME        NOT NULL,
  `odometer_out`  INT UNSIGNED    NULL,
  `time_in`       DATETIME        NULL,
  `odometer_in`   INT UNSIGNED    NULL,
  `return_status` ENUM('Operational','Not Operational','Under Preventive Maintenance') NULL,
  `remarks`       TEXT            NULL,
  `created_at`    TIMESTAMP       NULL,
  `updated_at`    TIMESTAMP       NULL,
  PRIMARY KEY (`id`),
  KEY `dispatches_agency_id_index` (`agency_id`),
  KEY `dispatches_vehicle_id_index` (`vehicle_id`),
  KEY `dispatches_driver_id_index` (`driver_id`),
  CONSTRAINT `dispatches_agency_id_fk`  FOREIGN KEY (`agency_id`)  REFERENCES `agencies` (`id`),
  CONSTRAINT `dispatches_vehicle_id_fk` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`id`),
  CONSTRAINT `dispatches_driver_id_fk`  FOREIGN KEY (`driver_id`)  REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- notifications — one stored alert addressed to one recipient (FR-23).
-- ---------------------------------------------------------------------
CREATE TABLE `notifications` (
  `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `agency_id`  BIGINT UNSIGNED NOT NULL,
  `user_id`    BIGINT UNSIGNED NOT NULL,
  `type`       ENUM('PM_Reminder','Vehicle_Status_Update','New_Damage_Report','Inspection_Flagged','License_Expiring','License_Expired','PM_Due_Soon','PM_Due','New_Access_Request','Password_Reset','New_Admin') NOT NULL,
  `title`      VARCHAR(255)    NOT NULL,
  `message`    TEXT            NOT NULL,
  `data`       JSON            NULL,
  `is_read`    TINYINT(1)      NOT NULL DEFAULT 0,
  `read_at`    DATETIME        NULL,
  `created_at` TIMESTAMP       NULL,
  `updated_at` TIMESTAMP       NULL,
  PRIMARY KEY (`id`),
  KEY `notifications_agency_id_index` (`agency_id`),
  KEY `notifications_user_id_index` (`user_id`),
  CONSTRAINT `notifications_agency_id_fk` FOREIGN KEY (`agency_id`) REFERENCES `agencies` (`id`),
  CONSTRAINT `notifications_user_id_fk`   FOREIGN KEY (`user_id`)   REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
