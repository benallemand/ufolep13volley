SELECT REPLACE(REPLACE(registry_key, '.is_remind_matches', ''), 'users.', '') AS team_id
FROM registry
WHERE registry_key LIKE 'users.%.is_remind_matches'
  AND registry_value = 'on'