-- Issue #338 : clubs qui n'ont encore inscrit AUCUNE équipe, alors que la
-- campagne d'inscription est ouverte — les retardataires à relancer.
--
-- La population de référence, ce sont les clubs qui ont joué la saison passée
-- (`classements`) et qui n'ont pas déclaré forfait (`will_register_again`) :
-- eux sont attendus. Un club qui a explicitement dit qu'il ne se réengageait
-- pas n'est pas en retard, il est parti — l'indicateur « Equipes qui ne
-- s'engageront pas » le couvre déjà.
--
-- La maille est le CLUB, pas l'équipe : un club qui n'a rien inscrit du tout
-- n'a pas commencé sa saisie, c'est un coup de téléphone. Un club qui en a
-- inscrit deux sur trois a juste une équipe en moins, et c'est l'indicateur
-- « Equipes non réengagées » qui le dit, équipe par équipe.
--
-- La fenêtre (`start_register_date` / `limit_register_date`) porte deux rôles :
-- elle donne `jours_restants`, et surtout elle ETEINT la tuile toute seule une
-- fois la date limite passée. Sans elle, `register` gardant ses lignes toute la
-- saison, les clubs absents resteraient signalés jusqu'au prochain millésime.
-- Repousser la date limite dans l'écran Compétitions rallonge donc d'autant la
-- durée de vie de l'indicateur.
--
-- `indicator_id` alimente le bouton « corriger » du tableau de bord (#312) : il
-- est retiré du détail affiché par `Indicator::getResult()`, et ouvre l'écran
-- des clubs filtré sur ces seules lignes.
--
-- `contact` donne l'adresse à qui écrire : le ou les comptes du club
-- (`users_clubs` → `comptes_acces`), qui sont le référent officiel depuis #326.
-- A défaut de compte, on retombe sur les emails des responsables d'équipe du
-- club — un club sans compte est de toute façon déjà signalé par ailleurs.
SELECT cl.id                                                                    AS indicator_id,
       cl.nom                                                                   AS club,
       COUNT(DISTINCT e.id_equipe)                                              AS equipes_saison_passee,
       GROUP_CONCAT(DISTINCT comp.libelle ORDER BY comp.libelle SEPARATOR ', ') AS competitions_saison_passee,
       DATE_FORMAT(MIN(comp.limit_register_date), '%d/%m/%Y')                   AS date_limite,
       DATEDIFF(MIN(comp.limit_register_date), CURRENT_DATE)                    AS jours_restants,
       COALESCE(
               NULLIF((SELECT GROUP_CONCAT(DISTINCT ca.email ORDER BY ca.email SEPARATOR ', ')
                       FROM users_clubs uc
                                JOIN comptes_acces ca ON ca.id = uc.user_id
                       WHERE uc.club_id = cl.id), ''),
               (SELECT GROUP_CONCAT(DISTINCT j.email ORDER BY j.email SEPARATOR ', ')
                FROM joueurs j
                WHERE j.id_club = cl.id
                  AND NULLIF(TRIM(j.email), '') IS NOT NULL
                  AND EXISTS (SELECT 1
                              FROM joueur_equipe je
                              WHERE je.id_joueur = j.id
                                AND je.is_leader + 0 > 0))
           )                                                                    AS contact
FROM clubs cl
         JOIN equipes e ON e.id_club = cl.id
         JOIN classements c ON c.id_equipe = e.id_equipe
         JOIN competitions comp ON comp.code_competition = c.code_competition
WHERE c.code_competition IN ('m', 'f', 'mo')
  AND c.will_register_again = 1
  -- Bornes incluses, comme `Competition::is_registration_available()`.
  AND CURRENT_DATE BETWEEN comp.start_register_date AND comp.limit_register_date
  AND NOT EXISTS (SELECT 1 FROM register r WHERE r.id_club = cl.id)
GROUP BY cl.id, cl.nom
ORDER BY equipes_saison_passee DESC, club
