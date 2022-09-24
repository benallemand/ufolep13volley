<?php
require_once __DIR__ . '/../includes/fonctions_inc.php';
/**
 * @throws Exception
 */
function my_players($parameters)
{
    @session_start();
    return getPlayersPdf($_SESSION['id_equipe']);
}