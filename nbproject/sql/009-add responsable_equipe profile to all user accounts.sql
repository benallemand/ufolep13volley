INSERT INTO users_profiles (user_id, profile_id) 
SELECT ca.id, p.id FROM comptes_acces ca JOIN profiles p ON p.name='RESPONSABLE_EQUIPE';
