<?php
/**
 * Issue #249 — nettoyage des données E2E d'inscription (inclus par le setup
 * et le teardown). Suppose $sql (SqlManager) défini par l'appelant.
 */
$sql->execute("DELETE FROM emails WHERE to_email LIKE 'e2e_reg_%@ufolep.test'");
$sql->execute("DELETE FROM register WHERE new_team_name LIKE 'E2E Reg Team%'");
$sql->execute("DELETE FROM users_clubs WHERE user_id IN (SELECT id FROM comptes_acces WHERE email = 'e2e_reg_club@ufolep.test')");
$sql->execute("DELETE FROM comptes_acces WHERE email = 'e2e_reg_club@ufolep.test'");
$sql->execute("DELETE FROM clubs WHERE nom = 'E2E Reg Club'");
$sql->execute("DELETE FROM competitions WHERE code_competition = 'zz'");
