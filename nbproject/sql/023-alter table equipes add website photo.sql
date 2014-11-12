ALTER TABLE equipes ADD web_site VARCHAR(50);
ALTER TABLE equipes ADD id_photo SMALLINT(5);

UPDATE equipes e, details_equipes de SET e.web_site = de.site_web WHERE e.id_equipe = de.id_equipe;