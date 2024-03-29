SELECT DISTINCT m.code_match,
                m.date_reception,
                j.prenom,
                j.nom,
                c.nom as club,
                j.date_homologation
from matches m
         join match_player mp on m.id_match = mp.id_match
         join players_view j on mp.id_player = j.id
         join clubs c on j.id_club = c.id
where (STR_TO_DATE(j.date_homologation, '%d/%m/%Y') > m.date_reception OR j.est_actif = 0)
  AND m.match_status = 'CONFIRMED'
order by date_reception, code_match