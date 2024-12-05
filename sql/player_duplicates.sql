SELECT j.prenom,
       j.nom,
       j2.prenom,
       j2.nom
FROM joueurs j,
     joueurs j2
WHERE j.id != j2.id
  AND REPLACE(UPPER(j.nom), ' ', '') = REPLACE(UPPER(j2.nom), ' ', '')
  AND REPLACE(UPPER(j.prenom), ' ', '') = REPLACE(UPPER(j2.prenom), ' ', '')
ORDER BY j.nom