-- Indicateur : joueurs inscrits sur la fiche équipe d'une équipe de la Coupe Khoury Hanna
-- (code compétition 'kh' en poules, 'kf' en finales) APRES la date limite.
--
-- Date limite = date de la DERNIERE occurrence de l'activité « Les présents ont été renseignés
-- pour le match {code_match} » du PREMIER match (par date de réception) parmi les matchs déjà
-- renseignés de l'équipe (la fiche peut être ré-éditée : on retient la saisie la plus récente).
-- Un match non renseigné (sans cette activité) n'a pas de date limite et n'est pas pris en compte.
--
-- Détection = activités « Ajout de {joueur} a l'equipe {nom}(kh) » dont la date est postérieure
-- à la date limite de l'équipe concernée.
--
-- Le rapprochement activité -> équipe / match se fait par correspondance de chaînes : les
-- commentaires d'activité ne stockent ni id_equipe ni id_match (cf. issue #233). Les équipes
-- de la Coupe KH sont enregistrées en 'kh' (les matchs 'kf' réutilisent les mêmes id_equipe).
WITH
match_filled AS (
    SELECT m.code_match,
           m.id_equipe_dom,
           m.id_equipe_ext,
           m.date_reception,
           MAX(a.activity_date) AS filled_date
    FROM matches m
    JOIN activity a
      ON a.comment = CONCAT('Les présents ont été renseignés pour le match ', m.code_match)
    WHERE m.code_competition IN ('kh', 'kf')
    GROUP BY m.code_match, m.id_equipe_dom, m.id_equipe_ext, m.date_reception
),
team_match AS (
    SELECT id_equipe_dom AS id_equipe, code_match, date_reception, filled_date FROM match_filled
    UNION ALL
    SELECT id_equipe_ext AS id_equipe, code_match, date_reception, filled_date FROM match_filled
),
team_deadline AS (
    SELECT id_equipe, code_match AS premier_match, filled_date AS date_limite
    FROM (
        SELECT id_equipe,
               code_match,
               filled_date,
               ROW_NUMBER() OVER (PARTITION BY id_equipe ORDER BY date_reception, filled_date) AS rn
        FROM team_match
    ) t
    WHERE rn = 1
),
additions AS (
    SELECT a.activity_date,
           SUBSTRING(a.comment, 10, LOCATE(' a l''equipe ', a.comment) - 10)                      AS joueur,
           SUBSTRING(a.comment, LOCATE(' a l''equipe ', a.comment) + CHAR_LENGTH(' a l''equipe ')) AS equipe_full
    FROM activity a
    WHERE a.comment LIKE 'Ajout de %a l''equipe %(kh)'
)
SELECT e.nom_equipe                              AS equipe,
       ad.joueur                                 AS joueur,
       DATE_FORMAT(ad.activity_date, '%d/%m/%Y') AS date_ajout,
       DATE_FORMAT(td.date_limite, '%d/%m/%Y')   AS date_limite,
       td.premier_match                          AS premier_match
FROM additions ad
JOIN equipes e ON CONCAT(e.nom_equipe, '(', e.code_competition, ')') = ad.equipe_full
JOIN team_deadline td ON td.id_equipe = e.id_equipe
WHERE ad.activity_date > td.date_limite
ORDER BY e.nom_equipe, ad.activity_date;
