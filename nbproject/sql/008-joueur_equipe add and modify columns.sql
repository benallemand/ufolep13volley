ALTER TABLE joueur_equipe CHANGE is_vice_captain is_vice_leader BIT(1);
ALTER TABLE joueur_equipe CHANGE est_capitaine is_leader BIT(1);
ALTER TABLE joueur_equipe ADD is_captain BIT(1);
UPDATE joueur_equipe SET is_captain = 0;
UPDATE joueur_equipe SET is_leader = 0;
UPDATE joueur_equipe SET is_vice_leader = 0;