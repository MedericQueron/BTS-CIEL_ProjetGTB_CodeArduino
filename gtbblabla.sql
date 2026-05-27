-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1:3306
-- Généré le : mer. 27 mai 2026 à 20:46
-- Version du serveur : 8.4.7
-- Version de PHP : 8.3.28

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `gtb`
--

-- --------------------------------------------------------

--
-- Structure de la table `alertes`
--

DROP TABLE IF EXISTS `alertes`;
CREATE TABLE IF NOT EXISTS `alertes` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_capteur` int UNSIGNED NOT NULL,
  `type_alerte` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `valeur_declencheur` decimal(10,4) DEFAULT NULL,
  `seuil` decimal(10,4) DEFAULT NULL,
  `niveau` enum('info','warning','critical') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'warning',
  `is_resolved` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `resolved_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_alertes_capteur` (`id_capteur`),
  KEY `idx_alertes_resolved` (`is_resolved`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `alertes`
--

INSERT INTO `alertes` (`id`, `id_capteur`, `type_alerte`, `message`, `valeur_declencheur`, `seuil`, `niveau`, `is_resolved`, `created_at`, `resolved_at`) VALUES
(1, 1, 'co2_eleve', 'CO2 élevé en Open Space : 1100 ppm (seuil : 1000 ppm)', 1100.0000, 1000.0000, 'warning', 0, '2026-05-18 15:17:08', NULL),
(2, 3, 'temperature_critique', 'CRITIQUE : Température Local Technique à 36.2°C (seuil : 30°C)', 36.2000, 30.0000, 'critical', 0, '2026-05-18 15:17:08', NULL),
(3, 1, 'humidite_haute', 'Humidité élevée en Open Space : 63% (seuil : 60%)', 63.0000, 60.0000, 'warning', 1, '2026-05-13 16:17:08', '2026-05-14 16:17:08');

-- --------------------------------------------------------

--
-- Structure de la table `cameras`
--

DROP TABLE IF EXISTS `cameras`;
CREATE TABLE IF NOT EXISTS `cameras` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_salle` int UNSIGNED NOT NULL,
  `nom` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `url_flux` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `camera_status` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `fk_cameras_salle` (`id_salle`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `cameras`
--

INSERT INTO `cameras` (`id`, `id_salle`, `nom`, `url_flux`, `camera_status`) VALUES
(1, 1, 'Cam Open Space', 'rtsp://192.168.1.10:554/stream1', 1),
(2, 2, 'Cam Local Technique', 'rtsp://192.168.1.11:554/stream1', 1);

-- --------------------------------------------------------

--
-- Structure de la table `capteurs`
--

DROP TABLE IF EXISTS `capteurs`;
CREATE TABLE IF NOT EXISTS `capteurs` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_salle` int UNSIGNED NOT NULL,
  `type` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `unite` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `id_arduino` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_connected` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `fk_capteurs_salle` (`id_salle`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `capteurs`
--

INSERT INTO `capteurs` (`id`, `id_salle`, `type`, `unite`, `id_arduino`, `is_connected`) VALUES
(1, 1, 'SCD30', 'multi', 'ARD-001', 1),
(2, 1, 'Light Sensor', 'lux', 'ARD-001', 1),
(3, 2, 'SCD30', 'multi', 'ARD-002', 1),
(4, 2, 'Light Sensor', 'lux', 'ARD-002', 1);

-- --------------------------------------------------------

--
-- Structure de la table `login_attempts`
--

DROP TABLE IF EXISTS `login_attempts`;
CREATE TABLE IF NOT EXISTS `login_attempts` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `ip` varchar(45) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempted_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_attempts_ip_email` (`ip`,`email`),
  KEY `idx_attempts_date` (`attempted_at`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `login_attempts`
--

INSERT INTO `login_attempts` (`id`, `ip`, `email`, `attempted_at`) VALUES
(1, '192.168.1.55', 'admin@gtb.local', '2026-05-18 16:07:08'),
(2, '192.168.1.55', 'admin@gtb.local', '2026-05-18 16:09:08'),
(3, '192.168.1.55', 'admin@gtb.local', '2026-05-18 16:11:08');

-- --------------------------------------------------------

--
-- Structure de la table `mesures`
--

DROP TABLE IF EXISTS `mesures`;
CREATE TABLE IF NOT EXISTS `mesures` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_capteur` int UNSIGNED NOT NULL,
  `type_mesure` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `valeur` decimal(10,4) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_mesures_capteur` (`id_capteur`)
) ENGINE=InnoDB AUTO_INCREMENT=33 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `mesures`
--

INSERT INTO `mesures` (`id`, `id_capteur`, `type_mesure`, `valeur`, `created_at`) VALUES
(1, 1, 'co2', 412.0000, '2026-05-16 16:17:08'),
(2, 1, 'temperature', 20.5000, '2026-05-16 16:17:08'),
(3, 1, 'humidite', 44.0000, '2026-05-16 16:17:08'),
(4, 1, 'co2', 580.0000, '2026-05-17 16:17:08'),
(5, 1, 'temperature', 21.8000, '2026-05-17 16:17:08'),
(6, 1, 'humidite', 47.5000, '2026-05-17 16:17:08'),
(7, 1, 'co2', 950.0000, '2026-05-18 10:17:08'),
(8, 1, 'temperature', 23.1000, '2026-05-18 10:17:08'),
(9, 1, 'humidite', 52.0000, '2026-05-18 10:17:08'),
(10, 1, 'co2', 1100.0000, '2026-05-18 15:17:08'),
(11, 1, 'temperature', 23.9000, '2026-05-18 15:17:08'),
(12, 1, 'humidite', 54.2000, '2026-05-18 15:17:08'),
(13, 2, 'luminosite', 50.0000, '2026-05-16 16:17:08'),
(14, 2, 'luminosite', 420.0000, '2026-05-17 16:17:08'),
(15, 2, 'luminosite', 780.0000, '2026-05-18 10:17:08'),
(16, 2, 'luminosite', 640.0000, '2026-05-18 15:17:08'),
(17, 3, 'co2', 430.0000, '2026-05-16 16:17:08'),
(18, 3, 'temperature', 19.0000, '2026-05-16 16:17:08'),
(19, 3, 'humidite', 41.0000, '2026-05-16 16:17:08'),
(20, 3, 'co2', 460.0000, '2026-05-17 16:17:08'),
(21, 3, 'temperature', 19.5000, '2026-05-17 16:17:08'),
(22, 3, 'humidite', 42.0000, '2026-05-17 16:17:08'),
(23, 3, 'co2', 490.0000, '2026-05-18 10:17:08'),
(24, 3, 'temperature', 34.8000, '2026-05-18 10:17:08'),
(25, 3, 'humidite', 38.5000, '2026-05-18 10:17:08'),
(26, 3, 'co2', 510.0000, '2026-05-18 15:17:08'),
(27, 3, 'temperature', 36.2000, '2026-05-18 15:17:08'),
(28, 3, 'humidite', 37.0000, '2026-05-18 15:17:08'),
(29, 4, 'luminosite', 80.0000, '2026-05-16 16:17:08'),
(30, 4, 'luminosite', 95.0000, '2026-05-17 16:17:08'),
(31, 4, 'luminosite', 110.0000, '2026-05-18 10:17:08'),
(32, 4, 'luminosite', 105.0000, '2026-05-18 15:17:08');

-- --------------------------------------------------------

--
-- Structure de la table `password_reset_tokens`
--

DROP TABLE IF EXISTS `password_reset_tokens`;
CREATE TABLE IF NOT EXISTS `password_reset_tokens` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expires_at` datetime NOT NULL,
  `used` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_token` (`token`),
  KEY `idx_reset_email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `password_reset_tokens`
--

INSERT INTO `password_reset_tokens` (`id`, `email`, `token`, `expires_at`, `used`, `created_at`) VALUES
(1, 'jean@gtb.local', 'a1b2c3d4e5f6a1b2c3d4e5f6a1b2c3d4e5f6a1b2c3d4e5f6a1b2c3d4e5f6a1b2', '2026-05-18 17:17:08', 0, '2026-05-18 16:17:08'),
(2, 'admin@gtb.local', 'deadbeefdeadbeefdeadbeefdeadbeefdeadbeefdeadbeefdeadbeefdeadbeef', '2026-05-18 14:17:08', 0, '2026-05-18 13:17:08'),
(3, 'admin@gtb.local', 'used000000used000000used000000used000000used000000used000000used', '2026-05-18 17:17:08', 1, '2026-05-18 15:47:08');

-- --------------------------------------------------------

--
-- Structure de la table `salles`
--

DROP TABLE IF EXISTS `salles`;
CREATE TABLE IF NOT EXISTS `salles` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `nom` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `open_for_all` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `salles`
--

INSERT INTO `salles` (`id`, `nom`, `type`, `open_for_all`) VALUES
(1, 'Open Space', 'Espace de travail', 1),
(2, 'Local Technique', 'Local technique', 0);

-- --------------------------------------------------------

--
-- Structure de la table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `username` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `passwrd` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_users_email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `passwrd`) VALUES
(1, 'admin', 'admin@gtb.local', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uHphaWECK'),
(2, 'jean.dupont', 'jean@gtb.local', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uHphaWECK'),
(3, 'Jean', 'jean@gmail.com', '$2y$10$K5MEQIvcQl04.41NudbLNeo0Lve.2R5z4aA2TVyNQJQgRMY9QoLaO');

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `alertes`
--
ALTER TABLE `alertes`
  ADD CONSTRAINT `fk_alertes_capteur` FOREIGN KEY (`id_capteur`) REFERENCES `capteurs` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `cameras`
--
ALTER TABLE `cameras`
  ADD CONSTRAINT `fk_cameras_salle` FOREIGN KEY (`id_salle`) REFERENCES `salles` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `capteurs`
--
ALTER TABLE `capteurs`
  ADD CONSTRAINT `fk_capteurs_salle` FOREIGN KEY (`id_salle`) REFERENCES `salles` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `mesures`
--
ALTER TABLE `mesures`
  ADD CONSTRAINT `fk_mesures_capteur` FOREIGN KEY (`id_capteur`) REFERENCES `capteurs` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
