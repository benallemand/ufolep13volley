SELECT num_licence, COUNT(*) AS nb_duplicats
FROM joueurs
GROUP BY num_licence
HAVING COUNT(*) > 1
   AND num_licence != ''