-- ============================================================
-- Base de données : gtb
-- Générée depuis l'analyse du code source
-- ============================================================

CREATE DATABASE IF NOT EXISTS `gtb`
    DEFAULT CHARACTER SET utf8mb4
    DEFAULT COLLATE utf8mb4_unicode_ci;

USE `gtb`;

-- ------------------------------------------------------------
-- Table : users
-- Comptes utilisateurs de l'application
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
    `id`       INT UNSIGNED          NOT NULL AUTO_INCREMENT,
    `username` VARCHAR(100)          NOT NULL,
    `email`    VARCHAR(255)          NOT NULL,
    `passwrd`  VARCHAR(255)          NOT NULL,
    `role`     ENUM('admin','user')  NOT NULL DEFAULT 'user',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_users_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Table : salles
-- Salles du bâtiment surveillées par le système GTB
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `salles` (
    `id`           INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    `nom`          VARCHAR(150)  NOT NULL,
    `type`         VARCHAR(100)  NOT NULL,
    `open_for_all` TINYINT(1)    NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Table : capteurs
-- Capteurs physiques rattachés à une salle
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `capteurs` (
    `id`           INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    `id_salle`     INT UNSIGNED  NOT NULL,
    `type`         VARCHAR(100)  NOT NULL,
    `unite`        VARCHAR(20)   NOT NULL DEFAULT '',
    `id_arduino`   VARCHAR(50)   NULL     DEFAULT NULL,
    `is_connected` TINYINT(1)   NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    KEY `fk_capteurs_salle` (`id_salle`),
    CONSTRAINT `fk_capteurs_salle`
        FOREIGN KEY (`id_salle`) REFERENCES `salles` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Table : mesures
-- Valeurs relevées par les capteurs au fil du temps
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `mesures` (
    `id`          INT UNSIGNED     NOT NULL AUTO_INCREMENT,
    `id_capteur`  INT UNSIGNED     NOT NULL,
    `type_mesure` VARCHAR(100)     NOT NULL,
    `valeur`      DECIMAL(10, 4)   NOT NULL,
    `created_at`  DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `fk_mesures_capteur` (`id_capteur`),
    CONSTRAINT `fk_mesures_capteur`
        FOREIGN KEY (`id_capteur`) REFERENCES `capteurs` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Table : alertes
-- Alertes générées automatiquement quand un seuil est dépassé
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `alertes` (
    `id`                 INT UNSIGNED                       NOT NULL AUTO_INCREMENT,
    `id_capteur`         INT UNSIGNED                       NOT NULL,
    `type_alerte`        VARCHAR(200)                       NOT NULL,
    `message`            TEXT                               NOT NULL,
    `valeur_declencheur` DECIMAL(10, 4)                     NULL DEFAULT NULL,
    `seuil`              DECIMAL(10, 4)                     NULL DEFAULT NULL,
    `niveau`             ENUM('info','avertissement','critique')  NOT NULL DEFAULT 'avertissement',
    `is_resolved`        TINYINT(1)                         NOT NULL DEFAULT 0,
    `created_at`         DATETIME                           NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `resolved_at`        DATETIME                           NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `fk_alertes_capteur` (`id_capteur`),
    KEY `idx_alertes_resolved` (`is_resolved`),
    CONSTRAINT `fk_alertes_capteur`
        FOREIGN KEY (`id_capteur`) REFERENCES `capteurs` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Table : cameras
-- Caméras installées dans les salles
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `cameras` (
    `id`            INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    `id_salle`      INT UNSIGNED  NOT NULL,
    `nom`           VARCHAR(150)  NOT NULL,
    `url_flux`      VARCHAR(500)  NULL DEFAULT NULL,
    `camera_status` TINYINT(1)   NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    KEY `fk_cameras_salle` (`id_salle`),
    CONSTRAINT `fk_cameras_salle`
        FOREIGN KEY (`id_salle`) REFERENCES `salles` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Table : seuils
-- Seuils d'alerte configurables par capteur et type de mesure
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `seuils` (
    `id`          INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    `id_capteur`  INT UNSIGNED  NOT NULL,
    `type_mesure` VARCHAR(100)  NOT NULL,
    `valeur_min`  DECIMAL(10,4) NULL DEFAULT NULL,
    `valeur_max`  DECIMAL(10,4) NULL DEFAULT NULL,
    `niveau`      ENUM('info','avertissement','critique') NOT NULL DEFAULT 'avertissement',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_seuil` (`id_capteur`, `type_mesure`),
    KEY `fk_seuils_capteur` (`id_capteur`),
    CONSTRAINT `fk_seuils_capteur`
        FOREIGN KEY (`id_capteur`) REFERENCES `capteurs` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Table : login_attempts
-- Suivi des tentatives de connexion échouées (anti-brute-force)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `login_attempts` (
    `id`           INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    `ip`           VARCHAR(45)   NOT NULL,
    `email`        VARCHAR(255)  NOT NULL,
    `attempted_at` DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_attempts_ip_email`  (`ip`, `email`),
    KEY `idx_attempts_date`      (`attempted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- Données de démonstration
-- ============================================================

-- ------------------------------------------------------------
-- Utilisateurs
-- Les mots de passe doivent être générés avec :
--   echo password_hash('VotreMotDePasse', PASSWORD_DEFAULT);
--
-- Compte admin de démo    — mot de passe : Admin1234
-- Compte utilisateur démo — mot de passe : User1234
-- Remplacer les hashs ci-dessous si nécessaire.
-- ------------------------------------------------------------
INSERT INTO `users` (`username`, `email`, `passwrd`, `role`) VALUES
('admin',   'admin@gtb.local', '$2y$10$J5yV0WjQRq6Z3Y1K8mXd9u4TQ2nP7bLfW0sHkE3cVaO6iRpNmXquy', 'admin'),
('operateur', 'operateur@gtb.local', '$2y$10$A8cP2kQxNmT5bV7dLjH0ZuWo3rS1fYe9gKiR4nvD6wMqXpJtEcOlu', 'user');

-- ------------------------------------------------------------
-- Salles
-- ------------------------------------------------------------
INSERT INTO `salles` (`nom`, `type`, `open_for_all`) VALUES
('Salle Serveurs',    'Informatique', 0),
('Salle Réunion A',   'Réunion',      1),
('Bureau Direction',  'Bureau',       0),
('Couloir RDC',       'Circulation',  1);

-- ------------------------------------------------------------
-- Capteurs
-- id_salle 1 = Salle Serveurs
-- id_salle 2 = Salle Réunion A
-- id_salle 3 = Bureau Direction
-- ------------------------------------------------------------
INSERT INTO `capteurs` (`id_salle`, `type`, `unite`, `id_arduino`, `is_connected`) VALUES
(1, 'SCD30',           '', 'ARD-001', 1),   -- id 1 : capteur multi (temp/hum/co2) salle serveurs
(2, 'SCD30',           '', 'ARD-002', 1),   -- id 2 : capteur multi salle réunion
(3, 'Grove Sunlight',  '', 'ARD-003', 0);   -- id 3 : capteur luminosité bureau direction (déconnecté)

-- ------------------------------------------------------------
-- Mesures — capteur 1 (Salle Serveurs)
-- ------------------------------------------------------------
INSERT INTO `mesures` (`id_capteur`, `type_mesure`, `valeur`, `created_at`) VALUES
(1, 'temperature', 22.50, '2026-05-28 08:00:00'),
(1, 'temperature', 22.80, '2026-05-28 08:30:00'),
(1, 'temperature', 23.10, '2026-05-28 09:00:00'),
(1, 'temperature', 23.60, '2026-05-28 09:30:00'),
(1, 'temperature', 24.00, '2026-05-28 10:00:00'),
(1, 'temperature', 24.30, '2026-05-28 10:30:00'),
(1, 'temperature', 25.10, '2026-05-28 11:00:00'),
(1, 'temperature', 26.20, '2026-05-28 11:30:00'),
(1, 'temperature', 27.00, '2026-05-28 12:00:00'),
(1, 'temperature', 27.80, '2026-05-28 12:30:00'),
(1, 'humidite',    45.00, '2026-05-28 08:00:00'),
(1, 'humidite',    46.20, '2026-05-28 08:30:00'),
(1, 'humidite',    47.50, '2026-05-28 09:00:00'),
(1, 'humidite',    48.10, '2026-05-28 09:30:00'),
(1, 'humidite',    49.30, '2026-05-28 10:00:00'),
(1, 'humidite',    50.00, '2026-05-28 10:30:00'),
(1, 'co2',         800.00, '2026-05-28 08:00:00'),
(1, 'co2',         850.00, '2026-05-28 08:30:00'),
(1, 'co2',         920.00, '2026-05-28 09:00:00'),
(1, 'co2',        1050.00, '2026-05-28 09:30:00'),
(1, 'co2',        1200.00, '2026-05-28 10:00:00'),
(1, 'co2',        1380.00, '2026-05-28 10:30:00'),
(1, 'co2',        1500.00, '2026-05-28 11:00:00');

-- ------------------------------------------------------------
-- Mesures — capteur 2 (Salle Réunion A)
-- ------------------------------------------------------------
INSERT INTO `mesures` (`id_capteur`, `type_mesure`, `valeur`, `created_at`) VALUES
(2, 'temperature', 20.00, '2026-05-28 08:00:00'),
(2, 'temperature', 20.50, '2026-05-28 09:00:00'),
(2, 'temperature', 21.00, '2026-05-28 10:00:00'),
(2, 'temperature', 21.80, '2026-05-28 11:00:00'),
(2, 'temperature', 22.50, '2026-05-28 12:00:00'),
(2, 'humidite',    55.00, '2026-05-28 08:00:00'),
(2, 'humidite',    56.00, '2026-05-28 09:00:00'),
(2, 'humidite',    57.50, '2026-05-28 10:00:00'),
(2, 'humidite',    58.00, '2026-05-28 11:00:00'),
(2, 'humidite',    59.20, '2026-05-28 12:00:00'),
(2, 'co2',          600.00, '2026-05-28 08:00:00'),
(2, 'co2',          750.00, '2026-05-28 09:00:00'),
(2, 'co2',          900.00, '2026-05-28 10:00:00'),
(2, 'co2',         1100.00, '2026-05-28 11:00:00'),
(2, 'co2',         1350.00, '2026-05-28 12:00:00');

-- ------------------------------------------------------------
-- Mesures — capteur 3 (Bureau Direction — luminosité)
-- ------------------------------------------------------------
INSERT INTO `mesures` (`id_capteur`, `type_mesure`, `valeur`, `created_at`) VALUES
(3, 'luminosite', 320.00, '2026-05-27 08:00:00'),
(3, 'luminosite', 480.00, '2026-05-27 10:00:00'),
(3, 'luminosite', 610.00, '2026-05-27 12:00:00'),
(3, 'luminosite', 520.00, '2026-05-27 14:00:00'),
(3, 'luminosite', 290.00, '2026-05-27 16:00:00');

-- ------------------------------------------------------------
-- Caméras
-- ------------------------------------------------------------
INSERT INTO `cameras` (`id_salle`, `nom`, `url_flux`, `camera_status`) VALUES
-- url_flux : renseigner l'URL du flux MJPEG de la Tapo C500 une fois configurée
(1, 'Tapo C500 - Serveurs Rack A', NULL, 1),
(1, 'Tapo C500 - Serveurs Rack B', NULL, 0),
(2, 'Tapo C500 - Réunion',         NULL, 1),
(3, 'Tapo C500 - Direction',       NULL, 1);

-- ------------------------------------------------------------
-- Alertes
-- ------------------------------------------------------------
-- ------------------------------------------------------------
-- Seuils d'alerte de démonstration
-- capteur 1 = SCD30 Salle Serveurs / capteur 2 = SCD30 Salle Réunion A
-- ------------------------------------------------------------
INSERT INTO `seuils` (`id_capteur`, `type_mesure`, `valeur_min`, `valeur_max`, `niveau`) VALUES
(1, 'temperature', NULL,  27.0000, 'critique'),
(1, 'humidite',    20.0000, 70.0000, 'avertissement'),
(1, 'co2',         NULL, 1400.0000, 'avertissement'),
(2, 'temperature', NULL,  26.0000, 'avertissement'),
(2, 'humidite',    20.0000, 75.0000, 'info'),
(2, 'co2',         NULL, 1200.0000, 'avertissement');

-- ------------------------------------------------------------
INSERT INTO `alertes` (`id_capteur`, `type_alerte`, `message`, `valeur_declencheur`, `seuil`, `niveau`, `is_resolved`, `created_at`, `resolved_at`) VALUES
(1, 'Température élevée',
    'La température de la salle serveurs dépasse le seuil critique de 27 °C.',
    27.80, 27.00, 'critique', 0, '2026-05-28 12:30:00', NULL),

(1, 'CO₂ élevé',
    'Le taux de CO₂ de la salle serveurs dépasse 1 400 ppm. Vérifier la ventilation.',
    1500.00, 1400.00, 'avertissement', 0, '2026-05-28 11:00:00', NULL),

(2, 'CO₂ en hausse',
    'Le taux de CO₂ de la salle de réunion approche le seuil d'alerte.',
    1350.00, 1200.00, 'info', 1, '2026-05-28 12:00:00', '2026-05-28 13:15:00'),

(3, 'Capteur déconnecté',
    'Le capteur de luminosité du bureau direction ne répond plus.',
    NULL, NULL, 'avertissement', 0, '2026-05-28 07:45:00', NULL);
