-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:4033
-- Generation Time: Aug 17, 2026 at 03:45 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.3.31

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `practice_e-demo`
--

-- --------------------------------------------------------

--
-- Table structure for table `eb_accountable_officers`
--

CREATE TABLE `eb_accountable_officers` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `position` varchar(255) DEFAULT NULL,
  `fidelity_bond_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `eb_activity_logs`
--

CREATE TABLE `eb_activity_logs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `action` varchar(30) NOT NULL,
  `subject_type` varchar(50) DEFAULT NULL,
  `subject_id` bigint(20) UNSIGNED DEFAULT NULL,
  `description` text DEFAULT NULL,
  `properties` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`properties`)),
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `eb_address`
--

CREATE TABLE `eb_address` (
  `id` int(11) NOT NULL,
  `description` varchar(250) NOT NULL,
  `is_subd` int(11) NOT NULL DEFAULT 0,
  `rules_required` varchar(250) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `eb_approval_status`
--

CREATE TABLE `eb_approval_status` (
  `id` int(11) NOT NULL,
  `description` varchar(250) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `eb_blotters`
--

CREATE TABLE `eb_blotters` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `blotter_no` varchar(20) NOT NULL,
  `filed_date` date NOT NULL,
  `incident_date` date NOT NULL,
  `incident_time` time DEFAULT NULL,
  `type` varchar(50) NOT NULL DEFAULT 'complaint',
  `incident_location` varchar(255) DEFAULT NULL,
  `complainant_name` varchar(255) NOT NULL,
  `complainant_address` varchar(255) DEFAULT NULL,
  `complainant_contact` varchar(30) DEFAULT NULL,
  `complainant_citizen_id` bigint(20) UNSIGNED DEFAULT NULL,
  `respondent_name` varchar(255) NOT NULL,
  `respondent_address` varchar(255) DEFAULT NULL,
  `respondent_contact` varchar(30) DEFAULT NULL,
  `respondent_citizen_id` bigint(20) UNSIGNED DEFAULT NULL,
  `narrative` text NOT NULL,
  `witnesses` text DEFAULT NULL,
  `attending_officer` varchar(255) DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'filed',
  `action_taken` text DEFAULT NULL,
  `settled_date` date DEFAULT NULL,
  `referred_to` varchar(255) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `photos` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`photos`)),
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `updated_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `eb_blotter_actions`
--

CREATE TABLE `eb_blotter_actions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `blotter_id` bigint(20) UNSIGNED NOT NULL,
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
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `eb_blotter_action_versions`
--

CREATE TABLE `eb_blotter_action_versions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `action_id` int(11) NOT NULL,
  `blotter_id` int(11) NOT NULL,
  `outcome` varchar(30) NOT NULL,
  `complainant_attendance` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`complainant_attendance`)),
  `respondent_attendance` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`respondent_attendance`)),
  `notes` longtext DEFAULT NULL,
  `photos` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`photos`)),
  `saved_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `eb_blotter_parties`
--

CREATE TABLE `eb_blotter_parties` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `blotter_id` bigint(20) UNSIGNED NOT NULL,
  `role` enum('complainant','respondent') NOT NULL,
  `citizen_id` int(11) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `address` varchar(255) DEFAULT NULL,
  `contact` varchar(30) DEFAULT NULL,
  `sort_order` tinyint(3) UNSIGNED NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `eb_budget_adjustments`
--

CREATE TABLE `eb_budget_adjustments` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `fiscal_year_id` bigint(20) UNSIGNED NOT NULL,
  `allocation_id` bigint(20) UNSIGNED NOT NULL,
  `type` enum('supplemental','realignment_in','realignment_out') NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `reference_no` varchar(255) DEFAULT NULL,
  `effectivity_date` date NOT NULL,
  `notes` text DEFAULT NULL,
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `eb_budget_allocations`
--

CREATE TABLE `eb_budget_allocations` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `fiscal_year_id` bigint(20) UNSIGNED NOT NULL,
  `program_id` bigint(20) UNSIGNED NOT NULL,
  `object_class` enum('PS','MOOE','CO') NOT NULL,
  `appropriation` decimal(15,2) NOT NULL DEFAULT 0.00,
  `computed_amount` decimal(15,2) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `eb_budget_line_items`
--

CREATE TABLE `eb_budget_line_items` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `fiscal_year_id` bigint(20) UNSIGNED NOT NULL,
  `program_id` bigint(20) UNSIGNED NOT NULL,
  `object_class` enum('PS','MOOE','CO') NOT NULL,
  `name` varchar(255) NOT NULL,
  `object_code` varchar(20) DEFAULT NULL,
  `appropriation` decimal(15,2) NOT NULL DEFAULT 0.00,
  `sort_order` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `eb_budget_logs`
--

CREATE TABLE `eb_budget_logs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `action` varchar(30) NOT NULL,
  `module` varchar(50) NOT NULL,
  `subject_id` bigint(20) UNSIGNED DEFAULT NULL,
  `description` text DEFAULT NULL,
  `properties` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`properties`)),
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `eb_budget_programs`
--

CREATE TABLE `eb_budget_programs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `special_fund` enum('dev_fund','sk_fund','calamity','gad') DEFAULT NULL,
  `sort_order` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `eb_budget_settings`
--

CREATE TABLE `eb_budget_settings` (
  `id` bigint(20) UNSIGNED NOT NULL,
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
  `ca_deadline_days_local` tinyint(3) UNSIGNED NOT NULL DEFAULT 60,
  `ca_deadline_days_travel` tinyint(3) UNSIGNED NOT NULL DEFAULT 90,
  `voucher_prefix_dv` varchar(20) NOT NULL DEFAULT 'DV',
  `voucher_prefix_pcv` varchar(20) NOT NULL DEFAULT 'PCV',
  `voucher_prefix_payroll` varchar(20) NOT NULL DEFAULT 'PAY',
  `voucher_seq_padding` tinyint(3) UNSIGNED NOT NULL DEFAULT 3,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `eb_budget_suppliers`
--

CREATE TABLE `eb_budget_suppliers` (
  `id` bigint(20) UNSIGNED NOT NULL,
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
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `eb_budget_transactions`
--

CREATE TABLE `eb_budget_transactions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `fiscal_year_id` bigint(20) UNSIGNED NOT NULL,
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
  `supplier_id` bigint(20) UNSIGNED DEFAULT NULL,
  `mode_of_payment` enum('cash','check','bank_transfer') DEFAULT NULL,
  `check_no` varchar(100) DEFAULT NULL,
  `check_date` date DEFAULT NULL,
  `bank_name` varchar(100) DEFAULT NULL,
  `or_number` varchar(50) DEFAULT NULL,
  `or_date` date DEFAULT NULL,
  `approved_by` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `recorded_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `allocation_id` bigint(20) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `eb_budget_transaction_attachments`
--

CREATE TABLE `eb_budget_transaction_attachments` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `transaction_id` bigint(20) UNSIGNED NOT NULL,
  `original_name` varchar(255) NOT NULL,
  `stored_name` varchar(255) NOT NULL,
  `mime_type` varchar(100) DEFAULT NULL,
  `size` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `uploaded_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `eb_budget_transaction_lines`
--

CREATE TABLE `eb_budget_transaction_lines` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `transaction_id` bigint(20) UNSIGNED NOT NULL,
  `line_item_id` bigint(20) UNSIGNED NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `eb_cash_advances`
--

CREATE TABLE `eb_cash_advances` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `ca_no` varchar(255) NOT NULL,
  `fiscal_year_id` bigint(20) UNSIGNED NOT NULL,
  `officer_id` bigint(20) UNSIGNED NOT NULL,
  `allocation_id` bigint(20) UNSIGNED DEFAULT NULL,
  `purpose` varchar(255) NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `date_granted` date NOT NULL,
  `deadline_date` date NOT NULL,
  `status` enum('open','liquidated','cancelled') NOT NULL DEFAULT 'open',
  `approved_by` varchar(255) DEFAULT NULL,
  `reference_no` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `eb_citizen`
--

CREATE TABLE `eb_citizen` (
  `id` int(11) NOT NULL,
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
  `relationId` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `eb_citizen_ids`
--

CREATE TABLE `eb_citizen_ids` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `citizen_id` int(11) NOT NULL,
  `generated_by` bigint(20) UNSIGNED DEFAULT NULL,
  `valid_until` date NOT NULL,
  `sig_front` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `eb_citizen_id_templates`
--

CREATE TABLE `eb_citizen_id_templates` (
  `id` bigint(20) UNSIGNED NOT NULL,
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
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `eb_citizen_tag`
--

CREATE TABLE `eb_citizen_tag` (
  `citizen_id` int(11) NOT NULL,
  `tag_id` bigint(20) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `eb_civil`
--

CREATE TABLE `eb_civil` (
  `id` int(11) NOT NULL,
  `description` varchar(250) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `eb_clearance`
--

CREATE TABLE `eb_clearance` (
  `id` int(11) NOT NULL,
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
  `citizen_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `eb_document_fields`
--

CREATE TABLE `eb_document_fields` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `document_type_id` bigint(20) UNSIGNED NOT NULL,
  `field_key` varchar(255) NOT NULL,
  `field_label` varchar(255) NOT NULL,
  `field_type` varchar(255) NOT NULL DEFAULT 'text',
  `column_width` tinyint(3) UNSIGNED NOT NULL DEFAULT 12,
  `field_options` text DEFAULT NULL,
  `default_value` varchar(255) DEFAULT NULL,
  `is_required` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `eb_document_purposes`
--

CREATE TABLE `eb_document_purposes` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `eb_document_requests`
--

CREATE TABLE `eb_document_requests` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `document_type_id` bigint(20) UNSIGNED NOT NULL,
  `doc_number` int(10) UNSIGNED DEFAULT NULL,
  `citizen_id` bigint(20) UNSIGNED NOT NULL,
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
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
  `template_version_id` bigint(20) UNSIGNED DEFAULT NULL,
  `approved_by` bigint(20) UNSIGNED DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `released_at` timestamp NULL DEFAULT NULL,
  `print_count` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `eb_document_templates`
--

CREATE TABLE `eb_document_templates` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `paper_bg` varchar(255) DEFAULT NULL,
  `paper_size` varchar(255) NOT NULL DEFAULT 'a4',
  `orientation` varchar(255) NOT NULL DEFAULT 'portrait',
  `padding_top` smallint(5) UNSIGNED NOT NULL DEFAULT 160,
  `padding_bottom` smallint(5) UNSIGNED NOT NULL DEFAULT 72,
  `padding_left` smallint(5) UNSIGNED NOT NULL DEFAULT 72,
  `padding_right` smallint(5) UNSIGNED NOT NULL DEFAULT 72,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `current_version_id` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `eb_document_template_versions`
--

CREATE TABLE `eb_document_template_versions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `document_template_id` bigint(20) UNSIGNED NOT NULL,
  `version` smallint(5) UNSIGNED NOT NULL DEFAULT 1,
  `paper_bg` varchar(255) DEFAULT NULL,
  `paper_size` varchar(255) NOT NULL DEFAULT 'a4',
  `orientation` varchar(255) NOT NULL DEFAULT 'portrait',
  `padding_top` smallint(5) UNSIGNED NOT NULL DEFAULT 160,
  `padding_bottom` smallint(5) UNSIGNED NOT NULL DEFAULT 72,
  `padding_left` smallint(5) UNSIGNED NOT NULL DEFAULT 72,
  `padding_right` smallint(5) UNSIGNED NOT NULL DEFAULT 72,
  `change_note` varchar(255) DEFAULT NULL,
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `eb_document_types`
--

CREATE TABLE `eb_document_types` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `document_template_id` bigint(20) UNSIGNED DEFAULT NULL,
  `document_template_version_id` bigint(20) UNSIGNED DEFAULT NULL,
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
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `eb_event`
--

CREATE TABLE `eb_event` (
  `id` int(11) NOT NULL,
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
  `date_updated` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `eb_event_attendance`
--

CREATE TABLE `eb_event_attendance` (
  `id` int(11) NOT NULL,
  `eventId` int(11) NOT NULL,
  `citizenId` int(11) NOT NULL,
  `time_in` datetime NOT NULL,
  `method` enum('qr','manual','bulk') NOT NULL DEFAULT 'manual'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `eb_event_winner`
--

CREATE TABLE `eb_event_winner` (
  `id` int(11) NOT NULL,
  `eventId` int(11) NOT NULL,
  `citizenId` int(11) NOT NULL,
  `round` int(11) NOT NULL,
  `prize_label` varchar(200) DEFAULT NULL,
  `drawn_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `eb_family`
--

CREATE TABLE `eb_family` (
  `id` int(11) NOT NULL,
  `householdId` int(11) NOT NULL,
  `citizenId` int(11) NOT NULL,
  `date_created` datetime NOT NULL,
  `date_last_updated` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `eb_fiscal_years`
--

CREATE TABLE `eb_fiscal_years` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `year` smallint(6) NOT NULL,
  `label` varchar(100) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 0,
  `beginning_cash_balance` decimal(15,2) NOT NULL DEFAULT 0.00,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `eb_household`
--

CREATE TABLE `eb_household` (
  `id` int(11) NOT NULL,
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
  `date_lastupdated` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `eb_income_estimates`
--

CREATE TABLE `eb_income_estimates` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `fiscal_year_id` bigint(20) UNSIGNED NOT NULL,
  `source_type` enum('nta','rpt','clearance','other') NOT NULL DEFAULT 'other',
  `source_label` varchar(255) NOT NULL,
  `estimated_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `two_years_ago_actual` decimal(15,2) DEFAULT NULL,
  `prior_year_actual` decimal(15,2) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `updated_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `eb_inventory_categories`
--

CREATE TABLE `eb_inventory_categories` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `icon` varchar(255) DEFAULT 'mgc_box_line',
  `color` varchar(255) DEFAULT 'primary',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `eb_inventory_items`
--

CREATE TABLE `eb_inventory_items` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `category_id` bigint(20) UNSIGNED NOT NULL,
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
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `eb_inventory_releases`
--

CREATE TABLE `eb_inventory_releases` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `citizen_id` int(11) NOT NULL,
  `released_by` bigint(20) UNSIGNED DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'pending',
  `purpose` text DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `approved_by` bigint(20) UNSIGNED DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `released_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `eb_inventory_release_items`
--

CREATE TABLE `eb_inventory_release_items` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `release_id` bigint(20) UNSIGNED NOT NULL,
  `item_id` bigint(20) UNSIGNED NOT NULL,
  `quantity` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `eb_inventory_stock_ins`
--

CREATE TABLE `eb_inventory_stock_ins` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `type` varchar(255) NOT NULL DEFAULT 'stock_in',
  `item_id` bigint(20) UNSIGNED NOT NULL,
  `quantity` int(11) NOT NULL,
  `quantity_before` int(11) DEFAULT NULL,
  `source` varchar(255) DEFAULT NULL,
  `reference` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `eb_liquidation_lines`
--

CREATE TABLE `eb_liquidation_lines` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `liquidation_report_id` bigint(20) UNSIGNED NOT NULL,
  `or_no` varchar(255) DEFAULT NULL,
  `receipt_date` date DEFAULT NULL,
  `particulars` varchar(255) NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `eb_liquidation_reports`
--

CREATE TABLE `eb_liquidation_reports` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `cash_advance_id` bigint(20) UNSIGNED NOT NULL,
  `liquidation_date` date NOT NULL,
  `total_expenses` decimal(15,2) NOT NULL DEFAULT 0.00,
  `refund_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `refund_or_no` varchar(255) DEFAULT NULL,
  `status` enum('draft','closed') NOT NULL DEFAULT 'draft',
  `notes` text DEFAULT NULL,
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `eb_logs`
--

CREATE TABLE `eb_logs` (
  `id` int(11) NOT NULL,
  `citizen_id` int(11) DEFAULT NULL,
  `user_id` int(11) NOT NULL,
  `dateTime` datetime NOT NULL,
  `type` int(11) NOT NULL,
  `table_id` int(11) NOT NULL,
  `table_name` varchar(250) NOT NULL,
  `fields` text NOT NULL,
  `action` varchar(250) NOT NULL,
  `note` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `eb_officials`
--

CREATE TABLE `eb_officials` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `position` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `sort_order` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `eb_purpose`
--

CREATE TABLE `eb_purpose` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `created_date` datetime NOT NULL,
  `user_created` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `eb_relation`
--

CREATE TABLE `eb_relation` (
  `id` int(11) NOT NULL,
  `description` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `eb_settings`
--

CREATE TABLE `eb_settings` (
  `id` bigint(20) UNSIGNED NOT NULL,
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
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `eb_tags`
--

CREATE TABLE `eb_tags` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `color` varchar(7) NOT NULL DEFAULT '#6366f1',
  `description` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `failed_jobs`
--

CREATE TABLE `failed_jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `migrations`
--

CREATE TABLE `migrations` (
  `id` int(10) UNSIGNED NOT NULL,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `model_has_permissions`
--

CREATE TABLE `model_has_permissions` (
  `permission_id` bigint(20) UNSIGNED NOT NULL,
  `model_type` varchar(255) NOT NULL,
  `model_id` bigint(20) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `model_has_roles`
--

CREATE TABLE `model_has_roles` (
  `role_id` bigint(20) UNSIGNED NOT NULL,
  `model_type` varchar(255) NOT NULL,
  `model_id` bigint(20) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `password_reset_tokens`
--

CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `permissions`
--

CREATE TABLE `permissions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `guard_name` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `personal_access_tokens`
--

CREATE TABLE `personal_access_tokens` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tokenable_type` varchar(255) NOT NULL,
  `tokenable_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `token` varchar(64) NOT NULL,
  `abilities` text DEFAULT NULL,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `guard_name` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `role_has_permissions`
--

CREATE TABLE `role_has_permissions` (
  `permission_id` bigint(20) UNSIGNED NOT NULL,
  `role_id` bigint(20) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` bigint(20) UNSIGNED NOT NULL,
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
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `eb_accountable_officers`
--
ALTER TABLE `eb_accountable_officers`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `eb_activity_logs`
--
ALTER TABLE `eb_activity_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `eb_activity_logs_action_created_at_index` (`action`,`created_at`),
  ADD KEY `eb_activity_logs_subject_type_subject_id_index` (`subject_type`,`subject_id`),
  ADD KEY `eb_activity_logs_user_id_index` (`user_id`);

--
-- Indexes for table `eb_address`
--
ALTER TABLE `eb_address`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `eb_approval_status`
--
ALTER TABLE `eb_approval_status`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `eb_blotters`
--
ALTER TABLE `eb_blotters`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `eb_blotters_blotter_no_unique` (`blotter_no`),
  ADD KEY `eb_blotters_status_filed_date_index` (`status`,`filed_date`),
  ADD KEY `eb_blotters_incident_date_index` (`incident_date`);

--
-- Indexes for table `eb_blotter_actions`
--
ALTER TABLE `eb_blotter_actions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `eb_blotter_actions_blotter_id_scheduled_date_index` (`blotter_id`,`scheduled_date`);

--
-- Indexes for table `eb_blotter_action_versions`
--
ALTER TABLE `eb_blotter_action_versions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `eb_blotter_action_versions_action_id_index` (`action_id`),
  ADD KEY `eb_blotter_action_versions_blotter_id_index` (`blotter_id`);

--
-- Indexes for table `eb_blotter_parties`
--
ALTER TABLE `eb_blotter_parties`
  ADD PRIMARY KEY (`id`),
  ADD KEY `eb_blotter_parties_blotter_id_role_index` (`blotter_id`,`role`),
  ADD KEY `eb_blotter_parties_citizen_id_index` (`citizen_id`);

--
-- Indexes for table `eb_budget_adjustments`
--
ALTER TABLE `eb_budget_adjustments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `eb_budget_adjustments_fiscal_year_id_foreign` (`fiscal_year_id`),
  ADD KEY `eb_budget_adjustments_allocation_id_foreign` (`allocation_id`),
  ADD KEY `eb_budget_adjustments_created_by_foreign` (`created_by`);

--
-- Indexes for table `eb_budget_allocations`
--
ALTER TABLE `eb_budget_allocations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `alloc_fy_prog_obj_unique` (`fiscal_year_id`,`program_id`,`object_class`),
  ADD KEY `eb_budget_allocations_program_id_foreign` (`program_id`);

--
-- Indexes for table `eb_budget_line_items`
--
ALTER TABLE `eb_budget_line_items`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `line_items_fy_prog_cls_name_unique` (`fiscal_year_id`,`program_id`,`object_class`,`name`),
  ADD KEY `eb_budget_line_items_program_id_foreign` (`program_id`);

--
-- Indexes for table `eb_budget_logs`
--
ALTER TABLE `eb_budget_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `eb_budget_logs_module_created_at_index` (`module`,`created_at`),
  ADD KEY `eb_budget_logs_action_created_at_index` (`action`,`created_at`),
  ADD KEY `eb_budget_logs_user_id_index` (`user_id`);

--
-- Indexes for table `eb_budget_programs`
--
ALTER TABLE `eb_budget_programs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `eb_budget_settings`
--
ALTER TABLE `eb_budget_settings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `eb_budget_suppliers`
--
ALTER TABLE `eb_budget_suppliers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `eb_budget_suppliers_tin_unique` (`tin`);

--
-- Indexes for table `eb_budget_transactions`
--
ALTER TABLE `eb_budget_transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `eb_budget_transactions_recorded_by_foreign` (`recorded_by`),
  ADD KEY `ebt_fy_type_date_idx` (`fiscal_year_id`,`type`,`transaction_date`),
  ADD KEY `ebt_fy_cat_idx` (`fiscal_year_id`),
  ADD KEY `eb_budget_transactions_allocation_id_foreign` (`allocation_id`),
  ADD KEY `eb_budget_transactions_supplier_id_foreign` (`supplier_id`);

--
-- Indexes for table `eb_budget_transaction_attachments`
--
ALTER TABLE `eb_budget_transaction_attachments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `eb_budget_transaction_attachments_transaction_id_foreign` (`transaction_id`),
  ADD KEY `eb_budget_transaction_attachments_uploaded_by_foreign` (`uploaded_by`);

--
-- Indexes for table `eb_budget_transaction_lines`
--
ALTER TABLE `eb_budget_transaction_lines`
  ADD PRIMARY KEY (`id`),
  ADD KEY `eb_budget_transaction_lines_transaction_id_foreign` (`transaction_id`),
  ADD KEY `eb_budget_transaction_lines_line_item_id_foreign` (`line_item_id`);

--
-- Indexes for table `eb_cash_advances`
--
ALTER TABLE `eb_cash_advances`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `eb_cash_advances_ca_no_unique` (`ca_no`),
  ADD KEY `eb_cash_advances_fiscal_year_id_foreign` (`fiscal_year_id`),
  ADD KEY `eb_cash_advances_officer_id_foreign` (`officer_id`),
  ADD KEY `eb_cash_advances_allocation_id_foreign` (`allocation_id`),
  ADD KEY `eb_cash_advances_created_by_foreign` (`created_by`);

--
-- Indexes for table `eb_citizen`
--
ALTER TABLE `eb_citizen`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `id` (`id`),
  ADD KEY `eb_citizen_qrcode_index` (`qrcode`(768)),
  ADD KEY `eb_citizen_is_active_index` (`is_active`),
  ADD KEY `eb_citizen_lname_fname_index` (`lname`,`fname`);

--
-- Indexes for table `eb_citizen_ids`
--
ALTER TABLE `eb_citizen_ids`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `eb_citizen_id_templates`
--
ALTER TABLE `eb_citizen_id_templates`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `eb_citizen_tag`
--
ALTER TABLE `eb_citizen_tag`
  ADD PRIMARY KEY (`citizen_id`,`tag_id`),
  ADD KEY `eb_citizen_tag_tag_id_foreign` (`tag_id`);

--
-- Indexes for table `eb_civil`
--
ALTER TABLE `eb_civil`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `eb_clearance`
--
ALTER TABLE `eb_clearance`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `eb_document_fields`
--
ALTER TABLE `eb_document_fields`
  ADD PRIMARY KEY (`id`),
  ADD KEY `eb_document_fields_document_type_id_foreign` (`document_type_id`);

--
-- Indexes for table `eb_document_purposes`
--
ALTER TABLE `eb_document_purposes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `eb_document_purposes_name_unique` (`name`);

--
-- Indexes for table `eb_document_requests`
--
ALTER TABLE `eb_document_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `eb_document_requests_document_type_id_foreign` (`document_type_id`),
  ADD KEY `eb_document_requests_template_version_id_foreign` (`template_version_id`);

--
-- Indexes for table `eb_document_templates`
--
ALTER TABLE `eb_document_templates`
  ADD PRIMARY KEY (`id`),
  ADD KEY `eb_document_templates_current_version_id_foreign` (`current_version_id`);

--
-- Indexes for table `eb_document_template_versions`
--
ALTER TABLE `eb_document_template_versions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `eb_document_template_versions_document_template_id_foreign` (`document_template_id`),
  ADD KEY `eb_document_template_versions_created_by_foreign` (`created_by`);

--
-- Indexes for table `eb_document_types`
--
ALTER TABLE `eb_document_types`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `eb_document_types_prefix_unique` (`prefix`),
  ADD KEY `eb_document_types_document_template_id_foreign` (`document_template_id`),
  ADD KEY `eb_document_types_document_template_version_id_foreign` (`document_template_version_id`);

--
-- Indexes for table `eb_event`
--
ALTER TABLE `eb_event`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `eb_event_attendance`
--
ALTER TABLE `eb_event_attendance`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `eb_event_attendance_eventid_citizenid_unique` (`eventId`,`citizenId`);

--
-- Indexes for table `eb_event_winner`
--
ALTER TABLE `eb_event_winner`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `eb_family`
--
ALTER TABLE `eb_family`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `eb_fiscal_years`
--
ALTER TABLE `eb_fiscal_years`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `eb_fiscal_years_year_unique` (`year`);

--
-- Indexes for table `eb_household`
--
ALTER TABLE `eb_household`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `eb_income_estimates`
--
ALTER TABLE `eb_income_estimates`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `eb_income_estimates_fiscal_year_id_source_label_unique` (`fiscal_year_id`,`source_label`);

--
-- Indexes for table `eb_inventory_categories`
--
ALTER TABLE `eb_inventory_categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `eb_inventory_categories_slug_unique` (`slug`);

--
-- Indexes for table `eb_inventory_items`
--
ALTER TABLE `eb_inventory_items`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `eb_inventory_items_sku_unique` (`sku`),
  ADD KEY `eb_inventory_items_category_id_foreign` (`category_id`);

--
-- Indexes for table `eb_inventory_releases`
--
ALTER TABLE `eb_inventory_releases`
  ADD PRIMARY KEY (`id`),
  ADD KEY `eb_inventory_releases_citizen_id_foreign` (`citizen_id`),
  ADD KEY `eb_inventory_releases_released_by_foreign` (`released_by`),
  ADD KEY `eb_inventory_releases_approved_by_foreign` (`approved_by`);

--
-- Indexes for table `eb_inventory_release_items`
--
ALTER TABLE `eb_inventory_release_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `eb_inventory_release_items_release_id_foreign` (`release_id`),
  ADD KEY `eb_inventory_release_items_item_id_foreign` (`item_id`);

--
-- Indexes for table `eb_inventory_stock_ins`
--
ALTER TABLE `eb_inventory_stock_ins`
  ADD PRIMARY KEY (`id`),
  ADD KEY `eb_inventory_stock_ins_item_id_foreign` (`item_id`),
  ADD KEY `eb_inventory_stock_ins_created_by_foreign` (`created_by`);

--
-- Indexes for table `eb_liquidation_lines`
--
ALTER TABLE `eb_liquidation_lines`
  ADD PRIMARY KEY (`id`),
  ADD KEY `eb_liquidation_lines_liquidation_report_id_foreign` (`liquidation_report_id`);

--
-- Indexes for table `eb_liquidation_reports`
--
ALTER TABLE `eb_liquidation_reports`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `eb_liquidation_reports_cash_advance_id_unique` (`cash_advance_id`),
  ADD KEY `eb_liquidation_reports_created_by_foreign` (`created_by`);

--
-- Indexes for table `eb_logs`
--
ALTER TABLE `eb_logs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `eb_officials`
--
ALTER TABLE `eb_officials`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `eb_purpose`
--
ALTER TABLE `eb_purpose`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `eb_relation`
--
ALTER TABLE `eb_relation`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `eb_settings`
--
ALTER TABLE `eb_settings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `eb_tags`
--
ALTER TABLE `eb_tags`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`);

--
-- Indexes for table `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `model_has_permissions`
--
ALTER TABLE `model_has_permissions`
  ADD PRIMARY KEY (`permission_id`,`model_id`,`model_type`),
  ADD KEY `model_has_permissions_model_id_model_type_index` (`model_id`,`model_type`);

--
-- Indexes for table `model_has_roles`
--
ALTER TABLE `model_has_roles`
  ADD PRIMARY KEY (`role_id`,`model_id`,`model_type`),
  ADD KEY `model_has_roles_model_id_model_type_index` (`model_id`,`model_type`);

--
-- Indexes for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD PRIMARY KEY (`email`);

--
-- Indexes for table `permissions`
--
ALTER TABLE `permissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `permissions_name_guard_name_unique` (`name`,`guard_name`);

--
-- Indexes for table `personal_access_tokens`
--
ALTER TABLE `personal_access_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  ADD KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `roles_name_guard_name_unique` (`name`,`guard_name`);

--
-- Indexes for table `role_has_permissions`
--
ALTER TABLE `role_has_permissions`
  ADD PRIMARY KEY (`permission_id`,`role_id`),
  ADD KEY `role_has_permissions_role_id_foreign` (`role_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `users_email_unique` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `eb_accountable_officers`
--
ALTER TABLE `eb_accountable_officers`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `eb_activity_logs`
--
ALTER TABLE `eb_activity_logs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `eb_blotters`
--
ALTER TABLE `eb_blotters`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `eb_blotter_actions`
--
ALTER TABLE `eb_blotter_actions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `eb_blotter_action_versions`
--
ALTER TABLE `eb_blotter_action_versions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `eb_blotter_parties`
--
ALTER TABLE `eb_blotter_parties`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `eb_budget_adjustments`
--
ALTER TABLE `eb_budget_adjustments`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `eb_budget_allocations`
--
ALTER TABLE `eb_budget_allocations`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `eb_budget_line_items`
--
ALTER TABLE `eb_budget_line_items`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `eb_budget_logs`
--
ALTER TABLE `eb_budget_logs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `eb_budget_programs`
--
ALTER TABLE `eb_budget_programs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `eb_budget_settings`
--
ALTER TABLE `eb_budget_settings`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `eb_budget_suppliers`
--
ALTER TABLE `eb_budget_suppliers`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `eb_budget_transactions`
--
ALTER TABLE `eb_budget_transactions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `eb_budget_transaction_attachments`
--
ALTER TABLE `eb_budget_transaction_attachments`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `eb_budget_transaction_lines`
--
ALTER TABLE `eb_budget_transaction_lines`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `eb_cash_advances`
--
ALTER TABLE `eb_cash_advances`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `eb_citizen`
--
ALTER TABLE `eb_citizen`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `eb_citizen_ids`
--
ALTER TABLE `eb_citizen_ids`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `eb_citizen_id_templates`
--
ALTER TABLE `eb_citizen_id_templates`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `eb_civil`
--
ALTER TABLE `eb_civil`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `eb_clearance`
--
ALTER TABLE `eb_clearance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `eb_document_fields`
--
ALTER TABLE `eb_document_fields`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `eb_document_purposes`
--
ALTER TABLE `eb_document_purposes`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `eb_document_requests`
--
ALTER TABLE `eb_document_requests`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `eb_document_templates`
--
ALTER TABLE `eb_document_templates`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `eb_document_template_versions`
--
ALTER TABLE `eb_document_template_versions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `eb_document_types`
--
ALTER TABLE `eb_document_types`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `eb_event`
--
ALTER TABLE `eb_event`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `eb_event_attendance`
--
ALTER TABLE `eb_event_attendance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `eb_event_winner`
--
ALTER TABLE `eb_event_winner`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `eb_family`
--
ALTER TABLE `eb_family`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `eb_fiscal_years`
--
ALTER TABLE `eb_fiscal_years`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `eb_household`
--
ALTER TABLE `eb_household`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `eb_income_estimates`
--
ALTER TABLE `eb_income_estimates`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `eb_inventory_categories`
--
ALTER TABLE `eb_inventory_categories`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `eb_inventory_items`
--
ALTER TABLE `eb_inventory_items`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `eb_inventory_releases`
--
ALTER TABLE `eb_inventory_releases`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `eb_inventory_release_items`
--
ALTER TABLE `eb_inventory_release_items`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `eb_inventory_stock_ins`
--
ALTER TABLE `eb_inventory_stock_ins`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `eb_liquidation_lines`
--
ALTER TABLE `eb_liquidation_lines`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `eb_liquidation_reports`
--
ALTER TABLE `eb_liquidation_reports`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `eb_logs`
--
ALTER TABLE `eb_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `eb_officials`
--
ALTER TABLE `eb_officials`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `eb_purpose`
--
ALTER TABLE `eb_purpose`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `eb_relation`
--
ALTER TABLE `eb_relation`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `eb_settings`
--
ALTER TABLE `eb_settings`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `eb_tags`
--
ALTER TABLE `eb_tags`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `permissions`
--
ALTER TABLE `permissions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `personal_access_tokens`
--
ALTER TABLE `personal_access_tokens`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `eb_blotter_actions`
--
ALTER TABLE `eb_blotter_actions`
  ADD CONSTRAINT `eb_blotter_actions_blotter_id_foreign` FOREIGN KEY (`blotter_id`) REFERENCES `eb_blotters` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `eb_blotter_parties`
--
ALTER TABLE `eb_blotter_parties`
  ADD CONSTRAINT `eb_blotter_parties_blotter_id_foreign` FOREIGN KEY (`blotter_id`) REFERENCES `eb_blotters` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `eb_budget_adjustments`
--
ALTER TABLE `eb_budget_adjustments`
  ADD CONSTRAINT `eb_budget_adjustments_allocation_id_foreign` FOREIGN KEY (`allocation_id`) REFERENCES `eb_budget_allocations` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `eb_budget_adjustments_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `eb_budget_adjustments_fiscal_year_id_foreign` FOREIGN KEY (`fiscal_year_id`) REFERENCES `eb_fiscal_years` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `eb_budget_allocations`
--
ALTER TABLE `eb_budget_allocations`
  ADD CONSTRAINT `eb_budget_allocations_fiscal_year_id_foreign` FOREIGN KEY (`fiscal_year_id`) REFERENCES `eb_fiscal_years` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `eb_budget_allocations_program_id_foreign` FOREIGN KEY (`program_id`) REFERENCES `eb_budget_programs` (`id`) ON DELETE NO ACTION;

--
-- Constraints for table `eb_budget_line_items`
--
ALTER TABLE `eb_budget_line_items`
  ADD CONSTRAINT `eb_budget_line_items_fiscal_year_id_foreign` FOREIGN KEY (`fiscal_year_id`) REFERENCES `eb_fiscal_years` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `eb_budget_line_items_program_id_foreign` FOREIGN KEY (`program_id`) REFERENCES `eb_budget_programs` (`id`);

--
-- Constraints for table `eb_budget_transactions`
--
ALTER TABLE `eb_budget_transactions`
  ADD CONSTRAINT `eb_budget_transactions_allocation_id_foreign` FOREIGN KEY (`allocation_id`) REFERENCES `eb_budget_allocations` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `eb_budget_transactions_fiscal_year_id_foreign` FOREIGN KEY (`fiscal_year_id`) REFERENCES `eb_fiscal_years` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `eb_budget_transactions_recorded_by_foreign` FOREIGN KEY (`recorded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `eb_budget_transactions_supplier_id_foreign` FOREIGN KEY (`supplier_id`) REFERENCES `eb_budget_suppliers` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `eb_budget_transaction_attachments`
--
ALTER TABLE `eb_budget_transaction_attachments`
  ADD CONSTRAINT `eb_budget_transaction_attachments_transaction_id_foreign` FOREIGN KEY (`transaction_id`) REFERENCES `eb_budget_transactions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `eb_budget_transaction_attachments_uploaded_by_foreign` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `eb_budget_transaction_lines`
--
ALTER TABLE `eb_budget_transaction_lines`
  ADD CONSTRAINT `eb_budget_transaction_lines_line_item_id_foreign` FOREIGN KEY (`line_item_id`) REFERENCES `eb_budget_line_items` (`id`),
  ADD CONSTRAINT `eb_budget_transaction_lines_transaction_id_foreign` FOREIGN KEY (`transaction_id`) REFERENCES `eb_budget_transactions` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `eb_cash_advances`
--
ALTER TABLE `eb_cash_advances`
  ADD CONSTRAINT `eb_cash_advances_allocation_id_foreign` FOREIGN KEY (`allocation_id`) REFERENCES `eb_budget_allocations` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `eb_cash_advances_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `eb_cash_advances_fiscal_year_id_foreign` FOREIGN KEY (`fiscal_year_id`) REFERENCES `eb_fiscal_years` (`id`),
  ADD CONSTRAINT `eb_cash_advances_officer_id_foreign` FOREIGN KEY (`officer_id`) REFERENCES `eb_accountable_officers` (`id`);

--
-- Constraints for table `eb_citizen_tag`
--
ALTER TABLE `eb_citizen_tag`
  ADD CONSTRAINT `eb_citizen_tag_tag_id_foreign` FOREIGN KEY (`tag_id`) REFERENCES `eb_tags` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `eb_document_fields`
--
ALTER TABLE `eb_document_fields`
  ADD CONSTRAINT `eb_document_fields_document_type_id_foreign` FOREIGN KEY (`document_type_id`) REFERENCES `eb_document_types` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `eb_document_requests`
--
ALTER TABLE `eb_document_requests`
  ADD CONSTRAINT `eb_document_requests_document_type_id_foreign` FOREIGN KEY (`document_type_id`) REFERENCES `eb_document_types` (`id`),
  ADD CONSTRAINT `eb_document_requests_template_version_id_foreign` FOREIGN KEY (`template_version_id`) REFERENCES `eb_document_template_versions` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `eb_document_templates`
--
ALTER TABLE `eb_document_templates`
  ADD CONSTRAINT `eb_document_templates_current_version_id_foreign` FOREIGN KEY (`current_version_id`) REFERENCES `eb_document_template_versions` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `eb_document_template_versions`
--
ALTER TABLE `eb_document_template_versions`
  ADD CONSTRAINT `eb_document_template_versions_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `eb_document_template_versions_document_template_id_foreign` FOREIGN KEY (`document_template_id`) REFERENCES `eb_document_templates` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `eb_document_types`
--
ALTER TABLE `eb_document_types`
  ADD CONSTRAINT `eb_document_types_document_template_id_foreign` FOREIGN KEY (`document_template_id`) REFERENCES `eb_document_templates` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `eb_document_types_document_template_version_id_foreign` FOREIGN KEY (`document_template_version_id`) REFERENCES `eb_document_template_versions` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `eb_income_estimates`
--
ALTER TABLE `eb_income_estimates`
  ADD CONSTRAINT `eb_income_estimates_fiscal_year_id_foreign` FOREIGN KEY (`fiscal_year_id`) REFERENCES `eb_fiscal_years` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `eb_inventory_items`
--
ALTER TABLE `eb_inventory_items`
  ADD CONSTRAINT `eb_inventory_items_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `eb_inventory_categories` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `eb_inventory_releases`
--
ALTER TABLE `eb_inventory_releases`
  ADD CONSTRAINT `eb_inventory_releases_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `eb_inventory_releases_citizen_id_foreign` FOREIGN KEY (`citizen_id`) REFERENCES `eb_citizen` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `eb_inventory_releases_released_by_foreign` FOREIGN KEY (`released_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `eb_inventory_release_items`
--
ALTER TABLE `eb_inventory_release_items`
  ADD CONSTRAINT `eb_inventory_release_items_item_id_foreign` FOREIGN KEY (`item_id`) REFERENCES `eb_inventory_items` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `eb_inventory_release_items_release_id_foreign` FOREIGN KEY (`release_id`) REFERENCES `eb_inventory_releases` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `eb_inventory_stock_ins`
--
ALTER TABLE `eb_inventory_stock_ins`
  ADD CONSTRAINT `eb_inventory_stock_ins_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `eb_inventory_stock_ins_item_id_foreign` FOREIGN KEY (`item_id`) REFERENCES `eb_inventory_items` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `eb_liquidation_lines`
--
ALTER TABLE `eb_liquidation_lines`
  ADD CONSTRAINT `eb_liquidation_lines_liquidation_report_id_foreign` FOREIGN KEY (`liquidation_report_id`) REFERENCES `eb_liquidation_reports` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `eb_liquidation_reports`
--
ALTER TABLE `eb_liquidation_reports`
  ADD CONSTRAINT `eb_liquidation_reports_cash_advance_id_foreign` FOREIGN KEY (`cash_advance_id`) REFERENCES `eb_cash_advances` (`id`),
  ADD CONSTRAINT `eb_liquidation_reports_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `model_has_permissions`
--
ALTER TABLE `model_has_permissions`
  ADD CONSTRAINT `model_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `model_has_roles`
--
ALTER TABLE `model_has_roles`
  ADD CONSTRAINT `model_has_roles_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
