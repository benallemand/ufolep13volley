<?php
/**
 * Supprime les données de archives_setup.php (#435).
 */
function archives_cleanup(SqlManager $sql): void
{
    $sql->execute("DELETE FROM classements_archives WHERE saison = '2001-2002'");
    $sql->execute("DELETE FROM matches WHERE code_match = 'E2EARCH1'");
    $sql->execute("DELETE FROM equipes WHERE nom_equipe LIKE 'E2E Archives %'");
}
