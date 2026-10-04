-- `indicator_id` ouvre l'écran de correction filtré sur ces lignes (#312, #409).
-- Deux équipes dont les deux dernières rencontres, dans la même compétition,
-- se sont jouées chez la même équipe (issue #397).
--
-- Lu sur `matches`, où `date_reception` est une vraie date : la version
-- précédente passait par `matchs_view`, qui la rend en texte jj/mm/aaaa, et
-- prenait « la plus récente » par un MAX sur ce texte — un match du 16/01
-- passait pour plus récent qu'un match du 13/03. Elle comparait aussi des
-- compétitions différentes (un match de poule face à un match de championnat),
-- et mettait 7 s à répondre.
--
-- On ne signale que si l'équipe qui s'est déplacée deux fois peut recevoir
-- (elle a un créneau), et si l'avant-dernière rencontre date de moins de neuf
-- mois.
--
-- Et seulement si la dernière rencontre n'est PAS archivée : l'indicateur
-- sert à corriger le calendrier à venir. Deux rencontres déjà jouées et
-- archivées ne sont que de l'historique, il n'y a plus rien à déplacer.
-- L'avant-dernière peut l'être : c'est justement le cas visé (aller archivé,
-- retour programmé chez la même équipe).
WITH rencontres AS (SELECT m.id_match,
                           m.code_match,
                           m.code_competition,
                           m.id_equipe_dom,
                           m.id_equipe_ext,
                           m.date_reception,
                           m.match_status,
                           ROW_NUMBER() OVER (
                               PARTITION BY m.code_competition,
                                   LEAST(m.id_equipe_dom, m.id_equipe_ext),
                                   GREATEST(m.id_equipe_dom, m.id_equipe_ext)
                               ORDER BY m.date_reception DESC, m.id_match DESC) AS rang
                    FROM matches m
                    WHERE m.date_reception IS NOT NULL)
SELECT CONCAT(avant.id_match, ',', dernier.id_match) AS indicator_id,
       comp.libelle                                AS competition,
       edom.nom_equipe                             AS recoit_deux_fois,
       eext.nom_equipe                             AS se_deplace_deux_fois,
       avant.code_match                            AS avant_dernier_match,
       DATE_FORMAT(avant.date_reception, '%d/%m/%Y') AS avant_derniere_date,
       dernier.code_match                          AS dernier_match,
       DATE_FORMAT(dernier.date_reception, '%d/%m/%Y') AS derniere_date
FROM rencontres dernier
         JOIN rencontres avant
              ON avant.code_competition = dernier.code_competition
                  AND LEAST(avant.id_equipe_dom, avant.id_equipe_ext) =
                      LEAST(dernier.id_equipe_dom, dernier.id_equipe_ext)
                  AND GREATEST(avant.id_equipe_dom, avant.id_equipe_ext) =
                      GREATEST(dernier.id_equipe_dom, dernier.id_equipe_ext)
                  AND avant.rang = 2
         JOIN equipes edom ON edom.id_equipe = dernier.id_equipe_dom
         JOIN equipes eext ON eext.id_equipe = dernier.id_equipe_ext
         JOIN competitions comp ON comp.code_competition = dernier.code_competition
WHERE dernier.rang = 1
  AND dernier.match_status <> 'ARCHIVED'
  AND avant.id_equipe_dom = dernier.id_equipe_dom
  AND avant.date_reception > CURRENT_DATE - INTERVAL 9 MONTH
  AND EXISTS (SELECT 1 FROM creneau c WHERE c.id_equipe = dernier.id_equipe_ext)
ORDER BY competition, recoit_deux_fois, dernier.date_reception
