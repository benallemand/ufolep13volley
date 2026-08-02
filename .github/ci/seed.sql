-- Jeu de donnees de reference minimal pour le job PHPUnit de la CI.
--
-- Charge apres schema.sql dans un MySQL vierge (.github/workflows/tests.yml).
-- Contenu 100 % fictif : aucune donnee reelle n'est versionnee ici.
--
-- Objectif : donner aux tests le socle qu'ils ne creent pas eux-memes.
-- Beaucoup de tests construisent leurs propres fixtures (clubs, equipes,
-- matchs) puis les suppriment ; ils supposent en revanche l'existence de
-- donnees de reference stables. Les ids sont explicites car plusieurs tests
-- les codent en dur (Team::create_team(..., 1) dans PlayersTest,
-- id_gymnase = 45 dans MatchManagerTest).
--
-- Volumes imposes par les tests :
--   - >= 25 joueurs de chaque sexe (MatchManagerTest va jusqu'a LIMIT 20,5)
--   - un club avec >= 2 equipes ayant des creneaux (ClubLeaderTest)
--   - au moins un gymnase hors de ce club (ClubLeaderTest)

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------------------------------------
-- Competitions : reference reelle du championnat (codes utilises par le code
-- metier, cf. Competition::is_championship qui distingue championnats et coupes)
-- ---------------------------------------------------------------------------
INSERT INTO competitions (id, code_competition, libelle, id_compet_maitre, start_date, is_home_and_away) VALUES
  (1, 'm',  'Championnat Masculin',                'm',  CURRENT_DATE - INTERVAL 60 DAY, b'0'),
  (2, 'f',  'Championnat Feminin',                 'f',  CURRENT_DATE - INTERVAL 60 DAY, b'0'),
  (3, 'mo', 'Championnat Mixte',                   'mo', CURRENT_DATE - INTERVAL 60 DAY, b'0'),
  (4, 'c',  'Coupe Isoardi',                       'm',  CURRENT_DATE - INTERVAL 60 DAY, b'0'),
  (5, 'cf', 'Coupe Isoardi - Phase Finales',       'c',  CURRENT_DATE - INTERVAL 60 DAY, b'0'),
  (6, 'kh', 'Coupe Khoury Hanna',                  'kh', CURRENT_DATE - INTERVAL 60 DAY, b'0'),
  (7, 'kf', 'Coupe Khoury Hanna - Phase Finales',  'kh', CURRENT_DATE - INTERVAL 60 DAY, b'0'),
  (8, 'l',  'Coupe 6x6 Feminin',                   'l',  CURRENT_DATE - INTERVAL 60 DAY, b'0');

INSERT INTO journees (id, code_competition, numero, nommage, libelle, start_date) VALUES
  (1, 'm', 1, 'J1', 'Journee 1', CURRENT_DATE - INTERVAL 30 DAY),
  (2, 'm', 2, 'J2', 'Journee 2', CURRENT_DATE + INTERVAL 7 DAY),
  (3, 'f', 1, 'J1', 'Journee 1', CURRENT_DATE - INTERVAL 30 DAY),
  (4, 'mo', 1, 'J1', 'Journee 1', CURRENT_DATE - INTERVAL 30 DAY);

-- ---------------------------------------------------------------------------
-- Clubs, gymnases, equipes
-- Le club 1 porte 2 equipes avec creneaux : c'est celui que ClubLeaderTest
-- selectionne dynamiquement (HAVING nb >= 2 + JOIN creneau).
-- ---------------------------------------------------------------------------
INSERT INTO clubs (id, nom, affiliation_number, nom_responsable, prenom_responsable, tel1_responsable, email_responsable) VALUES
  (1, 'CI Club Alpha', '013001', 'Martin', 'Alex', '0600000001', 'alpha@example.test'),
  (2, 'CI Club Beta',  '013002', 'Durand', 'Camille', '0600000002', 'beta@example.test'),
  (3, 'CI Club Gamma', '013003', 'Petit',  'Dominique', '0600000003', 'gamma@example.test');

-- id 45 code en dur par MatchManagerTest::create_test_blacklist_gymnase
-- gps doit etre renseigne : Team::getSql concatene ville/nom/adresse/gps sans
-- IFNULL, donc un gps NULL vide tout le champ gymnasiums_list (teste par TeamTest).
INSERT INTO gymnase (id, nom, adresse, code_postal, ville, gps, nb_terrain, remarques) VALUES
  (1,  'CI Gymnase Alpha', '1 rue Alpha', 13001, 'Marseille', '43.2951,5.3750', 3, 'Parquet'),
  (2,  'CI Gymnase Beta',  '2 rue Beta',  13002, 'Marseille', '43.3021,5.3689', 2, NULL),
  (3,  'CI Gymnase Gamma', '3 rue Gamma', 13003, 'Marseille', '43.3105,5.3812', 3, 'Acces par l arriere'),
  (45, 'CI Gymnase Delta', '4 rue Delta', 13004, 'Marseille', '43.2887,5.4001', 1, NULL);

INSERT INTO equipes (id_equipe, code_competition, nom_equipe, id_club, is_cup_registered) VALUES
  (1, 'm',  'CI Alpha 1', 1, b'1'),
  (2, 'm',  'CI Alpha 2', 1, b'0'),
  (3, 'm',  'CI Beta 1',  2, b'1'),
  (4, 'f',  'CI Beta 2',  2, b'0'),
  (5, 'mo', 'CI Gamma 1', 3, b'0');

INSERT INTO creneau (id, id_gymnase, jour, heure, id_equipe, usage_priority) VALUES
  (1, 1, 'Lundi',    '20:00', 1, 1),
  (2, 2, 'Mardi',    '20:30', 2, 1),
  (3, 3, 'Mercredi', '21:00', 3, 1),
  (4, 3, 'Jeudi',    '20:00', 4, 1),
  (5, 1, 'Vendredi', '19:00', 5, 1);

INSERT INTO classements (id, code_competition, division, id_equipe, rank_start, penalite) VALUES
  (1, 'm',  '1', 1, 1, 0),
  (2, 'm',  '1', 2, 2, 0),
  (3, 'm',  '1', 3, 3, 0),
  (4, 'f',  '1', 4, 1, 0),
  (5, 'mo', '1', 5, 1, 0);

-- ---------------------------------------------------------------------------
-- Joueurs : 30 hommes (ids 1-30) + 30 femmes (ids 31-60).
-- MatchManagerTest peuple ses equipes de test par tranches successives
-- ("SELECT id FROM joueurs WHERE sexe = 'M' LIMIT 20,5" pour la 3e equipe) :
-- il faut donc au minimum 25 joueurs de chaque sexe. 30 laisse de la marge.
-- ---------------------------------------------------------------------------
INSERT INTO joueurs (id, prenom, nom, sexe, email, telephone, num_licence, departement_affiliation, id_club, date_homologation) VALUES
  (1, 'Joueur01', 'CiTest', 'M', 'joueur01@example.test', '0700000001', 'LIC0001', 13, 1, CURRENT_DATE - INTERVAL 200 DAY),
  (2, 'Joueur02', 'CiTest', 'M', 'joueur02@example.test', '0700000002', 'LIC0002', 13, 2, CURRENT_DATE - INTERVAL 200 DAY),
  (3, 'Joueur03', 'CiTest', 'M', 'joueur03@example.test', '0700000003', 'LIC0003', 13, 3, CURRENT_DATE - INTERVAL 200 DAY),
  (4, 'Joueur04', 'CiTest', 'M', 'joueur04@example.test', '0700000004', 'LIC0004', 13, 1, CURRENT_DATE - INTERVAL 200 DAY),
  (5, 'Joueur05', 'CiTest', 'M', 'joueur05@example.test', '0700000005', 'LIC0005', 13, 2, CURRENT_DATE - INTERVAL 200 DAY),
  (6, 'Joueur06', 'CiTest', 'M', 'joueur06@example.test', '0700000006', 'LIC0006', 13, 3, CURRENT_DATE - INTERVAL 200 DAY),
  (7, 'Joueur07', 'CiTest', 'M', 'joueur07@example.test', '0700000007', 'LIC0007', 13, 1, CURRENT_DATE - INTERVAL 200 DAY),
  (8, 'Joueur08', 'CiTest', 'M', 'joueur08@example.test', '0700000008', 'LIC0008', 13, 2, CURRENT_DATE - INTERVAL 200 DAY),
  (9, 'Joueur09', 'CiTest', 'M', 'joueur09@example.test', '0700000009', 'LIC0009', 13, 3, CURRENT_DATE - INTERVAL 200 DAY),
  (10, 'Joueur10', 'CiTest', 'M', 'joueur10@example.test', '0700000010', 'LIC0010', 13, 1, CURRENT_DATE - INTERVAL 200 DAY),
  (11, 'Joueur11', 'CiTest', 'M', 'joueur11@example.test', '0700000011', 'LIC0011', 13, 2, CURRENT_DATE - INTERVAL 200 DAY),
  (12, 'Joueur12', 'CiTest', 'M', 'joueur12@example.test', '0700000012', 'LIC0012', 13, 3, CURRENT_DATE - INTERVAL 200 DAY),
  (13, 'Joueur13', 'CiTest', 'M', 'joueur13@example.test', '0700000013', 'LIC0013', 13, 1, CURRENT_DATE - INTERVAL 200 DAY),
  (14, 'Joueur14', 'CiTest', 'M', 'joueur14@example.test', '0700000014', 'LIC0014', 13, 2, CURRENT_DATE - INTERVAL 200 DAY),
  (15, 'Joueur15', 'CiTest', 'M', 'joueur15@example.test', '0700000015', 'LIC0015', 13, 3, CURRENT_DATE - INTERVAL 200 DAY),
  (16, 'Joueur16', 'CiTest', 'M', 'joueur16@example.test', '0700000016', 'LIC0016', 13, 1, CURRENT_DATE - INTERVAL 200 DAY),
  (17, 'Joueur17', 'CiTest', 'M', 'joueur17@example.test', '0700000017', 'LIC0017', 13, 2, CURRENT_DATE - INTERVAL 200 DAY),
  (18, 'Joueur18', 'CiTest', 'M', 'joueur18@example.test', '0700000018', 'LIC0018', 13, 3, CURRENT_DATE - INTERVAL 200 DAY),
  (19, 'Joueur19', 'CiTest', 'M', 'joueur19@example.test', '0700000019', 'LIC0019', 13, 1, CURRENT_DATE - INTERVAL 200 DAY),
  (20, 'Joueur20', 'CiTest', 'M', 'joueur20@example.test', '0700000020', 'LIC0020', 13, 2, CURRENT_DATE - INTERVAL 200 DAY),
  (21, 'Joueur21', 'CiTest', 'M', 'joueur21@example.test', '0700000021', 'LIC0021', 13, 3, CURRENT_DATE - INTERVAL 200 DAY),
  (22, 'Joueur22', 'CiTest', 'M', 'joueur22@example.test', '0700000022', 'LIC0022', 13, 1, CURRENT_DATE - INTERVAL 200 DAY),
  (23, 'Joueur23', 'CiTest', 'M', 'joueur23@example.test', '0700000023', 'LIC0023', 13, 2, CURRENT_DATE - INTERVAL 200 DAY),
  (24, 'Joueur24', 'CiTest', 'M', 'joueur24@example.test', '0700000024', 'LIC0024', 13, 3, CURRENT_DATE - INTERVAL 200 DAY),
  (25, 'Joueur25', 'CiTest', 'M', 'joueur25@example.test', '0700000025', 'LIC0025', 13, 1, CURRENT_DATE - INTERVAL 200 DAY),
  (26, 'Joueur26', 'CiTest', 'M', 'joueur26@example.test', '0700000026', 'LIC0026', 13, 2, CURRENT_DATE - INTERVAL 200 DAY),
  (27, 'Joueur27', 'CiTest', 'M', 'joueur27@example.test', '0700000027', 'LIC0027', 13, 3, CURRENT_DATE - INTERVAL 200 DAY),
  (28, 'Joueur28', 'CiTest', 'M', 'joueur28@example.test', '0700000028', 'LIC0028', 13, 1, CURRENT_DATE - INTERVAL 200 DAY),
  (29, 'Joueur29', 'CiTest', 'M', 'joueur29@example.test', '0700000029', 'LIC0029', 13, 2, CURRENT_DATE - INTERVAL 200 DAY),
  (30, 'Joueur30', 'CiTest', 'M', 'joueur30@example.test', '0700000030', 'LIC0030', 13, 3, CURRENT_DATE - INTERVAL 200 DAY),
  (31, 'Joueuse01', 'CiTest', 'F', 'joueuse01@example.test', '0710000001', 'LIC1001', 13, 1, CURRENT_DATE - INTERVAL 200 DAY),
  (32, 'Joueuse02', 'CiTest', 'F', 'joueuse02@example.test', '0710000002', 'LIC1002', 13, 2, CURRENT_DATE - INTERVAL 200 DAY),
  (33, 'Joueuse03', 'CiTest', 'F', 'joueuse03@example.test', '0710000003', 'LIC1003', 13, 3, CURRENT_DATE - INTERVAL 200 DAY),
  (34, 'Joueuse04', 'CiTest', 'F', 'joueuse04@example.test', '0710000004', 'LIC1004', 13, 1, CURRENT_DATE - INTERVAL 200 DAY),
  (35, 'Joueuse05', 'CiTest', 'F', 'joueuse05@example.test', '0710000005', 'LIC1005', 13, 2, CURRENT_DATE - INTERVAL 200 DAY),
  (36, 'Joueuse06', 'CiTest', 'F', 'joueuse06@example.test', '0710000006', 'LIC1006', 13, 3, CURRENT_DATE - INTERVAL 200 DAY),
  (37, 'Joueuse07', 'CiTest', 'F', 'joueuse07@example.test', '0710000007', 'LIC1007', 13, 1, CURRENT_DATE - INTERVAL 200 DAY),
  (38, 'Joueuse08', 'CiTest', 'F', 'joueuse08@example.test', '0710000008', 'LIC1008', 13, 2, CURRENT_DATE - INTERVAL 200 DAY),
  (39, 'Joueuse09', 'CiTest', 'F', 'joueuse09@example.test', '0710000009', 'LIC1009', 13, 3, CURRENT_DATE - INTERVAL 200 DAY),
  (40, 'Joueuse10', 'CiTest', 'F', 'joueuse10@example.test', '0710000010', 'LIC1010', 13, 1, CURRENT_DATE - INTERVAL 200 DAY),
  (41, 'Joueuse11', 'CiTest', 'F', 'joueuse11@example.test', '0710000011', 'LIC1011', 13, 2, CURRENT_DATE - INTERVAL 200 DAY),
  (42, 'Joueuse12', 'CiTest', 'F', 'joueuse12@example.test', '0710000012', 'LIC1012', 13, 3, CURRENT_DATE - INTERVAL 200 DAY),
  (43, 'Joueuse13', 'CiTest', 'F', 'joueuse13@example.test', '0710000013', 'LIC1013', 13, 1, CURRENT_DATE - INTERVAL 200 DAY),
  (44, 'Joueuse14', 'CiTest', 'F', 'joueuse14@example.test', '0710000014', 'LIC1014', 13, 2, CURRENT_DATE - INTERVAL 200 DAY),
  (45, 'Joueuse15', 'CiTest', 'F', 'joueuse15@example.test', '0710000015', 'LIC1015', 13, 3, CURRENT_DATE - INTERVAL 200 DAY),
  (46, 'Joueuse16', 'CiTest', 'F', 'joueuse16@example.test', '0710000016', 'LIC1016', 13, 1, CURRENT_DATE - INTERVAL 200 DAY),
  (47, 'Joueuse17', 'CiTest', 'F', 'joueuse17@example.test', '0710000017', 'LIC1017', 13, 2, CURRENT_DATE - INTERVAL 200 DAY),
  (48, 'Joueuse18', 'CiTest', 'F', 'joueuse18@example.test', '0710000018', 'LIC1018', 13, 3, CURRENT_DATE - INTERVAL 200 DAY),
  (49, 'Joueuse19', 'CiTest', 'F', 'joueuse19@example.test', '0710000019', 'LIC1019', 13, 1, CURRENT_DATE - INTERVAL 200 DAY),
  (50, 'Joueuse20', 'CiTest', 'F', 'joueuse20@example.test', '0710000020', 'LIC1020', 13, 2, CURRENT_DATE - INTERVAL 200 DAY),
  (51, 'Joueuse21', 'CiTest', 'F', 'joueuse21@example.test', '0710000021', 'LIC1021', 13, 3, CURRENT_DATE - INTERVAL 200 DAY),
  (52, 'Joueuse22', 'CiTest', 'F', 'joueuse22@example.test', '0710000022', 'LIC1022', 13, 1, CURRENT_DATE - INTERVAL 200 DAY),
  (53, 'Joueuse23', 'CiTest', 'F', 'joueuse23@example.test', '0710000023', 'LIC1023', 13, 2, CURRENT_DATE - INTERVAL 200 DAY),
  (54, 'Joueuse24', 'CiTest', 'F', 'joueuse24@example.test', '0710000024', 'LIC1024', 13, 3, CURRENT_DATE - INTERVAL 200 DAY),
  (55, 'Joueuse25', 'CiTest', 'F', 'joueuse25@example.test', '0710000025', 'LIC1025', 13, 1, CURRENT_DATE - INTERVAL 200 DAY),
  (56, 'Joueuse26', 'CiTest', 'F', 'joueuse26@example.test', '0710000026', 'LIC1026', 13, 2, CURRENT_DATE - INTERVAL 200 DAY),
  (57, 'Joueuse27', 'CiTest', 'F', 'joueuse27@example.test', '0710000027', 'LIC1027', 13, 3, CURRENT_DATE - INTERVAL 200 DAY),
  (58, 'Joueuse28', 'CiTest', 'F', 'joueuse28@example.test', '0710000028', 'LIC1028', 13, 1, CURRENT_DATE - INTERVAL 200 DAY),
  (59, 'Joueuse29', 'CiTest', 'F', 'joueuse29@example.test', '0710000029', 'LIC1029', 13, 2, CURRENT_DATE - INTERVAL 200 DAY),
  (60, 'Joueuse30', 'CiTest', 'F', 'joueuse30@example.test', '0710000030', 'LIC1030', 13, 3, CURRENT_DATE - INTERVAL 200 DAY);

INSERT INTO joueur_equipe (id_joueur, id_equipe, is_leader, is_vice_leader, is_captain) VALUES
  (1,  1, b'1', b'0', b'1'),
  (2,  1, b'0', b'1', b'0'),
  (3,  1, b'0', b'0', b'0'),
  (4,  2, b'1', b'0', b'1'),
  (5,  2, b'0', b'1', b'0'),
  (6,  3, b'1', b'0', b'1'),
  (31, 4, b'1', b'0', b'1'),
  (32, 4, b'0', b'1', b'0'),
  (41, 5, b'1', b'0', b'1');

-- ---------------------------------------------------------------------------
-- Comptes d'acces et roles (issue #245 : roles derives et cumulables)
-- password_hash = bcrypt de 'ci-password'
-- ---------------------------------------------------------------------------
INSERT INTO comptes_acces (id, login, email, password_hash, is_admin) VALUES
  (1, 'ci_admin',       'ci.admin@example.test',       '$2y$10$e0NRHDZ1mYlrbpFHK2Vy8ONVeDoyFmDh0RVWG5DzuVjb7ZTgO2Rw6', 1),
  (2, 'ci_leader_1',    'ci.leader1@example.test',     '$2y$10$e0NRHDZ1mYlrbpFHK2Vy8ONVeDoyFmDh0RVWG5DzuVjb7ZTgO2Rw6', 0),
  (3, 'ci_leader_2',    'ci.leader2@example.test',     '$2y$10$e0NRHDZ1mYlrbpFHK2Vy8ONVeDoyFmDh0RVWG5DzuVjb7ZTgO2Rw6', 0),
  (4, 'ci_leader_3',    'ci.leader3@example.test',     '$2y$10$e0NRHDZ1mYlrbpFHK2Vy8ONVeDoyFmDh0RVWG5DzuVjb7ZTgO2Rw6', 0),
  (5, 'ci_club_leader', 'ci.clubleader@example.test',  '$2y$10$e0NRHDZ1mYlrbpFHK2Vy8ONVeDoyFmDh0RVWG5DzuVjb7ZTgO2Rw6', 0),
  (6, 'ci_multi_team',  'ci.multiteam@example.test',   '$2y$10$e0NRHDZ1mYlrbpFHK2Vy8ONVeDoyFmDh0RVWG5DzuVjb7ZTgO2Rw6', 0);

INSERT INTO users_teams (user_id, team_id) VALUES
  (2, 1),
  (3, 2),
  (4, 3),
  (6, 1),
  (6, 2);

INSERT INTO users_clubs (user_id, club_id) VALUES
  (5, 1);

-- ---------------------------------------------------------------------------
-- Matchs : un passe (confirme) et un a venir, pour les tests qui piochent
-- un match existant (LiveScoreTest, MatchDateModificationTest...).
-- ---------------------------------------------------------------------------
INSERT INTO matches (id_match, code_match, code_competition, division, id_equipe_dom, id_equipe_ext,
                     date_reception, date_original, id_journee, id_gymnasium, match_status) VALUES
  (1, 'CI001', 'm', '1', 1, 2, CURRENT_DATE - INTERVAL 30 DAY, CURRENT_DATE - INTERVAL 30 DAY, 1, 1, 'CONFIRMED'),
  (2, 'CI002', 'm', '1', 2, 3, CURRENT_DATE + INTERVAL 7 DAY,  CURRENT_DATE + INTERVAL 7 DAY,  2, 2, 'NOT_CONFIRMED'),
  (3, 'CI003', 'm', '1', 3, 1, CURRENT_DATE + INTERVAL 14 DAY, CURRENT_DATE + INTERVAL 14 DAY, 2, 3, 'NOT_CONFIRMED');

SET FOREIGN_KEY_CHECKS = 1;
