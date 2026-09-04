-- MySQL dump 10.13  Distrib 9.3.0, for Win64 (x86_64)
--
-- Host: 127.0.0.1    Database: tenant_a33f14a9-db93-4988-b3ec-d39512a9ef65
-- ------------------------------------------------------
-- Server version	5.5.5-10.4.27-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `compliance_settings`
--

DROP TABLE IF EXISTS `compliance_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `compliance_settings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `version_label` varchar(255) NOT NULL,
  `effective_date` date NOT NULL,
  `settings` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`settings`)),
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `compliance_settings_effective_date_version_label_unique` (`effective_date`,`version_label`),
  KEY `compliance_settings_created_by_foreign` (`created_by`),
  KEY `compliance_settings_effective_date_index` (`effective_date`),
  CONSTRAINT `compliance_settings_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `compliance_settings`
--

LOCK TABLES `compliance_settings` WRITE;
/*!40000 ALTER TABLE `compliance_settings` DISABLE KEYS */;
/*!40000 ALTER TABLE `compliance_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `employees`
--

DROP TABLE IF EXISTS `employees`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `employees` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `national_id` varchar(255) NOT NULL,
  `ssc_number` varchar(255) DEFAULT NULL,
  `ssc_enrollment_date` date DEFAULT NULL,
  `hire_date` date NOT NULL,
  `job_title` varchar(255) DEFAULT NULL,
  `salary` decimal(16,3) NOT NULL,
  `bank_iban` varchar(255) DEFAULT NULL,
  `status` enum('active','on_leave','terminated','inactive') NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `employees_national_id_unique` (`national_id`),
  KEY `employees_user_id_foreign` (`user_id`),
  KEY `employees_ssc_number_index` (`ssc_number`),
  KEY `employees_hire_date_index` (`hire_date`),
  KEY `employees_status_index` (`status`),
  CONSTRAINT `employees_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `employees`
--

LOCK TABLES `employees` WRITE;
/*!40000 ALTER TABLE `employees` DISABLE KEYS */;
/*!40000 ALTER TABLE `employees` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `failed_jobs`
--

DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
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

--
-- Dumping data for table `failed_jobs`
--

LOCK TABLES `failed_jobs` WRITE;
/*!40000 ALTER TABLE `failed_jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `failed_jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `jobs`
--

DROP TABLE IF EXISTS `jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` tinyint(3) unsigned NOT NULL,
  `reserved_at` int(10) unsigned DEFAULT NULL,
  `available_at` int(10) unsigned NOT NULL,
  `created_at` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jobs`
--

LOCK TABLES `jobs` WRITE;
/*!40000 ALTER TABLE `jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `leave_balances`
--

DROP TABLE IF EXISTS `leave_balances`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `leave_balances` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `employee_id` bigint(20) unsigned NOT NULL,
  `year` smallint(5) unsigned NOT NULL,
  `compliance_setting_id` bigint(20) unsigned NOT NULL,
  `annual_entitlement` decimal(8,2) NOT NULL DEFAULT 0.00,
  `annual_used` decimal(8,2) NOT NULL DEFAULT 0.00,
  `annual_remaining` decimal(8,2) NOT NULL DEFAULT 0.00,
  `sick_entitlement` decimal(8,2) NOT NULL DEFAULT 0.00,
  `sick_used` decimal(8,2) NOT NULL DEFAULT 0.00,
  `sick_remaining` decimal(8,2) NOT NULL DEFAULT 0.00,
  `hospital_extra_entitlement` decimal(8,2) NOT NULL DEFAULT 0.00,
  `hospital_extra_used` decimal(8,2) NOT NULL DEFAULT 0.00,
  `hospital_extra_remaining` decimal(8,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `leave_balances_employee_id_year_unique` (`employee_id`,`year`),
  KEY `leave_balances_compliance_setting_id_foreign` (`compliance_setting_id`),
  CONSTRAINT `leave_balances_compliance_setting_id_foreign` FOREIGN KEY (`compliance_setting_id`) REFERENCES `compliance_settings` (`id`),
  CONSTRAINT `leave_balances_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `leave_balances`
--

LOCK TABLES `leave_balances` WRITE;
/*!40000 ALTER TABLE `leave_balances` DISABLE KEYS */;
/*!40000 ALTER TABLE `leave_balances` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `leave_requests`
--

DROP TABLE IF EXISTS `leave_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `leave_requests` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `employee_id` bigint(20) unsigned NOT NULL,
  `type` enum('annual','sick') NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `days` decimal(8,2) NOT NULL,
  `hospitalized` tinyint(1) NOT NULL DEFAULT 0,
  `status` enum('pending','approved','rejected','cancelled') NOT NULL DEFAULT 'pending',
  `approver_id` bigint(20) unsigned DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `reason` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `leave_requests_employee_id_foreign` (`employee_id`),
  KEY `leave_requests_approver_id_foreign` (`approver_id`),
  KEY `leave_requests_type_index` (`type`),
  KEY `leave_requests_status_index` (`status`),
  CONSTRAINT `leave_requests_approver_id_foreign` FOREIGN KEY (`approver_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `leave_requests_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `leave_requests`
--

LOCK TABLES `leave_requests` WRITE;
/*!40000 ALTER TABLE `leave_requests` DISABLE KEYS */;
/*!40000 ALTER TABLE `leave_requests` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `migrations`
--

DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES (1,'2026_08_29_100000_create_users_and_employees',1),(2,'2026_08_29_100010_create_compliance_and_holidays',1),(3,'2026_08_29_100020_create_payroll_tables',1),(4,'2026_08_29_100030_create_leave_and_overtime_tables',1),(5,'2026_08_29_100040_create_termination_and_onboarding_tables',1),(6,'2026_08_29_100050_create_jobs_table',1);
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `onboarding_tasks`
--

DROP TABLE IF EXISTS `onboarding_tasks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `onboarding_tasks` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `employee_id` bigint(20) unsigned NOT NULL,
  `task_key` varchar(255) NOT NULL,
  `title_ar` varchar(255) NOT NULL,
  `title_en` varchar(255) NOT NULL,
  `due_date` date NOT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `completed_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `onboarding_tasks_employee_id_task_key_unique` (`employee_id`,`task_key`),
  KEY `onboarding_tasks_completed_by_foreign` (`completed_by`),
  KEY `onboarding_tasks_due_date_index` (`due_date`),
  CONSTRAINT `onboarding_tasks_completed_by_foreign` FOREIGN KEY (`completed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `onboarding_tasks_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `onboarding_tasks`
--

LOCK TABLES `onboarding_tasks` WRITE;
/*!40000 ALTER TABLE `onboarding_tasks` DISABLE KEYS */;
/*!40000 ALTER TABLE `onboarding_tasks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `overtime_entries`
--

DROP TABLE IF EXISTS `overtime_entries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `overtime_entries` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `employee_id` bigint(20) unsigned NOT NULL,
  `date` date NOT NULL,
  `hours` decimal(8,2) NOT NULL,
  `rate_type` enum('standard','rest_holiday') NOT NULL,
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `approver_id` bigint(20) unsigned DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `overtime_entries_approver_id_foreign` (`approver_id`),
  KEY `overtime_entries_employee_id_date_status_index` (`employee_id`,`date`,`status`),
  KEY `overtime_entries_date_index` (`date`),
  KEY `overtime_entries_status_index` (`status`),
  CONSTRAINT `overtime_entries_approver_id_foreign` FOREIGN KEY (`approver_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `overtime_entries_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `overtime_entries`
--

LOCK TABLES `overtime_entries` WRITE;
/*!40000 ALTER TABLE `overtime_entries` DISABLE KEYS */;
/*!40000 ALTER TABLE `overtime_entries` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payroll_runs`
--

DROP TABLE IF EXISTS `payroll_runs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `payroll_runs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `month` tinyint(3) unsigned NOT NULL,
  `year` smallint(5) unsigned NOT NULL,
  `status` enum('draft','processing','completed','void') NOT NULL DEFAULT 'draft',
  `compliance_setting_id` bigint(20) unsigned NOT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `payroll_runs_month_year_unique` (`month`,`year`),
  KEY `payroll_runs_compliance_setting_id_foreign` (`compliance_setting_id`),
  KEY `payroll_runs_created_by_foreign` (`created_by`),
  CONSTRAINT `payroll_runs_compliance_setting_id_foreign` FOREIGN KEY (`compliance_setting_id`) REFERENCES `compliance_settings` (`id`),
  CONSTRAINT `payroll_runs_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payroll_runs`
--

LOCK TABLES `payroll_runs` WRITE;
/*!40000 ALTER TABLE `payroll_runs` DISABLE KEYS */;
/*!40000 ALTER TABLE `payroll_runs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payslips`
--

DROP TABLE IF EXISTS `payslips`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `payslips` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `payroll_run_id` bigint(20) unsigned NOT NULL,
  `employee_id` bigint(20) unsigned NOT NULL,
  `gross_salary` decimal(16,3) NOT NULL DEFAULT 0.000,
  `overtime_pay` decimal(16,3) NOT NULL DEFAULT 0.000,
  `ssc_employee` decimal(16,3) NOT NULL DEFAULT 0.000,
  `ssc_employer` decimal(16,3) NOT NULL DEFAULT 0.000,
  `income_tax` decimal(16,3) NOT NULL DEFAULT 0.000,
  `surcharge` decimal(16,3) NOT NULL DEFAULT 0.000,
  `net_salary` decimal(16,3) NOT NULL DEFAULT 0.000,
  `calculation_snapshot` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`calculation_snapshot`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `payslips_payroll_run_id_employee_id_unique` (`payroll_run_id`,`employee_id`),
  KEY `payslips_employee_id_foreign` (`employee_id`),
  CONSTRAINT `payslips_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`),
  CONSTRAINT `payslips_payroll_run_id_foreign` FOREIGN KEY (`payroll_run_id`) REFERENCES `payroll_runs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payslips`
--

LOCK TABLES `payslips` WRITE;
/*!40000 ALTER TABLE `payslips` DISABLE KEYS */;
/*!40000 ALTER TABLE `payslips` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `public_holidays`
--

DROP TABLE IF EXISTS `public_holidays`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `public_holidays` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `date` date NOT NULL,
  `name_ar` varchar(255) NOT NULL,
  `name_en` varchar(255) NOT NULL,
  `year` smallint(5) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `public_holidays_date_unique` (`date`),
  KEY `public_holidays_year_index` (`year`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `public_holidays`
--

LOCK TABLES `public_holidays` WRITE;
/*!40000 ALTER TABLE `public_holidays` DISABLE KEYS */;
/*!40000 ALTER TABLE `public_holidays` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `termination_records`
--

DROP TABLE IF EXISTS `termination_records`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `termination_records` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `employee_id` bigint(20) unsigned NOT NULL,
  `termination_date` date NOT NULL,
  `reason` varchar(255) NOT NULL,
  `notice_given_at` date DEFAULT NULL,
  `required_notice_days` int(10) unsigned NOT NULL DEFAULT 0,
  `served_notice_days` int(10) unsigned NOT NULL DEFAULT 0,
  `notice_pay_in_lieu` decimal(16,3) NOT NULL DEFAULT 0.000,
  `prorated_salary` decimal(16,3) NOT NULL DEFAULT 0.000,
  `unused_leave_days` decimal(8,2) NOT NULL DEFAULT 0.00,
  `unused_leave_pay` decimal(16,3) NOT NULL DEFAULT 0.000,
  `final_settlement` decimal(16,3) NOT NULL DEFAULT 0.000,
  `calculation_snapshot` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`calculation_snapshot`)),
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `termination_records_employee_id_foreign` (`employee_id`),
  KEY `termination_records_created_by_foreign` (`created_by`),
  CONSTRAINT `termination_records_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `termination_records_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `termination_records`
--

LOCK TABLES `termination_records` WRITE;
/*!40000 ALTER TABLE `termination_records` DISABLE KEYS */;
/*!40000 ALTER TABLE `termination_records` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('company_admin','hr','employee') NOT NULL DEFAULT 'employee',
  `locale` varchar(5) NOT NULL DEFAULT 'ar',
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'Yazan Shratih Admin','admin@payyyyroll.test','$2y$12$7C45.DSoHvQk3B9xVjzzPuLOpeLAr44m8T4SWG13Vh8NE1KK2DVSS','company_admin','en',1,NULL,'2026-08-30 09:28:57','2026-09-04 12:07:37',NULL);
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-09-04 15:10:34
