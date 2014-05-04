ALTER TABLE joueur_equipe ADD is_vice_captain BIT(1);
UPDATE joueur_equipe SET is_vice_captain = 0;