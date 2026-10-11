<?php
/**
 * Supprime les données de multi_team_setup.php (#421).
 */
function multi_team_cleanup(SqlManager $sql): void
{
    $sql->execute("DELETE FROM matches WHERE code_match LIKE 'E2M%'");
    $sql->execute("DELETE FROM users_teams WHERE user_id IN
                       (SELECT id FROM comptes_acces WHERE login = 'e2e_multi_team')");
    $sql->execute("DELETE FROM comptes_acces WHERE login = 'e2e_multi_team'");
}
