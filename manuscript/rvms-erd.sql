-- =====================================================================
--  RESCUE VEHICLE MANAGEMENT SYSTEM (RVMS)
--  Database schema for the Chapter 4 Entity Relationship Diagram
--
--  Northwest Samar State University · Capstone Project · August 2026
--  Bureau of Fire Protection · Philippine National Police ·
--  City Disaster Risk Reduction and Management Office · City Health Office
-- =====================================================================
--
--  HOW TO GET THE ERD OUT OF THIS FILE (MySQL Workbench)
--
--    1. Open MySQL Workbench and connect to your MySQL server.
--    2. File > Open SQL Script…  and choose this file.
--    3. Click the lightning bolt to run it. It creates a database named
--       rvms_erd, separate from the live rvms database, so nothing you
--       already have is touched.
--    4. Database > Reverse Engineer…  (Ctrl+R)
--    5. Choose your connection > Next > Next, tick rvms_erd, then keep
--       clicking Next until it finishes.
--    6. Workbench draws the EER diagram, with every relationship taken
--       from the FOREIGN KEY constraints below.
--    7. Arrange the boxes as you like, then File > Export > as PNG.
--
--  WHAT THIS FILE CONTAINS
--
--    11 tables, 131 columns, 23 foreign keys — the same entities,
--    attributes and relationships shown in Figure 6 and documented in
--    Tables 5 to 15 of Chapter 4.
--
--  WHAT IT DELIBERATELY LEAVES OUT
--
--    * Framework tables (sessions, jobs, cache, password reset tokens,
--      personal access tokens). They hold no fleet information and are
--      created by the web framework, not designed by the proponents, so
--      they are not part of the data model of the study.
--    * Three columns on `vehicles` that exist in the running system to
--      support the user interface — a note on the most recent manual
--      status change, the module that last set the status, and the time
--      it was set. No functional requirement calls for them, so they are
--      excluded from the diagram, the data dictionary, and this file.
--
--  Every type, length and enumeration below was taken from the system's
--  own migrations, not retyped from memory.
-- =====================================================================

DROP DATABASE IF EXISTS `rvms_erd`;
CREATE DATABASE `rvms_erd`
  DEFAULT CHARACTER SET utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;
USE `rvms_erd`;

SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------------------------------
--  agencies — the four participating agencies (FR-02)
--  Holds each agency's profile and the licence warning period it sets.
-- ---------------------------------------------------------------------
CREATE TABLE `agencies` (
  `id`                          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `code`                        VARCHAR(10)     NOT NULL COMMENT 'BFP, PNP, CDRRMO, or CHO',
  `name`                        VARCHAR(255)    NOT NULL,
  `location`                    VARCHAR(255)        NULL,
  `contact_number`              VARCHAR(50)         NULL,
  `email`                       VARCHAR(255)        NULL,
  `logo_path`                   VARCHAR(255)        NULL,
  `license_expiry_warning_days` SMALLINT UNSIGNED NOT NULL DEFAULT 30
                                COMMENT 'Configurable: days before expiry a licence is flagged (FR-08)',
  `created_at`                  TIMESTAMP           NULL,
  `updated_at`                  TIMESTAMP           NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `agencies_code_unique` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
--  users — every account that can sign in (FR-01, FR-03, FR-04, FR-06)
--  Administrators and Authorized Drivers share one table, separated by
--  `role`; the licence columns are filled in for drivers only.
-- ---------------------------------------------------------------------
CREATE TABLE `users` (
  `id`                  BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `agency_id`           BIGINT UNSIGNED NOT NULL,
  `role`                ENUM('admin','driver') NOT NULL,
  `name`                VARCHAR(255)    NOT NULL,
  `email`               VARCHAR(255)    NOT NULL,
  `email_verified_at`   TIMESTAMP           NULL,
  `password`            VARCHAR(255)    NOT NULL COMMENT 'Hashed, never stored in plain text (NFR-02)',
  `status`              ENUM('pending','active','rejected') NOT NULL DEFAULT 'active',
  `license_number`      VARCHAR(50)         NULL,
  `license_expiry_date` DATE                NULL,
  `fcm_token`           VARCHAR(255)        NULL COMMENT 'Device token for push notifications (FR-21)',
  `remember_token`      VARCHAR(100)        NULL,
  `created_at`          TIMESTAMP           NULL,
  `updated_at`          TIMESTAMP           NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`),
  UNIQUE KEY `users_agency_license_unique` (`agency_id`,`license_number`),
  KEY `users_agency_id_index` (`agency_id`),
  CONSTRAINT `users_agency_id_foreign` FOREIGN KEY (`agency_id`) REFERENCES `agencies` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
--  vehicles — the fleet of each agency (FR-05, FR-18)
--  `status` is the single shared operational status every module reads
--  and writes, so all screens report the same condition.
-- ---------------------------------------------------------------------
CREATE TABLE `vehicles` (
  `id`                 BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `agency_id`          BIGINT UNSIGNED NOT NULL,
  `assigned_driver_id` BIGINT UNSIGNED     NULL COMMENT 'Primary Authorized Driver',
  `type`               VARCHAR(100)    NOT NULL,
  `plate_number`       VARCHAR(20)     NOT NULL,
  `make`               VARCHAR(100)    NOT NULL,
  `model`              VARCHAR(100)    NOT NULL,
  `engine_number`      VARCHAR(50)         NULL,
  `chassis_number`     VARCHAR(50)         NULL,
  `current_mileage`    INT UNSIGNED    NOT NULL DEFAULT 0 COMMENT 'Odometer in km; feeds mileage-based PM (FR-14)',
  `status`             ENUM('Operational','Dispatched','Not Operational','Under Preventive Maintenance')
                       NOT NULL DEFAULT 'Operational',
  `created_at`         TIMESTAMP           NULL,
  `updated_at`         TIMESTAMP           NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `vehicles_agency_id_plate_number_unique` (`agency_id`,`plate_number`),
  UNIQUE KEY `vehicles_agency_engine_unique`  (`agency_id`,`engine_number`),
  UNIQUE KEY `vehicles_agency_chassis_unique` (`agency_id`,`chassis_number`),
  KEY `vehicles_agency_id_index` (`agency_id`),
  KEY `vehicles_assigned_driver_id_index` (`assigned_driver_id`),
  CONSTRAINT `vehicles_agency_id_foreign`
    FOREIGN KEY (`agency_id`) REFERENCES `agencies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `vehicles_assigned_driver_id_foreign`
    FOREIGN KEY (`assigned_driver_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
--  inspection_checklist_items — the BLOWBAGETS catalogue (FR-09)
--  Twelve standard items, plus two that apply only to BFP vehicles.
--  Shared by all four agencies, so it carries no agency_id.
-- ---------------------------------------------------------------------
CREATE TABLE `inspection_checklist_items` (
  `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`        VARCHAR(100)    NOT NULL,
  `is_bfp_only` TINYINT(1)      NOT NULL DEFAULT 0 COMMENT 'Hydraulic System and Fire Pump',
  `sort_order`  SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  `created_at`  TIMESTAMP           NULL,
  `updated_at`  TIMESTAMP           NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
--  inspections — one daily BLOWBAGETS inspection (FR-09, FR-10)
--  `driver_id` is who submitted it; `reviewed_by` is who assessed it.
-- ---------------------------------------------------------------------
CREATE TABLE `inspections` (
  `id`              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `agency_id`       BIGINT UNSIGNED NOT NULL,
  `vehicle_id`      BIGINT UNSIGNED NOT NULL,
  `driver_id`       BIGINT UNSIGNED NOT NULL,
  `inspection_date` DATE            NOT NULL,
  `review_status`   ENUM('Pending','Reviewed') NOT NULL DEFAULT 'Pending',
  `reviewed_by`     BIGINT UNSIGNED     NULL,
  `reviewed_at`     DATETIME            NULL,
  `created_at`      TIMESTAMP           NULL,
  `updated_at`      TIMESTAMP           NULL,
  PRIMARY KEY (`id`),
  KEY `inspections_agency_id_index`  (`agency_id`),
  KEY `inspections_vehicle_id_index` (`vehicle_id`),
  KEY `inspections_driver_id_index`  (`driver_id`),
  KEY `inspections_reviewed_by_index` (`reviewed_by`),
  CONSTRAINT `inspections_agency_id_foreign`
    FOREIGN KEY (`agency_id`) REFERENCES `agencies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `inspections_vehicle_id_foreign`
    FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`id`) ON DELETE CASCADE,
  CONSTRAINT `inspections_driver_id_foreign`
    FOREIGN KEY (`driver_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `inspections_reviewed_by_foreign`
    FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
--  inspection_items — the result of one checklist item (FR-09)
--  Remarks are required when the status is 'Has Issue'.
-- ---------------------------------------------------------------------
CREATE TABLE `inspection_items` (
  `id`                BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `inspection_id`     BIGINT UNSIGNED NOT NULL,
  `checklist_item_id` BIGINT UNSIGNED NOT NULL,
  `status`            ENUM('OK','Has Issue') NOT NULL,
  `remarks`           TEXT                NULL,
  PRIMARY KEY (`id`),
  KEY `inspection_items_inspection_id_index`     (`inspection_id`),
  KEY `inspection_items_checklist_item_id_index` (`checklist_item_id`),
  CONSTRAINT `inspection_items_inspection_id_foreign`
    FOREIGN KEY (`inspection_id`) REFERENCES `inspections` (`id`) ON DELETE CASCADE,
  CONSTRAINT `inspection_items_checklist_item_id_foreign`
    FOREIGN KEY (`checklist_item_id`) REFERENCES `inspection_checklist_items` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
--  damage_reports — damage reported by a driver (FR-11, FR-12)
-- ---------------------------------------------------------------------
CREATE TABLE `damage_reports` (
  `id`               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `agency_id`        BIGINT UNSIGNED NOT NULL,
  `vehicle_id`       BIGINT UNSIGNED NOT NULL,
  `driver_id`        BIGINT UNSIGNED NOT NULL,
  `nature_of_damage` TEXT            NOT NULL,
  `suspected_parts`  VARCHAR(255)        NULL,
  `photo_path`       VARCHAR(255)        NULL COMMENT 'Optional photo attachment',
  `date_reported`    DATE            NOT NULL,
  `status`           ENUM('Pending','Reviewed') NOT NULL DEFAULT 'Pending',
  `reviewed_by`      BIGINT UNSIGNED     NULL,
  `reviewed_at`      DATETIME            NULL,
  `created_at`       TIMESTAMP           NULL,
  `updated_at`       TIMESTAMP           NULL,
  PRIMARY KEY (`id`),
  KEY `damage_reports_agency_id_index`   (`agency_id`),
  KEY `damage_reports_vehicle_id_index`  (`vehicle_id`),
  KEY `damage_reports_driver_id_index`   (`driver_id`),
  KEY `damage_reports_reviewed_by_index` (`reviewed_by`),
  CONSTRAINT `damage_reports_agency_id_foreign`
    FOREIGN KEY (`agency_id`) REFERENCES `agencies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `damage_reports_vehicle_id_foreign`
    FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`id`) ON DELETE CASCADE,
  CONSTRAINT `damage_reports_driver_id_foreign`
    FOREIGN KEY (`driver_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `damage_reports_reviewed_by_foreign`
    FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
--  repair_logs — repairs carried out on a vehicle (FR-13)
--  The shop name is recorded when the source is an external shop.
-- ---------------------------------------------------------------------
CREATE TABLE `repair_logs` (
  `id`                 BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `agency_id`          BIGINT UNSIGNED NOT NULL,
  `vehicle_id`         BIGINT UNSIGNED NOT NULL,
  `driver_id`          BIGINT UNSIGNED     NULL,
  `repair_date`        DATE            NOT NULL,
  `scope_of_work`      TEXT            NOT NULL,
  `parts_replaced`     TEXT                NULL,
  `cost`               DECIMAL(10,2)       NULL,
  `repair_source`      ENUM('Internal Office','GSO Motorpool','External Repair Shop') NOT NULL,
  `external_shop_name` VARCHAR(255)        NULL,
  `receipt_path`       VARCHAR(255)        NULL COMMENT 'Receipt/invoice or GSO job order; required for External Repair Shop',
  `remarks`            TEXT                NULL,
  `created_at`         TIMESTAMP           NULL,
  `updated_at`         TIMESTAMP           NULL,
  PRIMARY KEY (`id`),
  KEY `repair_logs_agency_id_index`  (`agency_id`),
  KEY `repair_logs_vehicle_id_index` (`vehicle_id`),
  KEY `repair_logs_driver_id_index`  (`driver_id`),
  CONSTRAINT `repair_logs_agency_id_foreign`
    FOREIGN KEY (`agency_id`) REFERENCES `agencies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `repair_logs_vehicle_id_foreign`
    FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`id`) ON DELETE CASCADE,
  CONSTRAINT `repair_logs_driver_id_foreign`
    FOREIGN KEY (`driver_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
--  pm_schedules — preventive maintenance plans (FR-14)
--  Mileage-based schedules use the km columns, time-based use due_date.
--  Each schedule carries its own configurable Due Soon threshold.
-- ---------------------------------------------------------------------
CREATE TABLE `pm_schedules` (
  `id`                             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `agency_id`                      BIGINT UNSIGNED NOT NULL,
  `vehicle_id`                     BIGINT UNSIGNED NOT NULL,
  `service_target`                 VARCHAR(255)    NOT NULL COMMENT 'e.g. Oil Change and Filter',
  `pm_type`                        ENUM('Mileage-Based','Time-Based') NOT NULL,
  `interval_km`                    INT UNSIGNED        NULL,
  `last_pm_mileage`                INT UNSIGNED        NULL,
  `due_mileage`                    INT UNSIGNED        NULL,
  `due_date`                       DATE                NULL,
  `due_soon_threshold_km`          INT UNSIGNED        NULL COMMENT 'Configurable per schedule',
  `due_soon_threshold_days`        SMALLINT UNSIGNED   NULL COMMENT 'Configurable per schedule',
  `status`                         ENUM('Upcoming','Due Soon','Due','Completed') NOT NULL DEFAULT 'Upcoming',
  `date_serviced`                  DATE                NULL,
  `completion_mileage`             INT UNSIGNED        NULL COMMENT 'Odometer at service; mileage-based schedules',
  `completion_repair_source`       ENUM('Internal Office','GSO Motorpool','External Repair Shop') NULL,
  `completion_external_shop_name`  VARCHAR(255)        NULL,
  `completion_receipt_path`        VARCHAR(255)        NULL COMMENT 'Receipt/invoice or GSO job order; required for External Repair Shop',
  `completion_parts_replaced`      TEXT                NULL,
  `completion_remarks`             TEXT                NULL,
  `created_at`                     TIMESTAMP           NULL,
  `updated_at`                     TIMESTAMP           NULL,
  PRIMARY KEY (`id`),
  KEY `pm_schedules_agency_id_index`  (`agency_id`),
  KEY `pm_schedules_vehicle_id_index` (`vehicle_id`),
  CONSTRAINT `pm_schedules_agency_id_foreign`
    FOREIGN KEY (`agency_id`) REFERENCES `agencies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `pm_schedules_vehicle_id_foreign`
    FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
--  dispatches — one deployment of a vehicle (FR-15, FR-16, FR-17)
--  A row with no `time_in` is an active dispatch. The time-in odometer
--  updates the vehicle's current mileage when it is higher.
-- ---------------------------------------------------------------------
CREATE TABLE `dispatches` (
  `id`            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `agency_id`     BIGINT UNSIGNED NOT NULL,
  `vehicle_id`    BIGINT UNSIGNED NOT NULL,
  `driver_id`     BIGINT UNSIGNED NOT NULL,
  `mission_type`  ENUM('Fire Response','Medical Response','Rescue Operation',
                       'Patrol','Administrative Travel','Others') NOT NULL,
  `mission_other` VARCHAR(255)        NULL COMMENT 'Required when mission_type is Others',
  `location`      VARCHAR(255)    NOT NULL,
  `time_out`      DATETIME        NOT NULL COMMENT 'Opening sets the vehicle to Dispatched',
  `odometer_out`  INT UNSIGNED        NULL,
  `time_in`       DATETIME            NULL COMMENT 'NULL means the dispatch is still active',
  `odometer_in`   INT UNSIGNED        NULL,
  `return_status` ENUM('Operational','Not Operational','Under Preventive Maintenance') NULL,
  `remarks`       TEXT                NULL,
  `created_at`    TIMESTAMP           NULL,
  `updated_at`    TIMESTAMP           NULL,
  PRIMARY KEY (`id`),
  KEY `dispatches_agency_id_index`  (`agency_id`),
  KEY `dispatches_vehicle_id_index` (`vehicle_id`),
  KEY `dispatches_driver_id_index`  (`driver_id`),
  CONSTRAINT `dispatches_agency_id_foreign`
    FOREIGN KEY (`agency_id`) REFERENCES `agencies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `dispatches_vehicle_id_foreign`
    FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`id`) ON DELETE CASCADE,
  CONSTRAINT `dispatches_driver_id_foreign`
    FOREIGN KEY (`driver_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
--  notifications — alerts for drivers and administrators (FR-21, FR-22)
-- ---------------------------------------------------------------------
CREATE TABLE `notifications` (
  `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `agency_id`  BIGINT UNSIGNED NOT NULL,
  `user_id`    BIGINT UNSIGNED NOT NULL COMMENT 'The recipient',
  `type`       ENUM('PM_Reminder','Vehicle_Status_Update','New_Damage_Report',
                    'Inspection_Flagged','License_Expiring','License_Expired',
                    'PM_Due_Soon','PM_Due','New_Access_Request','Password_Reset') NOT NULL,
  `title`      VARCHAR(255)    NOT NULL,
  `message`    TEXT            NOT NULL,
  `data`       JSON                NULL COMMENT 'Reference payload, e.g. the plate number',
  `is_read`    TINYINT(1)      NOT NULL DEFAULT 0,
  `read_at`    DATETIME            NULL,
  `created_at` TIMESTAMP           NULL,
  `updated_at` TIMESTAMP           NULL,
  PRIMARY KEY (`id`),
  KEY `notifications_agency_id_index`            (`agency_id`),
  KEY `notifications_user_id_is_read_index`      (`user_id`,`is_read`),
  KEY `notifications_user_id_created_at_index`   (`user_id`,`created_at`),
  CONSTRAINT `notifications_agency_id_foreign`
    FOREIGN KEY (`agency_id`) REFERENCES `agencies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `notifications_user_id_foreign`
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================================
--  THE 23 RELATIONSHIPS, AS WORKBENCH WILL DRAW THEM
--
--  agencies   1 --- * users                    employs
--  agencies   1 --- * vehicles                 owns
--  agencies   1 --- * inspections              has
--  agencies   1 --- * damage_reports           has
--  agencies   1 --- * repair_logs              has
--  agencies   1 --- * pm_schedules             has
--  agencies   1 --- * dispatches               has
--  agencies   1 --- * notifications            has
--  users      1 --- * vehicles                 drives (primary driver)
--  users      1 --- * inspections              submits      (driver_id)
--  users      1 --- * inspections              reviews      (reviewed_by)
--  users      1 --- * damage_reports           submits      (driver_id)
--  users      1 --- * damage_reports           reviews      (reviewed_by)
--  users      1 --- * repair_logs              assigned to  (driver_id)
--  users      1 --- * dispatches               dispatched on
--  users      1 --- * notifications            receives
--  vehicles   1 --- * inspections              undergoes
--  vehicles   1 --- * damage_reports           has
--  vehicles   1 --- * repair_logs              has
--  vehicles   1 --- * pm_schedules             has
--  vehicles   1 --- * dispatches               used in
--  inspections 1 --- * inspection_items        contains
--  inspection_checklist_items 1 --- * inspection_items   assessed in
--
--  A driver may be the primary driver of more than one vehicle, while
--  each vehicle has at most one primary driver.
-- =====================================================================
