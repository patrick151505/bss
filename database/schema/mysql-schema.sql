
/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
DROP TABLE IF EXISTS `eb_accountable_officers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `eb_accountable_officers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `position` varchar(255) DEFAULT NULL,
  `fidelity_bond_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_activity_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `eb_activity_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `action` varchar(30) NOT NULL,
  `subject_type` varchar(50) DEFAULT NULL,
  `subject_id` bigint(20) unsigned DEFAULT NULL,
  `description` text DEFAULT NULL,
  `properties` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`properties`)),
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `eb_activity_logs_action_created_at_index` (`action`,`created_at`),
  KEY `eb_activity_logs_subject_type_subject_id_index` (`subject_type`,`subject_id`),
  KEY `eb_activity_logs_user_id_index` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=99 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_address`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `eb_address` (
  `id` int(11) NOT NULL,
  `description` varchar(250) NOT NULL,
  `is_subd` int(11) NOT NULL DEFAULT 0,
  `rules_required` varchar(250) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_approval_status`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `eb_approval_status` (
  `id` int(11) NOT NULL,
  `description` varchar(250) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_blotter_action_versions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `eb_blotter_action_versions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `action_id` int(11) NOT NULL,
  `blotter_id` int(11) NOT NULL,
  `outcome` varchar(30) NOT NULL,
  `complainant_attendance` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`complainant_attendance`)),
  `respondent_attendance` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`respondent_attendance`)),
  `notes` longtext DEFAULT NULL,
  `photos` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`photos`)),
  `saved_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `eb_blotter_action_versions_action_id_index` (`action_id`),
  KEY `eb_blotter_action_versions_blotter_id_index` (`blotter_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_blotter_actions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `eb_blotter_actions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `blotter_id` bigint(20) unsigned NOT NULL,
  `action_type` varchar(50) NOT NULL,
  `scheduled_date` date NOT NULL,
  `scheduled_time` time DEFAULT NULL,
  `venue` varchar(255) DEFAULT NULL,
  `conducted_by` varchar(255) DEFAULT NULL,
  `outcome` varchar(30) NOT NULL DEFAULT 'pending',
  `complainant_attendance` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL DEFAULT '{}' CHECK (json_valid(`complainant_attendance`)),
  `respondent_attendance` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL DEFAULT '{}' CHECK (json_valid(`respondent_attendance`)),
  `notes` text DEFAULT NULL,
  `photos` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`photos`)),
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `eb_blotter_actions_blotter_id_scheduled_date_index` (`blotter_id`,`scheduled_date`),
  CONSTRAINT `eb_blotter_actions_blotter_id_foreign` FOREIGN KEY (`blotter_id`) REFERENCES `eb_blotters` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_blotter_parties`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `eb_blotter_parties` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `blotter_id` bigint(20) unsigned NOT NULL,
  `role` enum('complainant','respondent') NOT NULL,
  `citizen_id` int(11) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `address` varchar(255) DEFAULT NULL,
  `contact` varchar(30) DEFAULT NULL,
  `sort_order` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `eb_blotter_parties_blotter_id_role_index` (`blotter_id`,`role`),
  KEY `eb_blotter_parties_citizen_id_index` (`citizen_id`),
  CONSTRAINT `eb_blotter_parties_blotter_id_foreign` FOREIGN KEY (`blotter_id`) REFERENCES `eb_blotters` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_blotters`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `eb_blotters` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `blotter_no` varchar(20) NOT NULL,
  `filed_date` date NOT NULL,
  `incident_date` date NOT NULL,
  `incident_time` time DEFAULT NULL,
  `type` varchar(50) NOT NULL DEFAULT 'complaint',
  `incident_location` varchar(255) DEFAULT NULL,
  `complainant_name` varchar(255) NOT NULL,
  `complainant_address` varchar(255) DEFAULT NULL,
  `complainant_contact` varchar(30) DEFAULT NULL,
  `complainant_citizen_id` bigint(20) unsigned DEFAULT NULL,
  `respondent_name` varchar(255) NOT NULL,
  `respondent_address` varchar(255) DEFAULT NULL,
  `respondent_contact` varchar(30) DEFAULT NULL,
  `respondent_citizen_id` bigint(20) unsigned DEFAULT NULL,
  `narrative` text NOT NULL,
  `witnesses` text DEFAULT NULL,
  `attending_officer` varchar(255) DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'filed',
  `action_taken` text DEFAULT NULL,
  `settled_date` date DEFAULT NULL,
  `referred_to` varchar(255) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `photos` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`photos`)),
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `eb_blotters_blotter_no_unique` (`blotter_no`),
  KEY `eb_blotters_status_filed_date_index` (`status`,`filed_date`),
  KEY `eb_blotters_incident_date_index` (`incident_date`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_budget_adjustments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `eb_budget_adjustments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `fiscal_year_id` bigint(20) unsigned NOT NULL,
  `allocation_id` bigint(20) unsigned NOT NULL,
  `type` enum('supplemental','realignment_in','realignment_out') NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `reference_no` varchar(255) DEFAULT NULL,
  `effectivity_date` date NOT NULL,
  `notes` text DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `eb_budget_adjustments_fiscal_year_id_foreign` (`fiscal_year_id`),
  KEY `eb_budget_adjustments_allocation_id_foreign` (`allocation_id`),
  KEY `eb_budget_adjustments_created_by_foreign` (`created_by`),
  CONSTRAINT `eb_budget_adjustments_allocation_id_foreign` FOREIGN KEY (`allocation_id`) REFERENCES `eb_budget_allocations` (`id`) ON DELETE CASCADE,
  CONSTRAINT `eb_budget_adjustments_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `eb_budget_adjustments_fiscal_year_id_foreign` FOREIGN KEY (`fiscal_year_id`) REFERENCES `eb_fiscal_years` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_budget_allocations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `eb_budget_allocations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `fiscal_year_id` bigint(20) unsigned NOT NULL,
  `program_id` bigint(20) unsigned NOT NULL,
  `object_class` enum('PS','MOOE','CO') NOT NULL,
  `appropriation` decimal(15,2) NOT NULL DEFAULT 0.00,
  `computed_amount` decimal(15,2) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `alloc_fy_prog_obj_unique` (`fiscal_year_id`,`program_id`,`object_class`),
  KEY `eb_budget_allocations_program_id_foreign` (`program_id`),
  CONSTRAINT `eb_budget_allocations_fiscal_year_id_foreign` FOREIGN KEY (`fiscal_year_id`) REFERENCES `eb_fiscal_years` (`id`) ON DELETE CASCADE,
  CONSTRAINT `eb_budget_allocations_program_id_foreign` FOREIGN KEY (`program_id`) REFERENCES `eb_budget_programs` (`id`) ON DELETE NO ACTION
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_budget_line_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `eb_budget_line_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `fiscal_year_id` bigint(20) unsigned NOT NULL,
  `program_id` bigint(20) unsigned NOT NULL,
  `object_class` enum('PS','MOOE','CO') NOT NULL,
  `name` varchar(255) NOT NULL,
  `object_code` varchar(20) DEFAULT NULL,
  `appropriation` decimal(15,2) NOT NULL DEFAULT 0.00,
  `sort_order` smallint(5) unsigned NOT NULL DEFAULT 0,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `line_items_fy_prog_cls_name_unique` (`fiscal_year_id`,`program_id`,`object_class`,`name`),
  KEY `eb_budget_line_items_program_id_foreign` (`program_id`),
  CONSTRAINT `eb_budget_line_items_fiscal_year_id_foreign` FOREIGN KEY (`fiscal_year_id`) REFERENCES `eb_fiscal_years` (`id`) ON DELETE CASCADE,
  CONSTRAINT `eb_budget_line_items_program_id_foreign` FOREIGN KEY (`program_id`) REFERENCES `eb_budget_programs` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_budget_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `eb_budget_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `action` varchar(30) NOT NULL,
  `module` varchar(50) NOT NULL,
  `subject_id` bigint(20) unsigned DEFAULT NULL,
  `description` text DEFAULT NULL,
  `properties` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`properties`)),
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `eb_budget_logs_module_created_at_index` (`module`,`created_at`),
  KEY `eb_budget_logs_action_created_at_index` (`action`,`created_at`),
  KEY `eb_budget_logs_user_id_index` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_budget_programs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `eb_budget_programs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `special_fund` enum('dev_fund','sk_fund','calamity','gad') DEFAULT NULL,
  `sort_order` smallint(5) unsigned NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_budget_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `eb_budget_settings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `barangay_name` varchar(255) DEFAULT NULL,
  `municipality` varchar(255) DEFAULT NULL,
  `province` varchar(255) DEFAULT NULL,
  `treasurer_name` varchar(255) DEFAULT NULL,
  `treasurer_position` varchar(255) DEFAULT 'Barangay Treasurer',
  `appropriation_chairman` varchar(255) DEFAULT NULL,
  `appropriation_chairman_position` varchar(255) DEFAULT NULL,
  `punong_barangay` varchar(255) DEFAULT NULL,
  `fund_account_no` varchar(255) DEFAULT NULL,
  `dev_fund_rate` decimal(5,2) NOT NULL DEFAULT 20.00,
  `dev_fund_base` varchar(30) NOT NULL DEFAULT 'nta',
  `sk_fund_rate` decimal(5,2) NOT NULL DEFAULT 10.00,
  `sk_fund_base` varchar(30) NOT NULL DEFAULT 'general_fund',
  `calamity_rate` decimal(5,2) NOT NULL DEFAULT 5.00,
  `calamity_base` varchar(30) NOT NULL DEFAULT 'regular_sources',
  `gad_rate` decimal(5,2) NOT NULL DEFAULT 5.00,
  `gad_base` varchar(30) NOT NULL DEFAULT 'total_budget',
  `petty_cash_fund_limit` decimal(15,2) NOT NULL DEFAULT 5000.00,
  `ca_deadline_days_local` tinyint(3) unsigned NOT NULL DEFAULT 60,
  `ca_deadline_days_travel` tinyint(3) unsigned NOT NULL DEFAULT 90,
  `voucher_prefix_dv` varchar(20) NOT NULL DEFAULT 'DV',
  `voucher_prefix_pcv` varchar(20) NOT NULL DEFAULT 'PCV',
  `voucher_prefix_payroll` varchar(20) NOT NULL DEFAULT 'PAY',
  `voucher_seq_padding` tinyint(3) unsigned NOT NULL DEFAULT 3,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_budget_suppliers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `eb_budget_suppliers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `tin` varchar(50) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `zip_code` varchar(10) DEFAULT NULL,
  `line_of_business` varchar(255) DEFAULT NULL,
  `business_type` enum('individual','corporation','na') NOT NULL DEFAULT 'na',
  `vat_type` enum('vat','non_vat','exempt') NOT NULL DEFAULT 'non_vat',
  `provides` enum('goods','services','both') NOT NULL DEFAULT 'goods',
  `contact_person` varchar(255) DEFAULT NULL,
  `contact_no` varchar(50) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `eb_budget_suppliers_tin_unique` (`tin`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_budget_transaction_attachments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `eb_budget_transaction_attachments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `transaction_id` bigint(20) unsigned NOT NULL,
  `original_name` varchar(255) NOT NULL,
  `stored_name` varchar(255) NOT NULL,
  `mime_type` varchar(100) DEFAULT NULL,
  `size` bigint(20) unsigned NOT NULL DEFAULT 0,
  `uploaded_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `eb_budget_transaction_attachments_transaction_id_foreign` (`transaction_id`),
  KEY `eb_budget_transaction_attachments_uploaded_by_foreign` (`uploaded_by`),
  CONSTRAINT `eb_budget_transaction_attachments_transaction_id_foreign` FOREIGN KEY (`transaction_id`) REFERENCES `eb_budget_transactions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `eb_budget_transaction_attachments_uploaded_by_foreign` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_budget_transaction_lines`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `eb_budget_transaction_lines` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `transaction_id` bigint(20) unsigned NOT NULL,
  `line_item_id` bigint(20) unsigned NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `eb_budget_transaction_lines_transaction_id_foreign` (`transaction_id`),
  KEY `eb_budget_transaction_lines_line_item_id_foreign` (`line_item_id`),
  CONSTRAINT `eb_budget_transaction_lines_line_item_id_foreign` FOREIGN KEY (`line_item_id`) REFERENCES `eb_budget_line_items` (`id`),
  CONSTRAINT `eb_budget_transaction_lines_transaction_id_foreign` FOREIGN KEY (`transaction_id`) REFERENCES `eb_budget_transactions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_budget_transactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `eb_budget_transactions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `fiscal_year_id` bigint(20) unsigned NOT NULL,
  `type` enum('income','expense') NOT NULL,
  `voucher_type` enum('DV','PCV','Payroll') DEFAULT NULL,
  `voucher_no` varchar(50) DEFAULT NULL,
  `status` enum('draft','approved','paid','cancelled') NOT NULL DEFAULT 'draft',
  `fund_cluster` varchar(50) DEFAULT NULL,
  `amount` decimal(15,2) NOT NULL,
  `tax_withheld` decimal(15,2) NOT NULL DEFAULT 0.00,
  `tax_type` varchar(30) DEFAULT NULL,
  `transaction_date` date NOT NULL,
  `reference_no` varchar(100) DEFAULT NULL,
  `payee` varchar(255) DEFAULT NULL,
  `payee_tin` varchar(50) DEFAULT NULL,
  `payee_address` text DEFAULT NULL,
  `payee_zip_code` varchar(10) DEFAULT NULL,
  `supplier_id` bigint(20) unsigned DEFAULT NULL,
  `mode_of_payment` enum('cash','check','bank_transfer') DEFAULT NULL,
  `check_no` varchar(100) DEFAULT NULL,
  `check_date` date DEFAULT NULL,
  `bank_name` varchar(100) DEFAULT NULL,
  `or_number` varchar(50) DEFAULT NULL,
  `or_date` date DEFAULT NULL,
  `approved_by` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `recorded_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `allocation_id` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `eb_budget_transactions_recorded_by_foreign` (`recorded_by`),
  KEY `ebt_fy_type_date_idx` (`fiscal_year_id`,`type`,`transaction_date`),
  KEY `ebt_fy_cat_idx` (`fiscal_year_id`),
  KEY `eb_budget_transactions_allocation_id_foreign` (`allocation_id`),
  KEY `eb_budget_transactions_supplier_id_foreign` (`supplier_id`),
  CONSTRAINT `eb_budget_transactions_allocation_id_foreign` FOREIGN KEY (`allocation_id`) REFERENCES `eb_budget_allocations` (`id`) ON DELETE SET NULL,
  CONSTRAINT `eb_budget_transactions_fiscal_year_id_foreign` FOREIGN KEY (`fiscal_year_id`) REFERENCES `eb_fiscal_years` (`id`) ON DELETE CASCADE,
  CONSTRAINT `eb_budget_transactions_recorded_by_foreign` FOREIGN KEY (`recorded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `eb_budget_transactions_supplier_id_foreign` FOREIGN KEY (`supplier_id`) REFERENCES `eb_budget_suppliers` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_cash_advances`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `eb_cash_advances` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `ca_no` varchar(255) NOT NULL,
  `fiscal_year_id` bigint(20) unsigned NOT NULL,
  `officer_id` bigint(20) unsigned NOT NULL,
  `allocation_id` bigint(20) unsigned DEFAULT NULL,
  `purpose` varchar(255) NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `date_granted` date NOT NULL,
  `deadline_date` date NOT NULL,
  `status` enum('open','liquidated','cancelled') NOT NULL DEFAULT 'open',
  `approved_by` varchar(255) DEFAULT NULL,
  `reference_no` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `eb_cash_advances_ca_no_unique` (`ca_no`),
  KEY `eb_cash_advances_fiscal_year_id_foreign` (`fiscal_year_id`),
  KEY `eb_cash_advances_officer_id_foreign` (`officer_id`),
  KEY `eb_cash_advances_allocation_id_foreign` (`allocation_id`),
  KEY `eb_cash_advances_created_by_foreign` (`created_by`),
  CONSTRAINT `eb_cash_advances_allocation_id_foreign` FOREIGN KEY (`allocation_id`) REFERENCES `eb_budget_allocations` (`id`) ON DELETE SET NULL,
  CONSTRAINT `eb_cash_advances_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `eb_cash_advances_fiscal_year_id_foreign` FOREIGN KEY (`fiscal_year_id`) REFERENCES `eb_fiscal_years` (`id`),
  CONSTRAINT `eb_cash_advances_officer_id_foreign` FOREIGN KEY (`officer_id`) REFERENCES `eb_accountable_officers` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_citizen`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `eb_citizen` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `qrcode` text NOT NULL,
  `fname` varchar(100) NOT NULL,
  `mname` text DEFAULT NULL,
  `lname` varchar(100) NOT NULL,
  `suffix` varchar(250) DEFAULT '',
  `bday` date NOT NULL,
  `bplace` text NOT NULL,
  `contact` text DEFAULT NULL,
  `email` varchar(250) DEFAULT NULL,
  `gender` tinyint(1) NOT NULL DEFAULT 1,
  `civil_status` int(11) NOT NULL,
  `is_soloparents` int(11) DEFAULT NULL,
  `address` int(11) DEFAULT NULL,
  `no` varchar(50) DEFAULT NULL,
  `street` varchar(250) DEFAULT NULL,
  `blk` int(11) DEFAULT NULL,
  `lot` int(11) DEFAULT NULL,
  `phase` int(11) DEFAULT NULL,
  `year_stay` date DEFAULT NULL,
  `date_created` datetime NOT NULL,
  `date_approved` datetime DEFAULT NULL,
  `approval_status` int(11) NOT NULL DEFAULT 2,
  `date_last_updated` datetime NOT NULL,
  `user_id_approved` int(11) DEFAULT NULL,
  `is_active` int(11) NOT NULL DEFAULT 1,
  `owner_status` tinyint(1) NOT NULL,
  `complete_address` text DEFAULT NULL,
  `voters` tinyint(1) NOT NULL DEFAULT 0,
  `is_notify` tinyint(1) DEFAULT 0,
  `is_id_release` tinyint(1) DEFAULT 0,
  `pricinct_no` varchar(100) DEFAULT NULL,
  `note` text DEFAULT NULL,
  `is_download` int(11) NOT NULL DEFAULT 0,
  `is_verify` int(11) DEFAULT 0,
  `ic_email` text DEFAULT NULL,
  `ic_fullname` text DEFAULT NULL,
  `ic_contact` text DEFAULT NULL,
  `ic_address` text DEFAULT NULL,
  `ic_relationship` text DEFAULT NULL,
  `is_pwd` int(11) NOT NULL,
  `citizenship` varchar(250) DEFAULT NULL,
  `occupation` varchar(250) DEFAULT NULL,
  `profile` varchar(250) DEFAULT NULL,
  `familyId` int(11) DEFAULT NULL,
  `isHead` tinyint(4) NOT NULL DEFAULT 0,
  `relationId` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id` (`id`),
  KEY `eb_citizen_qrcode_index` (`qrcode`(768)),
  KEY `eb_citizen_is_active_index` (`is_active`),
  KEY `eb_citizen_lname_fname_index` (`lname`,`fname`)
) ENGINE=InnoDB AUTO_INCREMENT=31 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_citizen_id_templates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `eb_citizen_id_templates` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `orientation_front` enum('landscape','portrait') NOT NULL DEFAULT 'landscape',
  `bg_front` varchar(255) DEFAULT NULL,
  `html_front` longtext DEFAULT NULL,
  `layout_front` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`layout_front`)),
  `css_front` longtext DEFAULT NULL,
  `js_front` longtext DEFAULT NULL,
  `orientation_back` enum('landscape','portrait') NOT NULL DEFAULT 'landscape',
  `bg_back` varchar(255) DEFAULT NULL,
  `html_back` longtext DEFAULT NULL,
  `layout_back` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`layout_back`)),
  `css_back` longtext DEFAULT NULL,
  `css_shared` longtext DEFAULT NULL,
  `js_shared` longtext DEFAULT NULL,
  `js_back` longtext DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_citizen_ids`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `eb_citizen_ids` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `citizen_id` int(11) NOT NULL,
  `generated_by` bigint(20) unsigned DEFAULT NULL,
  `valid_until` date NOT NULL,
  `sig_front` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_citizen_tag`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `eb_citizen_tag` (
  `citizen_id` int(11) NOT NULL,
  `tag_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`citizen_id`,`tag_id`),
  KEY `eb_citizen_tag_tag_id_foreign` (`tag_id`),
  CONSTRAINT `eb_citizen_tag_tag_id_foreign` FOREIGN KEY (`tag_id`) REFERENCES `eb_tags` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_civil`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `eb_civil` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `description` varchar(250) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_clearance`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `eb_clearance` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `fname` varchar(50) NOT NULL,
  `mname` varchar(50) DEFAULT NULL,
  `lname` varchar(50) NOT NULL,
  `suffix` varchar(10) DEFAULT NULL,
  `address` int(11) NOT NULL,
  `pos` date NOT NULL,
  `bday` date NOT NULL,
  `bplace` varchar(150) NOT NULL,
  `gender` text NOT NULL,
  `purpose` varchar(100) NOT NULL,
  `remarks` varchar(250) DEFAULT NULL,
  `civilstatus` int(11) NOT NULL,
  `ctc` varchar(250) DEFAULT NULL,
  `date_issue` varchar(50) NOT NULL,
  `given_by` int(11) NOT NULL,
  `blk` int(11) DEFAULT NULL,
  `lot` int(11) DEFAULT NULL,
  `phase` int(11) DEFAULT NULL,
  `complete_address` varchar(250) DEFAULT NULL,
  `no` int(11) DEFAULT NULL,
  `street` varchar(250) DEFAULT NULL,
  `citizen_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_document_fields`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `eb_document_fields` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `document_type_id` bigint(20) unsigned NOT NULL,
  `field_key` varchar(255) NOT NULL,
  `field_label` varchar(255) NOT NULL,
  `field_type` varchar(255) NOT NULL DEFAULT 'text',
  `column_width` tinyint(3) unsigned NOT NULL DEFAULT 12,
  `field_options` text DEFAULT NULL,
  `default_value` varchar(255) DEFAULT NULL,
  `is_required` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `eb_document_fields_document_type_id_foreign` (`document_type_id`),
  CONSTRAINT `eb_document_fields_document_type_id_foreign` FOREIGN KEY (`document_type_id`) REFERENCES `eb_document_types` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_document_purposes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `eb_document_purposes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `eb_document_purposes_name_unique` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=31 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_document_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `eb_document_requests` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `document_type_id` bigint(20) unsigned NOT NULL,
  `doc_number` int(10) unsigned DEFAULT NULL,
  `citizen_id` bigint(20) unsigned NOT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `purpose` varchar(255) DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'pending',
  `custom_fields` text DEFAULT NULL,
  `body_override` longtext DEFAULT NULL,
  `is_paid` tinyint(1) NOT NULL DEFAULT 0,
  `fee` decimal(10,2) NOT NULL DEFAULT 0.00,
  `fee_paid` tinyint(1) NOT NULL DEFAULT 0,
  `amount_paid` decimal(10,2) DEFAULT NULL,
  `or_number` varchar(255) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `template_version_id` bigint(20) unsigned DEFAULT NULL,
  `approved_by` bigint(20) unsigned DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `released_at` timestamp NULL DEFAULT NULL,
  `print_count` int(10) unsigned NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `eb_document_requests_document_type_id_foreign` (`document_type_id`),
  KEY `eb_document_requests_template_version_id_foreign` (`template_version_id`),
  CONSTRAINT `eb_document_requests_document_type_id_foreign` FOREIGN KEY (`document_type_id`) REFERENCES `eb_document_types` (`id`),
  CONSTRAINT `eb_document_requests_template_version_id_foreign` FOREIGN KEY (`template_version_id`) REFERENCES `eb_document_template_versions` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=36 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_document_template_versions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `eb_document_template_versions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `document_template_id` bigint(20) unsigned NOT NULL,
  `version` smallint(5) unsigned NOT NULL DEFAULT 1,
  `paper_bg` varchar(255) DEFAULT NULL,
  `paper_size` varchar(255) NOT NULL DEFAULT 'a4',
  `orientation` varchar(255) NOT NULL DEFAULT 'portrait',
  `padding_top` smallint(5) unsigned NOT NULL DEFAULT 160,
  `padding_bottom` smallint(5) unsigned NOT NULL DEFAULT 72,
  `padding_left` smallint(5) unsigned NOT NULL DEFAULT 72,
  `padding_right` smallint(5) unsigned NOT NULL DEFAULT 72,
  `change_note` varchar(255) DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `eb_document_template_versions_document_template_id_foreign` (`document_template_id`),
  KEY `eb_document_template_versions_created_by_foreign` (`created_by`),
  CONSTRAINT `eb_document_template_versions_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `eb_document_template_versions_document_template_id_foreign` FOREIGN KEY (`document_template_id`) REFERENCES `eb_document_templates` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_document_templates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `eb_document_templates` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `paper_bg` varchar(255) DEFAULT NULL,
  `paper_size` varchar(255) NOT NULL DEFAULT 'a4',
  `orientation` varchar(255) NOT NULL DEFAULT 'portrait',
  `padding_top` smallint(5) unsigned NOT NULL DEFAULT 160,
  `padding_bottom` smallint(5) unsigned NOT NULL DEFAULT 72,
  `padding_left` smallint(5) unsigned NOT NULL DEFAULT 72,
  `padding_right` smallint(5) unsigned NOT NULL DEFAULT 72,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `current_version_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `eb_document_templates_current_version_id_foreign` (`current_version_id`),
  CONSTRAINT `eb_document_templates_current_version_id_foreign` FOREIGN KEY (`current_version_id`) REFERENCES `eb_document_template_versions` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_document_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `eb_document_types` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `document_template_id` bigint(20) unsigned DEFAULT NULL,
  `document_template_version_id` bigint(20) unsigned DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `short_name` varchar(255) DEFAULT NULL,
  `prefix` varchar(20) NOT NULL,
  `description` text DEFAULT NULL,
  `is_paid` tinyint(1) NOT NULL DEFAULT 0,
  `fee` decimal(10,2) NOT NULL DEFAULT 0.00,
  `requires_approval` tinyint(1) NOT NULL DEFAULT 1,
  `template_body` longtext DEFAULT NULL,
  `allow_body_edit` tinyint(1) NOT NULL DEFAULT 0,
  `header_style` varchar(255) NOT NULL DEFAULT 'classic',
  `paper_bg` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `eb_document_types_prefix_unique` (`prefix`),
  KEY `eb_document_types_document_template_id_foreign` (`document_template_id`),
  KEY `eb_document_types_document_template_version_id_foreign` (`document_template_version_id`),
  CONSTRAINT `eb_document_types_document_template_id_foreign` FOREIGN KEY (`document_template_id`) REFERENCES `eb_document_templates` (`id`) ON DELETE SET NULL,
  CONSTRAINT `eb_document_types_document_template_version_id_foreign` FOREIGN KEY (`document_template_version_id`) REFERENCES `eb_document_template_versions` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=36 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_event`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `eb_event` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `category` varchar(100) DEFAULT NULL,
  `venue` varchar(200) DEFAULT NULL,
  `event_date` date NOT NULL,
  `event_start` time DEFAULT NULL,
  `event_end` time DEFAULT NULL,
  `cover_photo` varchar(255) DEFAULT NULL,
  `raffle_enabled` tinyint(4) NOT NULL DEFAULT 0,
  `allow_winner_repeat` tinyint(4) NOT NULL DEFAULT 0,
  `raffle_pin` varchar(6) DEFAULT NULL,
  `raffle_pin_expires_at` datetime DEFAULT NULL,
  `status` enum('open','closed') NOT NULL DEFAULT 'open',
  `user_id` int(11) NOT NULL,
  `date_created` datetime NOT NULL,
  `date_updated` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_event_attendance`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `eb_event_attendance` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `eventId` int(11) NOT NULL,
  `citizenId` int(11) NOT NULL,
  `time_in` datetime NOT NULL,
  `method` enum('qr','manual','bulk') NOT NULL DEFAULT 'manual',
  PRIMARY KEY (`id`),
  UNIQUE KEY `eb_event_attendance_eventid_citizenid_unique` (`eventId`,`citizenId`)
) ENGINE=InnoDB AUTO_INCREMENT=73 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_event_winner`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `eb_event_winner` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `eventId` int(11) NOT NULL,
  `citizenId` int(11) NOT NULL,
  `round` int(11) NOT NULL,
  `prize_label` varchar(200) DEFAULT NULL,
  `drawn_at` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_family`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `eb_family` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `householdId` int(11) NOT NULL,
  `citizenId` int(11) NOT NULL,
  `date_created` datetime NOT NULL,
  `date_last_updated` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_fiscal_years`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `eb_fiscal_years` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `year` smallint(6) NOT NULL,
  `label` varchar(100) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 0,
  `beginning_cash_balance` decimal(15,2) NOT NULL DEFAULT 0.00,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `eb_fiscal_years_year_unique` (`year`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_household`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `eb_household` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `addressId` int(11) NOT NULL,
  `blk` int(11) DEFAULT NULL,
  `lot` int(11) DEFAULT NULL,
  `phaseStreet` varchar(100) DEFAULT NULL,
  `no` varchar(100) DEFAULT NULL,
  `internal` varchar(99) DEFAULT NULL,
  `completeAdress` text DEFAULT NULL,
  `citizenHeadId` int(11) DEFAULT NULL,
  `user_id` int(11) NOT NULL,
  `date_created` datetime NOT NULL,
  `date_lastupdated` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_income_estimates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `eb_income_estimates` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `fiscal_year_id` bigint(20) unsigned NOT NULL,
  `source_type` enum('nta','rpt','clearance','other') NOT NULL DEFAULT 'other',
  `source_label` varchar(255) NOT NULL,
  `estimated_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `two_years_ago_actual` decimal(15,2) DEFAULT NULL,
  `prior_year_actual` decimal(15,2) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `eb_income_estimates_fiscal_year_id_source_label_unique` (`fiscal_year_id`,`source_label`),
  CONSTRAINT `eb_income_estimates_fiscal_year_id_foreign` FOREIGN KEY (`fiscal_year_id`) REFERENCES `eb_fiscal_years` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_inventory_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `eb_inventory_categories` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `icon` varchar(255) DEFAULT 'mgc_box_line',
  `color` varchar(255) DEFAULT 'primary',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `eb_inventory_categories_slug_unique` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_inventory_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `eb_inventory_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `category_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `sku` varchar(255) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `unit` varchar(255) NOT NULL DEFAULT 'pcs',
  `description` text DEFAULT NULL,
  `stock` int(11) NOT NULL DEFAULT 0,
  `low_stock_threshold` int(11) NOT NULL DEFAULT 10,
  `requires_approval` tinyint(1) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `eb_inventory_items_sku_unique` (`sku`),
  KEY `eb_inventory_items_category_id_foreign` (`category_id`),
  CONSTRAINT `eb_inventory_items_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `eb_inventory_categories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_inventory_release_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `eb_inventory_release_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `release_id` bigint(20) unsigned NOT NULL,
  `item_id` bigint(20) unsigned NOT NULL,
  `quantity` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `eb_inventory_release_items_release_id_foreign` (`release_id`),
  KEY `eb_inventory_release_items_item_id_foreign` (`item_id`),
  CONSTRAINT `eb_inventory_release_items_item_id_foreign` FOREIGN KEY (`item_id`) REFERENCES `eb_inventory_items` (`id`) ON DELETE CASCADE,
  CONSTRAINT `eb_inventory_release_items_release_id_foreign` FOREIGN KEY (`release_id`) REFERENCES `eb_inventory_releases` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_inventory_releases`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `eb_inventory_releases` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `citizen_id` int(11) NOT NULL,
  `released_by` bigint(20) unsigned DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'pending',
  `purpose` text DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `approved_by` bigint(20) unsigned DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `released_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `eb_inventory_releases_citizen_id_foreign` (`citizen_id`),
  KEY `eb_inventory_releases_released_by_foreign` (`released_by`),
  KEY `eb_inventory_releases_approved_by_foreign` (`approved_by`),
  CONSTRAINT `eb_inventory_releases_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `eb_inventory_releases_citizen_id_foreign` FOREIGN KEY (`citizen_id`) REFERENCES `eb_citizen` (`id`) ON DELETE CASCADE,
  CONSTRAINT `eb_inventory_releases_released_by_foreign` FOREIGN KEY (`released_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_inventory_stock_ins`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `eb_inventory_stock_ins` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `type` varchar(255) NOT NULL DEFAULT 'stock_in',
  `item_id` bigint(20) unsigned NOT NULL,
  `quantity` int(11) NOT NULL,
  `quantity_before` int(11) DEFAULT NULL,
  `source` varchar(255) DEFAULT NULL,
  `reference` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `eb_inventory_stock_ins_item_id_foreign` (`item_id`),
  KEY `eb_inventory_stock_ins_created_by_foreign` (`created_by`),
  CONSTRAINT `eb_inventory_stock_ins_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `eb_inventory_stock_ins_item_id_foreign` FOREIGN KEY (`item_id`) REFERENCES `eb_inventory_items` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_liquidation_lines`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `eb_liquidation_lines` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `liquidation_report_id` bigint(20) unsigned NOT NULL,
  `or_no` varchar(255) DEFAULT NULL,
  `receipt_date` date DEFAULT NULL,
  `particulars` varchar(255) NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `eb_liquidation_lines_liquidation_report_id_foreign` (`liquidation_report_id`),
  CONSTRAINT `eb_liquidation_lines_liquidation_report_id_foreign` FOREIGN KEY (`liquidation_report_id`) REFERENCES `eb_liquidation_reports` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_liquidation_reports`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `eb_liquidation_reports` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `cash_advance_id` bigint(20) unsigned NOT NULL,
  `liquidation_date` date NOT NULL,
  `total_expenses` decimal(15,2) NOT NULL DEFAULT 0.00,
  `refund_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `refund_or_no` varchar(255) DEFAULT NULL,
  `status` enum('draft','closed') NOT NULL DEFAULT 'draft',
  `notes` text DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `eb_liquidation_reports_cash_advance_id_unique` (`cash_advance_id`),
  KEY `eb_liquidation_reports_created_by_foreign` (`created_by`),
  CONSTRAINT `eb_liquidation_reports_cash_advance_id_foreign` FOREIGN KEY (`cash_advance_id`) REFERENCES `eb_cash_advances` (`id`),
  CONSTRAINT `eb_liquidation_reports_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `eb_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `citizen_id` int(11) DEFAULT NULL,
  `user_id` int(11) NOT NULL,
  `dateTime` datetime NOT NULL,
  `type` int(11) NOT NULL,
  `table_id` int(11) NOT NULL,
  `table_name` varchar(250) NOT NULL,
  `fields` text NOT NULL,
  `action` varchar(250) NOT NULL,
  `note` text DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_officials`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `eb_officials` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `position` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `sort_order` smallint(5) unsigned NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_purpose`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `eb_purpose` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `created_date` datetime NOT NULL,
  `user_created` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_relation`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `eb_relation` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `description` varchar(100) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `eb_settings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `barangay_name` varchar(255) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `municipality` varchar(255) DEFAULT NULL,
  `province` varchar(255) DEFAULT NULL,
  `contact` varchar(20) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `logo` varchar(500) DEFAULT NULL,
  `municipal_logo` varchar(500) DEFAULT NULL,
  `captain_name` varchar(255) DEFAULT NULL,
  `captain_position` varchar(100) NOT NULL DEFAULT 'Barangay Captain',
  `captain_signature` varchar(255) DEFAULT NULL,
  `citizen_id_prefix` varchar(20) NOT NULL DEFAULT 'EBT',
  `citizen_id_digits` tinyint(4) NOT NULL DEFAULT 6,
  `id_validity` varchar(4) NOT NULL DEFAULT '2y',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eb_tags`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `eb_tags` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `color` varchar(7) NOT NULL DEFAULT '#6366f1',
  `description` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `failed_jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `migrations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `model_has_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `model_has_permissions` (
  `permission_id` bigint(20) unsigned NOT NULL,
  `model_type` varchar(255) NOT NULL,
  `model_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`permission_id`,`model_id`,`model_type`),
  KEY `model_has_permissions_model_id_model_type_index` (`model_id`,`model_type`),
  CONSTRAINT `model_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `model_has_roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `model_has_roles` (
  `role_id` bigint(20) unsigned NOT NULL,
  `model_type` varchar(255) NOT NULL,
  `model_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`role_id`,`model_id`,`model_type`),
  KEY `model_has_roles_model_id_model_type_index` (`model_id`,`model_type`),
  CONSTRAINT `model_has_roles_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `permissions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `guard_name` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `permissions_name_guard_name_unique` (`name`,`guard_name`)
) ENGINE=InnoDB AUTO_INCREMENT=55 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `personal_access_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `personal_access_tokens` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tokenable_type` varchar(255) NOT NULL,
  `tokenable_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `token` varchar(64) NOT NULL,
  `abilities` text DEFAULT NULL,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `role_has_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `role_has_permissions` (
  `permission_id` bigint(20) unsigned NOT NULL,
  `role_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`permission_id`,`role_id`),
  KEY `role_has_permissions_role_id_foreign` (`role_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `roles` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `guard_name` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `roles_name_guard_name_unique` (`name`,`guard_name`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `landing_route` varchar(255) DEFAULT NULL,
  `two_factor_secret` text DEFAULT NULL,
  `two_factor_recovery_codes` text DEFAULT NULL,
  `two_factor_confirmed_at` timestamp NULL DEFAULT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

