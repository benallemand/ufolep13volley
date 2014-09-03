ALTER TABLE comptes_acces ADD email VARCHAR(200);
UPDATE comptes_acces ca, details_equipes de SET ca.email = de.email
WHERE ca.id_equipe = de.id_equipe;