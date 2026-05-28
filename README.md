# GTB - Gestion Technique de Bâtiment

## Présentation

GTB est un projet de supervision technique de bâtiment réalisé dans un cadre pédagogique.
Il permet de suivre plusieurs salles d'un bâtiment à partir de capteurs, de caméras IP, d'une base de données MySQL et d'une interface web en PHP.

L'objectif est de centraliser les informations techniques du bâtiment pour consulter l'état des salles, les mesures environnementales, les caméras et les alertes depuis une interface sécurisée.

---

## Fonctionnalités actuelles

- authentification utilisateur avec connexion, inscription et déconnexion
- mots de passe stockés avec `password_hash` et vérifiés avec `password_verify`
- protection CSRF sur tous les formulaires sensibles
- protection anti-brute-force : blocage après 5 tentatives échouées en 15 minutes par IP et par email
- messages flash affichés sous forme de notifications Bootstrap
- tableau de bord avec accès rapide aux principales pages
- barre de navigation commune aux pages connectées avec cloche de notifications en temps réel
- cloche de notifications avec badge indiquant le nombre d'alertes actives et un aperçu des 10 dernières
- page `Salles` qui liste les salles enregistrées en base avec badge d'accès
- page de détail d'une salle avec capteurs, statistiques de mesures (min, max, moyenne, total) et caméras rattachées
- sélection du type de mesure par capteur via menu déroulant (température, humidité, CO₂, luminosité)
- actualisation automatique de la page de détail des salles toutes les 30 secondes
- page `Caméras` affichant la liste des caméras depuis la base de données, avec aperçu du flux, statut actif/inactif et salle associée
- page `Alertes` fonctionnelle avec liste des alertes triées par statut et date, niveaux colorés (info / warning / critical) et bouton de résolution réservé aux administrateurs
- page `Administration` réservée aux administrateurs pour créer et supprimer des comptes utilisateurs avec gestion des rôles
- protection contre la suppression du dernier compte administrateur
- configuration de la base via variables d'environnement
- styles séparés pour l'interface globale, le tableau de bord et les pages d'authentification

---

## Pages principales

| Fichier | Rôle |
| --- | --- |
| `login.php` | Connexion utilisateur avec protection CSRF et anti-brute-force |
| `register.php` | Création de compte avec validation des champs |
| `forgot-password.php` | Page d'aide pour mot de passe oublié |
| `logout.php` | Déconnexion sécurisée en POST avec token CSRF |
| `dashboard.php` | Tableau de bord après connexion |
| `salles.php` | Liste des salles présentes en base |
| `salle-detail.php` | Détail d'une salle : capteurs, mesures, statistiques et caméras |
| `cameras.php` | Liste des caméras avec statut et aperçu des flux vidéo |
| `alertes.php` | Alertes déclenchées par les capteurs avec gestion admin |
| `admin.php` | Panneau d'administration : gestion des comptes utilisateurs |

---

## Structure du projet

```text
.
+-- api/
|   +-- create_alert.php     (endpoint déprécié — alertes gérées côté serveur)
+-- assets/
|   +-- css/
|   |   +-- dashboard.css
|   |   +-- global.css
|   |   +-- login.css
|   +-- js/
|       +-- dashboard.js
+-- config/
|   +-- database.php
+-- includes/
|   +-- auth_check.php
|   +-- footer.php
|   +-- header.php
|   +-- navbar.php
|   +-- security.php
+-- admin.php
+-- alertes.php
+-- cameras.php
+-- dashboard.php
+-- forgot-password.php
+-- login.php
+-- logout.php
+-- register.php
+-- salle-detail.php
+-- salles.php
```

---

## Sécurité

Le projet intègre plusieurs protections côté application :

- démarrage centralisé des sessions avec `ensure_session_started`
- génération et validation de tokens CSRF
- déconnexion uniquement en requête POST validée par token
- régénération de l'identifiant de session après connexion
- hash des mots de passe avant enregistrement
- échappement HTML avec `htmlspecialchars` lors de l'affichage
- requêtes préparées PDO pour toutes les données utilisateur
- accès protégé aux pages internes avec `includes/auth_check.php`
- accès à `admin.php` limité au rôle `admin` via `require_admin()`
- anti-brute-force sur la connexion : 5 échecs max par IP ou email en 15 minutes, enregistrés dans `login_attempts`

Ce projet est publié à des fins pédagogiques. Les identifiants, mots de passe, adresses IP privées, clés API ou données personnelles ne doivent pas être versionnés dans ce dépôt.

---

## Base de données

La connexion MySQL se fait avec PDO dans `config/database.php`.

Par défaut, l'application utilise :

| Variable | Valeur par défaut |
| --- | --- |
| `GTB_DB_HOST` | `localhost` |
| `GTB_DB_NAME` | `gtb` |
| `GTB_DB_USER` | `root` |
| `GTB_DB_PASS` | `root` |

Ces valeurs peuvent être remplacées par des variables d'environnement.

Tables de la base de données :

| Table | Rôle |
| --- | --- |
| `users` | Comptes utilisateurs avec rôle `admin` ou `user` |
| `salles` | Salles du bâtiment surveillées |
| `capteurs` | Capteurs physiques rattachés à une salle |
| `mesures` | Valeurs relevées par les capteurs au fil du temps |
| `cameras` | Caméras IP installées dans les salles |
| `alertes` | Alertes générées quand un seuil est dépassé |
| `login_attempts` | Tentatives de connexion échouées pour l'anti-brute-force |

Le fichier `gtb.sql` contient la définition complète des tables ainsi que des données de démonstration.

---

## Paramètres surveillés

Les principaux paramètres prévus sont :

- température (°C)
- humidité (%)
- CO₂ (ppm)
- luminosité (lux)

La page de détail d'une salle affiche pour chaque type de mesure :

- dernière valeur
- moyenne
- minimum
- maximum
- nombre total de relevés
- 10 dernières mesures avec horodatage

---

## Matériel utilisé

- caméra IP Tapo C500 V2
- capteur température, humidité et CO2 Grove - SCD30
- capteur de luminosité Grove - Sunlight Sensor
- microcontrôleur Arduino Uno R4 WiFi

---

## Communication

Le projet prévoit une communication par WiFi entre les éléments du système.
Le protocole HTTPS est prévu pour sécuriser les échanges entre les composants.
Les mesures sont envoyées depuis l'Arduino vers la base de données MySQL directement côté serveur.
Les alertes sont générées côté serveur lors de la réception des mesures, sans passer par un endpoint HTTP côté client.

---

## Technologies utilisées

- PHP
- MySQL
- PDO
- HTML
- CSS
- JavaScript
- Bootstrap 5
- Bootstrap Icons
- Arduino Uno R4 WiFi
- capteurs environnementaux
- caméra IP
- Git / GitHub

---

## Statut du projet

Projet en cours de développement.

Les pages `Caméras` et `Alertes` sont fonctionnelles et affichent les données présentes en base.
La page `Administration` permet de gérer les comptes utilisateurs depuis l'interface web.
La connexion entre les capteurs Arduino et la base de données est en cours d'intégration.

---

## Remarques

Ce dépôt est public.
Aucune information sensible, personnelle ou confidentielle ne doit y être publiée.

Ce fichier README a été rédigé avec l'aide de l'intelligence artificielle.
