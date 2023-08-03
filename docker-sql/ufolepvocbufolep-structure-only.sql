-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: ufolepvocbufolep.mysql.db
-- Generation Time: Aug 03, 2023 at 10:43 PM
-- Server version: 5.7.42-log
-- PHP Version: 8.1.16

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `ufolepvocbufolep`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity`
--

CREATE TABLE `activity` (
  `id` smallint(10) NOT NULL,
  `comment` varchar(400) DEFAULT NULL,
  `activity_date` datetime DEFAULT NULL,
  `user_id` smallint(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `blacklist_by_city`
--

CREATE TABLE `blacklist_by_city` (
  `id` smallint(10) NOT NULL,
  `city` varchar(200) NOT NULL,
  `from_date` datetime NOT NULL,
  `to_date` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `blacklist_date`
--

CREATE TABLE `blacklist_date` (
  `id` smallint(10) NOT NULL,
  `closed_date` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `blacklist_gymnase`
--

CREATE TABLE `blacklist_gymnase` (
  `id` smallint(10) NOT NULL,
  `id_gymnase` smallint(10) NOT NULL,
  `closed_date` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `blacklist_team`
--

CREATE TABLE `blacklist_team` (
  `id` smallint(10) NOT NULL,
  `id_team` smallint(10) NOT NULL,
  `closed_date` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `blacklist_teams`
--

CREATE TABLE `blacklist_teams` (
  `id` smallint(10) NOT NULL,
  `id_team_1` smallint(10) NOT NULL,
  `id_team_2` smallint(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `classements`
--

CREATE TABLE `classements` (
  `code_competition` varchar(2) NOT NULL,
  `division` varchar(2) NOT NULL,
  `id_equipe` smallint(3) NOT NULL,
  `penalite` tinyint(1) NOT NULL DEFAULT '0',
  `id` smallint(10) NOT NULL,
  `report_count` smallint(10) DEFAULT '0',
  `rank_start` smallint(10) DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `clubs`
--

CREATE TABLE `clubs` (
  `nom` varchar(200) DEFAULT NULL,
  `id` smallint(10) NOT NULL,
  `affiliation_number` varchar(200) DEFAULT NULL,
  `nom_responsable` varchar(200) DEFAULT NULL,
  `prenom_responsable` varchar(200) DEFAULT NULL,
  `tel1_responsable` varchar(200) DEFAULT NULL,
  `tel2_responsable` varchar(200) DEFAULT NULL,
  `email_responsable` varchar(200) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `commission`
--

CREATE TABLE `commission` (
  `id_commission` smallint(10) NOT NULL,
  `nom` varchar(20) NOT NULL,
  `prenom` varchar(20) NOT NULL,
  `fonction` varchar(80) NOT NULL,
  `telephone1` varchar(15) NOT NULL,
  `telephone2` varchar(15) NOT NULL,
  `email` varchar(50) NOT NULL,
  `photo` varchar(50) NOT NULL,
  `type` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `competitions`
--

CREATE TABLE `competitions` (
  `id` smallint(10) NOT NULL,
  `code_competition` varchar(2) NOT NULL,
  `libelle` varchar(50) NOT NULL,
  `id_compet_maitre` varchar(2) NOT NULL,
  `start_date` date DEFAULT NULL,
  `is_home_and_away` bit(1) DEFAULT b'0',
  `limit_register_date` date DEFAULT NULL,
  `start_register_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `comptes_acces`
--

CREATE TABLE `comptes_acces` (
  `id_equipe` smallint(10) DEFAULT NULL,
  `login` varchar(200) DEFAULT NULL,
  `id` smallint(10) NOT NULL,
  `email` varchar(200) DEFAULT NULL,
  `password_hash` varchar(200) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `creneau`
--

CREATE TABLE `creneau` (
  `id_gymnase` smallint(10) DEFAULT NULL,
  `jour` varchar(20) DEFAULT NULL,
  `heure` varchar(5) DEFAULT NULL,
  `id_equipe` smallint(10) DEFAULT NULL,
  `id` smallint(10) NOT NULL,
  `has_time_constraint` bit(1) DEFAULT NULL,
  `usage_priority` smallint(10) NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `dates_limite`
--

CREATE TABLE `dates_limite` (
  `id_date` smallint(10) NOT NULL,
  `code_competition` varchar(2) NOT NULL,
  `date_limite` varchar(40) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `emails`
--

CREATE TABLE `emails` (
  `id` int(11) NOT NULL,
  `from_email` text NOT NULL,
  `to_email` text NOT NULL,
  `cc` text NOT NULL,
  `bcc` text NOT NULL,
  `subject` text NOT NULL,
  `body` text NOT NULL,
  `creation_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `sent_date` datetime DEFAULT NULL,
  `sending_status` enum('TO_DO','DONE','ERROR') NOT NULL DEFAULT 'TO_DO'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `emails_files`
--

CREATE TABLE `emails_files` (
  `id_email` int(11) NOT NULL,
  `id_file` smallint(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `equipes`
--

CREATE TABLE `equipes` (
  `id_equipe` smallint(10) NOT NULL,
  `code_competition` varchar(2) NOT NULL,
  `nom_equipe` varchar(50) NOT NULL,
  `id_club` smallint(10) DEFAULT NULL,
  `web_site` varchar(100) DEFAULT NULL,
  `id_photo` smallint(10) DEFAULT NULL,
  `is_cup_registered` bit(1) DEFAULT b'0'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `files`
--

CREATE TABLE `files` (
  `id` smallint(10) NOT NULL,
  `path_file` varchar(500) NOT NULL,
  `hash` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `friendships`
--

CREATE TABLE `friendships` (
  `id` smallint(10) NOT NULL,
  `id_club_1` smallint(10) NOT NULL,
  `id_club_2` smallint(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `gymnase`
--

CREATE TABLE `gymnase` (
  `nom` varchar(200) DEFAULT NULL,
  `adresse` varchar(200) DEFAULT NULL,
  `code_postal` int(11) DEFAULT NULL,
  `ville` varchar(100) DEFAULT NULL,
  `gps` varchar(20) DEFAULT NULL,
  `id` smallint(10) NOT NULL,
  `nb_terrain` smallint(10) NOT NULL DEFAULT '3'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `hall_of_fame`
--

CREATE TABLE `hall_of_fame` (
  `id` smallint(10) NOT NULL,
  `title` varchar(500) NOT NULL,
  `team_name` varchar(500) NOT NULL,
  `period` varchar(500) NOT NULL,
  `league` varchar(100) NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `joueurs`
--

CREATE TABLE `joueurs` (
  `prenom` varchar(50) DEFAULT NULL,
  `nom` varchar(50) DEFAULT NULL,
  `telephone` varchar(20) DEFAULT NULL,
  `email` varchar(50) DEFAULT NULL,
  `num_licence` varchar(50) DEFAULT NULL,
  `sexe` varchar(1) DEFAULT NULL,
  `departement_affiliation` int(11) DEFAULT '13',
  `est_actif` bit(1) DEFAULT b'0',
  `id_club` smallint(10) DEFAULT NULL,
  `telephone2` varchar(20) DEFAULT NULL,
  `email2` varchar(50) DEFAULT NULL,
  `est_responsable_club` bit(1) DEFAULT NULL,
  `id` smallint(10) NOT NULL,
  `date_homologation` date DEFAULT NULL,
  `show_photo` bit(1) DEFAULT NULL,
  `id_photo` smallint(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `joueur_equipe`
--

CREATE TABLE `joueur_equipe` (
  `id_joueur` smallint(10) NOT NULL DEFAULT '0',
  `id_equipe` smallint(10) NOT NULL DEFAULT '0',
  `is_leader` bit(1) DEFAULT NULL,
  `is_vice_leader` bit(1) DEFAULT NULL,
  `is_captain` bit(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `journees`
--

CREATE TABLE `journees` (
  `id` smallint(10) NOT NULL,
  `code_competition` varchar(2) NOT NULL,
  `numero` tinyint(2) NOT NULL,
  `nommage` varchar(30) NOT NULL,
  `libelle` varchar(50) DEFAULT NULL,
  `start_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `matches`
--

CREATE TABLE `matches` (
  `id_match` bigint(20) NOT NULL,
  `code_match` varchar(10) DEFAULT NULL,
  `code_competition` varchar(2) NOT NULL,
  `division` varchar(2) NOT NULL,
  `id_equipe_dom` smallint(3) NOT NULL,
  `id_equipe_ext` smallint(3) NOT NULL,
  `score_equipe_dom` tinyint(1) NOT NULL DEFAULT '0',
  `score_equipe_ext` tinyint(1) NOT NULL DEFAULT '0',
  `set_1_dom` tinyint(2) NOT NULL DEFAULT '0',
  `set_1_ext` tinyint(2) NOT NULL DEFAULT '0',
  `set_2_dom` tinyint(2) NOT NULL DEFAULT '0',
  `set_2_ext` tinyint(2) NOT NULL DEFAULT '0',
  `set_3_dom` tinyint(2) NOT NULL DEFAULT '0',
  `set_3_ext` tinyint(2) NOT NULL DEFAULT '0',
  `set_4_dom` tinyint(2) NOT NULL DEFAULT '0',
  `set_4_ext` tinyint(2) NOT NULL DEFAULT '0',
  `set_5_dom` tinyint(2) NOT NULL DEFAULT '0',
  `set_5_ext` tinyint(2) NOT NULL DEFAULT '0',
  `date_reception` date DEFAULT NULL,
  `forfait_dom` tinyint(1) NOT NULL DEFAULT '0',
  `forfait_ext` tinyint(1) NOT NULL DEFAULT '0',
  `certif` tinyint(1) NOT NULL DEFAULT '0',
  `id_journee` smallint(10) DEFAULT NULL,
  `sheet_received` tinyint(1) NOT NULL DEFAULT '0',
  `note` text,
  `report_status` varchar(100) DEFAULT 'NOT_ASKED',
  `date_original` date DEFAULT NULL,
  `match_status` enum('NOT_CONFIRMED','CONFIRMED','ARCHIVED') NOT NULL DEFAULT 'NOT_CONFIRMED',
  `id_gymnasium` smallint(10) DEFAULT NULL,
  `is_sign_team_dom` bit(1) DEFAULT b'0',
  `is_sign_team_ext` bit(1) DEFAULT b'0',
  `is_sign_match_dom` bit(1) DEFAULT b'0',
  `is_sign_match_ext` bit(1) DEFAULT b'0'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Triggers `matches`
--
DELIMITER $$
CREATE TRIGGER `trg_bkp_orig_date` BEFORE UPDATE ON `matches` FOR EACH ROW BEGIN
    IF NEW.date_reception <> OLD.date_reception
    THEN
      SET NEW.date_original = OLD.date_reception;
    END IF;
  END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `matches_files`
--

CREATE TABLE `matches_files` (
  `id_match` bigint(20) NOT NULL DEFAULT '0',
  `id_file` smallint(10) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Stand-in structure for view `matchs_view`
-- (See below for the actual view)
--
CREATE TABLE `matchs_view` (
`id_match` bigint(20)
,`is_forfait` int(1)
,`is_match_player_filled` int(1)
,`is_match_player_requested` int(1)
,`has_forbidden_player` int(1)
,`code_match` varchar(10)
,`code_competition` varchar(2)
,`parent_code_competition` varchar(2)
,`libelle_competition` varchar(50)
,`division` varchar(2)
,`numero_journee` tinyint(2)
,`id_journee` smallint(10)
,`journee` varchar(317)
,`id_equipe_dom` smallint(3)
,`equipe_dom` varchar(50)
,`id_equipe_ext` smallint(3)
,`equipe_ext` varchar(50)
,`score_equipe_dom` int(5)
,`score_equipe_ext` int(5)
,`set_1_dom` tinyint(2)
,`set_1_ext` tinyint(2)
,`set_2_dom` tinyint(2)
,`set_2_ext` tinyint(2)
,`set_3_dom` tinyint(2)
,`set_3_ext` tinyint(2)
,`set_4_dom` tinyint(2)
,`set_4_ext` tinyint(2)
,`set_5_dom` tinyint(2)
,`set_5_ext` tinyint(2)
,`heure_reception` varchar(5)
,`id_gymnasium` smallint(10)
,`gymnasium` varchar(200)
,`date_reception` varchar(10)
,`date_reception_raw` bigint(15)
,`date_original` varchar(10)
,`date_original_raw` bigint(15)
,`forfait_dom` tinyint(1)
,`forfait_ext` tinyint(1)
,`sheet_received` int(4)
,`note` text
,`certif` tinyint(1)
,`report_status` varchar(100)
,`retard` int(1)
,`is_file_attached` int(1)
,`match_status` enum('NOT_CONFIRMED','CONFIRMED','ARCHIVED')
,`files_paths` text
,`is_sign_match_dom` bit(1)
,`is_sign_match_ext` bit(1)
,`is_sign_team_dom` bit(1)
,`is_sign_team_ext` bit(1)
,`email_dom` varchar(50)
,`email_ext` varchar(50)
);

-- --------------------------------------------------------

--
-- Table structure for table `match_player`
--

CREATE TABLE `match_player` (
  `id` bigint(20) NOT NULL,
  `id_match` bigint(20) NOT NULL,
  `id_player` smallint(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `news`
--

CREATE TABLE `news` (
  `id` bigint(10) NOT NULL,
  `title` varchar(500) NOT NULL,
  `file_path` varchar(200) NOT NULL,
  `news_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `text` text,
  `is_disabled` bit(1) DEFAULT b'0'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `photos`
--

CREATE TABLE `photos` (
  `id` smallint(10) NOT NULL,
  `path_photo` varchar(500) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Stand-in structure for view `players_view`
-- (See below for the actual view)
--
CREATE TABLE `players_view` (
`full_name` varchar(154)
,`prenom` varchar(50)
,`nom` varchar(50)
,`telephone` varchar(20)
,`email` varchar(50)
,`num_licence` varchar(50)
,`num_licence_ext` varchar(53)
,`path_photo` varchar(500)
,`sexe` varchar(1)
,`departement_affiliation` int(11)
,`est_actif` int(1)
,`id_club` smallint(10)
,`club` varchar(200)
,`telephone2` varchar(20)
,`email2` varchar(50)
,`est_responsable_club` int(2) unsigned
,`is_captain` int(1)
,`is_vice_leader` int(1)
,`is_leader` int(1)
,`id_captain` text
,`id_vl` text
,`id_l` text
,`show_photo` int(2) unsigned
,`id` smallint(10)
,`teams_list` text
,`team_leader_list` text
,`date_homologation` varchar(10)
);

-- --------------------------------------------------------

--
-- Table structure for table `profiles`
--

CREATE TABLE `profiles` (
  `id` smallint(10) NOT NULL,
  `name` varchar(200) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `register`
--

CREATE TABLE `register` (
  `id` bigint(20) NOT NULL,
  `new_team_name` varchar(100) NOT NULL,
  `id_club` smallint(10) NOT NULL,
  `id_competition` smallint(10) NOT NULL,
  `old_team_id` smallint(10) DEFAULT NULL,
  `leader_name` varchar(100) NOT NULL,
  `leader_first_name` varchar(100) NOT NULL,
  `leader_email` varchar(100) NOT NULL,
  `leader_phone` varchar(100) NOT NULL,
  `id_court_1` smallint(10) DEFAULT NULL,
  `day_court_1` varchar(100) DEFAULT NULL,
  `hour_court_1` varchar(100) DEFAULT NULL,
  `id_court_2` smallint(10) DEFAULT NULL,
  `day_court_2` varchar(100) DEFAULT NULL,
  `hour_court_2` varchar(100) DEFAULT NULL,
  `remarks` varchar(5000) DEFAULT NULL,
  `creation_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `division` varchar(2) DEFAULT NULL,
  `rank_start` smallint(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `registry`
--

CREATE TABLE `registry` (
  `id` smallint(10) NOT NULL,
  `registry_key` varchar(500) NOT NULL,
  `registry_value` varchar(200) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `users_profiles`
--

CREATE TABLE `users_profiles` (
  `id` smallint(10) NOT NULL,
  `user_id` smallint(10) NOT NULL,
  `profile_id` smallint(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Structure for view `matchs_view`
--
DROP TABLE IF EXISTS `matchs_view`;

CREATE OR REPLACE VIEW `matchs_view`  AS SELECT `m`.`id_match` AS `id_match`, if(((`m`.`forfait_dom` + `m`.`forfait_ext`) > 0),1,0) AS `is_forfait`, if(`m`.`id_match` in (select `b`.`id_match` from (select `a`.`id_match` AS `id_match`,sum(`a`.`count_dom`) AS `SUM(a.count_dom)`,sum(`a`.`count_ext`) AS `SUM(a.count_ext)` from ((select `m`.`id_match` AS `id_match`,count(distinct `mp`.`id_player`) AS `count_dom`,0 AS `count_ext` from ((`match_player` `mp` join `matches` `m` on((`mp`.`id_match` = `m`.`id_match`))) join `joueur_equipe` `jed` on(((`jed`.`id_equipe` = `m`.`id_equipe_dom`) and (`jed`.`id_joueur` = `mp`.`id_player`)))) where (`m`.`match_status` = 'CONFIRMED') group by `m`.`id_match`) union all (select `m`.`id_match` AS `id_match`,0 AS `count_dom`,count(distinct `mp`.`id_player`) AS `count_ext` from ((`match_player` `mp` join `matches` `m` on((`mp`.`id_match` = `m`.`id_match`))) join `joueur_equipe` `jee` on(((`jee`.`id_equipe` = `m`.`id_equipe_ext`) and (`jee`.`id_joueur` = `mp`.`id_player`)))) where (`m`.`match_status` = 'CONFIRMED') group by `m`.`id_match`)) `a` group by `a`.`id_match` having ((sum(`a`.`count_dom`) > 0) and (sum(`a`.`count_ext`) > 0))) `b`),1,0) AS `is_match_player_filled`, if(((not(`m`.`id_match` in (select `b`.`id_match` from (select `a`.`id_match` AS `id_match`,sum(`a`.`count_dom`) AS `SUM(a.count_dom)`,sum(`a`.`count_ext`) AS `SUM(a.count_ext)` from ((select `m`.`id_match` AS `id_match`,count(distinct `mp`.`id_player`) AS `count_dom`,0 AS `count_ext` from ((`match_player` `mp` join `matches` `m` on((`mp`.`id_match` = `m`.`id_match`))) join `joueur_equipe` `jed` on(((`jed`.`id_equipe` = `m`.`id_equipe_dom`) and (`jed`.`id_joueur` = `mp`.`id_player`)))) where (`m`.`match_status` = 'CONFIRMED') group by `m`.`id_match`) union all (select `m`.`id_match` AS `id_match`,0 AS `count_dom`,count(distinct `mp`.`id_player`) AS `count_ext` from ((`match_player` `mp` join `matches` `m` on((`mp`.`id_match` = `m`.`id_match`))) join `joueur_equipe` `jee` on(((`jee`.`id_equipe` = `m`.`id_equipe_ext`) and (`jee`.`id_joueur` = `mp`.`id_player`)))) where (`m`.`match_status` = 'CONFIRMED') group by `m`.`id_match`)) `a` group by `a`.`id_match` having ((sum(`a`.`count_dom`) > 0) and (sum(`a`.`count_ext`) > 0))) `b`))) and ((`m`.`forfait_dom` + `m`.`forfait_ext`) = 0) and (`m`.`sheet_received` > 0) and (`m`.`certif` = 0)),1,0) AS `is_match_player_requested`, if(`m`.`id_match` in (select `match_player`.`id_match` from (`match_player` join `players_view` `j2` on((`match_player`.`id_player` = `j2`.`id`))) where ((`j2`.`est_actif` = 0) or (`j2`.`date_homologation` > `m`.`date_reception`) or isnull(`j2`.`date_homologation`) or isnull(`j2`.`num_licence`))),1,0) AS `has_forbidden_player`, `m`.`code_match` AS `code_match`, `m`.`code_competition` AS `code_competition`, `c`.`id_compet_maitre` AS `parent_code_competition`, `c`.`libelle` AS `libelle_competition`, `m`.`division` AS `division`, `j`.`numero` AS `numero_journee`, `j`.`id` AS `id_journee`, concat(`j`.`nommage`,' : ','Semaine du ',convert(date_format(`j`.`start_date`,'%W %d %M') using utf8),' au ',convert(date_format((`j`.`start_date` + interval 4 day),'%W %d %M %Y') using utf8)) AS `journee`, `m`.`id_equipe_dom` AS `id_equipe_dom`, `e1`.`nom_equipe` AS `equipe_dom`, `m`.`id_equipe_ext` AS `id_equipe_ext`, `e2`.`nom_equipe` AS `equipe_ext`, (`m`.`score_equipe_dom` + 0) AS `score_equipe_dom`, (`m`.`score_equipe_ext` + 0) AS `score_equipe_ext`, `m`.`set_1_dom` AS `set_1_dom`, `m`.`set_1_ext` AS `set_1_ext`, `m`.`set_2_dom` AS `set_2_dom`, `m`.`set_2_ext` AS `set_2_ext`, `m`.`set_3_dom` AS `set_3_dom`, `m`.`set_3_ext` AS `set_3_ext`, `m`.`set_4_dom` AS `set_4_dom`, `m`.`set_4_ext` AS `set_4_ext`, `m`.`set_5_dom` AS `set_5_dom`, `m`.`set_5_ext` AS `set_5_ext`, `cr`.`heure` AS `heure_reception`, `m`.`id_gymnasium` AS `id_gymnasium`, `g`.`nom` AS `gymnasium`, date_format(`m`.`date_reception`,'%d/%m/%Y') AS `date_reception`, (unix_timestamp(((`m`.`date_reception` + interval 23 hour) + interval 59 minute)) * 1000) AS `date_reception_raw`, date_format(`m`.`date_original`,'%d/%m/%Y') AS `date_original`, (unix_timestamp(((`m`.`date_original` + interval 23 hour) + interval 59 minute)) * 1000) AS `date_original_raw`, `m`.`forfait_dom` AS `forfait_dom`, `m`.`forfait_ext` AS `forfait_ext`, (case when ((`m`.`is_sign_team_ext` = 1) and (`m`.`is_sign_team_dom` = 1) and (`m`.`is_sign_match_ext` = 1) and (`m`.`is_sign_match_dom` = 1)) then 1 else `m`.`sheet_received` end) AS `sheet_received`, `m`.`note` AS `note`, `m`.`certif` AS `certif`, `m`.`report_status` AS `report_status`, (case when ((`m`.`score_equipe_dom` + `m`.`score_equipe_ext`) > 0) then 0 when (`m`.`date_reception` >= curdate()) then 0 when (curdate() >= (`m`.`date_reception` + interval 10 day)) then 2 when (curdate() >= (`m`.`date_reception` + interval 5 day)) then 1 end) AS `retard`, if((`mf`.`id_file` is not null),1,0) AS `is_file_attached`, `m`.`match_status` AS `match_status`, group_concat(`f`.`path_file` separator '|') AS `files_paths`, `m`.`is_sign_match_dom` AS `is_sign_match_dom`, `m`.`is_sign_match_ext` AS `is_sign_match_ext`, `m`.`is_sign_team_dom` AS `is_sign_team_dom`, `m`.`is_sign_team_ext` AS `is_sign_team_ext`, `jresp_dom`.`email` AS `email_dom`, `jresp_ext`.`email` AS `email_ext` FROM ((((((((((((`matches` `m` join `competitions` `c` on((`c`.`code_competition` = convert(`m`.`code_competition` using utf8)))) join `equipes` `e1` on((`e1`.`id_equipe` = `m`.`id_equipe_dom`))) left join `joueur_equipe` `jeresp_dom` on(((`jeresp_dom`.`id_equipe` = `e1`.`id_equipe`) and (`jeresp_dom`.`is_leader` = 1)))) left join `joueurs` `jresp_dom` on((`jeresp_dom`.`id_joueur` = `jresp_dom`.`id`))) join `equipes` `e2` on((`e2`.`id_equipe` = `m`.`id_equipe_ext`))) left join `joueur_equipe` `jeresp_ext` on(((`jeresp_ext`.`id_equipe` = `e2`.`id_equipe`) and (`jeresp_ext`.`is_leader` = 1)))) left join `joueurs` `jresp_ext` on((`jeresp_ext`.`id_joueur` = `jresp_ext`.`id`))) left join `journees` `j` on((`m`.`id_journee` = `j`.`id`))) left join `creneau` `cr` on(((`cr`.`id_equipe` = `m`.`id_equipe_dom`) and (`cr`.`jour` = convert(elt((weekday(`m`.`date_reception`) + 2),'Dimanche','Lundi','Mardi','Mercredi','Jeudi','Vendredi','Samedi') using latin1)) and (`cr`.`id_gymnase` = `m`.`id_gymnasium`)))) left join `gymnase` `g` on((`m`.`id_gymnasium` = `g`.`id`))) left join `matches_files` `mf` on((`mf`.`id_match` = `m`.`id_match`))) left join `files` `f` on((`mf`.`id_file` = `f`.`id`))) WHERE (1 = 1) GROUP BY `m`.`code_competition`, `m`.`division`, `numero_journee`, `m`.`code_match` ORDER BY `m`.`code_competition` ASC, `m`.`division` ASC, `numero_journee` ASC, `m`.`code_match` ASC ;

-- --------------------------------------------------------

--
-- Structure for view `players_view`
--
DROP TABLE IF EXISTS `players_view`;

CREATE OR REPLACE VIEW `players_view`  AS SELECT concat(upper(`j`.`nom`),' ',`j`.`prenom`,' (',ifnull(`j`.`num_licence`,''),')') AS `full_name`, `j`.`prenom` AS `prenom`, upper(`j`.`nom`) AS `nom`, `j`.`telephone` AS `telephone`, `j`.`email` AS `email`, `j`.`num_licence` AS `num_licence`, concat(convert(lpad(`j`.`departement_affiliation`,3,'0') using utf8),`j`.`num_licence`) AS `num_licence_ext`, `p`.`path_photo` AS `path_photo`, `j`.`sexe` AS `sexe`, `j`.`departement_affiliation` AS `departement_affiliation`, (case when isnull(`j`.`date_homologation`) then 0 when (`j`.`date_homologation` > now()) then 0 when isnull(`j`.`num_licence`) then 0 when (month(`comp`.`start_date`) > 7) then (case when ((year(`j`.`date_homologation`) = year(`comp`.`start_date`)) and (month(`j`.`date_homologation`) > 7)) then 1 when (year(`j`.`date_homologation`) = (year(`comp`.`start_date`) + 1)) then 1 else 0 end) when (month(`comp`.`start_date`) <= 7) then (case when ((year(`j`.`date_homologation`) = (year(`comp`.`start_date`) - 1)) and (month(`j`.`date_homologation`) > 7)) then 1 when (year(`j`.`date_homologation`) = year(`comp`.`start_date`)) then 1 else 0 end) when isnull(`je`.`id_joueur`) then (case when (month(`min_comp`.`start_date`) > 7) then (case when ((year(`j`.`date_homologation`) = year(`min_comp`.`start_date`)) and (month(`j`.`date_homologation`) > 7)) then 1 when (year(`j`.`date_homologation`) = (year(`min_comp`.`start_date`) + 1)) then 1 else 0 end) when (month(`min_comp`.`start_date`) <= 7) then (case when ((year(`j`.`date_homologation`) = (year(`min_comp`.`start_date`) - 1)) and (month(`j`.`date_homologation`) > 7)) then 1 when (year(`j`.`date_homologation`) = year(`min_comp`.`start_date`)) then 1 else 0 end) end) else 0 end) AS `est_actif`, `j`.`id_club` AS `id_club`, `c`.`nom` AS `club`, `j`.`telephone2` AS `telephone2`, `j`.`email2` AS `email2`, (`j`.`est_responsable_club` + 0) AS `est_responsable_club`, if(`j`.`id` in (select `joueur_equipe`.`id_joueur` from `joueur_equipe` where (`joueur_equipe`.`is_captain` = 1)),1,0) AS `is_captain`, if(`j`.`id` in (select `joueur_equipe`.`id_joueur` from `joueur_equipe` where (`joueur_equipe`.`is_vice_leader` = 1)),1,0) AS `is_vice_leader`, if(`j`.`id` in (select `joueur_equipe`.`id_joueur` from `joueur_equipe` where (`joueur_equipe`.`is_leader` = 1)),1,0) AS `is_leader`, group_concat(distinct `je_cap`.`id_equipe` separator ',') AS `id_captain`, group_concat(distinct `je_vl`.`id_equipe` separator ',') AS `id_vl`, group_concat(distinct `je_l`.`id_equipe` separator ',') AS `id_l`, (`j`.`show_photo` + 0) AS `show_photo`, `j`.`id` AS `id`, group_concat(distinct concat(convert(`e`.`nom_equipe` using utf8),' (',`comp`.`libelle`,')',' (D',convert(`cl`.`division` using utf8),')') separator '<br/>') AS `teams_list`, group_concat(distinct `e2`.`nom_equipe` separator '<br/>') AS `team_leader_list`, date_format(`j`.`date_homologation`,'%d/%m/%Y') AS `date_homologation` FROM ((((((((((((`joueurs` `j` left join `joueur_equipe` `je_cap` on(((`je_cap`.`id_joueur` = `j`.`id`) and (`je_cap`.`is_captain` = 1)))) left join `joueur_equipe` `je_vl` on(((`je_vl`.`id_joueur` = `j`.`id`) and (`je_vl`.`is_vice_leader` = 1)))) left join `joueur_equipe` `je_l` on(((`je_l`.`id_joueur` = `j`.`id`) and (`je_l`.`is_leader` = 1)))) left join `joueur_equipe` `je` on((`je`.`id_joueur` = `j`.`id`))) left join `joueur_equipe` `je2` on(((`je2`.`id_joueur` = `j`.`id`) and ((`je2`.`is_leader` + 0) > 0)))) left join `equipes` `e` on((`e`.`id_equipe` = `je`.`id_equipe`))) left join `equipes` `e2` on((`e2`.`id_equipe` = `je2`.`id_equipe`))) left join `clubs` `c` on((`c`.`id` = `j`.`id_club`))) left join `photos` `p` on((`p`.`id` = `j`.`id_photo`))) left join `classements` `cl` on((`cl`.`id_equipe` = `e`.`id_equipe`))) left join `competitions` `comp` on((`comp`.`code_competition` = convert(`e`.`code_competition` using utf8)))) join `competitions` `min_comp` on((`min_comp`.`start_date` = (select min(`competitions`.`start_date`) from `competitions`)))) WHERE (1 = 1) GROUP BY `j`.`id`, `j`.`sexe`, upper(`j`.`nom`) ORDER BY `j`.`sexe` ASC, upper(`j`.`nom`) ASC ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity`
--
ALTER TABLE `activity`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id` (`id`);

--
-- Indexes for table `blacklist_by_city`
--
ALTER TABLE `blacklist_by_city`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `blacklist_date`
--
ALTER TABLE `blacklist_date`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `blacklist_gymnase`
--
ALTER TABLE `blacklist_gymnase`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_gymnase` (`id_gymnase`);

--
-- Indexes for table `blacklist_team`
--
ALTER TABLE `blacklist_team`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_team` (`id_team`);

--
-- Indexes for table `blacklist_teams`
--
ALTER TABLE `blacklist_teams`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_team_1` (`id_team_1`),
  ADD KEY `id_team_2` (`id_team_2`);

--
-- Indexes for table `classements`
--
ALTER TABLE `classements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_classements_equipes` (`id_equipe`),
  ADD KEY `id` (`id`);

--
-- Indexes for table `clubs`
--
ALTER TABLE `clubs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id` (`id`);

--
-- Indexes for table `commission`
--
ALTER TABLE `commission`
  ADD PRIMARY KEY (`id_commission`),
  ADD KEY `id_commission` (`id_commission`);

--
-- Indexes for table `competitions`
--
ALTER TABLE `competitions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id` (`id`);

--
-- Indexes for table `comptes_acces`
--
ALTER TABLE `comptes_acces`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id` (`id`);

--
-- Indexes for table `creneau`
--
ALTER TABLE `creneau`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_creneau_gymnase` (`id_gymnase`),
  ADD KEY `id` (`id`),
  ADD KEY `fk_creneau_equipes` (`id_equipe`);

--
-- Indexes for table `dates_limite`
--
ALTER TABLE `dates_limite`
  ADD PRIMARY KEY (`id_date`),
  ADD KEY `id_date` (`id_date`);

--
-- Indexes for table `emails`
--
ALTER TABLE `emails`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `emails_files`
--
ALTER TABLE `emails_files`
  ADD PRIMARY KEY (`id_email`,`id_file`),
  ADD KEY `fk_emails_files_file` (`id_file`),
  ADD KEY `fk_emails_files_email` (`id_email`);

--
-- Indexes for table `equipes`
--
ALTER TABLE `equipes`
  ADD PRIMARY KEY (`id_equipe`),
  ADD KEY `fk_equipes_clubs` (`id_club`),
  ADD KEY `id_equipe` (`id_equipe`);

--
-- Indexes for table `files`
--
ALTER TABLE `files`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id` (`id`);

--
-- Indexes for table `friendships`
--
ALTER TABLE `friendships`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_club_1` (`id_club_1`),
  ADD KEY `id_club_2` (`id_club_2`);

--
-- Indexes for table `gymnase`
--
ALTER TABLE `gymnase`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id` (`id`);

--
-- Indexes for table `hall_of_fame`
--
ALTER TABLE `hall_of_fame`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id` (`id`);

--
-- Indexes for table `joueurs`
--
ALTER TABLE `joueurs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id` (`id`);

--
-- Indexes for table `joueur_equipe`
--
ALTER TABLE `joueur_equipe`
  ADD PRIMARY KEY (`id_joueur`,`id_equipe`),
  ADD KEY `fk_joueur_equipe_equipe` (`id_equipe`),
  ADD KEY `fk_joueur_equipe_joueur` (`id_joueur`);

--
-- Indexes for table `journees`
--
ALTER TABLE `journees`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id` (`id`);

--
-- Indexes for table `matches`
--
ALTER TABLE `matches`
  ADD PRIMARY KEY (`id_match`),
  ADD UNIQUE KEY `code_match` (`code_match`),
  ADD UNIQUE KEY `code_match_2` (`code_match`),
  ADD KEY `fk_matches_journees` (`id_journee`),
  ADD KEY `fk_matches_equipesdom` (`id_equipe_dom`),
  ADD KEY `fk_matches_equipesext` (`id_equipe_ext`),
  ADD KEY `id_match` (`id_match`),
  ADD KEY `fk_matches_gymnasium` (`id_gymnasium`);

--
-- Indexes for table `matches_files`
--
ALTER TABLE `matches_files`
  ADD PRIMARY KEY (`id_match`,`id_file`),
  ADD KEY `fk_matches_files_file` (`id_file`),
  ADD KEY `fk_matches_files_match` (`id_match`);

--
-- Indexes for table `match_player`
--
ALTER TABLE `match_player`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_match` (`id_match`),
  ADD KEY `id_player` (`id_player`);

--
-- Indexes for table `news`
--
ALTER TABLE `news`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `photos`
--
ALTER TABLE `photos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id` (`id`);

--
-- Indexes for table `profiles`
--
ALTER TABLE `profiles`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id` (`id`);

--
-- Indexes for table `register`
--
ALTER TABLE `register`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `new_team_name` (`new_team_name`),
  ADD UNIQUE KEY `old_team_id` (`old_team_id`),
  ADD KEY `id_club` (`id_club`),
  ADD KEY `id_competition` (`id_competition`),
  ADD KEY `id_court_1` (`id_court_1`),
  ADD KEY `id_court_2` (`id_court_2`);

--
-- Indexes for table `registry`
--
ALTER TABLE `registry`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id` (`id`);

--
-- Indexes for table `users_profiles`
--
ALTER TABLE `users_profiles`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_users_profiles_profile` (`profile_id`),
  ADD KEY `id` (`id`),
  ADD KEY `fk_users_profiles_user` (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity`
--
ALTER TABLE `activity`
  MODIFY `id` smallint(10) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `blacklist_by_city`
--
ALTER TABLE `blacklist_by_city`
  MODIFY `id` smallint(10) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `blacklist_date`
--
ALTER TABLE `blacklist_date`
  MODIFY `id` smallint(10) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `blacklist_gymnase`
--
ALTER TABLE `blacklist_gymnase`
  MODIFY `id` smallint(10) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `blacklist_team`
--
ALTER TABLE `blacklist_team`
  MODIFY `id` smallint(10) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `blacklist_teams`
--
ALTER TABLE `blacklist_teams`
  MODIFY `id` smallint(10) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `classements`
--
ALTER TABLE `classements`
  MODIFY `id` smallint(10) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `clubs`
--
ALTER TABLE `clubs`
  MODIFY `id` smallint(10) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `commission`
--
ALTER TABLE `commission`
  MODIFY `id_commission` smallint(10) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `competitions`
--
ALTER TABLE `competitions`
  MODIFY `id` smallint(10) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `comptes_acces`
--
ALTER TABLE `comptes_acces`
  MODIFY `id` smallint(10) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `creneau`
--
ALTER TABLE `creneau`
  MODIFY `id` smallint(10) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `dates_limite`
--
ALTER TABLE `dates_limite`
  MODIFY `id_date` smallint(10) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `emails`
--
ALTER TABLE `emails`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `equipes`
--
ALTER TABLE `equipes`
  MODIFY `id_equipe` smallint(10) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `files`
--
ALTER TABLE `files`
  MODIFY `id` smallint(10) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `friendships`
--
ALTER TABLE `friendships`
  MODIFY `id` smallint(10) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `gymnase`
--
ALTER TABLE `gymnase`
  MODIFY `id` smallint(10) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `hall_of_fame`
--
ALTER TABLE `hall_of_fame`
  MODIFY `id` smallint(10) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `joueurs`
--
ALTER TABLE `joueurs`
  MODIFY `id` smallint(10) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `journees`
--
ALTER TABLE `journees`
  MODIFY `id` smallint(10) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `matches`
--
ALTER TABLE `matches`
  MODIFY `id_match` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `match_player`
--
ALTER TABLE `match_player`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `news`
--
ALTER TABLE `news`
  MODIFY `id` bigint(10) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `photos`
--
ALTER TABLE `photos`
  MODIFY `id` smallint(10) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `profiles`
--
ALTER TABLE `profiles`
  MODIFY `id` smallint(10) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `register`
--
ALTER TABLE `register`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `registry`
--
ALTER TABLE `registry`
  MODIFY `id` smallint(10) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users_profiles`
--
ALTER TABLE `users_profiles`
  MODIFY `id` smallint(10) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `blacklist_gymnase`
--
ALTER TABLE `blacklist_gymnase`
  ADD CONSTRAINT `blacklist_gymnase_ibfk_1` FOREIGN KEY (`id_gymnase`) REFERENCES `gymnase` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `blacklist_team`
--
ALTER TABLE `blacklist_team`
  ADD CONSTRAINT `blacklist_team_ibfk_1` FOREIGN KEY (`id_team`) REFERENCES `equipes` (`id_equipe`) ON DELETE CASCADE;

--
-- Constraints for table `blacklist_teams`
--
ALTER TABLE `blacklist_teams`
  ADD CONSTRAINT `blacklist_teams_ibfk_1` FOREIGN KEY (`id_team_1`) REFERENCES `equipes` (`id_equipe`) ON DELETE CASCADE,
  ADD CONSTRAINT `blacklist_teams_ibfk_2` FOREIGN KEY (`id_team_2`) REFERENCES `equipes` (`id_equipe`) ON DELETE CASCADE;

--
-- Constraints for table `classements`
--
ALTER TABLE `classements`
  ADD CONSTRAINT `fk_classements_equipes` FOREIGN KEY (`id_equipe`) REFERENCES `equipes` (`id_equipe`);

--
-- Constraints for table `creneau`
--
ALTER TABLE `creneau`
  ADD CONSTRAINT `fk_creneau_equipes` FOREIGN KEY (`id_equipe`) REFERENCES `equipes` (`id_equipe`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_creneau_gymnase` FOREIGN KEY (`id_gymnase`) REFERENCES `gymnase` (`id`);

--
-- Constraints for table `emails_files`
--
ALTER TABLE `emails_files`
  ADD CONSTRAINT `fk_emails_files_email` FOREIGN KEY (`id_email`) REFERENCES `emails` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_emails_files_file` FOREIGN KEY (`id_file`) REFERENCES `files` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `equipes`
--
ALTER TABLE `equipes`
  ADD CONSTRAINT `fk_equipes_clubs` FOREIGN KEY (`id_club`) REFERENCES `clubs` (`id`);

--
-- Constraints for table `friendships`
--
ALTER TABLE `friendships`
  ADD CONSTRAINT `friendships_ibfk_1` FOREIGN KEY (`id_club_1`) REFERENCES `clubs` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `friendships_ibfk_2` FOREIGN KEY (`id_club_2`) REFERENCES `clubs` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `joueur_equipe`
--
ALTER TABLE `joueur_equipe`
  ADD CONSTRAINT `fk_joueur_equipe_equipe` FOREIGN KEY (`id_equipe`) REFERENCES `equipes` (`id_equipe`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_joueur_equipe_joueur` FOREIGN KEY (`id_joueur`) REFERENCES `joueurs` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `matches`
--
ALTER TABLE `matches`
  ADD CONSTRAINT `fk_matches_equipesdom` FOREIGN KEY (`id_equipe_dom`) REFERENCES `equipes` (`id_equipe`),
  ADD CONSTRAINT `fk_matches_equipesext` FOREIGN KEY (`id_equipe_ext`) REFERENCES `equipes` (`id_equipe`),
  ADD CONSTRAINT `fk_matches_gymnasium` FOREIGN KEY (`id_gymnasium`) REFERENCES `gymnase` (`id`),
  ADD CONSTRAINT `fk_matches_journees` FOREIGN KEY (`id_journee`) REFERENCES `journees` (`id`);

--
-- Constraints for table `matches_files`
--
ALTER TABLE `matches_files`
  ADD CONSTRAINT `fk_matches_files_file` FOREIGN KEY (`id_file`) REFERENCES `files` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_matches_files_match` FOREIGN KEY (`id_match`) REFERENCES `matches` (`id_match`) ON DELETE CASCADE;

--
-- Constraints for table `match_player`
--
ALTER TABLE `match_player`
  ADD CONSTRAINT `match_player_ibfk_1` FOREIGN KEY (`id_match`) REFERENCES `matches` (`id_match`) ON DELETE CASCADE,
  ADD CONSTRAINT `match_player_ibfk_2` FOREIGN KEY (`id_player`) REFERENCES `joueurs` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `register`
--
ALTER TABLE `register`
  ADD CONSTRAINT `register_ibfk_1` FOREIGN KEY (`id_club`) REFERENCES `clubs` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `register_ibfk_2` FOREIGN KEY (`id_competition`) REFERENCES `competitions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `register_ibfk_3` FOREIGN KEY (`old_team_id`) REFERENCES `equipes` (`id_equipe`) ON DELETE CASCADE,
  ADD CONSTRAINT `register_ibfk_4` FOREIGN KEY (`id_court_1`) REFERENCES `gymnase` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `register_ibfk_5` FOREIGN KEY (`id_court_2`) REFERENCES `gymnase` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `users_profiles`
--
ALTER TABLE `users_profiles`
  ADD CONSTRAINT `fk_users_profiles_profile` FOREIGN KEY (`profile_id`) REFERENCES `profiles` (`id`),
  ADD CONSTRAINT `fk_users_profiles_user` FOREIGN KEY (`user_id`) REFERENCES `comptes_acces` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
