SELECT m.code_match,
       m.equipe_dom,
       m.equipe_ext,
       IF(m.is_sign_team_dom = 0,
          CONCAT('<a href=\'https://www.ufolep13volley.org/team_sheets.php?id_match=', m.id_match, '\'>non signée</a>'),
          'ok') AS is_sign_team_dom,
       IF(m.is_sign_team_ext = 0,
          CONCAT('<a href=\'https://www.ufolep13volley.org/team_sheets.php?id_match=', m.id_match, '\'>non signée</a>'),
          'ok') AS is_sign_team_ext,
       IF(m.is_sign_match_dom = 0,
          CONCAT('<a href=\'https://www.ufolep13volley.org/match.php?id_match=', m.id_match, '\'>non signée</a>'),
          'ok') AS is_sign_match_dom,
       IF(m.is_sign_match_ext = 0,
          CONCAT('<a href=\'https://www.ufolep13volley.org/match.php?id_match=', m.id_match, '\'>non signée</a>'),
          'ok') AS is_sign_match_ext,
       m.email_dom,
       m.email_ext
from matchs_view m
WHERE m.match_status = 'CONFIRMED'
  AND STR_TO_DATE(m.date_reception, '%d/%m/%Y') < CURRENT_DATE
  AND m.id_match NOT IN (SELECT id_match FROM matches_files)
  AND (m.is_sign_team_dom + m.is_sign_team_ext + m.is_sign_match_dom + m.is_sign_match_ext < 4)
  AND m.certif = 0
order by date_reception, code_match