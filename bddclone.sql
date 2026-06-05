-- MySQL dump 10.13  Distrib 8.4.7, for Win64 (x86_64)
--
-- Host: localhost    Database: gtb
-- ------------------------------------------------------
-- Server version	8.4.7

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
-- Table structure for table `alertes`
--

DROP TABLE IF EXISTS `alertes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `alertes` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `id_capteur` int unsigned NOT NULL,
  `type_alerte` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `message` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `valeur_declencheur` decimal(10,4) DEFAULT NULL,
  `seuil` decimal(10,4) DEFAULT NULL,
  `niveau` enum('info','avertissement','critique') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'avertissement',
  `is_resolved` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `resolved_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_alertes_capteur` (`id_capteur`),
  KEY `idx_alertes_resolved` (`is_resolved`),
  CONSTRAINT `fk_alertes_capteur` FOREIGN KEY (`id_capteur`) REFERENCES `capteurs` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `alertes`
--

LOCK TABLES `alertes` WRITE;
/*!40000 ALTER TABLE `alertes` DISABLE KEYS */;
INSERT INTO `alertes` VALUES (1,2,'Luminosité trop basse','Luminosité relevée : 120 lux, en dessous du seuil minimum (300.0000 lux).',120.0000,300.0000,'info',1,'2026-06-04 07:30:00','2026-06-04 09:00:00'),(2,4,'Luminosité trop basse','Luminosité relevée : 95 lux, en dessous du seuil minimum (100.0000 lux).',95.0000,100.0000,'avertissement',1,'2026-06-04 08:00:00','2026-06-04 10:00:00'),(3,1,'CO₂ trop élevé','CO₂ relevée : 1100 ppm, seuil maximum dépassé (1000.0000 ppm).',1100.0000,1000.0000,'avertissement',1,'2026-06-04 12:00:00','2026-06-05 09:45:22'),(4,4,'Luminosité trop basse','Luminosité relevée : 90 lux, en dessous du seuil minimum (100.0000 lux).',90.0000,100.0000,'avertissement',0,'2026-06-04 16:00:00',NULL),(5,2,'Luminosité trop basse','Luminosité relevée : 80 lux, en dessous du seuil minimum (300.0000 lux).',80.0000,300.0000,'info',0,'2026-06-04 19:00:00',NULL),(6,1,'Température trop élevée','Température relevée : 28.5 °C, seuil maximum dépassé (26.0000 °C).',28.5000,26.0000,'avertissement',0,'2026-06-05 14:02:35',NULL),(7,1,'CO₂ trop élevé','CO₂ relevée : 1500 ppm, seuil maximum dépassé (1000.0000 ppm).',1500.0000,1000.0000,'avertissement',1,'2026-06-05 14:02:35','2026-06-05 17:15:29'),(8,1,'CO₂ trop élevé','CO₂ relevée : 1075 ppm, seuil maximum dépassé (1000.0000 ppm).',1075.0000,1000.0000,'avertissement',1,'2026-06-05 17:19:35','2026-06-05 17:22:51'),(9,1,'Humidité trop élevée','Humidité relevée : 76 %, seuil maximum dépassé (70.0000 %).',76.0000,70.0000,'info',1,'2026-06-05 17:20:05','2026-06-05 17:20:36');
/*!40000 ALTER TABLE `alertes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cameras`
--

DROP TABLE IF EXISTS `cameras`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cameras` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `id_salle` int unsigned NOT NULL,
  `nom` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `url_flux` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `camera_status` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `fk_cameras_salle` (`id_salle`),
  CONSTRAINT `fk_cameras_salle` FOREIGN KEY (`id_salle`) REFERENCES `salles` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cameras`
--

LOCK TABLES `cameras` WRITE;
/*!40000 ALTER TABLE `cameras` DISABLE KEYS */;
/*!40000 ALTER TABLE `cameras` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `capteurs`
--

DROP TABLE IF EXISTS `capteurs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `capteurs` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `id_salle` int unsigned NOT NULL,
  `type` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `unite` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `id_arduino` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_connected` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `fk_capteurs_salle` (`id_salle`),
  CONSTRAINT `fk_capteurs_salle` FOREIGN KEY (`id_salle`) REFERENCES `salles` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `capteurs`
--

LOCK TABLES `capteurs` WRITE;
/*!40000 ALTER TABLE `capteurs` DISABLE KEYS */;
INSERT INTO `capteurs` VALUES (1,1,'SCD30','','ARD-001',1),(2,1,'Light Sensor','','ARD-002',1),(3,2,'SCD30','','ARD-003',1),(4,2,'Light Sensor','','ARD-004',1);
/*!40000 ALTER TABLE `capteurs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `login_attempts`
--

DROP TABLE IF EXISTS `login_attempts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `login_attempts` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `ip` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempted_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_attempts_ip_email` (`ip`,`email`),
  KEY `idx_attempts_date` (`attempted_at`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `login_attempts`
--

LOCK TABLES `login_attempts` WRITE;
/*!40000 ALTER TABLE `login_attempts` DISABLE KEYS */;
INSERT INTO `login_attempts` VALUES (2,'::1','operateur@gtb.local','2026-06-05 09:46:02');
/*!40000 ALTER TABLE `login_attempts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mesures`
--

DROP TABLE IF EXISTS `mesures`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `mesures` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `id_capteur` int unsigned NOT NULL,
  `type_mesure` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `valeur` decimal(10,4) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_mesures_capteur` (`id_capteur`),
  CONSTRAINT `fk_mesures_capteur` FOREIGN KEY (`id_capteur`) REFERENCES `capteurs` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=150 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mesures`
--

LOCK TABLES `mesures` WRITE;
/*!40000 ALTER TABLE `mesures` DISABLE KEYS */;
INSERT INTO `mesures` VALUES (34,1,'temperature',20.3000,'2026-06-04 08:00:00'),(35,1,'temperature',21.1000,'2026-06-04 10:00:00'),(36,1,'temperature',22.5000,'2026-06-04 12:00:00'),(37,1,'temperature',23.2000,'2026-06-04 14:00:00'),(38,1,'humidite',52.0000,'2026-06-04 08:00:00'),(39,1,'humidite',54.3000,'2026-06-04 10:00:00'),(40,1,'humidite',57.8000,'2026-06-04 12:00:00'),(41,1,'humidite',59.1000,'2026-06-04 14:00:00'),(42,1,'co2',650.0000,'2026-06-04 08:00:00'),(43,1,'co2',870.0000,'2026-06-04 10:00:00'),(44,1,'co2',1100.0000,'2026-06-04 12:00:00'),(45,1,'co2',1250.0000,'2026-06-04 14:00:00'),(46,2,'luminosite',120.0000,'2026-06-04 07:30:00'),(47,2,'luminosite',310.0000,'2026-06-04 09:00:00'),(48,2,'luminosite',520.0000,'2026-06-04 11:00:00'),(49,2,'luminosite',680.0000,'2026-06-04 13:00:00'),(50,2,'luminosite',590.0000,'2026-06-04 15:00:00'),(51,2,'luminosite',350.0000,'2026-06-04 17:00:00'),(52,2,'luminosite',80.0000,'2026-06-04 19:00:00'),(53,3,'temperature',18.5000,'2026-06-04 08:00:00'),(54,3,'temperature',19.2000,'2026-06-04 12:00:00'),(55,3,'temperature',19.8000,'2026-06-04 16:00:00'),(56,3,'humidite',43.0000,'2026-06-04 08:00:00'),(57,3,'humidite',45.5000,'2026-06-04 12:00:00'),(58,3,'humidite',47.2000,'2026-06-04 16:00:00'),(59,3,'co2',520.0000,'2026-06-04 08:00:00'),(60,3,'co2',610.0000,'2026-06-04 12:00:00'),(61,3,'co2',580.0000,'2026-06-04 16:00:00'),(62,4,'luminosite',95.0000,'2026-06-04 08:00:00'),(63,4,'luminosite',180.0000,'2026-06-04 10:00:00'),(64,4,'luminosite',210.0000,'2026-06-04 12:00:00'),(65,4,'luminosite',175.0000,'2026-06-04 14:00:00'),(66,4,'luminosite',90.0000,'2026-06-04 16:00:00'),(67,1,'temperature',28.5000,'2026-06-05 14:02:35'),(68,1,'humidite',52.0000,'2026-06-05 14:02:35'),(69,1,'co2',1500.0000,'2026-06-05 14:02:35'),(70,1,'temperature',27.0000,'2026-06-05 17:15:29'),(71,1,'humidite',39.0000,'2026-06-05 17:15:29'),(72,1,'co2',795.0000,'2026-06-05 17:15:29'),(73,1,'luminosite',739.0000,'2026-06-05 17:15:29'),(74,1,'temperature',26.9000,'2026-06-05 17:15:58'),(75,1,'humidite',39.0000,'2026-06-05 17:15:58'),(76,1,'co2',787.0000,'2026-06-05 17:15:58'),(77,1,'luminosite',739.0000,'2026-06-05 17:15:58'),(78,1,'temperature',26.9000,'2026-06-05 17:16:29'),(79,1,'humidite',39.0000,'2026-06-05 17:16:29'),(80,1,'co2',757.0000,'2026-06-05 17:16:29'),(81,1,'luminosite',739.0000,'2026-06-05 17:16:29'),(82,1,'temperature',26.9000,'2026-06-05 17:17:00'),(83,1,'humidite',39.0000,'2026-06-05 17:17:00'),(84,1,'co2',739.0000,'2026-06-05 17:17:00'),(85,1,'luminosite',739.0000,'2026-06-05 17:17:00'),(86,1,'temperature',26.9000,'2026-06-05 17:17:31'),(87,1,'humidite',39.0000,'2026-06-05 17:17:31'),(88,1,'co2',739.0000,'2026-06-05 17:17:31'),(89,1,'luminosite',736.0000,'2026-06-05 17:17:31'),(90,1,'temperature',26.9000,'2026-06-05 17:18:02'),(91,1,'humidite',39.0000,'2026-06-05 17:18:02'),(92,1,'co2',735.0000,'2026-06-05 17:18:02'),(93,1,'luminosite',738.0000,'2026-06-05 17:18:02'),(94,1,'temperature',26.9000,'2026-06-05 17:18:33'),(95,1,'humidite',39.0000,'2026-06-05 17:18:33'),(96,1,'co2',894.0000,'2026-06-05 17:18:33'),(97,1,'luminosite',739.0000,'2026-06-05 17:18:33'),(98,1,'temperature',26.9000,'2026-06-05 17:19:04'),(99,1,'humidite',39.0000,'2026-06-05 17:19:04'),(100,1,'co2',955.0000,'2026-06-05 17:19:04'),(101,1,'luminosite',739.0000,'2026-06-05 17:19:04'),(102,1,'temperature',26.9000,'2026-06-05 17:19:35'),(103,1,'humidite',42.0000,'2026-06-05 17:19:35'),(104,1,'co2',1075.0000,'2026-06-05 17:19:35'),(105,1,'luminosite',739.0000,'2026-06-05 17:19:35'),(106,1,'temperature',27.3000,'2026-06-05 17:20:05'),(107,1,'humidite',76.0000,'2026-06-05 17:20:05'),(108,1,'co2',14353.0000,'2026-06-05 17:20:05'),(109,1,'luminosite',739.0000,'2026-06-05 17:20:05'),(110,1,'temperature',27.2000,'2026-06-05 17:20:36'),(111,1,'humidite',62.0000,'2026-06-05 17:20:36'),(112,1,'co2',12475.0000,'2026-06-05 17:20:36'),(113,1,'luminosite',741.0000,'2026-06-05 17:20:36'),(114,1,'temperature',27.2000,'2026-06-05 17:21:07'),(115,1,'humidite',56.0000,'2026-06-05 17:21:07'),(116,1,'co2',4660.0000,'2026-06-05 17:21:07'),(117,1,'luminosite',741.0000,'2026-06-05 17:21:07'),(118,1,'temperature',27.0000,'2026-06-05 17:21:34'),(119,1,'humidite',49.0000,'2026-06-05 17:21:34'),(120,1,'co2',1746.0000,'2026-06-05 17:21:34'),(121,1,'luminosite',739.0000,'2026-06-05 17:21:34'),(122,1,'temperature',26.9000,'2026-06-05 17:22:05'),(123,1,'humidite',50.0000,'2026-06-05 17:22:05'),(124,1,'co2',1005.0000,'2026-06-05 17:22:05'),(125,1,'luminosite',739.0000,'2026-06-05 17:22:05'),(126,1,'temperature',26.6000,'2026-06-05 17:22:51'),(127,1,'humidite',48.0000,'2026-06-05 17:22:51'),(128,1,'co2',722.0000,'2026-06-05 17:22:51'),(129,1,'luminosite',741.0000,'2026-06-05 17:22:51'),(130,1,'temperature',26.6000,'2026-06-05 17:23:22'),(131,1,'humidite',47.0000,'2026-06-05 17:23:22'),(132,1,'co2',713.0000,'2026-06-05 17:23:22'),(133,1,'luminosite',741.0000,'2026-06-05 17:23:22'),(134,1,'temperature',26.6000,'2026-06-05 17:23:53'),(135,1,'humidite',45.0000,'2026-06-05 17:23:53'),(136,1,'co2',715.0000,'2026-06-05 17:23:53'),(137,1,'luminosite',741.0000,'2026-06-05 17:23:53'),(138,1,'temperature',26.5000,'2026-06-05 17:24:24'),(139,1,'humidite',44.0000,'2026-06-05 17:24:24'),(140,1,'co2',716.0000,'2026-06-05 17:24:24'),(141,1,'luminosite',740.0000,'2026-06-05 17:24:24'),(142,1,'temperature',26.5000,'2026-06-05 17:24:54'),(143,1,'humidite',45.0000,'2026-06-05 17:24:54'),(144,1,'co2',731.0000,'2026-06-05 17:24:54'),(145,1,'luminosite',740.0000,'2026-06-05 17:24:54'),(146,1,'temperature',26.4000,'2026-06-05 17:25:25'),(147,1,'humidite',44.0000,'2026-06-05 17:25:25'),(148,1,'co2',736.0000,'2026-06-05 17:25:25'),(149,1,'luminosite',738.0000,'2026-06-05 17:25:25');
/*!40000 ALTER TABLE `mesures` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `salles`
--

DROP TABLE IF EXISTS `salles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `salles` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `nom` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `open_for_all` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `salles`
--

LOCK TABLES `salles` WRITE;
/*!40000 ALTER TABLE `salles` DISABLE KEYS */;
INSERT INTO `salles` VALUES (1,'Open Space','Espace de travail',1),(2,'Local Technique','Espace technique',0);
/*!40000 ALTER TABLE `salles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `seuils`
--

DROP TABLE IF EXISTS `seuils`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `seuils` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `id_capteur` int unsigned NOT NULL,
  `type_mesure` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `valeur_min` decimal(10,4) DEFAULT NULL,
  `valeur_max` decimal(10,4) DEFAULT NULL,
  `niveau` enum('info','avertissement','critique') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'avertissement',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_seuil` (`id_capteur`,`type_mesure`),
  KEY `fk_seuils_capteur` (`id_capteur`),
  CONSTRAINT `fk_seuils_capteur` FOREIGN KEY (`id_capteur`) REFERENCES `capteurs` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `seuils`
--

LOCK TABLES `seuils` WRITE;
/*!40000 ALTER TABLE `seuils` DISABLE KEYS */;
INSERT INTO `seuils` VALUES (1,1,'temperature',19.0000,26.0000,'avertissement'),(2,1,'humidite',30.0000,70.0000,'info'),(3,1,'co2',NULL,1000.0000,'avertissement'),(4,2,'luminosite',300.0000,NULL,'info'),(5,3,'temperature',NULL,27.0000,'critique'),(6,3,'humidite',30.0000,70.0000,'avertissement'),(7,3,'co2',NULL,1000.0000,'avertissement'),(8,4,'luminosite',100.0000,NULL,'avertissement');
/*!40000 ALTER TABLE `seuils` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `passwrd` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` enum('admin','user') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'user',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_users_email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'admin','admin@gtb.local','$2y$10$oBcm4Sdl0jzCGWuswtYCL.3uYG5yKw65lCFMV/H4mTBa90rwxx/ZW','admin'),(2,'operateur','operateur@gtb.local','$2y$10$xVwXhvZdylaP12BlPIX/KuacZpR5/pLNkxC1ktLvQUmC9gR/xbjYa','user'),(3,'Jean','jean@gmail.com','$2y$10$xzGU7Wc5Wpsa2aYNiBWgDe5UnwGqqftLvVGAoSNOAqDQFf2cU87qy','user');
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

-- Dump completed on 2026-06-05 17:31:02
