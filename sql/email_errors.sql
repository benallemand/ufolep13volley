-- Emails dont l'envoi a échoué.
--
-- Issue #397 : le corps HTML n'est plus rendu, brut, dans le détail. Il se lit
-- dans l'écran Emails, que `indicator_id` ouvre filtré sur ces lignes (#312),
-- et d'où « Relancer les erreurs » les renvoie.
SELECT id                                         AS indicator_id,
       DATE_FORMAT(creation_date, '%d/%m/%Y %H:%i') AS cree_le,
       to_email                                   AS destinataire,
       cc,
       subject                                    AS sujet
FROM emails
WHERE sending_status = 'ERROR'
ORDER BY creation_date DESC
