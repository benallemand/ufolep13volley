SET FOREIGN_KEY_CHECKS = 0;
ALTER TABLE equipes DROP FOREIGN KEY fk_equipes_imp_equipe;
ALTER TABLE equipes DROP COLUMN id_imp_equipe;
SET FOREIGN_KEY_CHECKS = 1;
COMMIT;
