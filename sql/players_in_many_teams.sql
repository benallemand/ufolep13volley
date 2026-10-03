-- Groupes de joueurs qui jouent ensemble dans plusieurs équipes : trop de
-- joueurs partagés entre deux équipes en font, de fait, la même équipe.
-- Seuil : plus de 2 joueurs partagés si l'une des équipes est en féminin ou en
-- mixte, plus de 3 si l'une est en masculin.
--
-- Issue #397 : un joueur se reconnaît à son identifiant, plus à son nom (deux
-- homonymes ne faisaient qu'un), et le type d'équipe se lit sur son code de
-- compétition. Le filtre `equipes LIKE '%f%'` était vrai pour tout nom
-- d'équipe contenant un « f ».
WITH par_joueur AS (SELECT j.id,
                           CONCAT(j.prenom, ' ', j.nom)                                AS joueur,
                           GROUP_CONCAT(CONCAT(e.nom_equipe, ' (', e.code_competition, ')')
                                        ORDER BY e.nom_equipe, e.id_equipe SEPARATOR ', ') AS equipes,
                           MAX(e.code_competition = 'm')                               AS en_masculin,
                           MAX(e.code_competition IN ('f', 'mo'))                      AS en_feminin_ou_mixte
                    FROM joueurs j
                             JOIN joueur_equipe je ON je.id_joueur = j.id
                             JOIN equipes e ON e.id_equipe = je.id_equipe
                    WHERE e.id_equipe IN (SELECT id_equipe FROM classements)
                      -- Une appartenance non jouante (issue #325) ne fait pas
                      -- jouer plus : elle ne doit pas gonfler le compte.
                      AND je.est_jouant + 0 > 0
                    GROUP BY j.id, j.prenom, j.nom
                    HAVING COUNT(DISTINCT e.id_equipe) > 1)
SELECT COUNT(*)                                                  AS nb_joueurs,
       equipes,
       GROUP_CONCAT(joueur ORDER BY joueur SEPARATOR ', ')       AS joueurs
FROM par_joueur
GROUP BY equipes
HAVING (COUNT(*) > 2 AND MAX(en_feminin_ou_mixte) = 1)
    OR (COUNT(*) > 3 AND MAX(en_masculin) = 1)
ORDER BY nb_joueurs DESC, equipes
