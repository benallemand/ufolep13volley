-- MySQL dump 10.13  Distrib 8.0.39, for Win64 (x86_64)
--
-- Host: 127.0.0.1    Database: ufolep_13volley
-- ------------------------------------------------------
-- Server version	8.0.39

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
-- Table structure for table `activity`
--

DROP TABLE IF EXISTS `activity`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `activity` (
  `id` smallint NOT NULL AUTO_INCREMENT,
  `comment` varchar(400) DEFAULT NULL,
  `activity_date` datetime DEFAULT NULL,
  `user_id` smallint DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `id` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=26714 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `blacklist_by_city`
--

DROP TABLE IF EXISTS `blacklist_by_city`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `blacklist_by_city` (
  `id` smallint NOT NULL AUTO_INCREMENT,
  `city` varchar(200) NOT NULL,
  `from_date` datetime NOT NULL,
  `to_date` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `blacklist_date`
--

DROP TABLE IF EXISTS `blacklist_date`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `blacklist_date` (
  `id` smallint NOT NULL AUTO_INCREMENT,
  `closed_date` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `blacklist_gymnase`
--

DROP TABLE IF EXISTS `blacklist_gymnase`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `blacklist_gymnase` (
  `id` smallint NOT NULL AUTO_INCREMENT,
  `id_gymnase` smallint NOT NULL,
  `closed_date` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `id_gymnase` (`id_gymnase`),
  CONSTRAINT `blacklist_gymnase_ibfk_1` FOREIGN KEY (`id_gymnase`) REFERENCES `gymnase` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=62 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `blacklist_team`
--

DROP TABLE IF EXISTS `blacklist_team`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `blacklist_team` (
  `id` smallint NOT NULL AUTO_INCREMENT,
  `id_team` smallint NOT NULL,
  `closed_date` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `id_team` (`id_team`),
  CONSTRAINT `blacklist_team_ibfk_1` FOREIGN KEY (`id_team`) REFERENCES `equipes` (`id_equipe`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `blacklist_teams`
--

DROP TABLE IF EXISTS `blacklist_teams`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `blacklist_teams` (
  `id` smallint NOT NULL AUTO_INCREMENT,
  `id_team_1` smallint NOT NULL,
  `id_team_2` smallint NOT NULL,
  PRIMARY KEY (`id`),
  KEY `id_team_1` (`id_team_1`),
  KEY `id_team_2` (`id_team_2`),
  CONSTRAINT `blacklist_teams_ibfk_1` FOREIGN KEY (`id_team_1`) REFERENCES `equipes` (`id_equipe`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `blacklist_teams_ibfk_2` FOREIGN KEY (`id_team_2`) REFERENCES `equipes` (`id_equipe`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=37 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `calendar_events`
--

DROP TABLE IF EXISTS `calendar_events`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `calendar_events` (
  `id` int NOT NULL AUTO_INCREMENT,
  `season` varchar(9) NOT NULL COMMENT 'ex. 2026-2027',
  `label` varchar(100) NOT NULL,
  `date_start` datetime NOT NULL,
  `date_end` datetime DEFAULT NULL COMMENT 'NULL = evenement ponctuel',
  PRIMARY KEY (`id`),
  KEY `idx_calendar_events_season` (`season`,`date_start`)
) ENGINE=InnoDB AUTO_INCREMENT=46 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `classements`
--

DROP TABLE IF EXISTS `classements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `classements` (
  `code_competition` varchar(2) NOT NULL,
  `division` varchar(2) NOT NULL,
  `id_equipe` smallint NOT NULL,
  `penalite` tinyint(1) NOT NULL DEFAULT '0',
  `id` smallint NOT NULL AUTO_INCREMENT,
  `report_count` smallint DEFAULT '0',
  `rank_start` smallint DEFAULT '0',
  `will_register_again` bit(1) DEFAULT b'1',
  PRIMARY KEY (`id`),
  KEY `fk_classements_equipes` (`id_equipe`),
  KEY `id` (`id`),
  CONSTRAINT `fk_classements_equipes` FOREIGN KEY (`id_equipe`) REFERENCES `equipes` (`id_equipe`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=3734 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `clubs`
--

DROP TABLE IF EXISTS `clubs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `clubs` (
  `nom` varchar(200) DEFAULT NULL,
  `id` smallint NOT NULL AUTO_INCREMENT,
  `affiliation_number` varchar(200) DEFAULT NULL,
  `nom_responsable` varchar(200) DEFAULT NULL,
  `prenom_responsable` varchar(200) DEFAULT NULL,
  `tel1_responsable` varchar(200) DEFAULT NULL,
  `tel2_responsable` varchar(200) DEFAULT NULL,
  `email_responsable` varchar(200) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `id` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=256 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `commission`
--

DROP TABLE IF EXISTS `commission`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `commission` (
  `id_commission` smallint NOT NULL AUTO_INCREMENT,
  `nom` varchar(20) NOT NULL,
  `prenom` varchar(20) NOT NULL,
  `fonction` varchar(80) NOT NULL,
  `telephone1` varchar(15) NOT NULL,
  `telephone2` varchar(15) NOT NULL,
  `email` varchar(50) NOT NULL,
  `photo` varchar(50) NOT NULL,
  `type` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`id_commission`),
  KEY `id_commission` (`id_commission`)
) ENGINE=InnoDB AUTO_INCREMENT=32 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `commission_division`
--

DROP TABLE IF EXISTS `commission_division`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `commission_division` (
  `id` smallint NOT NULL AUTO_INCREMENT,
  `id_commission` smallint NOT NULL,
  `division` varchar(5) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `id_commission` (`id_commission`),
  CONSTRAINT `commission_division_ibfk_1` FOREIGN KEY (`id_commission`) REFERENCES `commission` (`id_commission`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=130 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `competitions`
--

DROP TABLE IF EXISTS `competitions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `competitions` (
  `id` smallint NOT NULL AUTO_INCREMENT,
  `code_competition` varchar(2) NOT NULL,
  `libelle` varchar(50) NOT NULL,
  `id_compet_maitre` varchar(2) NOT NULL,
  `start_date` date DEFAULT NULL,
  `is_home_and_away` bit(1) DEFAULT b'0',
  `limit_register_date` date DEFAULT NULL,
  `start_register_date` date DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `id` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=128 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `comptes_acces`
--

DROP TABLE IF EXISTS `comptes_acces`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `comptes_acces` (
  `login` varchar(200) DEFAULT NULL,
  `id` smallint NOT NULL AUTO_INCREMENT,
  `email` varchar(200) DEFAULT NULL,
  `password_hash` varchar(200) DEFAULT NULL,
  `is_admin` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_email` (`email`),
  KEY `id` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1378 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `creneau`
--

DROP TABLE IF EXISTS `creneau`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `creneau` (
  `id_gymnase` smallint DEFAULT NULL,
  `jour` varchar(20) DEFAULT NULL,
  `heure` varchar(5) DEFAULT NULL,
  `id_equipe` smallint DEFAULT NULL,
  `id` smallint NOT NULL AUTO_INCREMENT,
  `has_time_constraint` bit(1) DEFAULT NULL,
  `usage_priority` smallint NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `fk_creneau_gymnase` (`id_gymnase`),
  KEY `id` (`id`),
  KEY `fk_creneau_equipes` (`id_equipe`),
  CONSTRAINT `fk_creneau_equipes` FOREIGN KEY (`id_equipe`) REFERENCES `equipes` (`id_equipe`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `fk_creneau_gymnase` FOREIGN KEY (`id_gymnase`) REFERENCES `gymnase` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=1964 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `dates_limite`
--

DROP TABLE IF EXISTS `dates_limite`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `dates_limite` (
  `id_date` smallint NOT NULL AUTO_INCREMENT,
  `code_competition` varchar(2) NOT NULL,
  `date_limite` varchar(40) NOT NULL,
  PRIMARY KEY (`id_date`),
  KEY `id_date` (`id_date`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `emails`
--

DROP TABLE IF EXISTS `emails`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `emails` (
  `id` int NOT NULL AUTO_INCREMENT,
  `from_email` text NOT NULL,
  `to_email` text NOT NULL,
  `cc` text NOT NULL,
  `bcc` text NOT NULL,
  `subject` text NOT NULL,
  `body` text NOT NULL,
  `creation_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `sent_date` datetime DEFAULT NULL,
  `sending_status` enum('TO_DO','DONE','ERROR') NOT NULL DEFAULT 'TO_DO',
  `is_read` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=21902 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `equipes`
--

DROP TABLE IF EXISTS `equipes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `equipes` (
  `id_equipe` smallint NOT NULL AUTO_INCREMENT,
  `code_competition` varchar(2) NOT NULL,
  `nom_equipe` varchar(50) NOT NULL,
  `id_club` smallint DEFAULT NULL,
  `web_site` varchar(100) DEFAULT NULL,
  `id_photo` smallint DEFAULT NULL,
  `is_cup_registered` bit(1) DEFAULT b'0',
  PRIMARY KEY (`id_equipe`),
  KEY `fk_equipes_clubs` (`id_club`),
  KEY `id_equipe` (`id_equipe`),
  CONSTRAINT `fk_equipes_clubs` FOREIGN KEY (`id_club`) REFERENCES `clubs` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=733 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `friendships`
--

DROP TABLE IF EXISTS `friendships`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `friendships` (
  `id` smallint NOT NULL AUTO_INCREMENT,
  `id_club_1` smallint NOT NULL,
  `id_club_2` smallint NOT NULL,
  PRIMARY KEY (`id`),
  KEY `id_club_1` (`id_club_1`),
  KEY `id_club_2` (`id_club_2`),
  CONSTRAINT `friendships_ibfk_1` FOREIGN KEY (`id_club_1`) REFERENCES `clubs` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `friendships_ibfk_2` FOREIGN KEY (`id_club_2`) REFERENCES `clubs` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `gymnase`
--

DROP TABLE IF EXISTS `gymnase`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `gymnase` (
  `nom` varchar(200) DEFAULT NULL,
  `adresse` varchar(200) DEFAULT NULL,
  `code_postal` int DEFAULT NULL,
  `ville` varchar(100) DEFAULT NULL,
  `gps` varchar(20) DEFAULT NULL,
  `id` smallint NOT NULL AUTO_INCREMENT,
  `nb_terrain` smallint NOT NULL DEFAULT '3',
  `remarques` text,
  PRIMARY KEY (`id`),
  KEY `id` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=77 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `hall_of_fame`
--

DROP TABLE IF EXISTS `hall_of_fame`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `hall_of_fame` (
  `id` smallint NOT NULL AUTO_INCREMENT,
  `title` varchar(500) NOT NULL,
  `team_name` varchar(500) NOT NULL,
  `period` varchar(500) NOT NULL,
  `league` varchar(100) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  KEY `id` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=513 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `joueur_equipe`
--

DROP TABLE IF EXISTS `joueur_equipe`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `joueur_equipe` (
  `id_joueur` smallint NOT NULL DEFAULT '0',
  `id_equipe` smallint NOT NULL DEFAULT '0',
  `is_leader` bit(1) DEFAULT NULL,
  `is_vice_leader` bit(1) DEFAULT NULL,
  `is_captain` bit(1) DEFAULT NULL,
  PRIMARY KEY (`id_joueur`,`id_equipe`),
  KEY `fk_joueur_equipe_equipe` (`id_equipe`),
  KEY `fk_joueur_equipe_joueur` (`id_joueur`),
  CONSTRAINT `fk_joueur_equipe_equipe` FOREIGN KEY (`id_equipe`) REFERENCES `equipes` (`id_equipe`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `fk_joueur_equipe_joueur` FOREIGN KEY (`id_joueur`) REFERENCES `joueurs` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `joueurs`
--

DROP TABLE IF EXISTS `joueurs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `joueurs` (
  `prenom` varchar(50) DEFAULT NULL,
  `nom` varchar(50) DEFAULT NULL,
  `telephone` varchar(20) DEFAULT NULL,
  `email` varchar(50) DEFAULT NULL,
  `num_licence` varchar(50) DEFAULT NULL,
  `sexe` varchar(1) DEFAULT NULL,
  `departement_affiliation` int DEFAULT '13',
  `id_club` smallint DEFAULT NULL,
  `telephone2` varchar(20) DEFAULT NULL,
  `email2` varchar(50) DEFAULT NULL,
  `est_responsable_club` bit(1) DEFAULT NULL,
  `id` smallint NOT NULL AUTO_INCREMENT,
  `date_homologation` date DEFAULT NULL,
  `id_photo` smallint DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `id` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4400 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `journees`
--

DROP TABLE IF EXISTS `journees`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `journees` (
  `id` smallint NOT NULL AUTO_INCREMENT,
  `code_competition` varchar(2) NOT NULL,
  `numero` tinyint NOT NULL,
  `nommage` varchar(30) NOT NULL,
  `libelle` varchar(50) DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `id` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1273 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `live_scores`
--

DROP TABLE IF EXISTS `live_scores`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `live_scores` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_match` varchar(20) NOT NULL,
  `set_en_cours` tinyint NOT NULL DEFAULT '1',
  `score_dom` tinyint NOT NULL DEFAULT '0',
  `score_ext` tinyint NOT NULL DEFAULT '0',
  `sets_dom` tinyint NOT NULL DEFAULT '0',
  `sets_ext` tinyint NOT NULL DEFAULT '0',
  `set_1_dom` tinyint NOT NULL DEFAULT '0',
  `set_1_ext` tinyint NOT NULL DEFAULT '0',
  `set_2_dom` tinyint NOT NULL DEFAULT '0',
  `set_2_ext` tinyint NOT NULL DEFAULT '0',
  `set_3_dom` tinyint NOT NULL DEFAULT '0',
  `set_3_ext` tinyint NOT NULL DEFAULT '0',
  `set_4_dom` tinyint NOT NULL DEFAULT '0',
  `set_4_ext` tinyint NOT NULL DEFAULT '0',
  `set_5_dom` tinyint NOT NULL DEFAULT '0',
  `set_5_ext` tinyint NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `version` int NOT NULL DEFAULT '1',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_match` (`id_match`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `match_player`
--

DROP TABLE IF EXISTS `match_player`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `match_player` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `id_match` bigint NOT NULL,
  `id_player` smallint NOT NULL,
  PRIMARY KEY (`id`),
  KEY `id_match` (`id_match`),
  KEY `id_player` (`id_player`),
  CONSTRAINT `match_player_ibfk_1` FOREIGN KEY (`id_match`) REFERENCES `matches` (`id_match`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `match_player_ibfk_2` FOREIGN KEY (`id_player`) REFERENCES `joueurs` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=56408 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Temporary view structure for view `match_players_count_view`
--

DROP TABLE IF EXISTS `match_players_count_view`;
/*!50001 DROP VIEW IF EXISTS `match_players_count_view`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `match_players_count_view` AS SELECT 
 1 AS `id_match`,
 1 AS `code_match`,
 1 AS `code_competition`,
 1 AS `count_dom`,
 1 AS `count_masc_dom`,
 1 AS `count_fem_dom`,
 1 AS `count_ext`,
 1 AS `count_masc_ext`,
 1 AS `count_fem_ext`,
 1 AS `count_renfort`,
 1 AS `count_status`*/;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `matches`
--

DROP TABLE IF EXISTS `matches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `matches` (
  `id_match` bigint NOT NULL AUTO_INCREMENT,
  `code_match` varchar(20) DEFAULT NULL,
  `code_competition` varchar(2) NOT NULL,
  `division` varchar(2) NOT NULL,
  `id_equipe_dom` smallint NOT NULL,
  `id_equipe_ext` smallint NOT NULL,
  `set_1_dom` tinyint NOT NULL DEFAULT '0',
  `set_1_ext` tinyint NOT NULL DEFAULT '0',
  `set_2_dom` tinyint NOT NULL DEFAULT '0',
  `set_2_ext` tinyint NOT NULL DEFAULT '0',
  `set_3_dom` tinyint NOT NULL DEFAULT '0',
  `set_3_ext` tinyint NOT NULL DEFAULT '0',
  `set_4_dom` tinyint NOT NULL DEFAULT '0',
  `set_4_ext` tinyint NOT NULL DEFAULT '0',
  `set_5_dom` tinyint NOT NULL DEFAULT '0',
  `set_5_ext` tinyint NOT NULL DEFAULT '0',
  `date_reception` date DEFAULT NULL,
  `certif` tinyint(1) NOT NULL DEFAULT '0',
  `id_journee` smallint DEFAULT NULL,
  `note` text,
  `report_status` varchar(100) DEFAULT 'NOT_ASKED',
  `date_original` date DEFAULT NULL,
  `match_status` enum('NOT_CONFIRMED','CONFIRMED','ARCHIVED') NOT NULL DEFAULT 'NOT_CONFIRMED',
  `id_gymnasium` smallint DEFAULT NULL,
  `is_sign_team_dom` bit(1) DEFAULT b'0',
  `is_sign_team_ext` bit(1) DEFAULT b'0',
  `is_sign_match_dom` bit(1) DEFAULT b'0',
  `is_sign_match_ext` bit(1) DEFAULT b'0',
  `referee` enum('HOME','AWAY','BOTH') DEFAULT 'HOME',
  PRIMARY KEY (`id_match`),
  UNIQUE KEY `code_match` (`code_match`),
  UNIQUE KEY `code_match_2` (`code_match`),
  KEY `fk_matches_journees` (`id_journee`),
  KEY `fk_matches_equipesdom` (`id_equipe_dom`),
  KEY `fk_matches_equipesext` (`id_equipe_ext`),
  KEY `id_match` (`id_match`),
  KEY `fk_matches_gymnasium` (`id_gymnasium`),
  CONSTRAINT `fk_matches_equipesdom` FOREIGN KEY (`id_equipe_dom`) REFERENCES `equipes` (`id_equipe`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fk_matches_equipesext` FOREIGN KEY (`id_equipe_ext`) REFERENCES `equipes` (`id_equipe`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fk_matches_gymnasium` FOREIGN KEY (`id_gymnasium`) REFERENCES `gymnase` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fk_matches_journees` FOREIGN KEY (`id_journee`) REFERENCES `journees` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=81122 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 */ /*!50003 TRIGGER `trg_bkp_orig_date` BEFORE UPDATE ON `matches` FOR EACH ROW BEGIN
    IF NEW.date_reception <> OLD.date_reception
    THEN
        SET NEW.date_original = OLD.date_reception;
    END IF;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Temporary view structure for view `matchs_view`
--

DROP TABLE IF EXISTS `matchs_view`;
/*!50001 DROP VIEW IF EXISTS `matchs_view`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `matchs_view` AS SELECT 
 1 AS `id_match`,
 1 AS `forfait_dom`,
 1 AS `forfait_ext`,
 1 AS `is_forfait`,
 1 AS `is_match_score_filled`,
 1 AS `is_match_player_filled`,
 1 AS `count_status`,
 1 AS `is_match_player_requested`,
 1 AS `has_forbidden_player`,
 1 AS `code_match`,
 1 AS `code_competition`,
 1 AS `parent_code_competition`,
 1 AS `libelle_competition`,
 1 AS `division`,
 1 AS `numero_journee`,
 1 AS `id_journee`,
 1 AS `journee`,
 1 AS `id_equipe_dom`,
 1 AS `equipe_dom`,
 1 AS `id_equipe_ext`,
 1 AS `equipe_ext`,
 1 AS `score_equipe_dom`,
 1 AS `score_equipe_ext`,
 1 AS `set_1_dom`,
 1 AS `set_1_ext`,
 1 AS `set_2_dom`,
 1 AS `set_2_ext`,
 1 AS `set_3_dom`,
 1 AS `set_3_ext`,
 1 AS `set_4_dom`,
 1 AS `set_4_ext`,
 1 AS `set_5_dom`,
 1 AS `set_5_ext`,
 1 AS `heure_reception`,
 1 AS `id_gymnasium`,
 1 AS `gymnasium`,
 1 AS `date_reception`,
 1 AS `date_reception_raw`,
 1 AS `date_original`,
 1 AS `date_original_raw`,
 1 AS `sheet_received`,
 1 AS `note`,
 1 AS `certif`,
 1 AS `report_status`,
 1 AS `retard`,
 1 AS `match_status`,
 1 AS `is_sign_match_dom`,
 1 AS `is_sign_match_ext`,
 1 AS `is_sign_team_dom`,
 1 AS `is_sign_team_ext`,
 1 AS `email_dom`,
 1 AS `email_ext`,
 1 AS `referee`,
 1 AS `is_survey_filled_dom`,
 1 AS `is_survey_filled_ext`,
 1 AS `contact_com`*/;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `news`
--

DROP TABLE IF EXISTS `news`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `news` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `title` varchar(500) NOT NULL,
  `file_path` varchar(200) NOT NULL,
  `news_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `text` text,
  `is_disabled` bit(1) DEFAULT b'0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `photos`
--

DROP TABLE IF EXISTS `photos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `photos` (
  `id` smallint NOT NULL AUTO_INCREMENT,
  `path_photo` varchar(500) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `id` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5191 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Temporary view structure for view `players_view`
--

DROP TABLE IF EXISTS `players_view`;
/*!50001 DROP VIEW IF EXISTS `players_view`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `players_view` AS SELECT 
 1 AS `full_name`,
 1 AS `prenom`,
 1 AS `nom`,
 1 AS `telephone`,
 1 AS `email`,
 1 AS `num_licence`,
 1 AS `num_licence_ext`,
 1 AS `path_photo`,
 1 AS `path_photo_low`,
 1 AS `sexe`,
 1 AS `departement_affiliation`,
 1 AS `est_actif`,
 1 AS `id_club`,
 1 AS `club`,
 1 AS `telephone2`,
 1 AS `email2`,
 1 AS `est_responsable_club`,
 1 AS `is_captain`,
 1 AS `is_vice_leader`,
 1 AS `is_leader`,
 1 AS `id_captain`,
 1 AS `id_vl`,
 1 AS `id_l`,
 1 AS `id`,
 1 AS `active_teams_list`,
 1 AS `inactive_teams_list`,
 1 AS `teams_list`,
 1 AS `team_leader_list`,
 1 AS `date_homologation`*/;
SET character_set_client = @saved_cs_client;

--
-- Temporary view structure for view `ranks_view`
--

DROP TABLE IF EXISTS `ranks_view`;
/*!50001 DROP VIEW IF EXISTS `ranks_view`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `ranks_view` AS SELECT 
 1 AS `code_competition`,
 1 AS `division`,
 1 AS `rang`,
 1 AS `id_equipe`,
 1 AS `equipe`,
 1 AS `points`,
 1 AS `joues`,
 1 AS `gagnes`,
 1 AS `perdus`,
 1 AS `sets_pour`,
 1 AS `sets_contre`,
 1 AS `diff`,
 1 AS `penalites`,
 1 AS `matches_lost_by_forfeit_count`,
 1 AS `report_count`*/;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `register`
--

DROP TABLE IF EXISTS `register`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `register` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `new_team_name` varchar(100) NOT NULL,
  `id_club` smallint NOT NULL,
  `id_competition` smallint NOT NULL,
  `old_team_id` smallint DEFAULT NULL,
  `leader_name` varchar(100) NOT NULL,
  `leader_first_name` varchar(100) NOT NULL,
  `leader_email` varchar(100) NOT NULL,
  `leader_phone` varchar(100) NOT NULL,
  `id_court_1` smallint DEFAULT NULL,
  `day_court_1` varchar(100) DEFAULT NULL,
  `hour_court_1` varchar(100) DEFAULT NULL,
  `id_court_2` smallint DEFAULT NULL,
  `day_court_2` varchar(100) DEFAULT NULL,
  `hour_court_2` varchar(100) DEFAULT NULL,
  `remarks` varchar(5000) DEFAULT NULL,
  `creation_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `division` varchar(2) DEFAULT NULL,
  `rank_start` smallint DEFAULT NULL,
  `is_paid` bit(1) DEFAULT b'0',
  `is_seeding_tournament_requested` bit(1) DEFAULT b'0',
  `can_seeding_tournament_setup` bit(1) DEFAULT b'0',
  `is_cup_registered` bit(1) DEFAULT b'0',
  `status` enum('PENDING','VALIDATED') NOT NULL DEFAULT 'PENDING',
  `validation_date` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `new_team_name` (`new_team_name`),
  UNIQUE KEY `old_team_id` (`old_team_id`),
  KEY `id_club` (`id_club`),
  KEY `id_competition` (`id_competition`),
  KEY `id_court_1` (`id_court_1`),
  KEY `id_court_2` (`id_court_2`),
  CONSTRAINT `register_ibfk_1` FOREIGN KEY (`id_club`) REFERENCES `clubs` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `register_ibfk_2` FOREIGN KEY (`id_competition`) REFERENCES `competitions` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `register_ibfk_3` FOREIGN KEY (`old_team_id`) REFERENCES `equipes` (`id_equipe`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `register_ibfk_4` FOREIGN KEY (`id_court_1`) REFERENCES `gymnase` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `register_ibfk_5` FOREIGN KEY (`id_court_2`) REFERENCES `gymnase` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=24 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `registry`
--

DROP TABLE IF EXISTS `registry`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `registry` (
  `id` smallint NOT NULL AUTO_INCREMENT,
  `registry_key` varchar(500) NOT NULL,
  `registry_value` varchar(200) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `id` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=252 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `survey`
--

DROP TABLE IF EXISTS `survey`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `survey` (
  `id` smallint NOT NULL AUTO_INCREMENT,
  `user_id` smallint NOT NULL,
  `id_match` bigint NOT NULL,
  `on_time` tinyint DEFAULT '0',
  `spirit` tinyint DEFAULT '0',
  `referee` tinyint DEFAULT '0',
  `catering` tinyint DEFAULT '0',
  `global` tinyint DEFAULT '0',
  `comment` text,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `id_match` (`id_match`),
  CONSTRAINT `survey_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `comptes_acces` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `survey_ibfk_2` FOREIGN KEY (`id_match`) REFERENCES `matches` (`id_match`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=19021 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Temporary view structure for view `survey_view`
--

DROP TABLE IF EXISTS `survey_view`;
/*!50001 DROP VIEW IF EXISTS `survey_view`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `survey_view` AS SELECT 
 1 AS `note`,
 1 AS `nb_matchs`,
 1 AS `moyenne`,
 1 AS `club`*/;
SET character_set_client = @saved_cs_client;

--
-- Temporary view structure for view `survey_view_raw`
--

DROP TABLE IF EXISTS `survey_view_raw`;
/*!50001 DROP VIEW IF EXISTS `survey_view_raw`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `survey_view_raw` AS SELECT 
 1 AS `id`,
 1 AS `id_team_sondeuse`,
 1 AS `code_match`,
 1 AS `id_match`,
 1 AS `equipe`,
 1 AS `club`,
 1 AS `on_time`,
 1 AS `coef_on_time`,
 1 AS `spirit`,
 1 AS `coef_spirit`,
 1 AS `referee`,
 1 AS `coef_referee`,
 1 AS `catering`,
 1 AS `coef_catering`,
 1 AS `global`,
 1 AS `coef_global`,
 1 AS `nb_penalites`*/;
SET character_set_client = @saved_cs_client;

--
-- Temporary view structure for view `teams_view`
--

DROP TABLE IF EXISTS `teams_view`;
/*!50001 DROP VIEW IF EXISTS `teams_view`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `teams_view` AS SELECT 
 1 AS `code_competition`,
 1 AS `libelle_competition`,
 1 AS `nom_equipe`,
 1 AS `team_full_name`,
 1 AS `id_club`,
 1 AS `club`,
 1 AS `id_equipe`,
 1 AS `responsable`,
 1 AS `responsable_base64`,
 1 AS `telephone_1`,
 1 AS `telephone_1_base64`,
 1 AS `telephone_2`,
 1 AS `telephone_2_base64`,
 1 AS `email`,
 1 AS `email_base64`,
 1 AS `gymnasiums_list`,
 1 AS `web_site`,
 1 AS `id_photo`,
 1 AS `path_photo`,
 1 AS `is_cup_registered`,
 1 AS `is_active_team`*/;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `users_clubs`
--

DROP TABLE IF EXISTS `users_clubs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users_clubs` (
  `user_id` smallint NOT NULL,
  `club_id` smallint NOT NULL,
  PRIMARY KEY (`user_id`,`club_id`),
  KEY `fk_uc_c` (`club_id`),
  CONSTRAINT `fk_uc_c` FOREIGN KEY (`club_id`) REFERENCES `clubs` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_uc_u` FOREIGN KEY (`user_id`) REFERENCES `comptes_acces` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `users_teams`
--

DROP TABLE IF EXISTS `users_teams`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users_teams` (
  `user_id` smallint NOT NULL,
  `team_id` smallint NOT NULL,
  PRIMARY KEY (`user_id`,`team_id`),
  KEY `fk_ut_t` (`team_id`),
  CONSTRAINT `fk_ut_t` FOREIGN KEY (`team_id`) REFERENCES `equipes` (`id_equipe`) ON DELETE CASCADE,
  CONSTRAINT `fk_ut_u` FOREIGN KEY (`user_id`) REFERENCES `comptes_acces` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping events for database 'ufolep_13volley'
--

--
-- Dumping routines for database 'ufolep_13volley'
--
/*!50003 DROP FUNCTION IF EXISTS `SPLIT_STRING` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
CREATE FUNCTION `SPLIT_STRING`(`str` VARCHAR(255), `delim` VARCHAR(12), `pos` INT) RETURNS varchar(255) CHARSET latin1
RETURN REPLACE(SUBSTRING(SUBSTRING_INDEX(str, delim, pos),

                           LENGTH(SUBSTRING_INDEX(str, delim, pos - 1)) + 1),
                 delim, '') ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Final view structure for view `match_players_count_view`
--

/*!50001 DROP VIEW IF EXISTS `match_players_count_view`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_0900_ai_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 SQL SECURITY INVOKER */
/*!50001 VIEW `match_players_count_view` AS select `a`.`id_match` AS `id_match`,`a`.`code_match` AS `code_match`,`a`.`code_competition` AS `code_competition`,sum(`a`.`count_dom`) AS `count_dom`,sum(`a`.`count_masc_dom`) AS `count_masc_dom`,sum(`a`.`count_fem_dom`) AS `count_fem_dom`,sum(`a`.`count_ext`) AS `count_ext`,sum(`a`.`count_masc_ext`) AS `count_masc_ext`,sum(`a`.`count_fem_ext`) AS `count_fem_ext`,sum(`a`.`count_renfort`) AS `count_renfort`,(case when ((sum(`a`.`count_dom`) = 0) and (sum(`a`.`count_ext`) = 0)) then 'fiches équipes non remplies' when (sum(`a`.`count_dom`) = 0) then 'fiche équipe à domicile non remplie' when (sum(`a`.`count_ext`) = 0) then 'fiche équipe à l\'extérieur non remplie' when ((`a`.`code_competition` in ('kh','kf','ut')) and (sum(`a`.`count_fem_dom`) < 2)) then 'pas assez de filles à domicile' when ((`a`.`code_competition` in ('kh','kf','ut')) and (sum(`a`.`count_fem_ext`) < 2)) then 'pas assez de filles à l\'extérieur' when ((`a`.`code_competition` in ('mo','ut')) and ((sum(`a`.`count_masc_dom`) = 0) or (sum(`a`.`count_fem_dom`) = 0))) then 'mixité obligatoire à domicile non respectée' when ((`a`.`code_competition` in ('mo','ut')) and ((sum(`a`.`count_masc_ext`) = 0) or (sum(`a`.`count_fem_ext`) = 0))) then 'mixité obligatoire à l\'extérieur non respectée' end) AS `count_status` from (select `m`.`id_match` AS `id_match`,`m`.`code_match` AS `code_match`,`m`.`code_competition` AS `code_competition`,count(distinct (case when (`jed`.`id_equipe` = `m`.`id_equipe_dom`) then `mp_dom`.`id_player` end)) AS `count_dom`,count(distinct (case when (`jed`.`id_equipe` = `m`.`id_equipe_dom`) then `j_masc_dom`.`id` end)) AS `count_masc_dom`,count(distinct (case when (`jed`.`id_equipe` = `m`.`id_equipe_dom`) then `j_fem_dom`.`id` end)) AS `count_fem_dom`,0 AS `count_ext`,0 AS `count_masc_ext`,0 AS `count_fem_ext`,0 AS `count_renfort` from ((((`matches` `m` left join `match_player` `mp_dom` on((`mp_dom`.`id_match` = `m`.`id_match`))) left join `joueur_equipe` `jed` on((`jed`.`id_joueur` = `mp_dom`.`id_player`))) left join `joueurs` `j_masc_dom` on(((`jed`.`id_joueur` = `j_masc_dom`.`id`) and (`j_masc_dom`.`sexe` = 'M')))) left join `joueurs` `j_fem_dom` on(((`jed`.`id_joueur` = `j_fem_dom`.`id`) and (`j_fem_dom`.`sexe` = 'F')))) where (`m`.`match_status` = 'CONFIRMED') group by `m`.`id_match`,`m`.`code_match`,`m`.`code_competition` union all select `m`.`id_match` AS `id_match`,`m`.`code_match` AS `code_match`,`m`.`code_competition` AS `code_competition`,0 AS `count_dom`,0 AS `count_masc_dom`,0 AS `count_fem_dom`,count(distinct (case when (`jee`.`id_equipe` = `m`.`id_equipe_ext`) then `mp_ext`.`id_player` end)) AS `count_ext`,count(distinct (case when (`jee`.`id_equipe` = `m`.`id_equipe_ext`) then `j_masc_ext`.`id` end)) AS `count_masc_ext`,count(distinct (case when (`jee`.`id_equipe` = `m`.`id_equipe_ext`) then `j_fem_ext`.`id` end)) AS `count_fem_ext`,0 AS `count_renfort` from ((((`matches` `m` left join `match_player` `mp_ext` on((`mp_ext`.`id_match` = `m`.`id_match`))) left join `joueur_equipe` `jee` on((`jee`.`id_joueur` = `mp_ext`.`id_player`))) left join `joueurs` `j_masc_ext` on(((`jee`.`id_joueur` = `j_masc_ext`.`id`) and (`j_masc_ext`.`sexe` = 'M')))) left join `joueurs` `j_fem_ext` on(((`jee`.`id_joueur` = `j_fem_ext`.`id`) and (`j_fem_ext`.`sexe` = 'F')))) where (`m`.`match_status` = 'CONFIRMED') group by `m`.`id_match`,`m`.`code_match`,`m`.`code_competition` union all select `m`.`id_match` AS `id_match`,`m`.`code_match` AS `code_match`,`m`.`code_competition` AS `code_competition`,0 AS `count_dom`,0 AS `count_masc_dom`,0 AS `count_fem_dom`,0 AS `count_ext`,0 AS `count_masc_ext`,0 AS `count_fem_ext`,count(distinct `mp_renfort`.`id_player`) AS `count_renfort` from (`matches` `m` left join `match_player` `mp_renfort` on((`mp_renfort`.`id_match` = `m`.`id_match`))) where (`mp_renfort`.`id_player` in (select `joueur_equipe`.`id_joueur` from `joueur_equipe` where (`joueur_equipe`.`id_equipe` in (`m`.`id_equipe_dom`,`m`.`id_equipe_ext`))) is false and (`m`.`match_status` = 'CONFIRMED')) group by `m`.`id_match`,`m`.`code_match`,`m`.`code_competition`) `a` group by `a`.`id_match`,`a`.`code_match`,`a`.`code_competition` having ((((sum(`a`.`count_dom`) + sum(`a`.`count_renfort`)) >= if((`a`.`code_competition` in ('m','c','cf')),5,3)) and ((sum(`a`.`count_ext`) + sum(`a`.`count_renfort`)) >= if((`a`.`code_competition` in ('m','c','cf')),5,3))) or (`count_status` is not null)) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `matchs_view`
--

/*!50001 DROP VIEW IF EXISTS `matchs_view`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_0900_ai_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 SQL SECURITY INVOKER */
/*!50001 VIEW `matchs_view` AS with `computed_forfait` as (select `m`.`id_match` AS `id_match`,if(((`m`.`set_1_dom` = 25) and (`m`.`set_1_ext` = 0) and (`m`.`set_2_dom` = 25) and (`m`.`set_2_ext` = 0) and (`m`.`set_3_dom` = 25) and (`m`.`set_3_ext` = 0) and (0 <> `m`.`is_sign_match_dom`) and (0 <> `m`.`is_sign_match_ext`)),1,0) AS `forfait_ext`,if(((`m`.`set_1_dom` = 0) and (`m`.`set_1_ext` = 25) and (`m`.`set_2_dom` = 0) and (`m`.`set_2_ext` = 25) and (`m`.`set_3_dom` = 0) and (`m`.`set_3_ext` = 25) and (0 <> `m`.`is_sign_match_dom`) and (0 <> `m`.`is_sign_match_ext`)),1,0) AS `forfait_dom`,if((((`m`.`set_1_dom` = 25) and (`m`.`set_1_ext` = 0) and (`m`.`set_2_dom` = 25) and (`m`.`set_2_ext` = 0) and (`m`.`set_3_dom` = 25) and (`m`.`set_3_ext` = 0) and (0 <> `m`.`is_sign_match_dom`) and (0 <> `m`.`is_sign_match_ext`)) or ((`m`.`set_1_dom` = 0) and (`m`.`set_1_ext` = 25) and (`m`.`set_2_dom` = 0) and (`m`.`set_2_ext` = 25) and (`m`.`set_3_dom` = 0) and (`m`.`set_3_ext` = 25) and (0 <> `m`.`is_sign_match_dom`) and (0 <> `m`.`is_sign_match_ext`))),1,0) AS `is_forfait` from `matches` `m`), `computed_score` as (select `m`.`id_match` AS `id_match`,((((if(((`m`.`set_1_dom` >= 25) and (`m`.`set_1_dom` >= (`m`.`set_1_ext` + 2))),1,0) + if(((`m`.`set_2_dom` >= 25) and (`m`.`set_2_dom` >= (`m`.`set_2_ext` + 2))),1,0)) + if(((`m`.`set_3_dom` >= 25) and (`m`.`set_3_dom` >= (`m`.`set_3_ext` + 2))),1,0)) + if(((`m`.`set_4_dom` >= 25) and (`m`.`set_4_dom` >= (`m`.`set_4_ext` + 2))),1,0)) + if(((`m`.`set_5_dom` >= 15) and (`m`.`set_5_dom` >= (`m`.`set_5_ext` + 2))),1,0)) AS `score_equipe_dom`,((((if(((`m`.`set_1_ext` >= 25) and (`m`.`set_1_ext` >= (`m`.`set_1_dom` + 2))),1,0) + if(((`m`.`set_2_ext` >= 25) and (`m`.`set_2_ext` >= (`m`.`set_2_dom` + 2))),1,0)) + if(((`m`.`set_3_ext` >= 25) and (`m`.`set_3_ext` >= (`m`.`set_3_dom` + 2))),1,0)) + if(((`m`.`set_4_ext` >= 25) and (`m`.`set_4_ext` >= (`m`.`set_4_dom` + 2))),1,0)) + if(((`m`.`set_5_ext` >= 15) and (`m`.`set_5_ext` >= (`m`.`set_5_dom` + 2))),1,0)) AS `score_equipe_ext` from `matches` `m`) select `m`.`id_match` AS `id_match`,`cf`.`forfait_dom` AS `forfait_dom`,`cf`.`forfait_ext` AS `forfait_ext`,`cf`.`is_forfait` AS `is_forfait`,if(((`cs`.`score_equipe_dom` = 3) or (`cs`.`score_equipe_ext` = 3)),1,0) AS `is_match_score_filled`,if((`mpcv`.`id_match` is not null),1,0) AS `is_match_player_filled`,`mpcv`.`count_status` AS `count_status`,if(((`mpcv`.`id_match` is null) and (`cf`.`is_forfait` = 0) and (`m`.`certif` = 0)),1,0) AS `is_match_player_requested`,if((`m`.`id_match` in (select `match_player`.`id_match` from (`match_player` join `players_view` `j2` on((`match_player`.`id_player` = `j2`.`id`))) where ((`j2`.`est_actif` = 0) or (str_to_date(`j2`.`date_homologation`,'%d/%m/%Y') > `m`.`date_reception`) or (`j2`.`date_homologation` is null) or (`j2`.`num_licence` is null))) and (`cf`.`is_forfait` = 0)),1,0) AS `has_forbidden_player`,`m`.`code_match` AS `code_match`,`m`.`code_competition` AS `code_competition`,`c`.`id_compet_maitre` AS `parent_code_competition`,`c`.`libelle` AS `libelle_competition`,`m`.`division` AS `division`,`j`.`numero` AS `numero_journee`,`j`.`id` AS `id_journee`,concat(`j`.`nommage`,' : ','Semaine du ',convert(date_format(`j`.`start_date`,'%W %d %M') using utf8mb3),' au ',convert(date_format((`j`.`start_date` + interval 4 day),'%W %d %M %Y') using utf8mb3)) AS `journee`,`m`.`id_equipe_dom` AS `id_equipe_dom`,`e1`.`nom_equipe` AS `equipe_dom`,`m`.`id_equipe_ext` AS `id_equipe_ext`,`e2`.`nom_equipe` AS `equipe_ext`,`cs`.`score_equipe_dom` AS `score_equipe_dom`,`cs`.`score_equipe_ext` AS `score_equipe_ext`,`m`.`set_1_dom` AS `set_1_dom`,`m`.`set_1_ext` AS `set_1_ext`,`m`.`set_2_dom` AS `set_2_dom`,`m`.`set_2_ext` AS `set_2_ext`,`m`.`set_3_dom` AS `set_3_dom`,`m`.`set_3_ext` AS `set_3_ext`,`m`.`set_4_dom` AS `set_4_dom`,`m`.`set_4_ext` AS `set_4_ext`,`m`.`set_5_dom` AS `set_5_dom`,`m`.`set_5_ext` AS `set_5_ext`,`cr`.`heure` AS `heure_reception`,`m`.`id_gymnasium` AS `id_gymnasium`,`g`.`nom` AS `gymnasium`,date_format(`m`.`date_reception`,'%d/%m/%Y') AS `date_reception`,(unix_timestamp(((`m`.`date_reception` + interval 23 hour) + interval 59 minute)) * 1000) AS `date_reception_raw`,date_format(`m`.`date_original`,'%d/%m/%Y') AS `date_original`,(unix_timestamp(((`m`.`date_original` + interval 23 hour) + interval 59 minute)) * 1000) AS `date_original_raw`,if(((`m`.`is_sign_team_ext` = 1) and (`m`.`is_sign_team_dom` = 1) and (`m`.`is_sign_match_ext` = 1) and (`m`.`is_sign_match_dom` = 1)),1,0) AS `sheet_received`,`m`.`note` AS `note`,`m`.`certif` AS `certif`,`m`.`report_status` AS `report_status`,(case when ((`cs`.`score_equipe_dom` + `cs`.`score_equipe_ext`) > 0) then 0 when (`m`.`date_reception` >= curdate()) then 0 when (curdate() >= (`m`.`date_reception` + interval 10 day)) then 2 when (curdate() >= (`m`.`date_reception` + interval 5 day)) then 1 end) AS `retard`,`m`.`match_status` AS `match_status`,`m`.`is_sign_match_dom` AS `is_sign_match_dom`,`m`.`is_sign_match_ext` AS `is_sign_match_ext`,`m`.`is_sign_team_dom` AS `is_sign_team_dom`,`m`.`is_sign_team_ext` AS `is_sign_team_ext`,`jresp_dom`.`email` AS `email_dom`,`jresp_ext`.`email` AS `email_ext`,`m`.`referee` AS `referee`,if((`s_dom`.`id` is not null),1,0) AS `is_survey_filled_dom`,if((`s_ext`.`id` is not null),1,0) AS `is_survey_filled_ext`,group_concat(distinct `com`.`email` separator ',') AS `contact_com` from (((((((((((((((((`matches` `m` join `computed_forfait` `cf` on((`m`.`id_match` = `cf`.`id_match`))) join `computed_score` `cs` on((`m`.`id_match` = `cs`.`id_match`))) join `competitions` `c` on((`c`.`code_competition` = `m`.`code_competition`))) join `equipes` `e1` on((`e1`.`id_equipe` = `m`.`id_equipe_dom`))) left join `joueur_equipe` `jeresp_dom` on(((`jeresp_dom`.`id_equipe` = `e1`.`id_equipe`) and (`jeresp_dom`.`is_leader` = 1)))) left join `joueurs` `jresp_dom` on((`jeresp_dom`.`id_joueur` = `jresp_dom`.`id`))) join `equipes` `e2` on((`e2`.`id_equipe` = `m`.`id_equipe_ext`))) left join `joueur_equipe` `jeresp_ext` on(((`jeresp_ext`.`id_equipe` = `e2`.`id_equipe`) and (`jeresp_ext`.`is_leader` = 1)))) left join `joueurs` `jresp_ext` on((`jeresp_ext`.`id_joueur` = `jresp_ext`.`id`))) left join `journees` `j` on((`m`.`id_journee` = `j`.`id`))) left join `creneau` `cr` on(((`cr`.`id_equipe` = `m`.`id_equipe_dom`) and (`cr`.`jour` = elt((weekday(`m`.`date_reception`) + 2),'Dimanche','Lundi','Mardi','Mercredi','Jeudi','Vendredi','Samedi')) and (`cr`.`id_gymnase` = `m`.`id_gymnasium`)))) left join `gymnase` `g` on((`m`.`id_gymnasium` = `g`.`id`))) left join `match_players_count_view` `mpcv` on((`mpcv`.`id_match` = `m`.`id_match`))) left join `survey` `s_dom` on(((`m`.`id_match` = `s_dom`.`id_match`) and `s_dom`.`user_id` in (select `ca`.`id` from (`comptes_acces` `ca` join `users_teams` `ut` on((`ca`.`id` = `ut`.`user_id`))) where (`ut`.`team_id` = `m`.`id_equipe_dom`))))) left join `survey` `s_ext` on(((`m`.`id_match` = `s_ext`.`id_match`) and `s_ext`.`user_id` in (select `ca`.`id` from (`comptes_acces` `ca` join `users_teams` `ut` on((`ca`.`id` = `ut`.`user_id`))) where (`ut`.`team_id` = `m`.`id_equipe_ext`))))) left join `commission_division` `cd` on((`cd`.`division` = concat(`m`.`code_competition`,'/',`m`.`division`)))) left join `commission` `com` on((`cd`.`id_commission` = `com`.`id_commission`))) where (1 = 1) group by `m`.`id_match`,`m`.`code_competition`,`m`.`division`,`numero_journee`,`m`.`code_match` order by `m`.`code_competition`,`m`.`division`,`numero_journee`,`m`.`code_match` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `players_view`
--

/*!50001 DROP VIEW IF EXISTS `players_view`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_0900_ai_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 SQL SECURITY INVOKER */
/*!50001 VIEW `players_view` AS select concat(upper(`j`.`nom`),' ',`j`.`prenom`,' (',ifnull(`j`.`num_licence`,''),')') AS `full_name`,`j`.`prenom` AS `prenom`,upper(`j`.`nom`) AS `nom`,`j`.`telephone` AS `telephone`,`j`.`email` AS `email`,`j`.`num_licence` AS `num_licence`,concat(convert(lpad(`j`.`departement_affiliation`,3,'0') using utf8mb3),`j`.`num_licence`) AS `num_licence_ext`,`p`.`path_photo` AS `path_photo`,replace(`p`.`path_photo`,'players_pics','players_pics_low') AS `path_photo_low`,`j`.`sexe` AS `sexe`,`j`.`departement_affiliation` AS `departement_affiliation`,(case when (`j`.`date_homologation` is null) then 0 when (`j`.`date_homologation` > now()) then 0 when (`j`.`num_licence` is null) then 0 when (month(`comp`.`start_date`) > 7) then (case when ((year(`j`.`date_homologation`) = year(`comp`.`start_date`)) and (month(`j`.`date_homologation`) > 7)) then 1 when (year(`j`.`date_homologation`) = (year(`comp`.`start_date`) + 1)) then 1 else 0 end) when (month(`comp`.`start_date`) <= 7) then (case when ((year(`j`.`date_homologation`) = (year(`comp`.`start_date`) - 1)) and (month(`j`.`date_homologation`) > 7)) then 1 when (year(`j`.`date_homologation`) = year(`comp`.`start_date`)) then 1 else 0 end) when (`je`.`id_joueur` is null) then (case when (month(`min_comp`.`start_date`) > 7) then (case when ((year(`j`.`date_homologation`) = year(`min_comp`.`start_date`)) and (month(`j`.`date_homologation`) > 7)) then 1 when (year(`j`.`date_homologation`) = (year(`min_comp`.`start_date`) + 1)) then 1 else 0 end) when (month(`min_comp`.`start_date`) <= 7) then (case when ((year(`j`.`date_homologation`) = (year(`min_comp`.`start_date`) - 1)) and (month(`j`.`date_homologation`) > 7)) then 1 when (year(`j`.`date_homologation`) = year(`min_comp`.`start_date`)) then 1 else 0 end) end) else 0 end) AS `est_actif`,`j`.`id_club` AS `id_club`,`c`.`nom` AS `club`,`j`.`telephone2` AS `telephone2`,`j`.`email2` AS `email2`,(`j`.`est_responsable_club` + 0) AS `est_responsable_club`,if(`j`.`id` in (select `joueur_equipe`.`id_joueur` from `joueur_equipe` where (`joueur_equipe`.`is_captain` = 1)),1,0) AS `is_captain`,if(`j`.`id` in (select `joueur_equipe`.`id_joueur` from `joueur_equipe` where (`joueur_equipe`.`is_vice_leader` = 1)),1,0) AS `is_vice_leader`,if(`j`.`id` in (select `joueur_equipe`.`id_joueur` from `joueur_equipe` where (`joueur_equipe`.`is_leader` = 1)),1,0) AS `is_leader`,group_concat(distinct `je_cap`.`id_equipe` separator ',') AS `id_captain`,group_concat(distinct `je_vl`.`id_equipe` separator ',') AS `id_vl`,group_concat(distinct `je_l`.`id_equipe` separator ',') AS `id_l`,`j`.`id` AS `id`,group_concat(distinct (case when (`cl`.`id` is not null) then concat(convert(`e`.`nom_equipe` using utf8mb3),' (',`comp`.`libelle`,')') end) separator '<br/>') AS `active_teams_list`,group_concat(distinct (case when (`cl`.`id` is null) then concat(convert(`e`.`nom_equipe` using utf8mb3),' (',`comp`.`libelle`,')') end) separator '<br/>') AS `inactive_teams_list`,group_concat(distinct concat(convert(`e`.`nom_equipe` using utf8mb3),' (',`comp`.`libelle`,')') separator '<br/>') AS `teams_list`,group_concat(distinct `e_l`.`nom_equipe` separator '<br/>') AS `team_leader_list`,date_format(`j`.`date_homologation`,'%d/%m/%Y') AS `date_homologation` from (((((((((((`joueurs` `j` left join `joueur_equipe` `je_cap` on(((`je_cap`.`id_joueur` = `j`.`id`) and (`je_cap`.`is_captain` = 1)))) left join `joueur_equipe` `je_vl` on(((`je_vl`.`id_joueur` = `j`.`id`) and (`je_vl`.`is_vice_leader` = 1)))) left join `joueur_equipe` `je_l` on(((`je_l`.`id_joueur` = `j`.`id`) and (`je_l`.`is_leader` = 1)))) left join `joueur_equipe` `je` on((`je`.`id_joueur` = `j`.`id`))) left join `equipes` `e` on((`e`.`id_equipe` = `je`.`id_equipe`))) left join `equipes` `e_l` on((`e_l`.`id_equipe` = `je_l`.`id_equipe`))) left join `clubs` `c` on((`c`.`id` = `j`.`id_club`))) left join `photos` `p` on((`p`.`id` = `j`.`id_photo`))) left join `classements` `cl` on((`cl`.`id_equipe` = `e`.`id_equipe`))) left join `competitions` `comp` on((`comp`.`code_competition` = `e`.`code_competition`))) join `competitions` `min_comp` on((`min_comp`.`start_date` = (select min(`competitions`.`start_date`) from `competitions`)))) where (1 = 1) group by `j`.`id`,`j`.`sexe`,upper(`j`.`nom`) order by upper(`full_name`) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `ranks_view`
--

/*!50001 DROP VIEW IF EXISTS `ranks_view`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_0900_ai_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 SQL SECURITY INVOKER */
/*!50001 VIEW `ranks_view` AS select `z`.`code_competition` AS `code_competition`,`z`.`division` AS `division`,rank() OVER (PARTITION BY `z`.`code_competition`,`z`.`division` ORDER BY `z`.`points` desc,`z`.`diff` desc,`z`.`rank_start` )  AS `rang`,`z`.`id_equipe` AS `id_equipe`,`z`.`equipe` AS `equipe`,`z`.`points` AS `points`,`z`.`joues` AS `joues`,`z`.`gagnes` AS `gagnes`,`z`.`perdus` AS `perdus`,`z`.`sets_pour` AS `sets_pour`,`z`.`sets_contre` AS `sets_contre`,`z`.`diff` AS `diff`,`z`.`penalites` AS `penalites`,`z`.`matches_lost_by_forfeit_count` AS `matches_lost_by_forfeit_count`,`z`.`report_count` AS `report_count` from (select `c`.`code_competition` AS `code_competition`,`c`.`division` AS `division`,`e`.`id_equipe` AS `id_equipe`,`e`.`nom_equipe` AS `equipe`,((((sum(if(((`e`.`id_equipe` = `m`.`id_equipe_dom`) and (`m`.`score_equipe_dom` = 3)),3,0)) + sum(if(((`e`.`id_equipe` = `m`.`id_equipe_ext`) and (`m`.`score_equipe_ext` = 3)),3,0))) + sum(if(((`e`.`id_equipe` = `m`.`id_equipe_dom`) and (`m`.`score_equipe_ext` = 3) and (`m`.`forfait_dom` = 0)),1,0))) + sum(if(((`e`.`id_equipe` = `m`.`id_equipe_ext`) and (`m`.`score_equipe_dom` = 3) and (`m`.`forfait_ext` = 0)),1,0))) - `c`.`penalite`) AS `points`,(((sum(if(((`e`.`id_equipe` = `m`.`id_equipe_dom`) and (`m`.`score_equipe_dom` = 3)),1,0)) + sum(if(((`e`.`id_equipe` = `m`.`id_equipe_ext`) and (`m`.`score_equipe_ext` = 3)),1,0))) + sum(if(((`e`.`id_equipe` = `m`.`id_equipe_dom`) and (`m`.`score_equipe_ext` = 3)),1,0))) + sum(if(((`e`.`id_equipe` = `m`.`id_equipe_ext`) and (`m`.`score_equipe_dom` = 3)),1,0))) AS `joues`,(sum(if(((`e`.`id_equipe` = `m`.`id_equipe_dom`) and (`m`.`score_equipe_dom` = 3)),1,0)) + sum(if(((`e`.`id_equipe` = `m`.`id_equipe_ext`) and (`m`.`score_equipe_ext` = 3)),1,0))) AS `gagnes`,(sum(if(((`e`.`id_equipe` = `m`.`id_equipe_dom`) and (`m`.`score_equipe_ext` = 3)),1,0)) + sum(if(((`e`.`id_equipe` = `m`.`id_equipe_ext`) and (`m`.`score_equipe_dom` = 3)),1,0))) AS `perdus`,sum(if((`e`.`id_equipe` = `m`.`id_equipe_dom`),`m`.`score_equipe_dom`,`m`.`score_equipe_ext`)) AS `sets_pour`,sum(if((`e`.`id_equipe` = `m`.`id_equipe_dom`),`m`.`score_equipe_ext`,`m`.`score_equipe_dom`)) AS `sets_contre`,(sum(if((`e`.`id_equipe` = `m`.`id_equipe_dom`),`m`.`score_equipe_dom`,`m`.`score_equipe_ext`)) - sum(if((`e`.`id_equipe` = `m`.`id_equipe_dom`),`m`.`score_equipe_ext`,`m`.`score_equipe_dom`))) AS `diff`,`c`.`penalite` AS `penalites`,(sum(if(((`e`.`id_equipe` = `m`.`id_equipe_dom`) and (`m`.`forfait_dom` = 1)),1,0)) + sum(if(((`e`.`id_equipe` = `m`.`id_equipe_ext`) and (`m`.`forfait_ext` = 1)),1,0))) AS `matches_lost_by_forfeit_count`,`c`.`report_count` AS `report_count`,`c`.`rank_start` AS `rank_start` from ((`classements` `c` join `equipes` `e` on((`e`.`id_equipe` = `c`.`id_equipe`))) left join `matchs_view` `m` on(((`m`.`code_competition` = `c`.`code_competition`) and (`m`.`division` = `c`.`division`) and ((`m`.`id_equipe_dom` = `e`.`id_equipe`) or (`m`.`id_equipe_ext` = `e`.`id_equipe`)) and (`m`.`match_status` <> 'ARCHIVED')))) group by `c`.`code_competition`,`c`.`division`,`e`.`id_equipe`,`e`.`nom_equipe`,`c`.`penalite`,`c`.`report_count`,`c`.`rank_start`) `z` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `survey_view`
--

/*!50001 DROP VIEW IF EXISTS `survey_view`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_0900_ai_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 SQL SECURITY INVOKER */
/*!50001 VIEW `survey_view` AS select (sum((((((`s1`.`on_time` * `s1`.`coef_on_time`) + (`s1`.`spirit` * `s1`.`coef_spirit`)) + (`s1`.`referee` * `s1`.`coef_referee`)) + (`s1`.`catering` * `s1`.`coef_catering`)) + (`s1`.`global` * `s1`.`coef_global`))) / ((((`s1`.`coef_on_time` + `s1`.`coef_spirit`) + `s1`.`coef_referee`) + `s1`.`coef_catering`) + `s1`.`coef_global`)) AS `note`,count(distinct `s1`.`code_match`) AS `nb_matchs`,((sum((((((`s1`.`on_time` * `s1`.`coef_on_time`) + (`s1`.`spirit` * `s1`.`coef_spirit`)) + (`s1`.`referee` * `s1`.`coef_referee`)) + (`s1`.`catering` * `s1`.`coef_catering`)) + (`s1`.`global` * `s1`.`coef_global`))) / ((((`s1`.`coef_on_time` + `s1`.`coef_spirit`) + `s1`.`coef_referee`) + `s1`.`coef_catering`) + `s1`.`coef_global`)) / count(distinct `s1`.`code_match`)) AS `moyenne`,`s1`.`club` AS `club` from `survey_view_raw` `s1` group by `s1`.`club` order by `moyenne` desc */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `survey_view_raw`
--

/*!50001 DROP VIEW IF EXISTS `survey_view_raw`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_0900_ai_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 SQL SECURITY INVOKER */
/*!50001 VIEW `survey_view_raw` AS select `s`.`id` AS `id`,`e_sondeuse`.`id_equipe` AS `id_team_sondeuse`,`m`.`code_match` AS `code_match`,`m`.`id_match` AS `id_match`,`e_sondee`.`nom_equipe` AS `equipe`,`c_sondee`.`nom` AS `club`,`s`.`on_time` AS `on_time`,2 AS `coef_on_time`,`s`.`spirit` AS `spirit`,3 AS `coef_spirit`,(case when ((`m`.`referee` = 'HOME') and (`m`.`id_equipe_dom` = `e_sondeuse`.`id_equipe`)) then 0 when ((`m`.`referee` = 'AWAY') and (`m`.`id_equipe_ext` = `e_sondeuse`.`id_equipe`)) then 0 when (`m`.`referee` = 'BOTH') then 0 else `s`.`referee` end) AS `referee`,(case when ((`m`.`referee` = 'HOME') and (`m`.`id_equipe_dom` = `e_sondeuse`.`id_equipe`)) then 0 when ((`m`.`referee` = 'AWAY') and (`m`.`id_equipe_ext` = `e_sondeuse`.`id_equipe`)) then 0 when (`m`.`referee` = 'BOTH') then 0 else 3 end) AS `coef_referee`,(case when (`m`.`id_equipe_dom` = `e_sondeuse`.`id_equipe`) then 0 else `s`.`catering` end) AS `catering`,(case when (`m`.`id_equipe_dom` = `e_sondeuse`.`id_equipe`) then 0 else 2 end) AS `coef_catering`,`s`.`global` AS `global`,5 AS `coef_global`,`c`.`penalite` AS `nb_penalites` from (((((((`survey` `s` join `matches` `m` on((`s`.`id_match` = `m`.`id_match`))) join `comptes_acces` `ca` on((`ca`.`id` = `s`.`user_id`))) join `users_teams` `ut` on((`ca`.`id` = `ut`.`user_id`))) join `equipes` `e_sondeuse` on((`ut`.`team_id` = `e_sondeuse`.`id_equipe`))) join `equipes` `e_sondee` on(((`e_sondee`.`id_equipe` in (`m`.`id_equipe_dom`,`m`.`id_equipe_ext`)) and (`e_sondeuse`.`id_equipe` in (`m`.`id_equipe_dom`,`m`.`id_equipe_ext`)) and (`e_sondee`.`id_equipe` <> `e_sondeuse`.`id_equipe`)))) join `clubs` `c_sondee` on((`e_sondee`.`id_club` = `c_sondee`.`id`))) join `classements` `c` on(((`e_sondee`.`id_equipe` = `c`.`id_equipe`) and (`c`.`code_competition` = `m`.`code_competition`)))) where (((((`s`.`on_time` + `s`.`spirit`) + `s`.`referee`) + `s`.`catering`) + `s`.`global`) > 0) order by `s`.`id` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `teams_view`
--

/*!50001 DROP VIEW IF EXISTS `teams_view`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_0900_ai_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 SQL SECURITY INVOKER */
/*!50001 VIEW `teams_view` AS select `e`.`code_competition` AS `code_competition`,`comp`.`libelle` AS `libelle_competition`,`e`.`nom_equipe` AS `nom_equipe`,concat(convert(`e`.`nom_equipe` using utf8mb3),' (',convert(`c`.`nom` using utf8mb3),') - ',group_concat(distinct concat(`comp`.`libelle`,convert(ifnull(concat('(',`cl`.`division`,')'),'') using utf8mb3)) order by `comp`.`libelle` ASC separator ',')) AS `team_full_name`,`e`.`id_club` AS `id_club`,`c`.`nom` AS `club`,`e`.`id_equipe` AS `id_equipe`,concat(`jresp`.`prenom`,' ',`jresp`.`nom`) AS `responsable`,to_base64(concat(`jresp`.`prenom`,' ',`jresp`.`nom`)) AS `responsable_base64`,`jresp`.`telephone` AS `telephone_1`,to_base64(`jresp`.`telephone`) AS `telephone_1_base64`,`jsupp`.`telephone` AS `telephone_2`,to_base64(`jsupp`.`telephone`) AS `telephone_2_base64`,`jresp`.`email` AS `email`,to_base64(`jresp`.`email`) AS `email_base64`,group_concat(distinct concat(concat(`g`.`ville`,' - ',`g`.`nom`,' - ',`g`.`adresse`,' - ',`g`.`gps`),' (',convert(`cr`.`jour` using utf8mb3),' à ',convert(`cr`.`heure` using utf8mb3),')',convert(if((`cr`.`has_time_constraint` > 0),' (CONTRAINTE HORAIRE FORTE)','') using utf8mb3)) separator ', ') AS `gymnasiums_list`,`e`.`web_site` AS `web_site`,`e`.`id_photo` AS `id_photo`,`p`.`path_photo` AS `path_photo`,`e`.`is_cup_registered` AS `is_cup_registered`,if((`cl`.`id` is null),0,1) AS `is_active_team` from ((((((((((`equipes` `e` left join `classements` `cl` on((`cl`.`id_equipe` = `e`.`id_equipe`))) left join `photos` `p` on((`p`.`id` = `e`.`id_photo`))) join `clubs` `c` on((`c`.`id` = `e`.`id_club`))) join `competitions` `comp` on((`comp`.`code_competition` = ifnull(`cl`.`code_competition`,`e`.`code_competition`)))) left join `joueur_equipe` `jeresp` on(((`jeresp`.`id_equipe` = `e`.`id_equipe`) and ((`jeresp`.`is_leader` + 0) > 0)))) left join `joueur_equipe` `jesupp` on(((`jesupp`.`id_equipe` = `e`.`id_equipe`) and ((`jesupp`.`is_vice_leader` + 0) > 0)))) left join `joueurs` `jresp` on((`jresp`.`id` = `jeresp`.`id_joueur`))) left join `joueurs` `jsupp` on((`jsupp`.`id` = `jesupp`.`id_joueur`))) left join `creneau` `cr` on((`cr`.`id_equipe` = `e`.`id_equipe`))) left join `gymnase` `g` on((`g`.`id` = `cr`.`id_gymnase`))) where (1 = 1) group by `e`.`id_equipe`,`e`.`nom_equipe` order by `e`.`nom_equipe` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed
