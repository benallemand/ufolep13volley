-- Cotisations attendues de chaque club pour la première demi-saison de
-- championnat (issue #417) : c'est l'aperçu exact de l'email envoyé à la
-- comptabilité (`Register::send_membership_fees_to_accounting`).
--
-- Seules comptent les inscriptions VALIDÉES de la campagne en cours
-- (`creation_date >= start_register_date` : `register` garde ses lignes d'une
-- saison à l'autre). Une demande en attente ou refusée n'est pas facturée.
-- La compétition se lit sur `code_competition`, jamais sur le libellé.
-- Tarifs : 10 € par équipe en championnat masculin, 5 € en féminin et mixte.
SELECT c2.nom                                                         AS club,
       cc.contact                                                     AS contact_club,
       GROUP_CONCAT(CONCAT(r.new_team_name, ' (', c.libelle, ')')
                    ORDER BY c.libelle, r.new_team_name SEPARATOR '<br>') AS competitions,
       COUNT(*)                                                       AS nb_equipes,
       SUM(IF(c.code_competition = 'm', 10, 5))                       AS cout
FROM register r
         JOIN competitions c ON c.id = r.id_competition
         JOIN clubs c2 ON c2.id = r.id_club
         LEFT JOIN club_contacts_view cc ON cc.id_club = c2.id
WHERE c.code_competition IN ('m', 'f', 'mo')
  AND r.status = 'VALIDATED'
  AND r.creation_date >= c.start_register_date
GROUP BY c2.id, c2.nom, cc.contact
ORDER BY club
