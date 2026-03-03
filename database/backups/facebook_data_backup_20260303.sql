mysqldump: [Warning] Using a password on the command line interface can be insecure.
-- MySQL dump 10.13  Distrib 8.4.7, for Win64 (x86_64)
--
-- Host: 165.22.163.137    Database: meta-boost-db
-- ------------------------------------------------------
-- Server version	8.4.8

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
mysqldump: Error: 'Access denied; you need (at least one of) the PROCESS privilege(s) for this operation' when trying to dump tablespaces

--
-- Table structure for table `facebook_pages`
--

DROP TABLE IF EXISTS `facebook_pages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `facebook_pages` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `page_id` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ad_account_id` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `page_name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `access_token` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `instagram_account_id` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `facebook_pages_page_id_unique` (`page_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `facebook_pages`
--

LOCK TABLES `facebook_pages` WRITE;
/*!40000 ALTER TABLE `facebook_pages` DISABLE KEYS */;
/*!40000 ALTER TABLE `facebook_pages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `posts_master`
--

DROP TABLE IF EXISTS `posts_master`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `posts_master` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `post_id` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `facebook_page_id` bigint unsigned NOT NULL,
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `thumbnail_url` text COLLATE utf8mb4_unicode_ci,
  `permalink_url` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` enum('photo','video','link','status') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'status',
  `impressions` bigint unsigned NOT NULL DEFAULT '0',
  `posted_at` timestamp NULL DEFAULT NULL,
  `last_synced_at` timestamp NULL DEFAULT NULL,
  `fb_status` enum('FB_OK','FB_DELETED_OR_UNAVAILABLE','FB_ERROR') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'FB_OK',
  `fb_last_checked_at` datetime DEFAULT NULL,
  `fb_last_error` text COLLATE utf8mb4_unicode_ci,
  `business_status` enum('ACTIVE','INACTIVE','ARCHIVED') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'ACTIVE',
  `is_boostable` tinyint NOT NULL DEFAULT '1',
  `last_sync_run_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `facebook_posts_post_id_unique` (`post_id`),
  KEY `facebook_posts_facebook_page_id_index` (`facebook_page_id`),
  KEY `facebook_posts_posted_at_index` (`posted_at`),
  KEY `fk_posts_master_sync_run` (`last_sync_run_id`),
  KEY `idx_fb_status` (`fb_status`),
  KEY `idx_business_status` (`business_status`),
  KEY `idx_boostable` (`is_boostable`),
  CONSTRAINT `facebook_posts_facebook_page_id_foreign` FOREIGN KEY (`facebook_page_id`) REFERENCES `facebook_pages` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_posts_master_sync_run` FOREIGN KEY (`last_sync_run_id`) REFERENCES `sync_runs` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `posts_master`
--

LOCK TABLES `posts_master` WRITE;
/*!40000 ALTER TABLE `posts_master` DISABLE KEYS */;
/*!40000 ALTER TABLE `posts_master` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `posts_history`
--

DROP TABLE IF EXISTS `posts_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `posts_history` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `post_master_id` bigint unsigned NOT NULL,
  `type` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `message` longtext COLLATE utf8mb4_unicode_ci,
  `permalink_url` varchar(700) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_time` datetime DEFAULT NULL,
  `full_picture` varchar(1000) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `link_url` varchar(1000) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `payload` json DEFAULT NULL,
  `row_hash` char(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `run_id` bigint unsigned NOT NULL,
  `is_active` tinyint NOT NULL DEFAULT '1',
  `valid_from` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `valid_to` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_ph_master_active` (`post_master_id`,`is_active`),
  KEY `idx_ph_run` (`run_id`),
  KEY `idx_ph_valid` (`valid_from`,`valid_to`),
  CONSTRAINT `fk_posts_history_master` FOREIGN KEY (`post_master_id`) REFERENCES `posts_master` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_posts_history_run` FOREIGN KEY (`run_id`) REFERENCES `sync_runs` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `posts_history`
--

LOCK TABLES `posts_history` WRITE;
/*!40000 ALTER TABLE `posts_history` DISABLE KEYS */;
/*!40000 ALTER TABLE `posts_history` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `post_media_history`
--

DROP TABLE IF EXISTS `post_media_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `post_media_history` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `post_master_id` bigint unsigned NOT NULL,
  `position` int NOT NULL DEFAULT '1',
  `media_type` enum('image','video','unknown') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'unknown',
  `source_url` varchar(1200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `preview_url` varchar(1200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `link_url` varchar(1200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `payload` json DEFAULT NULL,
  `row_hash` char(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `run_id` bigint unsigned NOT NULL,
  `is_active` tinyint NOT NULL DEFAULT '1',
  `valid_from` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `valid_to` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_mh_master` (`post_master_id`),
  KEY `idx_mh_master_active` (`post_master_id`,`is_active`),
  KEY `idx_mh_pos` (`post_master_id`,`position`),
  KEY `idx_mh_run` (`run_id`),
  CONSTRAINT `fk_media_history_master` FOREIGN KEY (`post_master_id`) REFERENCES `posts_master` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_media_history_run` FOREIGN KEY (`run_id`) REFERENCES `sync_runs` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `post_media_history`
--

LOCK TABLES `post_media_history` WRITE;
/*!40000 ALTER TABLE `post_media_history` DISABLE KEYS */;
/*!40000 ALTER TABLE `post_media_history` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sync_runs`
--

DROP TABLE IF EXISTS `sync_runs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sync_runs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `source` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'facebook',
  `page_id` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('RUNNING','FINISHED','FAILED') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'RUNNING',
  `started_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `finished_at` datetime DEFAULT NULL,
  `note` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_source_page` (`source`,`page_id`),
  KEY `idx_status` (`status`),
  KEY `idx_started_at` (`started_at`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sync_runs`
--

LOCK TABLES `sync_runs` WRITE;
/*!40000 ALTER TABLE `sync_runs` DISABLE KEYS */;
INSERT INTO `sync_runs` VALUES (1,'facebook','123456789','FINISHED','2026-03-03 21:13:54','2026-03-03 21:13:54',NULL),(2,'facebook','123456789','FINISHED','2026-03-03 21:13:59','2026-03-03 21:13:59',NULL),(3,'facebook','123456789','FINISHED','2026-03-03 21:14:35','2026-03-03 21:14:35',NULL);
/*!40000 ALTER TABLE `sync_runs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sync_errors`
--

DROP TABLE IF EXISTS `sync_errors`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sync_errors` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `run_id` bigint unsigned NOT NULL,
  `post_id` varchar(128) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `step` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `error_code` varchar(40) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `error_message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` json DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_err_run` (`run_id`),
  KEY `idx_err_post` (`post_id`),
  CONSTRAINT `fk_sync_errors_run` FOREIGN KEY (`run_id`) REFERENCES `sync_runs` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sync_errors`
--

LOCK TABLES `sync_errors` WRITE;
/*!40000 ALTER TABLE `sync_errors` DISABLE KEYS */;
INSERT INTO `sync_errors` VALUES (1,5,NULL,'fetch_feed','104','An access token is required to request this resource.','{\"body\": {\"error\": {\"code\": 104, \"type\": \"OAuthException\", \"message\": \"An access token is required to request this resource.\", \"fbtrace_id\": \"Av6tu8gzsZ12bdb3HoB0gkT\"}}, \"fbtrace_id\": \"Av6tu8gzsZ12bdb3HoB0gkT\"}','2026-03-03 21:11:19'),(2,1,NULL,'fetch_feed','104','An access token is required to request this resource.','{\"body\": {\"error\": {\"code\": 104, \"type\": \"OAuthException\", \"message\": \"An access token is required to request this resource.\", \"fbtrace_id\": \"ArxsxxBESgsZSeuVAt5H5Gy\"}}, \"fbtrace_id\": \"ArxsxxBESgsZSeuVAt5H5Gy\"}','2026-03-03 21:13:54'),(3,2,NULL,'fetch_feed','104','An access token is required to request this resource.','{\"body\": {\"error\": {\"code\": 104, \"type\": \"OAuthException\", \"message\": \"An access token is required to request this resource.\", \"fbtrace_id\": \"A3lFxsf3pCgzXQ7l5DbJM_Z\"}}, \"fbtrace_id\": \"A3lFxsf3pCgzXQ7l5DbJM_Z\"}','2026-03-03 21:13:59'),(4,3,NULL,'fetch_feed','104','An access token is required to request this resource.','{\"body\": {\"error\": {\"code\": 104, \"type\": \"OAuthException\", \"message\": \"An access token is required to request this resource.\", \"fbtrace_id\": \"AymjqHUzEP0mEKHyKZKmLN4\"}}, \"fbtrace_id\": \"AymjqHUzEP0mEKHyKZKmLN4\"}','2026-03-03 21:14:35');
/*!40000 ALTER TABLE `sync_errors` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-03-03 22:17:33
