-- `indicator_id` alimente le bouton « corriger » du tableau de bord (#312) :
-- il est retiré du détail affiché par `Indicator::getResult()`, et sert à
-- ouvrir l'écran des joueurs filtré sur ces seules lignes.
SELECT j.id                                          AS indicator_id,
       CONCAT(j.nom, ' ', j.prenom)                  AS joueur,
       c.nom                                         AS club,
       CONCAT(e.nom_equipe, ' (', comp.libelle, ')') AS equipe,
       jresp.email                                   AS responsable
FROM joueur_equipe je
         JOIN joueurs j ON j.id = je.id_joueur
         JOIN equipes e ON e.id_equipe = je.id_equipe
         JOIN joueur_equipe jeresp ON jeresp.id_equipe = e.id_equipe AND jeresp.is_leader + 0 > 0
         JOIN joueurs jresp ON jresp.id = jeresp.id_joueur
         JOIN competitions comp ON comp.code_competition = e.code_competition
         JOIN clubs c ON c.id = j.id_club
WHERE (j.num_licence IS NULL
    OR j.num_licence = '')
  -- Un membre non jouant (issue #325) n'a pas besoin de licence : le
  -- responsable d'une equipe ou il ne joue pas n'est pas une anomalie. La
  -- jointure `jeresp` ci-dessus, elle, n'est pas filtree — un responsable non
  -- jouant reste le responsable a qui l'on ecrit.
  AND je.est_jouant + 0 > 0
  AND e.id_equipe IN (SELECT id_equipe FROM classements)
  AND CURRENT_DATE >= comp.start_date
ORDER BY equipe