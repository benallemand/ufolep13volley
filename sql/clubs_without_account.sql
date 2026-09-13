-- Issue #326 : clubs engagés qui n'ont pas de compte.
--
-- Depuis que la création des comptes d'équipe est déléguée au compte rattaché à
-- un club, le référent d'un club EST son compte (`users_clubs` →
-- `comptes_acces`) : c'est la seule table où l'email est à la fois obligatoire
-- et unique. Un club actif sans compte n'a donc pas de contact fiable, et
-- personne pour inscrire ses équipes.
--
-- `indicator_id` alimente le bouton « corriger » du tableau de bord (#312) : il
-- est retiré du détail affiché par `Indicator::getResult()`, et ouvre l'écran
-- des clubs filtré sur ces seules lignes, où l'action « Créer le compte du
-- club » les traite une par une.
--
-- `contact_connu` disparaîtra avec les colonnes `clubs.*_responsable` (#327) :
-- il n'est là que pour donner à l'administrateur l'email à reprendre pendant le
-- rattrapage.
SELECT c.id                        AS indicator_id,
       c.nom                       AS club,
       COUNT(DISTINCT e.id_equipe) AS equipes_engagees,
       c.email_responsable         AS contact_connu
FROM clubs c
         JOIN equipes e ON e.id_club = c.id
         JOIN classements cl ON cl.id_equipe = e.id_equipe
WHERE NOT EXISTS (SELECT 1 FROM users_clubs uc WHERE uc.club_id = c.id)
GROUP BY c.id, c.nom, c.email_responsable
ORDER BY equipes_engagees DESC, club
