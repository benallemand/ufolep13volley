UPDATE users_profiles up SET up.profile_id=(SELECT p.id FROM profiles p WHERE p.name='ADMINISTRATEUR')
WHERE user_id IN (SELECT ca.id FROM comptes_acces ca WHERE ca.login='ufoladmin')
